<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @package Stripe/Admin
 * @author User
 *
 */
class WPP_Admin_Settings {

	public static function init() {
		add_action( 'woocommerce_settings_checkout', array( __CLASS__, 'output' ) );
		add_action( 'woocommerce_update_options_checkout', array( __CLASS__, 'save' ) );
	}

	public static function output() {
		global $current_section;
		do_action( 'wpp_stripe_settings_checkout_' . $current_section );
	}

	/**
	 * @deprecated
	 */
	public static function output_advanced_settings() {
		self::output_custom_section( '' );
	}

	/**
	 * @deprecated
	 */
	public static function output_custom_section( $sub_section = '' ) {
		global $current_section, $wpp_payment_subsection;
		$wpp_payment_subsection = isset( $_GET['stripe_sub_section'] ) ? sanitize_title( $_GET['stripe_sub_section'] ) : $sub_section;
		do_action( 'wpp_stripe_settings_checkout_' . $current_section . '_' . $wpp_payment_subsection );
	}


	/**
	 * @deprecated
	 */
	public static function save_custom_section( $sub_section = '' ) {
		global $current_section, $wpp_payment_subsection;
		$wpp_payment_subsection = isset( $_GET['stripe_sub_section'] ) ? sanitize_title( $_GET['stripe_sub_section'] ) : $sub_section;
		do_action( 'woocommerce_update_options_checkout_' . $current_section . '_' . $wpp_payment_subsection );
	}

	public static function save() {
		global $current_section;
		if ( $current_section && ! did_action( 'woocommerce_update_options_checkout_' . $current_section ) ) {
			do_action( 'woocommerce_update_options_checkout_' . $current_section );
		}
	}

	/**
	 * @deprecated
	 */
	public static function before_options() {
		global $current_section, $wpp_payment_subsection;
		do_action( 'wpp_payment_settings_before_options_' . $current_section . '_' . $wpp_payment_subsection );
	}

	/**
	 * @param        $settings
	 * @param string $section_id
	 *
	 * @return mixed
	 * @deprecated
	 */
	public static function get_email_settings( $settings, $section_id = '' ) {
		if ( ! $section_id ) {
			$settings[] = array(
				'type'  => 'title',
				'title' => __( 'Stripe Email Options', 'wc-stripe-payments' ),
			);
			$settings[] = array(
				'type'     => 'checkbox',
				'title'    => __( 'Email Receipt', 'wc-stripe-payments' ),
				'id'       => 'woocommerce_wpp_email_receipt',
				'autoload' => false,
				'desc'     => __( 'If enabled, an email receipt will be sent to the customer by Stripe when the order is processed.',
					'wc-stripe-payments' ),
			);
			$settings[] = array(
				'type' => 'sectionend',
				'id'   => 'stripe_email',
			);
		}

		return $settings;
	}

}

WPP_Admin_Settings::init();
