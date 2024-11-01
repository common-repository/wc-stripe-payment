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
class WPP_Payment_Token_Stripe_GooglePay extends WPP_Payment_Token_Stripe_CC {

	protected $type = 'Stripe_GooglePay';

	public function get_formats() {
		return array(
			       'gpay_name' => array(
				       'label'   => __( 'Gateway Name', 'wc-stripe-payments' ),
				       'example' => 'Visa 1111 (Google Pay)',
				       'format'  => '{brand} {last4} (Google Pay)'
			       )
		       ) + parent::get_formats();
	}

	public function get_basic_payment_method_title() {
		return __( 'Google Pay', 'wc-stripe-payments' );
	}
}
