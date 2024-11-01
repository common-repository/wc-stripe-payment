<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @package Stripe/Admin
 */
class WPP_Admin_Assets {

	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'wp_print_scripts', array( __CLASS__, 'localize_scripts' ) );

		add_action( 'admin_footer', array( __CLASS__, 'localize_scripts' ) );
		add_action( 'wpp_payment_localize_wpp_advanced_settings', array( __CLASS__, 'localize_advanced_scripts' ) );
	}

	public function enqueue_scripts() {
		global $current_section, $wpp_payment_subsection;
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$js_path   = stripe_wpp()->assets_url() . 'js/';
		$css_path  = stripe_wpp()->assets_url() . 'css/';

		wp_register_script( 'wpp-admin-settings', $js_path . 'admin/admin-settings.js', array(
			'jquery',
			'jquery-blockui'
		), stripe_wpp()->version, true );
		wp_register_script( 'wpp-meta-boxes-order', $js_path . 'admin/meta-boxes-order.js', array(
			'jquery',
			'jquery-blockui'
		), stripe_wpp()->version, true );
		wp_register_script( 'wpp-meta-boxes-subscription', $js_path . 'admin/meta-boxes-subscription.js', array(
			'jquery',
			'jquery-blockui'
		), stripe_wpp()->version, true );
		wp_register_script(
			'wpp-product-data',
			$js_path . 'admin/meta-boxes-product-data.js',
			array(
				'jquery',
				'jquery-blockui',
				'jquery-ui-sortable',
				'jquery-ui-widget',
				'jquery-ui-core',
				'jquery-tiptip',
			),
			stripe_wpp()->version(),
			true
		);
		wp_register_style( 'wpp-payment-css', $css_path . 'admin/admin.css', array(), stripe_wpp()->version );

		if ( strpos( $screen_id, 'wc-settings' ) !== false ) {
			if ( ( isset( $_REQUEST['section'] ) && preg_match( '/wpp_[\w]*/', $_REQUEST['section'] ) )  || ( isset($_REQUEST['page']) && $_REQUEST['page'] == 'wps-info')  ) {
				wp_enqueue_script( 'wpp-admin-settings' );
				wp_enqueue_style( 'wpp-payment-css' );
				wp_style_add_data( 'wpp-payment-css', 'rtl', 'replace' );
				//wp_enqueue_script( 'stripe-help-widget', $js_path . 'admin/help-widget.js', array(), stripe_wpp()->version(), true );
				wp_localize_script(
					'wpp-admin-settings',
					'wpp_payment_params',
					array(
						'routes'     => array(
							'apple_domain'    => WPP_Rest_API::get_admin_endpoint( stripe_wpp()->rest_api->settings->rest_uri( 'apple-domain' ) ),
							'create_webhook'  => WPP_Rest_API::get_admin_endpoint( stripe_wpp()->rest_api->settings->rest_uri( 'create-webhook' ) ),
							'delete_webhook'  => WPP_Rest_API::get_admin_endpoint( stripe_wpp()->rest_api->settings->rest_uri( 'delete-webhook' ) ),
							'connection_test' => WPP_Rest_API::get_admin_endpoint( stripe_wpp()->rest_api->settings->rest_uri( 'connection-test' ) ),
						),
						'rest_nonce' => wp_create_nonce( 'wp_rest' ),
					)
				);
			}
		}
		if ( $screen_id === 'shop_order' ) {
			wp_enqueue_style( 'wpp-payment-css' );
		}
		if ( $screen_id === 'product' ) {
			wp_enqueue_script( 'wpp-product-data' );
			wp_enqueue_style( 'wpp-payment-css' );
			wp_localize_script(
				'wpp-product-data',
				'wpp_product_params',
				array(
					'_wpnonce' => wp_create_nonce( 'wp_rest' ),
					'routes'   => array(
						'enable_gateway' => stripe_wpp()->rest_api->product_data->rest_url( 'gateway' ),
						'save'           => stripe_wpp()->rest_api->product_data->rest_url( 'save' ),
					),
				)
			);
		}

		if( isset($_REQUEST['page']) && $_REQUEST['page'] == 'wps-info' ){
			wp_enqueue_style( 'wpp-payment-css' );
		}
	}

	public static function localize_scripts() {
		global $current_section, $wpp_payment_subsection;
		if ( ! empty( $current_section ) ) {
			$wpp_payment_subsection = isset( $_GET['sub_section'] ) ? sanitize_title( $_GET['sub_section'] ) : '';
			do_action( 'wpp_payment_localize_' . $current_section . '_settings' );
			// added for WC 3.0.0 compatability.
			remove_action( 'admin_footer', array( __CLASS__, 'localize_scripts' ) );
		}
	}

	public static function localize_advanced_scripts() {
		global $current_section, $wpp_payment_subsection;
		do_action( 'wpp_payment_localize_' . $wpp_payment_subsection . '_settings' );
	}

}

new WPP_Admin_Assets();
