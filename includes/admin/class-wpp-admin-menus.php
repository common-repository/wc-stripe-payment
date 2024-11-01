<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @package Stripe/Admin
 *
 */
class WPP_Admin_Menus {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 10 );
		add_action( 'admin_menu', array( __CLASS__, 'sub_menu' ), 20 );
		add_action( 'admin_menu', array( __CLASS__, 'remove_submenu' ), 30 );
	}

	public static function admin_menu() {
		add_menu_page( __( 'Wp Payments', 'wc-stripe-payments' ), __( 'Wp Payments', 'wc-stripe-payments' ), 'manage_woocommerce', 'stripe_wpp', null, WPP_PAYMENT_ASSETS.'img/favicon.png', '10' );
	}

	public static function sub_menu() {
		add_submenu_page( 'stripe_wpp', __( 'Settings', 'wc-stripe-payments' ), __( 'Settings', 'wc-stripe-payments' ), 'manage_woocommerce', 'admin.php?page=wc-settings&tab=checkout&section=wpp_api' );
		add_submenu_page( 'stripe_wpp', __( 'Logs', 'wc-stripe-payments' ), __( 'Logs', 'wc-stripe-payments' ), 'manage_woocommerce', 'admin.php?page=wc-status&tab=logs' );
		add_submenu_page( 'stripe_wpp', __( 'Company Info', 'wc-stripe-payments' ), __( 'Company Info', 'wc-stripe-payments' ), 'manage_options', 'wps-info',array( __CLASS__, 'wps_company_info' ) );
		add_submenu_page( 'stripe_wpp', __( 'Documentation', 'wc-stripe-payments' ), __( 'Documentation', 'wc-stripe-payments' ), 'manage_woocommerce', 'https://wppayments.org/docs/' );
	}

	public static function remove_submenu() {
		global $submenu;
		if ( isset( $submenu['stripe_wpp'] ) ) {
			unset( $submenu['stripe_wpp'][0] );
		}
	}

	public static function data_migration_page() {
		include 'views/html-data-migration.php';
	}

	public static function wps_company_info(){
		include 'views/html-company-info.php';
	}
}

WPP_Admin_Menus::init();
