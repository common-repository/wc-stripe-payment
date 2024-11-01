<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @package Stripe/Tokens
 * @author WpPayments
 *
 */
class WPP_Payment_Token_Stripe_ACH extends WPP_Payment_Token_Stripe {

	use WPP_Token_Source_Trait;

	protected $type = 'Stripe_ACH';

	protected $stripe_payment_type = 'source';

	protected $stripe_data = array(
		'bank_name'      => '',
		'routing_number' => '',
		'last4'          => '',
	);

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WPP_Payment_Token_Stripe::details_to_props()
	 */
	public function details_to_props( $details ) {
		if ( isset( $details['ach_debit'] ) ) {
			$bank = $details['ach_debit'];
		} elseif ( $details instanceof \Stripe\BankAccount ) {
			$bank = $details;
		}
		$this->set_brand( $bank['bank_name'] );
		$this->set_bank_name( $bank['bank_name'] );
		$this->set_last4( $bank['last4'] );
		$this->set_routing_number( $bank['routing_number'] );
	}

	public function get_bank_name( $context = 'view' ) {
		return $this->get_prop( 'bank_name', $context );
	}

	public function get_routing_number( $context = 'view' ) {
		return $this->get_prop( 'routing_number', $context );
	}

	public function get_last4( $context = 'view' ) {
		return $this->get_prop( 'last4', $context );
	}

	public function set_bank_name( $value ) {
		$this->set_prop( 'bank_name', $value );
	}

	public function set_routing_number( $value ) {
		$this->set_prop( 'routing_number', $value );
	}

	public function set_last4( $value ) {
		$this->set_prop( 'last4', $value );
	}

	public function get_formats() {
		return apply_filters( 'wpp_payment_get_token_formats', array(
			'type_ending_in'    => array(
				'label'   => __( 'Type Ending In', 'wc-stripe-payments' ),
				'example' => 'Chase ending in 3434',
				'format'  => __( '{bank_name} ending in {last4}', 'wc-stripe-payments' ),
			),
			'name_masked_last4' => array(
				'label'   => __( 'Type Ending In', 'wc-stripe-payments' ),
				'example' => 'Chase **** 3434',
				'format'  => __( '{bank_name} **** {last4}', 'wc-stripe-payments' ),
			),
			'short_title'       => array(
				'label'   => __( 'Gateway Title', 'wc-stripe-payments' ),
				'example' => $this->get_basic_payment_method_title(),
				'format'  => '{short_title}'
			)
		), $this );
	}

	public function get_html_classes() {
		return 'wpp-payment-ach';
	}

	public function get_basic_payment_method_title() {
		return __( 'Bank Payment', 'wc-stripe-payments' );
	}
}
