<?php
defined( 'ABSPATH' ) || exit();

/**
 * Controller class that perfors cart operations for client side requests.
 *
 * @author WpPayments
 * @package Stripe/Controllers
 *
 */
class WPP_Controller_Source extends WPP_Rest_Controller {

	protected $namespace = 'source';

	public function register_routes() {
		register_rest_route( $this->rest_uri(), 'update', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'update_source' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'source_id'     => array( 'required' => true ),
				'client_secret' => array( 'required' => true ),
				'updates'       => array( 'required' => true ),
				'gateway_id'    => array( 'required', true )
			)
		) );
		register_rest_route(
			$this->rest_uri(), 'order/source', array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_order_source' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * @param WP_REST_Request $request
	 */
	public function update_source( $request ) {

		try {
			/**
			 * @var WPP_Payment_Gateway_Stripe $payment_method
			 */
			$payment_method = WC()->payment_gateways()->payment_gateways()[ $request['payment_method'] ];

			// fetch the source and check client token and status
			$source = $payment_method->payment_object->get_gateway()->sources->retrieve( $request['source_id'] );

			if ( is_wp_error( $source ) ) {
				throw new Exception( __( 'Error updating source.', 'wc-stripe-payments' ) );
			}
			if ( $source->status !== 'chargeable' ) {
				if ( ! hash_equals( $source->client_secret, $request['client_secret'] ) ) {
					throw new Exception( __( 'You do not have permission to update this source.', 'wc-stripe-payments' ) );
				}
				//update the source
				$updates = $request['updates'];
				if ( WC()->cart ) {
					$updates['amount'] = wpp_payment_add_number_precision( WC()->cart->total, strtoupper( $source->currency ) );
				}
				$source = $payment_method->payment_object->get_gateway()->sources->update( $request['source_id'], $updates );
				if ( is_wp_error( $source ) ) {
					throw new Exception( __( 'Error updating source.', 'wc-stripe-payments' ) );
				}
			}

			return rest_ensure_response( array( 'source' => $source->toArray() ) );
		} catch ( Exception $e ) {
			return new WP_Error( 'source-error', $e->getMessage(), array( 'status' => 400 ) );
		}
	}

	/**
	 * Deletes a source from an order if the order exists.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function delete_order_source( $request ) {
		$order_id = WC()->session->get( 'order_awaiting_payment', null );
		if ( $order_id ) {
			$order = wc_get_order( $order_id );
			$order->delete_meta_data( 'wpp_payment_source_id' );
			$order->save();
		}

		return rest_ensure_response( array( 'success' => true ) );
	}
}