<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @package Stripe/Classes
 * @author WpPayments
 *
 */
class WPP_Payment_Field_Manager {

	private static $cart_priority = 30;

	private static $stripe_btn_pos;

	public static $minicart_count = 0;

	public static function init() {
		add_action( 'woocommerce_checkout_before_customer_details', array( __CLASS__, 'output_banner_checkout_fields' ) );
		add_action( 'woocommerce_before_add_to_cart_form', array( __CLASS__, 'before_add_to_cart' ) );
		add_action( 'init', array( __CLASS__, 'init_action' ) );
		add_action( 'woocommerce_review_order_after_order_total', array( __CLASS__, 'output_checkout_fields' ) );
		add_action( 'before_woocommerce_add_payment_method', array( __CLASS__, 'add_payment_method_fields' ) );
		add_action( 'woocommerce_widget_shopping_cart_buttons', array( __CLASS__, 'mini_cart_buttons' ), 5 );
	}

	public static function init_action() {
		self::$cart_priority = apply_filters( 'wpp_payment_cart_buttons_order', 30 );
		add_action( 'woocommerce_proceed_to_checkout', array( __CLASS__, 'output_cart_fields' ), self::$cart_priority );
	}

	public static function output_banner_checkout_fields() {
		$gateways = array();
		foreach ( WC()->payment_gateways()->get_available_payment_gateways() as $gateway ) {
			if ( $gateway->supports( 'wpp_payment_banner_checkout' ) && $gateway->banner_checkout_enabled() ) {
				$gateways[ $gateway->id ] = $gateway;
			}
		}
		if ( $gateways ) {
			wpp_payment_template( 'checkout/checkout-banner.php', array( 'gateways' => $gateways ) );
		}
	}

	public static function output_checkout_fields() {
		if ( WC()->cart && wpp_is_subscription_active() && WC_Subscriptions_Cart::cart_contains_subscription() ) {
			wp_add_inline_script( 'wc-checkout', 'var wpp_payment_cart_contains_subscription = true;' );
		}
		if ( WC()->cart && wpp_payment_pre_orders_active() && WC_Pre_Orders_Cart::cart_contains_pre_order() && WC_Pre_Orders_Product::product_is_charged_upon_release( WC_Pre_Orders_Cart::get_pre_order_product() ) ) {
			wp_add_inline_script( 'wc-checkout', 'var wpp_payment_preorder_exists = true;' );
		}
		do_action( 'wpp_payment_output_checkout_fields' );
	}

	public static function before_add_to_cart() {
		global $product;
		self::$stripe_btn_pos = $product->get_meta( 'wpp_payment_btn_position' );
		if ( empty( self::$stripe_btn_pos ) ) {
			self::$stripe_btn_pos = 'bottom';
		}

		if ( 'bottom' == self::$stripe_btn_pos ) {
			$action = 'woocommerce_after_add_to_cart_button';
		} else {
			$action = 'woocommerce_before_add_to_cart_button';
		}
		add_action( $action, array( __CLASS__, 'output_product_checkout_fields' ) );
	}

	public static function output_product_checkout_fields() {
		global $product;
		$gateways        = array();
		$ordering        = $product->get_meta( 'wpp_payment_gateway_order' );
		$ordering        = ! $ordering ? array() : $ordering;
		$is_subscription = wpp_is_subscription_active() && WC_Subscriptions_Product::is_subscription( $product );
		$is_preorder     = wpp_payment_pre_orders_active() && WC_Pre_Orders_Product::product_is_charged_upon_release( $product );

		foreach ( WC()->payment_gateways()->get_available_payment_gateways() as $id => $gateway ) {
			/**
			 *
			 * @var WPP_Payment_Gateway_Stripe $gateway
			 */
			if ( $gateway->supports( 'wp_payment_product_checkout' ) && ! $product->is_type( 'external' ) ) {
				if ( ( $is_subscription && ! $gateway->supports( 'subscriptions' ) ) || ( $is_preorder && ! $gateway->supports( 'pre-orders' ) ) ) {
					continue;
				}
				$option = new WPP_Payment_Product_Gateway_Option( $product, $gateway );
				if ( $option->enabled() ) {
					if ( isset( $ordering[ $gateway->id ] ) ) {
						$gateways[ $ordering[ $gateway->id ] ] = $gateway;
					} else {
						$gateways[] = $gateway;
					}
				}
			}
		}
		ksort( $gateways );

		if ( count( apply_filters( 'wpp_payment_product_payment_methods', $gateways, $product ) ) > 0 ) {
			wpp_payment_template(
				'product/payment-methods.php',
				array(
					'position' => self::$stripe_btn_pos,
					'gateways' => $gateways
				)
			);
		}
	}

	public static function output_cart_fields() {
		$gateways = array();
		foreach ( WC()->payment_gateways()->get_available_payment_gateways() as $id => $gateway ) {
			/**
			 *
			 * @var WPP_Payment_Gateway_Stripe $gateway
			 */
			if ( $gateway->supports( 'wp_payment_cart_checkout' ) && $gateway->cart_checkout_enabled() ) {
				$gateways[ $gateway->id ] = $gateway;
			}
		}
		if ( count( apply_filters( 'wpp_payment_cart_payment_methods', $gateways ) ) > 0 ) {
			wpp_payment_template(
				'cart/payment-methods.php',
				array(
					'gateways'   => $gateways,
					'after'      => self::$cart_priority > 20,
					'cart_total' => WC()->cart->total,
				)
			);
		}
	}

	public static function mini_cart_buttons() {
		$gateways = array();
		foreach ( WC()->payment_gateways()->get_available_payment_gateways() as $id => $gateway ) {
			/**
			 *
			 * @var WPP_Payment_Gateway_Stripe $gateway
			 */
			if ( $gateway->supports( 'wp_payment_mini_cart_checkout' ) && $gateway->mini_cart_enabled() ) {
				$gateways[ $gateway->id ] = $gateway;
			}
		}
		if ( count( apply_filters( 'wpp_payment_mini_cart_payment_methods', $gateways ) ) > 0 ) {

			wpp_payment_template(
				'mini-cart/payment-methods.php',
				array(
					'gateways' => $gateways
				)
			);
		}
	}

	/**
	 * @deprecated 3.1.8
	 */
	public static function change_payment_request() {

	}

	public static function add_payment_method_fields() {
		wpp_payment_hidden_field( 'billing_first_name', '', WC()->customer->get_first_name() );
		wpp_payment_hidden_field( 'billing_last_name', '', WC()->customer->get_last_name() );
	}

	/**
	 * @deprecated 3.1.8
	 */
	public static function pay_order_fields() {
		global $wp;
		$order = wc_get_order( absint( $wp->query_vars['order-pay'] ) );
		self::output_required_fields( 'checkout', $order );
	}

	/**
	 * @param string $page
	 * @param WC_Order $order
	 */
	public static function output_required_fields( $page, $order = null ) {
		if ( in_array( $page, array( 'cart', 'checkout' ) ) ) {

			if ( 'cart' === $page ) {
				self::output_fields( 'billing' );

				if ( WC()->cart->needs_shipping() ) {
					self::output_fields( 'shipping' );
				}
			}

		} elseif ( 'product' === $page ) {
			global $product;

			self::output_fields( 'billing' );

			if ( $product->needs_shipping() ) {
				self::output_fields( 'shipping' );
			}
		}
	}

	public static function output_fields( $prefix ) {
		$fields = WC()->checkout()->get_checkout_fields( $prefix );
		foreach ( $fields as $key => $field ) {
			printf( '<input type="hidden" id="%1$s" name="%1$s" value="%2$s"/>', $key, WC()->checkout()->get_value( $key ) );
		}
	}

	/**
	 * @param bool $needs_shipping
	 *
	 * @deprecated
	 */
	public static function output_needs_shipping( $needs_shipping ) {

	}
}

if ( ! is_admin() ) {
	WPP_Payment_Field_Manager::init();
}
