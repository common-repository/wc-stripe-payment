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
class WPP_Controller_Checkout extends WPP_Rest_Controller {

	use WPP_Controller_MF_Trait;

	protected $namespace = '';

	private $order_review = false;

	/**
	 *
	 * @var WPP_Payment_Gateway_Stripe
	 */
	private $gateway = null;

	public function register_routes() {
		register_rest_route(
			$this->rest_uri(),
			'checkout',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'process_checkout' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			$this->rest_uri(),
			'checkout/payment',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'process_payment' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'order_id'  => array( 'required' => true ),
					'order_key' => array( 'required' => true )
				)
			)
		);
		register_rest_route(
			$this->rest_uri(),
			'order-pay',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'process_order_pay' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Process the WC Order
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function process_checkout( $request ) {
		$this->actions();
		$checkout       = WC()->checkout();
		$payment_method = $request->get_param( 'payment_method' );
		/**
		 *
		 * @var WPP_Payment_Gateway_Stripe $gateway
		 */
		$this->gateway = $gateway = WC()->payment_gateways()->payment_gateways()[ $payment_method ];

		try {
			do_action( 'wpp_payment_rest_process_checkout', $request, $gateway );
			if ( ! is_user_logged_in() ) {
				$this->create_customer( $request );
			}
			// set the checkout nonce so no exceptions are thrown.
			$_REQUEST['_wpnonce'] = $_POST['_wpnonce'] = wp_create_nonce( 'woocommerce-process_checkout' );

			if ( 'product' == $request->get_param( 'page_id' ) ) {
				wpp_payment_stash_cart( WC()->cart );
				$gateway->set_post_payment_process( array( $this, 'post_payment_processes' ) );
				add_filter( 'woocommerce_get_checkout_order_received_url', function ( $url, $order ) {
					return add_query_arg( 'wp_payment_product_checkout', $order->get_payment_method(), $url );
				}, 10, 2 );

				$option                           = new WPP_Payment_Product_Gateway_Option( current( WC()->cart->get_cart() )['data'], $gateway );
				$gateway->settings['charge_type'] = $option->get_option( 'charge_type' );
			}
			$this->required_post_data();
			$checkout->process_checkout();
		} catch ( Exception $e ) {
			wc_add_notice( $e->getMessage(), 'error' );
		}
		if ( wc_notice_count( 'error' ) > 0 ) {
			return $this->send_response( false );
		}

		return $this->send_response( true );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 */
	public function process_payment( $request ) {
		wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );

		// Indicator so we know payment is being processed after a 3DS authentication.
		wc_maybe_define_constant( 'processing_payment', true );
		try {
			$order_id = absint( $request->get_param( 'order_id' ) );
			$order    = wc_get_order( $order_id );
			if ( ! $order ) {
				throw new Exception( __( 'Invalid order ID.', 'wc-stripe-payments' ) );
			}
			if ( ! $order->key_is_valid( $request->get_param( 'order_key' ) ) ) {
				throw new Exception( __( 'Invalid order key.', 'wc-stripe-payments' ) );
			}
			/**
			 * @var WPP_Payment_Gateway_Stripe $payment_method
			 */
			$payment_method = WC()->payment_gateways()->payment_gateways()[ $order->get_payment_method() ];

			$result = $payment_method->process_payment( $order_id );

			if ( isset( $result['result'] ) && 'success' === $result['result'] ) {
				return $this->send_response( true, $result );
			}

			return $this->send_response( false, $result );
		} catch ( Exception $e ) {
			wc_add_notice( $e->getMessage(), 'error' );

			return $this->send_response( false );
		}
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function process_order_pay( $request ) {
		global $wp;
		$this->frontend_includes();

		$wp->set_query_var( 'order-pay', $request->get_param( 'order_id' ) );

		// wc_maybe_define_constant( 'WOOCOMMERCE_STRIPE_ORDER_PAY', true );

		WC_Form_Handler::pay_action();

		if ( wc_notice_count( 'error' ) > 0 ) {
			return rest_ensure_response(
				array(
					'success' => false,
					'message' => $this->get_messages( 'error' ),
				)
			);
		}
	}


	/**
	 *
	 * @param WP_REST_Request $request
	 */
	private function create_customer( $request ) {
		$create = WC()->checkout()->is_registration_required();
		// create an account for the user if it's required for things like subscriptions.
		if ( wpp_is_subscription_active() && WC_Subscriptions_Cart::cart_contains_subscription() ) {
			$create = true;
		}
		if ( $create ) {
			$password = wp_generate_password();
			$username = $email = $request->get_param( 'billing_email' );
			$result   = wc_create_new_customer( $email, $username, $password );
			if ( $result instanceof WP_Error ) {
				// for email exists errors you want customer to either login or use a different email address.
				throw new Exception( $result->get_error_message() );
			}

			// log the customer in
			wp_set_current_user( $result );
			wc_set_customer_auth_cookie( $result );

			// As we are now logged in, cart will need to refresh to receive updated nonces
			WC()->session->set( 'reload_checkout', true );
		}
	}

	private function send_response( $success, $defaults = array() ) {
		$reload = WC()->session->get( 'reload_checkout', false );
		$data   = wp_parse_args( $defaults, array(
			'result'   => $success ? 'success' : 'failure',
			'messages' => $reload ? null : $this->get_error_messages(),
			'reload'   => $reload,
		) );
		unset( WC()->session->reload_checkout );

		return rest_ensure_response( $data );
	}

	public function validate_payment_method( $payment_method ) {
		$gateways = WC()->payment_gateways()->payment_gateways();

		return isset( $gateways[ $payment_method ] ) ? true : new WP_Error( 'validation-error', 'Please choose a valid payment method.' );
	}

	private function actions() {
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'wpp_after_checkout_validation' ), 10, 2 );
		add_action( 'woocommerce_checkout_posted_data', array( $this, 'filter_posted_data' ) );
	}

	/**
	 *
	 * @param WC_Order $order
	 * @param WPP_Payment_Gateway_Stripe $gateway
	 */
	public function set_stashed_cart( $order, $gateway ) {
		wpp_payment_restore_cart( WC()->cart );
	}

	/**
	 *
	 * @param array $data
	 * @param WP_Error $errors
	 */
	public function wpp_after_checkout_validation( $data, $errors ) {
		if ( $errors->get_error_codes() ) {
			wpp_payment_log_info( sprintf( __CLASS__ . '::checkout errors: %s', print_r( $errors->get_error_codes(), true ) ) );
			wc_add_notice(
				apply_filters(
					'wpp_after_checkout_validation_notice', __( 'Please check your order details then click Place Order.', 'wc-stripe-payments' ),
					$data,
					$errors
				),
				'notice'
			);
			wp_send_json(
				array(
					'result'   => 'success',
					'redirect' => $this->get_order_review_url(),
					'reload'   => false,
				),
				200
			);
		}
	}

	private function required_post_data() {
		if ( WC()->cart->needs_shipping() ) {
			$_POST['ship_to_different_address'] = true;
		}
		if ( wc_get_page_id( 'terms' ) > 0 ) {
			$_POST['terms'] = 1;
		}
	}

	private function get_order_review_url() {
		return add_query_arg(
			array(
				'_stripe_order_review' => rawurlencode( base64_encode( wp_json_encode( array(
					'payment_method' => $this->gateway->id,
					'payment_nonce'  => $this->gateway->get_payment_source(),
				) ) ) ),
			),
			wc_get_checkout_url()
		);
	}

	/**
	 *
	 * @param int $order_id
	 * @param array $posted_data
	 * @param WC_Order $order
	 */
	public function checkout_order_processed( $order_id, $posted_data, $order ) {
		if ( $this->order_review ) {
			wc_add_notice( __( 'Please check your order details then click Place Order.', 'wc-stripe-payments' ), 'notice' );
			wp_send_json(
				array(
					'result'   => 'success',
					'redirect' => $this->get_order_review_url(),
				),
				200
			);
		}
	}

	public function post_payment_processes( $order, $gateway ) {
		wpp_payment_restore_cart( WC()->cart );
		$data = WC()->session->get( 'wpp_payment_cart', array() );
		unset( $data['product_cart'] );
		WC()->session->set( 'wpp_payment_cart', $data );
	}

	/**
	 * @param $data
	 *
	 * @return mixed
	 */
	public function filter_posted_data( $data ) {
		if ( isset( $data['shipping_method'], $data['shipping_country'], $data['shipping_state'] ) ) {
			$data['shipping_state'] = wpp_payment_filter_address_state( $data['shipping_state'], $data['shipping_country'] );
		}

		return $data;
	}
}
