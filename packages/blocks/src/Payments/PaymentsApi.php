<?php


namespace PaymentWps\Blocks\Stripe\Payments;

use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Payments\PaymentContext;
use Automattic\WooCommerce\Blocks\Payments\PaymentResult;
use Automattic\WooCommerce\Blocks\Registry\Container as Container;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use \PaymentWps\Blocks\Stripe\Assets\Api as AssetsApi;
use PaymentWps\Blocks\Stripe\Config;

class PaymentsApi {

	private $container;

	private $config;

	private $assets_registry;

	/**
	 * @var Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry
	 */
	private $payment_method_registry;

	/**
	 * @var PaymentResult
	 */
	protected $payment_result;

	public function __construct( Container $container, Config $config, AssetDataRegistry $assets_registry ) {
		$this->container       = $container;
		$this->config          = $config;
		$this->assets_registry = $assets_registry;
		$this->add_payment_methods();
		$this->init();
	}

	private function init() {
		add_action( 'woocommerce_blocks_payment_method_type_registration', array( $this, 'register_payment_methods' ) );
		add_action( 'woocommerce_blocks_checkout_enqueue_data', array( $this, 'enqueue_checkout_data' ) );
		add_action( 'woocommerce_blocks_cart_enqueue_data', array( $this, 'enqueue_cart_data' ) );
		add_action( 'woocommerce_rest_checkout_process_payment_with_context', array( $this, 'payment_with_context' ), 10, 2 );
		add_action( 'wpp_payment_blocks_enqueue_styles', array( $this, 'enqueue_payment_styles' ) );
	}

	private function add_payment_methods() {
		$this->container->register( CreditCardPayment::class, function ( Container $container ) {
			return new CreditCardPayment( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( GooglePayPayment::class, function ( Container $container ) {
			return new GooglePayPayment( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( ApplePayPayment::class, function ( Container $container ) {
			return new ApplePayPayment( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( PaymentRequest::class, function ( Container $container ) {
			return new PaymentRequest( $container->get( AssetsApi::class ) );
		} );
		$this->container->register( ACHPayment::class, function ( Container $container ) {
			return new ACHPayment( $container->get( AssetsApi::class ) );
		} );
	}

	/**
	 * Register all payment methods used by the plugin.
	 *
	 * @param PaymentMethodRegistry $registry
	 */
	public function register_payment_methods( PaymentMethodRegistry $registry ) {
		$payment_gateways              = WC()->payment_gateways()->payment_gateways();
		$this->payment_method_registry = $registry;
		$payment_methods               = array(
			CreditCardPayment::class,
			GooglePayPayment::class,
			ApplePayPayment::class,
			PaymentRequest::class,
			ACHPayment::class,
		);
		foreach ( $payment_methods as $clazz ) {
			$this->maybe_add_payment_method_to_registry( $clazz, $registry, $payment_gateways );
		}
	}

	/**
	 * @param $clazz
	 * @param PaymentMethodRegistry $registry
	 * @param array $payment_gateways
	 */
	private function maybe_add_payment_method_to_registry( $clazz, $registry, $payment_gateways ) {
		if ( class_exists( $clazz ) ) {
			try {
				$reflection_class = new \ReflectionClass( $clazz );
				$name             = $reflection_class->getDefaultProperties()['name'];
				if ( isset( $payment_gateways[ $name ] ) ) {
					$registry->register( $this->container->get( $clazz ) );
				}
			} catch ( \ReflectionException $e ) {
				// fail silently
			}
		}
	}

	/**
	 * @param \PaymentWps\Blocks\Stripe\Assets\Api $style_api
	 */
	public function enqueue_payment_styles( $style_api ) {
		foreach ( $this->payment_method_registry->get_all_registered() as $payment_method ) {
			if ( $payment_method instanceof AbstractStripePayment ) {
				$payment_method->enqueue_payment_method_styles( $style_api );
			}
		}
	}

	public function enqueue_checkout_data() {
		$this->enqueue_data( 'checkout' );
	}

	public function enqueue_cart_data() {
		$this->enqueue_data( 'cart' );
	}

	private function enqueue_data( $page ) {
		if ( ! $this->assets_registry->exists( 'stripeGeneralData' ) ) {
			$this->assets_registry->add( 'stripeGeneralData', array(
				'page'           => $page,
				'mode'           => wpp_payment_mode(),
				'publishableKey' => wpp_payment_get_publishable_key(),
				'account'        => wpp_payment_get_account_id(),
				'version'        => $this->config->get_version(),
				'blocksVersion'  => \Automattic\WooCommerce\Blocks\Package::get_version(),
				'routes'         => array(
					'process/payment'     => \WPP_Rest_API::get_endpoint( stripe_wpp()->rest_api->checkout->rest_uri( 'checkout/payment' ) ),
					'create/setup_intent' => \WPP_Rest_API::get_endpoint( stripe_wpp()->rest_api->payment_intent->rest_uri( 'setup-intent' ) ),
					'sync/intent'         => \WPP_Rest_API::get_endpoint( stripe_wpp()->rest_api->payment_intent->rest_uri( 'sync-payment-intent' ) ),
					'update/source'       => \WPP_Rest_API::get_endpoint( stripe_wpp()->rest_api->source->rest_uri( 'update' ) ),
					'create/linkToken'    => \WPP_Rest_API::get_endpoint( stripe_wpp()->rest_api->plaid->rest_uri( 'link-token' ) )
				)
			) );
		}
		if ( ! $this->assets_registry->exists( 'stripeErrorMessages' ) ) {
			$this->assets_registry->add( 'stripeErrorMessages', wpp_payment_get_error_messages() );
		}

		if ( ! $this->assets_registry->exists( 'stripePaymentData' ) ) {
			$payment_data = array();
			if ( WC()->cart && wpp_payment_pre_orders_active() && \WC_Pre_Orders_Cart::cart_contains_pre_order() && \WC_Pre_Orders_Product::product_is_charged_upon_release( \WC_Pre_Orders_Cart::get_pre_order_product() ) ) {
				$payment_data['pre_order'] = true;
			}
			if ( WC()->cart && wpp_is_subscription_active() && \WC_Subscriptions_Cart::cart_contains_subscription() ) {
				$payment_data['subscription'] = true;
			}
			$this->assets_registry->add( 'stripePaymentData', $payment_data );
		}
	}

	public function payment_with_context( PaymentContext $context, PaymentResult $result ) {
		$this->payment_result = $result;
		add_action( 'wpp_process_payment_error', array( $this, 'process_payment_error' ) );
	}

	/**
	 * @param WP_Error $error |null
	 */
	public function process_payment_error( $error ) {
		if ( $this->payment_result && $error ) {
			// add the error to the payment result
			$this->payment_result->set_payment_details( array(
				'stripeErrorMessage' => $error->get_error_message()
			) );
		}
	}

}