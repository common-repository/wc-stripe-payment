<?php
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WPP_Payment_Token_Stripe_CC' ) ) {
	return;
}

/**
 *
 * @author WpPayments
 * @package Stripe/Tokens
 *
 */
class WPP_Payment_Token_Stripe_ApplePay extends WPP_Payment_Token_Stripe_CC {

	protected $type = 'Stripe_ApplePay';

	public function get_basic_payment_method_title() {
		return __( 'Apple Pay', 'wc-stripe-payments' );
	}
}
