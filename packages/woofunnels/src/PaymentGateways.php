<?php


namespace PaymentWps\WooFunnels\Stripe;


class PaymentGateways {

	public function __construct() {
		$this->initialize();
	}

	private function initialize() {
		add_action( 'init', [ $this, 'initialize_gateways' ] );
		add_filter( 'wfocu_wc_get_supported_gateways', [ $this, 'add_supported_gateways' ] );
		add_filter( 'wpp_payment_force_save_payment_method', [ $this, 'maybe_set_save_payment_method' ], 10, 3 );
		add_action( 'wpp_payment_order_payment_complete', [ $this, 'maybe_setup_upsell' ], 10, 2 );
		add_action( 'wfocu_offer_new_order_created_before_complete', [ $this, 'add_new_order_data' ] );
		add_action( 'wfocu_footer_before_print_scripts', [ $this, 'add_scripts' ] );
		add_filter( 'wfocu_localized_data', [ $this, 'add_data' ] );
	}

	public function initialize_gateways() {
		foreach ( $this->get_payment_gateways() as $clazz ) {
			call_user_func( [ $clazz, 'get_instance' ] );
		}
	}

	public function add_supported_gateways( $gateways ) {
		return array_merge( $gateways, $this->get_payment_gateways() );
	}

	private function get_payment_gateways() {
		return [
			'wpp_stripe_cc'              => 'PaymentWps\WooFunnels\Stripe\PaymentGateways\CreditCardGateway',
			'wpp_stripe_googlepay'       => 'PaymentWps\WooFunnels\Stripe\PaymentGateways\GooglePayGateway',
			'wpp_stripe_applepay'        => 'PaymentWps\WooFunnels\Stripe\PaymentGateways\ApplePayGateway',
			'wpp_stripe_payment_request' => 'PaymentWps\WooFunnels\Stripe\PaymentGateways\PaymentRequestGateway'
		];
	}

	private function is_supported_gateway( $id ) {
		return isset( $this->get_payment_gateways()[ $id ] );
	}

	/**
	 * @param $id
	 *
	 * @return bool|\WFOCU_Gateway|\WFOCU_Gateways
	 */
	public function get_wfocu_payment_gateway( $id ) {
		return WFOCU_Core()->gateways->get_integration( $id );
	}

	/**
	 * @param $bool
	 * @param \WC_Order $order
	 * @param \WPP_Payment_Gateway_Stripe $payment_method
	 *
	 * @return bool
	 */
	public function maybe_set_save_payment_method( $bool, \WC_Order $order, \WPP_Payment_Gateway_Stripe $payment_method ) {
		if ( ! $bool ) {
			$payment_gateway = $this->get_wfocu_payment_gateway( $order->get_payment_method() );
			if ( $payment_gateway && $payment_gateway->should_tokenize() && ! $payment_method->use_saved_source() ) {
				$bool = true;
			}
		}

		return $bool;
	}

	/**
	 * Maybe setup the WooFunnels upsell if the charge has not been captured.
	 *
	 * @param \Stripe\Charge $charge
	 * @param \WC_Order $order
	 */
	public function maybe_setup_upsell( \Stripe\Charge $charge, \WC_Order $order ) {
		$payment_method = $order->get_payment_method();
		if ( ! $charge->captured && $this->is_supported_gateway( $payment_method ) ) {
			$payment_gateway = $this->get_wfocu_payment_gateway( $payment_method );
			if ( $payment_gateway && $payment_gateway->should_tokenize() ) {
				WFOCU_Core()->public->maybe_setup_upsell( $order->get_id() );
			}
		}
	}

	public function add_new_order_data( \WC_Order $order ) {
		$payment_method = $order->get_payment_method();
		if ( $this->is_supported_gateway( $payment_method ) ) {
			$order->update_meta_data( '_wpp_payment_mode', wpp_payment_mode() );
		}
	}

	public function add_data( $data ) {
		$data['stripeData'] = [
			'publishableKey' => wpp_payment_get_publishable_key(),
			'account'        => wpp_payment_get_account_id()
		];

		return $data;
	}

	public function add_scripts() {
		if ( ! \WFOCU_Core()->public->if_is_offer() || WFOCU_Core()->public->if_is_preview() ) {
			return true;
		}
		$order = WFOCU_Core()->data->get_current_order();
		if ( ! $order instanceof \WC_Order ) {
			return;
		}
		$payment_method = $order->get_payment_method();

		if ( in_array( $payment_method, array_keys( $this->get_payment_gateways() ) ) ) {
			global $wp_scripts;
			$assets_url = plugin_dir_url( __DIR__ ) . 'build/';
			$params     = require_once dirname( __DIR__ ) . '/build/wpp-payment-woofunnels.asset.php';
			wp_enqueue_script( 'wpp-payment-woofunnels', $assets_url . 'wpp-payment-woofunnels.js', $params['dependencies'], $params['version'], true );
			$wp_scripts->do_items( [ 'wpp-payment-woofunnels' ] );
		}
	}
}