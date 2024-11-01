<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @package Stripe/Classes
 *
 */
class WPP_Install {

	public static function init() {
		add_filter( 'plugin_action_links_' . WPP_PAYMENT_NAME, array( __CLASS__, 'plugin_page_setting_links' ) );
		register_activation_hook( WPP_PAYMENT_NAME, array( __CLASS__, 'install' ) );
	}

	public static function install() {
		update_option( 'wpp_stripe_version', stripe_wpp()->version() );

		/**
		 * Schedule required actions. Actions are scheduled during install as they only need to be setup
		 * once.
		 */
		stripe_wpp()->scheduled_actions();
	}

	/**
	 *
	 * @param array $links
	 */
	public static function plugin_page_setting_links( $links ) {
		$action_links = array(
			'settings' => sprintf( '<a href="%1$s">%2$s</a>', admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wpp_api' ), esc_html__( 'Settings', 'wc-stripe-payments' ) ),
			'docs'     => sprintf( '<a target="_blank" href="https://wppayments.org/docs/">%s</a>', __( 'Documentation', 'wc-stripe-payments' ) ),
		);

		return array_merge( $action_links, $links );
	}
}

WPP_Install::init();
