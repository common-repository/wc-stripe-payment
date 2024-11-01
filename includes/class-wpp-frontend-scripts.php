<?php
defined( 'ABSPATH' ) || exit();

/**
 * Handles scrip enqueuement and output of params needed by the plugin.
 *
 * @package Stripe/Classes
 * @author WpPayments
 */
class WPP_Payment_Frontend_Scripts {

	public $prefix = 'wpp-payment-';

	public $registered_scripts = array();

	public $enqueued_scripts = array();

	public $localized_scripts = array();

	public $localized_data = array();

	public $global_scripts = array(
		'external' => 'https://js.stripe.com/v3/',
		'gpay'     => 'https://pay.google.com/gp/p/js/pay.js'
	);

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_print_scripts', array( $this, 'localize_scripts' ), 5 );
		add_action( 'wp_print_footer_scripts', array( $this, 'localize_scripts' ), 5 );
		add_action( 'wp_print_footer_scripts', array( $this, 'print_footer_scripts' ), 6 );
	}

	/**
	 * Enqueue all frontend scripts needed by the plugin
	 */
	public function enqueue_scripts() {
		// register global scripts
		foreach ( $this->global_scripts as $handle => $src ) {
			$this->register_script( $handle, $src );
		}

		$this->register_script( 'form-handler', $this->assets_url( 'js/frontend/form-handler.js' ), array( 'jquery' ) );

		// register scripts that aren't part of gateways
		$this->register_script( 'wpp-payment', $this->assets_url( 'js/frontend/wpp-stripe.js' ),
			array(
				'jquery',
				$this->get_handle( 'external' ),
				'woocommerce',
				$this->get_handle( 'form-handler' )
			) );

		// mini cart is not relevant on cart and checkout page.
		if ( ! is_checkout() && ! is_cart() ) {
			foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
				if ( $gateway instanceof WPP_Payment_Gateway_Stripe && $gateway->is_available() && $gateway->mini_cart_enabled() ) {
					$gateway->enqueue_frontend_scripts( 'mini_cart' );
				}
			}
		}

		if ( function_exists( 'wp_add_inline_script' ) ) {
			wp_add_inline_script( $this->get_handle( 'wpp-payment' ), '(function(){
				if(window.navigator.userAgent.match(/MSIE|Trident/)){
					var script = document.createElement(\'script\');
					script.setAttribute(\'src\', \'' . $this->assets_url( 'js/frontend/promise-polyfill.min.js' ) . '\');
					document.head.appendChild(script);
				}
			}());' );
		}
	}

	public function localize_scripts() {
		$this->localize_script( 'wpp-payment',
			array(
				'api_key' => wpp_payment_get_publishable_key(),
				'account' => wpp_payment_get_account_id(),
				'page'    => $this->get_page_id(),
				'version' => stripe_wpp()->version(),
				'mode'    => wpp_payment_mode()
			),
			'wpp_payment_params_v3'
		);
		$this->localize_script( 'form-handler',
			array(
				'no_results' => __(
					'No matches found',
					'wc-stripe-payments'
				),
			)
		);
		$this->localize_script( 'wpp-payment', wpp_payment_get_error_messages(), 'wpp_payment_messages' );
		$this->localize_script( 'wpp-payment', wpp_payment_get_checkout_fields(), 'wpp_payment_checkout_fields' );

		// don't need to call localize_scripts twice.
		if ( doing_action( 'wp_print_scripts' ) ) {
			remove_action( 'wp_print_footer_scripts', array( $this, 'localize_scripts' ), 5 );
		}
	}

	public function enqueue_checkout_scripts() {
	}

	public function register_script( $handle, $src, $deps = array(), $version = '', $footer = true ) {
		$version                    = empty( $version ) && null !== $version ? stripe_wpp()->version() : $version;
		$this->registered_scripts[] = $this->get_handle( $handle );
		wp_register_script( $this->get_handle( $handle ), $src, $deps, $version, $footer );
	}

	public function enqueue_script( $handle, $src = '', $deps = array(), $version = '', $footer = true ) {
		$handle  = $this->get_handle( $handle );
		$version = empty( $version ) && null !== $version ? stripe_wpp()->version() : $version;
		if ( ! in_array( $handle, $this->registered_scripts ) ) {
			$this->register_script( $handle, $src, $deps, $version, $footer );
		}
		$this->enqueued_scripts[] = $handle;
		wp_enqueue_script( $handle );
	}

	/**
	 *
	 * @param string $handle
	 * @param array $data
	 * @param string $object_name
	 */
	public function localize_script( $handle, $data, $object_name = '' ) {
		$handle = $this->get_handle( $handle );
		if ( wp_script_is( $handle, 'registered' ) ) {
			$name = str_replace( $this->prefix, '', $handle );
			if ( ! $object_name ) {
				$object_name = str_replace( '-', '_', $handle ) . '_params';
			}
			if ( ! in_array( $object_name, $this->localized_data ) ) {
				$data = apply_filters( 'wpp_payment_localize_script_' . $name, $data, $object_name );
				if ( $data ) {
					$this->localized_scripts[] = $handle;
					$this->localized_data[]    = $object_name;
					wp_localize_script( $handle, $object_name, $data );
				}
			}
		}
	}

	public function get_handle( $handle ) {
		return strpos( $handle, $this->prefix ) === false ? $this->prefix . $handle : $handle;
	}

	/**
	 *
	 * @param string $uri
	 */
	public function assets_url( $uri = '' ) {
		// if minification scripts required, convert the uri to it's min format.
		$uri = ( ( $min = $this->get_min() ) ) ? preg_replace( '/([\w-]+)(\.(?<!min\.)(js|css))$/', '$1' . $min . '$2', $uri ) : $uri;

		return untrailingslashit( stripe_wpp()->assets_url( $uri ) );
	}

	public function get_min() {
		return $suffix = SCRIPT_DEBUG ? '' : '.min';
	}

	private function get_page_id() {
		return wpp_payment_get_current_page();
	}

	public function print_footer_scripts() {
		if ( is_checkout() && ! isset( $wp->query_vars['order_pay'] ) && ! is_order_received_page() && ! did_action( 'wpp_payment_blocks_enqueue_styles' ) ) {
			$available_gateways = array_keys( WC()->payment_gateways()->get_available_payment_gateways() );
			$gateways           = array_filter( WC()->payment_gateways()->payment_gateways(), function ( $gateway ) use ( $available_gateways ) {
				return $gateway instanceof WPP_Payment_Gateway_Stripe && $gateway->is_available() && ( ! in_array( $gateway->id, $available_gateways ) || ! $gateway->has_enqueued_scripts( $this ) );
			} );
			// If there are entries in the $gateways array that means some plugin filtered out the gateway.
			// It still needs to output its scripts
			foreach ( $gateways as $gateway ) {
				/**
				 * @var WPP_Payment_Gateway_Stripe $gateway
				 */
				$gateway->enqueue_frontend_scripts( 'checkout' );
			}
		}
	}
}
