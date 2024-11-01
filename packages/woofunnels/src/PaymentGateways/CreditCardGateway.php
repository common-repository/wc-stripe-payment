<?php


namespace PaymentWps\WooFunnels\Stripe\PaymentGateways;


class CreditCardGateway extends BasePaymentGateway {

	protected $key = 'wpp_stripe_cc';

	public function initialize() {
		add_filter( 'wpp_payment_cc_show_save_source', [ $this, 'show_save_source' ] );
	}

	public function show_save_source( $bool ) {
		if ( $bool ) {
			$bool = ! $this->should_tokenize();
		}

		return $bool;
	}
}