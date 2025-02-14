<?php


namespace PaymentWps\CartFlows\Stripe\Routes;


use PaymentWps\CartFlows\Stripe\Constants;

class PaymentIntentRoute extends AbstractRoute {

	public function get_route_args() {
		return array(
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'handle_request' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'client_secret' => array(
						'type'     => 'string',
						'required' => true
					),
					'order_id'      => array(
						'type'     => 'integer',
						'required' => true
					)
				)
			)
		);
	}

	public function get_path() {
		return 'payment-intent';
	}

	public function handle_post_request( $request ) {
		$order = wc_get_order( absint( $request['order_id'] ) );
		if ( ! $order ) {
			throw new \Exception( __( 'Invalid order id provided', 'wc-stripe-payments' ) );
		}

		$payment_intent = $this->client->paymentIntents->retrieve( $order->get_meta( Constants::CARTFLOWS_PAYMENT_INTENT_ID ) );
		if ( is_wp_error( $payment_intent ) ) {
			throw new \Exception( 'Invalid payment intent.' );
		}

		if ( ! hash_equals( $request['client_secret'], $payment_intent->client_secret ) ) {
			throw new \Exception( __( 'You are not authorized to update this order.', 'wc-stripe-payments' ) );
		}

		if ( $payment_intent->status === 'requires_payment_method' ) {
			$payment_intent = $this->client->paymentIntents->update( $payment_intent->id, array(
				'payment_method' => $order->get_meta( '_payment_method_token' )
			) );
			if ( is_wp_error( $payment_intent ) ) {
				throw new \Exception( 'Update of payment intent failed.' );
			}
		}

		return array( 'success' => true );

	}
}