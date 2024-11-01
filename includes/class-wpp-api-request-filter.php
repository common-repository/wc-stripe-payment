<?php

defined( 'ABSPATH' ) || exit();

class WPP_Payment_API_Request_Filter {

	private $advanced_settings;

	/**
	 * @param \WPP_Advanced_Settings $advanced_settings
	 */
	public function __construct( $advanced_settings ) {
		$this->advanced_settings = $advanced_settings;
		$this->initialize();
	}

	private function initialize() {
		if ( $this->advanced_settings->is_fee_enabled() ) {
			add_filter( 'wpp_payment_intent_args', array( $this, 'expand_balance_transaction' ) );
			add_filter( 'wpp_payment_payment_intent_confirmation_args', array( $this, 'expand_balance_transaction' ) );
			add_filter( 'wpp_payment_payment_intent_retrieve_args', array( $this, 'expand_balance_transaction' ) );
			add_filter( 'wpp_payment_payment_intent_capture_args', array( $this, 'expand_balance_transaction' ) );
			add_filter( 'wpp_payment_charge_order_args', array( $this, 'expand_balance_transaction_for_charge' ) );
		}
	}

	public function expand_balance_transaction( $args ) {
		if ( ! is_array( $args ) ) {
			$args = array();
		}
		$args['expand']   = isset( $args['expand'] ) ? $args['expand'] : array();
		$args['expand'][] = 'charges.data.balance_transaction';

		return $args;
	}

	public function expand_balance_transaction_for_charge( $args ) {
		$args['expand']   = isset( $args['expand'] ) ? $args['expand'] : array();
		$args['expand'][] = 'balance_transaction';

		return $args;
	}

}