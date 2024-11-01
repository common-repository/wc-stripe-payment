<?php
defined( 'ABSPATH' ) || exit();

/**
 * Controller which handles Payment Intent related actions such as creation.
 *
 * @author WpPayments
 * @package Stripe/Controllers
 *
 */
class WPP_Controller_Payment_Intent extends WPP_Rest_Controller {

	protected $namespace = '';

	public function register_routes() {
		register_rest_route(
			$this->rest_uri(),
			'setup-intent',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => '__return_true',
				'callback'            => array(
					$this,
					'create_setup_intent',
				),
				'args'                => array(
					'payment_method' => array(
						'required' => true
					)
				)
			)
		);
		register_rest_route(
			$this->rest_uri(),
			'sync-payment-intent',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'sync_payment_intent' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'order_id'      => array( 'required' => true ),
					'client_secret' => array( 'required' => true ),
				),
			)
		);
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function create_setup_intent( $request ) {
		/**
		 * @var WPP_Payment_Gateway_Stripe $payment_method
		 */
		$payment_method = WC()->payment_gateways()->payment_gateways()[ $request['payment_method'] ];
		$params         = array( 'usage' => 'off_session' );
		// @3.3.12 - check if 3DS is being forced
		if ( $payment_method->is_active( 'force_3d_secure' ) ) {
			$params['payment_method_options']['card']['request_three_d_secure'] = 'any';
		}
		$intent = $payment_method->payment_object->get_gateway()->setupIntents->create( $params );
		try {
			if ( is_wp_error( $intent ) ) {
				throw new Exception( $intent->get_error_message() );
			}

			return rest_ensure_response( array( 'intent' => array( 'client_secret' => $intent->client_secret ) ) );
		} catch ( Exception $e ) {
			return new WP_Error(
				'payment-intent-error',
				sprintf( __( 'Error creating payment intent. Reason: %s', 'wc-stripe-payments' ), $e->getMessage() ),
				array(
					'status' => 200,
				)
			);
		}
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function sync_payment_intent( $request ) {
		try {
			$order = wc_get_order( absint( $request->get_param( 'order_id' ) ) );
			if ( ! $order ) {
				throw new Exception( __( 'Invalid order id provided', 'wc-stripe-payments' ) );
			}

			$intent = WPP_Stripe_Gateway::load()->paymentIntents->retrieve( $order->get_meta( '_payment_intent_id' ) );

			if ( ! hash_equals( $intent->client_secret, $request->get_param( 'client_secret' ) ) ) {
				throw new Exception( __( 'You are not authorized to update this order.', 'wc-stripe-payments' ) );
			}

			$order->update_meta_data( '_payment_intent', $intent->jsonSerialize() );
			$order->save();

			return rest_ensure_response( array( 'success' => true ) );
		} catch ( Exception $e ) {
			return new WP_Error( 'payment-intent-error', $e->getMessage(), array( 'status' => 200 ) );
		}
	}
}
