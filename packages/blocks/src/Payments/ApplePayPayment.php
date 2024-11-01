<?php


namespace PaymentWps\Blocks\Stripe\Payments;


class ApplePayPayment extends AbstractStripePayment {

	protected $name = 'wpp_stripe_applepay';

	public function get_payment_method_script_handles() {
		$this->assets_api->register_script( 'wpp-payment-blocks-apple-pay', 'build/wpp-payment-apple-pay.js' );

		return array( 'wpp-payment-blocks-apple-pay' );
	}

	public function get_payment_method_data() {
		return wp_parse_args( array(
			'buttonType'  => $this->payment_method->get_option( 'button_type_checkout' ),
			'buttonStyle' => $this->payment_method->get_option( 'button_style' ),
			'editorIcon'  => $this->assets_api->get_asset_url( 'assets/img/apple_pay_button_black.svg' )
		), parent::get_payment_method_data() );
	}
}