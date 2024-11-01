<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @author WpPayments
 * @package Stripe/Trait
 *
 */
trait WPP_Token_Source_Trait {

	public function save_payment_method() {
		return WPP_Stripe_Gateway::load()->customers->createSource( $this->get_customer_id(), array( 'source' => $this->get_token() ) );
	}

	public function delete_from_stripe() {
		return WPP_Stripe_Gateway::load()->sources->mode( $this->get_environment() )->detach( $this->get_customer_id(), $this->get_token() );
	}
}

/**
 *
 * @author WpPayments
 * @package Stripe/Trait
 *
 */
trait WPP_Token_Payment_Method_Trait {

	public function save_payment_method() {
		return WPP_Stripe_Gateway::load()->paymentMethods->attach( $this->get_token(), array( 'customer' => $this->get_customer_id() ) );
	}

	public function delete_from_stripe() {
		return WPP_Stripe_Gateway::load()->paymentMethods->mode( $this->get_environment() )->detach( $this->get_token() );
	}
}
