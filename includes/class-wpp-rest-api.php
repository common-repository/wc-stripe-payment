<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @author WpPayments
 * @package Stripe/Classes
 * @property WPP_Rest_Controller $order_actions
 * @property WPP_Rest_Controller $cart
 * @property WPP_Rest_Controller $checkout
 * @property WPP_Rest_Controller $payment_intent
 * @property WPP_Rest_Controller $googlepay
 * @property WPP_Rest_Controller $settings
 * @property WPP_Rest_Controller $webhook
 * @property WPP_Rest_Controller $product_data
 * @property WPP_Rest_Controller $plaid
 * @property WPP_Rest_Controller $source
 */
class WPP_Rest_API {

	/**
	 *
	 * @var array
	 */
	private $controllers = array();

	public function __construct() {
		$this->include_classes();
		add_action( 'wc_ajax_wpp_api_frontend_request', array( $this, 'process_frontend_request' ) );
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'wp_ajax_wpp_api_admin_request', array( $this, 'process_frontend_request' ) );
	}

	/**
	 *
	 * @param WPP_Rest_Controller $key
	 */
	public function __get( $key ) {
		$controller = isset( $this->controllers[ $key ] ) ? $this->controllers[ $key ] : '';
		if ( empty( $controller ) ) {
			wc_doing_it_wrong( __FUNCTION__, sprintf( __( '%1$s is an invalid controller name.', 'wc-stripe-payments' ), $key ),
				stripe_wpp()->version );
		}

		return $controller;
	}

	public function __set( $key, $value ) {
		$this->controllers[ $key ] = $value;
	}

	private function include_classes() {
		include_once WPP_PAYMENT_FILE_PATH . 'includes/abstract/abstract-wpp-rest-controller.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/controllers/class-wpp-controller-order-actions.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/controllers/class-wpp-controller-payment-intent.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/controllers/class-wpp-controller-cart.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/controllers/class-wpp-controller-checkout.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/controllers/class-wpp-controller-googlepay.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/controllers/class-wpp-controller-payment-method.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/controllers/class-wpp-controller-gateway-settings.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/controllers/class-wpp-controller-webhook.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/controllers/class-wpp-controller-product-data.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/controllers/class-wpp-controller-plaid.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/controllers/class-wpp-controller-source.php';

		foreach ( $this->get_controllers() as $key => $class_name ) {
			if ( class_exists( $class_name ) ) {
				$this->{$key} = new $class_name();
			}
		}
	}

	public function register_routes() {
		if ( self::is_rest_api_request() ) {
			foreach ( $this->controllers as $key => $controller ) {
				if ( is_callable( array( $controller, 'register_routes' ) ) ) {
					$controller->register_routes();
				}
			}
		}
	}

	public function get_controllers() {
		$controllers = array(
			'order_actions'  => 'WPP_Controller_Order_Actions',
			'checkout'       => 'WPP_Controller_Checkout',
			'cart'           => 'WPP_Controller_Cart',
			'payment_intent' => 'WPP_Controller_Payment_Intent',
			'googlepay'      => 'WPP_Controller_GooglePay',
			'payment_method' => 'WPP_Controller_Payment_Method',
			'settings'       => 'WPP_Controller_Gateway_Settings',
			'webhook'        => 'WPP_Controller_Webhook',
			'product_data'   => 'WPP_Controller_Product_Data',
			'plaid'          => 'WPP_Controller_Plaid',
			'source'         => 'WPP_Controller_Source'
		);

		/**
		 * @param string[] $controllers
		 */
		return apply_filters( 'wpp_api_controllers', $controllers );
	}

	/**
	 * @return string
	 */
	public function rest_url() {
		return stripe_wpp()->rest_url();
	}

	/**
	 * @return string
	 */
	public function rest_uri() {
		return stripe_wpp()->rest_uri();
	}

	/**
	 * @return bool
	 */
	public static function is_rest_api_request() {
		global $wp;
		if ( ! empty( $wp->query_vars['rest_route'] ) && strpos( $wp->query_vars['rest_route'], stripe_wpp()->rest_uri() ) !== false ) {
			return true;
		}
		if ( ! empty( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], stripe_wpp()->rest_uri() ) !== false ) {
			return true;
		}

		return false;
	}

	/**
	 * Return true if this is a WP rest request. This function is a wrapper for WC()->is_rest_api_request()
	 * if it exists.
	 * @return bool
	 */
	public static function is_wp_rest_request() {
		if ( function_exists( 'WC' ) && property_exists( WC(), 'is_rest_api_request' ) ) {
			return WC()->is_rest_api_request();
		}

		return ! empty( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], trailingslashit( rest_get_url_prefix() ) ) !== false;
	}

	/**
	 */
	public function process_frontend_request() {
		if ( isset( $_GET['path'] ) ) {
			global $wp;
			$wp->set_query_var( 'rest_route', sanitize_text_field( $_GET['path'] ) );
			rest_api_loaded();
		}
	}

	/**
	 * Return an endpoint for ajax requests that integrate with the WP Rest API.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public static function get_endpoint( $path ) {
		if ( version_compare( WC()->version, '3.2.0', '<' ) ) {
			$endpoint = esc_url_raw( apply_filters( 'woocommerce_ajax_get_endpoint',
				add_query_arg( 'wc-ajax', 'wpp_api_frontend_request', remove_query_arg( array(
					'remove_item',
					'add-to-cart',
					'added-to-cart',
					'order_again',
					'_wpnonce'
				), home_url( '/', 'relative' ) ) ), 'wpp_api_frontend_request' ) );
		} else {
			$endpoint = WC_AJAX::get_endpoint( 'wpp_api_frontend_request' );
		}

		return add_query_arg( 'path', '/' . trim( $path, '/' ), $endpoint );
	}

	public static function get_admin_endpoint( $path ) {
		$url = admin_url( 'admin-ajax.php' );

		return add_query_arg( array( 'action' => 'wpp_api_admin_request', 'path' => '/' . trim( $path, '/' ) ), $url );
	}
}