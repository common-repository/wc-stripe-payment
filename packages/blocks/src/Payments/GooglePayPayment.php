<?php

namespace PaymentWps\Blocks\Stripe\Payments;


class GooglePayPayment extends AbstractStripePayment {

	protected $name = 'wpp_stripe_googlepay';

	public function get_payment_method_script_handles() {
		$this->assets_api->register_external_script( 'wpp-payment-gpay-external', 'https://pay.google.com/gp/p/js/pay.js', array(), null );
		$this->assets_api->register_script( 'wpp-payment-blocks-googlepay', 'build/wpp-payment-googlepay.js', array( 'wpp-payment-gpay-external' ) );

		return array( 'wpp-payment-blocks-googlepay' );
	}

	public function get_payment_method_data() {
		return wp_parse_args( array(
			'icon'              => $this->get_payment_method_icon(),
			'editorIcons'       => array(
				'long'  => $this->assets_api->get_asset_url( 'assets/img/gpay_button_buy_black.svg' ),
				'short' => $this->assets_api->get_asset_url( 'assets/img/gpay_button_black.svg' )
			),
			'merchantId'        => $this->get_merchant_id(),
			'merchantName'      => $this->payment_method->get_option( 'merchant_name' ),
			'totalPriceLabel'   => __( 'Total', 'wc-stripe-payments' ),
			'buttonStyle'       => array(
				'buttonColor'    => $this->payment_method->get_option( 'button_color' ),
				'buttonType'     => $this->payment_method->get_option( 'button_style' ),
				'buttonSizeMode' => 'fill'
			),
			'environment'       => $this->get_google_pay_environment(),
			'processingCountry' => WC()->countries ? WC()->countries->get_base_country() : wc_get_base_location()['country']
		), parent::get_payment_method_data() );
	}

	protected function get_payment_method_icon() {
		$icon = $this->payment_method->get_option( 'icon' );

		return array(
			'id'  => "{$this->name}_icon",
			'alt' => '',
			'src' => stripe_wpp()->assets_url( "img/{$icon}.svg" )
		);
	}

	private function get_merchant_id() {
		return 'test' === wpp_payment_mode() ? '' : $this->payment_method->get_option( 'merchant_id' );
	}

	private function get_google_pay_environment() {
		return wpp_payment_mode() === 'test' ? 'TEST' : 'PRODUCTION';
	}
}