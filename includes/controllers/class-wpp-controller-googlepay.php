<?php
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WPP_Rest_Controller' ) ) {
	return;
}

/**
 *
 * @author WpPayments
 * @package Stripe/Controllers
 */
class WPP_Controller_GooglePay extends WPP_Rest_Controller {

	use WPP_Controller_Cart_Trait;
	use WPP_Controller_MF_Trait;

	protected $namespace = 'googlepay';

	/**
	 *
	 * @var WPP_Gateway_Stripe_GooglePay
	 */
	private $gateway;

	/**
	 *
	 * @var string
	 */
	private $shipping_method_id;

	/**
	 *
	 * @var string
	 */
	private $reason_code;

	public function register_routes() {
		register_rest_route(
			$this->rest_uri(),
			'shipping-data',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_shipping_data' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'address'         => array( 'required' => true, 'validate_callback' => array( $this, 'validate_shipping_address' ) ),
					'shipping_method' => array( 'required' => false )
				),
			)
		);
	}

	/**
	 * Update the WC shipping data from the Google payment sheet.
	 *
	 * @param WP_REST_Request $request
	 */
	public function update_shipping_data( $request ) {
		wc_maybe_define_constant( 'WOOCOMMERCE_CART', true );

		$address                  = $request->get_param( 'address' );
		$this->shipping_method_id = ( ( $method_id = $request->get_param( 'shipping_method' ) ) ? $method_id : 'default' );

		$this->gateway = WC()->payment_gateways()->payment_gateways()['wpp_stripe_googlepay'];

		$this->add_ready_to_calc_shipping();

		try {

			wpp_payment_update_customer_location( $address );

			wpp_payment_update_shipping_methods( $this->get_shipping_method_from_request( $request ) );

			if ( 'product' == $request->get_param( 'page_id' ) ) {
				wpp_payment_stash_cart( WC()->cart );
			}

			// update the WC cart with the new shipping options
			WC()->cart->calculate_totals();

			// if shipping address is not serviceable, throw an error.
			if ( ! wpp_payment_shipping_address_serviceable( $this->gateway->get_shipping_packages() ) ) {
				$this->reason_code = 'SHIPPING_ADDRESS_UNSERVICEABLE';
				throw new Exception( __( 'Your shipping address is not serviceable.', 'wc-stripe-payments' ) );
			}

			$response = rest_ensure_response(
				apply_filters(
					'wpp_googlepay_paymentdata_response',
					array(
						'data' => array(
							'shipping_methods'     => $this->get_shipping_methods(),
							'paymentRequestUpdate' => $this->get_payment_response_data(),
							'address'              => $address
						),
					)
				)
			);
			if ( 'product' == $request->get_param( 'page_id' ) ) {
				wpp_payment_restore_cart( WC()->cart );
			}

			return $response;
		} catch ( Exception $e ) {
			return new WP_Error(
				'payment-data-error',
				$e->getMessage(),
				array(
					'status' => 200,
					'data'   => array(
						'error' => array(
							'reason'  => $this->reason_code,
							'message' => $e->getMessage(),
							'intent'  => 'SHIPPING_ADDRESS',
						),
					),
				)
			);
		}
	}

	/**
	 * Return a formatted array of response data required by the Google payment sheet.
	 */
	public function get_payment_response_data() {
		$shipping_options = $this->gateway->get_formatted_shipping_methods();

		return array(
			'newTransactionInfo'          => array(
				'currencyCode'     => get_woocommerce_currency(),
				'countryCode'      => WC()->countries->get_base_country(),
				'totalPriceStatus' => 'FINAL',
				'totalPrice'       => wc_format_decimal( WC()->cart->total, 2 ),
				'displayItems'     => $this->gateway->get_display_items(),
				'totalPriceLabel'  => __( 'Total', 'wc-stripe-payments' ),
			),
			'newShippingOptionParameters' => array(
				'shippingOptions'         => $shipping_options,
				'defaultSelectedOptionId' => $this->get_default_shipping_method( $shipping_options ),
			),
		);
	}

	private function get_shipping_methods() {
		return WC()->session->get( 'chosen_shipping_methods', array() );
	}

	/**
	 * Returns a default shipping method based on the chosen shipping methods.
	 *
	 * @param array $methods
	 *
	 * @return string
	 */
	private function get_default_shipping_method( $methods ) {
		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods', array() );
		$method_ids              = wp_list_pluck( $methods, 'id' );
		foreach ( $chosen_shipping_methods as $idx => $method ) {
			$method_id = sprintf( '%s:%s', $idx, $method );
			if ( in_array( $method_id, $method_ids ) ) {
				$this->shipping_method_id = $method_id;
			}
		}
		if ( ! $this->shipping_method_id ) {
			$this->shipping_method_id = current( $method_ids );
		}

		return $this->shipping_method_id;
	}
}
