<?php


namespace PaymentWps\Blocks\Stripe\Payments;

/**
 * Class PaymentRequest
 * @package PaymentWps\Blocks\Stripe\Payments
 */
class PaymentRequest extends AbstractStripePayment {

	protected $name = 'wpp_stripe_payment_request';

	public function get_payment_method_script_handles() {
		$this->assets_api->register_script( 'wpp-payment-blocks-payment-request', 'build/wpp-payment-request.js' );

		return array( 'wpp-payment-blocks-payment-request' );
	}

	public function get_payment_method_data() {
		return wp_parse_args( array(
			'paymentRequestButton' => array(
				'type'   => $this->payment_method->get_option( 'button_type' ),
				'theme'  => $this->payment_method->get_option( 'button_theme' ),
				'height' => $this->payment_method->get_button_height(),
			)
		), parent::get_payment_method_data() );
	}
}