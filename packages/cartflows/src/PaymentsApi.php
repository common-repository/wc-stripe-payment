<?php


namespace PaymentWps\CartFlows\Stripe;


class PaymentsApi {

	public function __construct() {
		add_filter( 'cartflows_offer_supported_payment_gateways', array( $this, 'add_payment_gateways' ) );
		add_filter( 'wpp_payment_force_save_payment_method', array( $this, 'maybe_force_save_payment_method' ), 10, 3 );
		add_filter( 'cartflows_offer_js_localize', array( $this, 'enqueue_scripts' ) );
	}

	public function add_payment_gateways( $supported_gateways ) {
		$ids = array( 'wpp_stripe_cc', 'wpp_stripe_googlepay', 'wpp_stripe_applepay', 'wpp_stripe_payment_request' );
		foreach ( $ids as $id ) {
			$supported_gateways[ $id ] = array(
				'path'  => dirname( __FILE__ ) . '/PaymentGateways/BasePaymentGateway.php',
				'class' => '\PaymentWps\CartFlows\Stripe\PaymentGateways\BasePaymentGateway'
			);
		}

		return $supported_gateways;
	}

	/**
	 * @param $bool
	 * @param $order
	 * @param $payment_method
	 *
	 * @return bool
	 */
	public function maybe_force_save_payment_method( bool $bool, \WC_Order $order, \WPP_Payment_Gateway_Stripe $payment_method ) {
		// validate that next step is an offer
		$checkout_id = wcf()->utils->get_checkout_id_from_post_data();
		$flow_id     = wcf()->utils->get_flow_id_from_post_data();
		if ( $checkout_id && $flow_id ) {
			$wcf_step_obj      = wcf_pro_get_step( $checkout_id );
			$next_step_id      = $wcf_step_obj->get_next_step_id();
			$wcf_next_step_obj = wcf_pro_get_step( $next_step_id );
			// todo eventually remove check for WPP_Payment_Intent so sources can be supported.
			if ( $next_step_id && $wcf_next_step_obj->is_offer_page() && ! $payment_method->use_saved_source() && $payment_method->payment_object instanceof \WPP_Payment_Intent ) {
				$bool = true;
			}
		}

		return $bool;
	}

	/**
	 * @param array $localize
	 */
	public function enqueue_scripts( $localize ) {
		if ( in_array( $localize['payment_method'], $this->get_payment_method_ids() ) ) {
			$localize['stripeData'] = array(
				'key'       => wpp_payment_get_publishable_key(),
				'accountId' => wpp_payment_get_account_id(),
				'version'   => stripe_wpp()->version(),
				'mode'      => wpp_payment_mode(),
				'msg'       => __( 'Processing Order...', 'cartflows-pro' ),
				'timeout'   => 3000
			);
			// enqueue cartflows script
			$assets_url = plugin_dir_url( __DIR__ ) . 'build/';
			$assets     = require_once dirname( __DIR__ ) . '/build/wpp-payment-cartflows.asset.php';
			wp_enqueue_script( 'wpp-payment-cartflows', $assets_url . 'wpp-payment-cartflows.js', $assets['dependencies'], stripe_wpp()->version(), true );
		}

		return $localize;
	}

	private function get_payment_method_ids() {
		/**
		 */
		return apply_filters( 'wpp_payment_cartflows_get_payment_method_ids', array(
			'wpp_stripe_cc',
			'wpp_stripe_applepay',
			'wpp_stripe_googlepay',
			'stripe_payment_request'
		) );
	}
}