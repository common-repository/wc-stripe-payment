<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @author WpPayments
 * @package Stripe/Controllers
 *
 */
class WPP_Controller_Webhook extends WPP_Rest_Controller {

	protected $namespace = '';

	private $secret;

	public function register_routes() {
		register_rest_route(
			$this->rest_uri(),
			'webhook',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'webhook' ),
				'permission_callback' => '__return_true'
			)
		);
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function webhook( $request ) {
		$payload      = $request->get_body();
		$json_payload = json_decode( $payload, true );
		$mode         = $json_payload['livemode'] == true ? 'live' : 'test';
		$this->secret = stripe_wpp()->api_settings->get_option( 'webhook_secret_' . $mode );
		$header       = isset(  $_SERVER['HTTP_STRIPE_SIGNATURE'] )  ? sanitize_text_field( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) : '';
		try {
			$event = \Stripe\Webhook::constructEvent( $payload, $header, $this->secret, apply_filters( 'wpp_payment_webhook_signature_tolerance', 600 ) );
			// $event = \Stripe\StripeObject::constructFrom(json_decode($payload, true));
			wpp_payment_log_info( sprintf( 'Webhook notification received: Event: %s. Payload: %s', $event->type, print_r( $payload, true ) ) );
			$type = $event->type;
			$type = str_replace( '.', '_', $type );

			// allow functionality to hook in to the event action
			do_action( 'wpp_payment_webhooks_' . $type, $event->data->object, $request, $event );

			return rest_ensure_response( apply_filters( 'wpp_payment_webhook_response', array(), $event, $request ) );
		} catch ( \Stripe\Exception\SignatureVerificationException $e ) {
			wpp_payment_log_error( sprintf( __( 'Invalid signature received. Verify that your webhook secret is correct. Error: %s', 'wc-stripe-payments' ), $e->getMessage() ) );

			return $this->send_error_response( __( 'Invalid signature received. Verify that your webhook secret is correct.', 'wc-stripe-payments' ), 401 );
		} catch ( Exception $e ) {
			wpp_payment_log_info( sprintf( __( 'Error processing webhook. Message: %s Exception: %s', 'wc-stripe-payments' ), $e->getMessage(), get_class( $e ) ) );

			return $this->send_error_response( $e->getMessage() );
		}
	}

	private function send_error_response( $message, $code = 400 ) {
		return new WP_Error( 'webhook-error', $message, array( 'status' => $code ) );
	}
}
