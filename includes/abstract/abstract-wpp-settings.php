<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @author WpPayments
 * @package Stripe/Abstract
 *
 */
abstract class WPP_Settings_API extends WC_Settings_API {

	use WPP_Settings_Trait;

	public function __construct() {
		$this->init_form_fields();
		$this->init_settings();
		$this->hooks();
	}

	public function hooks() {
		add_action( 'wpp_payment_localize_' . $this->id . '_settings', array( $this, 'localize_settings' ) );
	}

	public function localize_settings() {
		return $this->settings;
	}
}
