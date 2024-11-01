<?php

/**
 * Plugin Name: Payments via Stripe for WooCommerce
 * Plugin URI: https://wppayments.org/docs/introduction/
 * Description: Accept Credit Cards, Google Pay, Apple Pay, ACH and more using Stripe.
 * Version: 1.0
 * Author: websitelearners17, Contact@websitelearners.com
 * Text Domain: wc-stripe-payments
 * Domain Path: /i18n/languages/
 * Tested up to: 6.0
 * WC requires at least: 3.0.0
 * WC tested up to: 6.6
 */

defined( 'ABSPATH' ) || exit ();

define( 'WPP_PAYMENT_FILE_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPP_PAYMENT_ASSETS', plugin_dir_url( __FILE__ ) . 'assets/' );
define( 'WPP_PAYMENT_NAME', plugin_basename( __FILE__ ) );


add_action( 'admin_notices', 'render_missing_dependency' );

/**
 * The code will deactive the plugin if requirements doesn't match. 
 */
function deactivate_wpp_payments() {    
    deactivate_plugins( plugin_basename( __FILE__ ) );

}

/**
 * Missing dependency
 *
 *
 * @return void
 */
function render_missing_dependency() {
    
    if ( ( !class_exists( 'WooCommerce' ) || ! in_array( 'woocommerce/woocommerce.php', array_keys( get_plugins() ), true ) ) && current_user_can( 'activate_plugins' ) ) {
        include  WPP_PAYMENT_FILE_PATH . 'includes/admin/views/admin-dependency-notice.php';
        deactivate_wpp_payments();
    }
}

require_once( WPP_PAYMENT_FILE_PATH . 'vendor/autoload.php' );
// include main plugin file.
require_once( WPP_PAYMENT_FILE_PATH . 'includes/main.php' );

