<?php
defined( 'ABSPATH' ) || exit();

/**
 * Class WPP_Shortcodes
 */
class WPP_Shortcodes {

	public static function init() {
		$shortcodes = array(
			'wpp_payment_payment_buttons' => array( 'WPP_Shortcodes', 'payment_buttons' ),
		);

		foreach ( $shortcodes as $key => $function ) {
			add_shortcode( $key, apply_filters( 'wpp_payment_shortcode_function', $function ) );
		}
	}

	/**
	 * @param $atts
	 *
	 * @return string
	 */
	public static function payment_buttons( $atts ) {
		$method  = '';
		$wrapper = array(
			'class' => 'wpp-payment-shortcode'
		);
		if ( is_product() ) {
			$method           = 'output_product_buttons';
			$wrapper['class'] = $wrapper['class'] . ' wpp-payment-shortcode-product-buttons';
		} else if ( ! is_null( WC()->cart ) && ( is_cart() || ( isset( $atts['page'] ) && 'cart' === $atts['page'] ) ) ) {
			$method           = 'output_cart_buttons';
			$wrapper['class'] = $wrapper['class'] . ' wpp-payment-shortcode-cart-buttons';
		}
		if ( ! $method ) {
			return '';
		}
		include_once stripe_wpp()->plugin_path() . 'includes/shortcodes/class-wpp-shortcode-payment-buttons.php';

		return WC_Shortcodes::shortcode_wrapper( array( 'WPP_Shortcode_Payment_Buttons', $method ), $atts, $wrapper );
	}
}

WPP_Shortcodes::init();