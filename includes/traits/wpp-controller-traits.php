<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @author WpPayments
 */
trait WPP_Controller_Cart_Trait {

	/**
	 * Method that hooks in to the woocommerce_cart_ready_to_calc_shipping filter.
	 * Purpose is to ensure
	 * true is returned so shipping packages are calculated. Some 3rd party plugins and themes return false
	 * if the current page is the cart because they don't want to display the shipping calculator.
	 *
	 */
	public function add_ready_to_calc_shipping() {
		add_filter(
			'woocommerce_cart_ready_to_calc_shipping',
			function ( $show_shipping ) {
				return true;
			},
			1000
		);
	}

	/**
	 * @param WP_Rest_Request $request
	 *
	 * @return array
	 * @throws Exception
	 */
	private function get_shipping_method_from_request( $request ) {
		if ( ( $method = $request->get_param( 'shipping_method' ) ) ) {
			if ( ! preg_match( '/^(?P<index>[\w]+)\:(?P<id>.+)$/', $method, $shipping_method ) ) {
				throw new Exception( __( 'Invalid shipping method format. Expected: index:id', 'wc-stripe-payments' ) );
			}

			return array( $shipping_method['index'] => $shipping_method['id'] );
		}

		return array();
	}

	/**
	 * @param array $address
	 * @param WP_REST_Request $request
	 */
	public function validate_shipping_address( $address, $request ) {
		if ( isset( $address['state'], $address['country'] ) ) {
			$address['state']   = wpp_payment_filter_address_state( $address['state'], $address['country'] );
			$request['address'] = $address;
		}

		return true;
	}
}

/**
 * Trait WPP_Controller_MF_Trait
 */
trait WPP_Controller_MF_Trait {

	/**
	 * @var WP_REST_Request
	 */
	private $request;

	protected function cart_includes() {
		include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
		include_once WC_ABSPATH . 'includes/wc-notice-functions.php';
		wc_load_cart();
		// loads cart from session
		WC()->cart->get_cart();
		WC()->payment_gateways();
	}

	protected function frontend_includes() {
		WC()->frontend_includes();
		wc_load_cart();
		WC()->cart->get_cart();
		WC()->payment_gateways();
	}

	/**
	 * @param $request
	 *
	 * @return bool|WP_Error
	 */
	public function validate_rest_nonce( $request ) {
		if ( ! isset( $request['wp_rest_nonce'] ) || ! wp_verify_nonce( $request['wp_rest_nonce'], 'wp_rest' ) ) {
			return new WP_Error( 'rest_cookie_invalid_nonce', __( 'Cookie nonce is invalid' ), array( 'status' => 403 ) );
		}

		return true;
	}
}
