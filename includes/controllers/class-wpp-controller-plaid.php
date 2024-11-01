<?php
defined( 'ABSPATH' ) || exit();

class WPP_Controller_Plaid extends WPP_Rest_Controller {

	use WPP_Controller_MF_Trait;

	protected $namespace = 'plaid';

	public function register_routes() {
		register_rest_route( $this->rest_uri(), 'link-token', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'get_link_token' ),
			'permission_callback' => '__return_true'
		) );
	}

	/**
	 * @param WP_REST_Request $request
	 */
	public function get_link_token( $request ) {
		/**
		 * @var WPP_Gateway_Stripe_ACH $gateway
		 */
		$gateway = WC()->payment_gateways()->payment_gateways()['wpp_stripe_ach'];

		try {
			$response = $gateway->fetch_link_token();

			return rest_ensure_response( array( 'token' => $response->link_token ) );
		} catch ( Exception $e ) {
			return new WP_Error( 'plaid-error', $e->getMessage(), array( 'status' => 200 ) );
		}
	}
}