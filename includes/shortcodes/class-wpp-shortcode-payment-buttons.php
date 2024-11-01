<?php
defined( 'ABSPATH' ) || exit();

/**
 * Class WPP_Shortcode_Payment_Buttons
 */
class WPP_Shortcode_Payment_Buttons {

	public static function output_product_buttons( $atts ) {
		WPP_Payment_Field_Manager::output_product_checkout_fields();
	}

	public static function output_cart_buttons( $atts ) {
		WPP_Payment_Field_Manager::output_cart_fields();
	}
}