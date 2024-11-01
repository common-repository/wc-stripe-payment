<?php

namespace PaymentWps\Blocks\Stripe\Payments;

class CreditCardPayment extends AbstractStripePayment {

	protected $name = 'wpp_stripe_cc';

	public function get_payment_method_script_handles() {
		$this->assets_api->register_script( 'wpp-payment-block-credit-card', 'build/wpp-payment-credit-card.js' );

		return array( 'wpp-payment-block-credit-card' );
	}

	public function get_payment_method_data() {
		$assets_url = $this->assets_api->get_asset_url( '../../assets/img/cards/' );

		return wp_parse_args( array(
			'cardOptions'            => $this->payment_method->get_card_form_options(),
			'customFieldOptions'     => $this->payment_method->get_card_custom_field_options(),
			'customFormActive'       => $this->payment_method->is_custom_form_active(),
			'elementOptions'         => $this->payment_method->get_element_options(),
			'customForm'             => $this->payment_method->get_option( 'custom_form' ),
			'customFormLabels'       => wp_list_pluck( wpp_payment_get_custom_forms(), 'label' ),
			'postalCodeEnabled'      => $this->payment_method->postal_enabled(),
			'saveCardEnabled'        => $this->payment_method->is_active( 'save_card_enabled' ),
			'savePaymentMethodLabel' => __( 'Save Card', 'wc-stripe-payments' ),
			'cards'                  => array(
				'visa'       => $assets_url . 'visa.svg',
				'amex'       => $assets_url . 'amex.svg',
				'mastercard' => $assets_url . 'mastercard.svg',
				'discover'   => $assets_url . 'discover.svg',
				'diners'     => $assets_url . 'diners.svg',
				'jcb'        => $assets_url . 'jcb.svg',
				'maestro'    => $assets_url . 'maestro.svg',
				'unionpay'   => $assets_url . 'china_union_pay.svg',
				'unknown'    => $this->payment_method->get_custom_form()['cardBrand'],
			)
		), parent::get_payment_method_data() );
	}

	protected function get_payment_method_icon() {
		$icons = array();
		foreach ( $this->payment_method->get_option( 'cards', [] ) as $id ) {
			$icons[] = array(
				'id'  => $id,
				'alt' => '',
				'src' => stripe_wpp()->assets_url( "img/cards/{$id}.svg" )
			);
		}

		return $icons;
	}

	/**
	 * @param \PaymentWps\Blocks\Stripe\Assets\Api $style_api
	 */
	public function enqueue_payment_method_styles( $style_api ) {
		if ( $this->payment_method->is_custom_form_active() ) {
			$form = $this->payment_method->get_option( 'custom_form' );
			wp_enqueue_style( 'wpp-payment-credit-card-style', $style_api->get_asset_url( "build/credit-card/{$form}.css" ) );
			wp_style_add_data( 'wpp-payment-credit-card-style', 'rtl', 'replace' );
		}
	}
}