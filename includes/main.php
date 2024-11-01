<?php

defined( 'ABSPATH' ) || exit();

/**
 * Singleton class that handles plugin functionality like class loading.
 *
 * @author WpPayments
 * @package Stripe/Classes
 *
 */
class WPP_Manager {

	public static $_instance;

	public static function instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 *
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 *
	 * @var WPP_Settings_API
	 */
	public $api_settings;

	/**
	 * @var WPP_Settings_API
	 */
	public $account_settings;

	/**
	 * @var \WPP_Advanced_Settings
	 */
	public $advanced_settings;

	/**
	 *
	 * @var WPP_Rest_API
	 */
	public $rest_api;

	/**
	 *
	 * @var string
	 */
	public $client_id = 'ca_KeuVkPfHbwmvr3pjh3qafD7avrDeB8Si';

	/**
	 * Test client id;
	 *
	 * @var string
	 */
	//public $client_id = 'ca_KeuVMDFZMfTpQ3xaUccMW7Z2wxo5Ltj2';

	/**
	 *
	 * @var WPP_Payment_Frontend_Scripts
	 */
	private $scripts;

	/**
	 *
	 * @var array
	 */
	private $payment_gateways;

	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 10 );
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'woocommerce_init', array( $this, 'woocommerce_dependencies' ) );
		add_action( 'woocommerce_blocks_loaded', array( '\PaymentWps\Blocks\Stripe\Package', 'init' ) );
		$this->includes();
	}

	/**
	 * Return the plugin version.
	 *
	 * @return string
	 */
	public function version() {
		return $this->version;
	}

	/**
	 * Return the url for the plugin assets.
	 *
	 * @return string
	 */
	public function assets_url( $uri = '' ) {
		$url = WPP_PAYMENT_ASSETS . $uri;
		if ( ! preg_match( '/(\.js)|(\.css)|(\.svg)|(\.png)/', $uri ) ) {
			return trailingslashit( $url );
		}

		return $url;
	}

	/**
	 * Return the dir path for the plugin.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return WPP_PAYMENT_FILE_PATH;
	}

	public function plugins_loaded() {
		load_plugin_textdomain( 'wc-stripe-payments', false, dirname( WPP_PAYMENT_NAME ) . '/i18n/languages' );

		/**
		 * Version 4.5.4 of the WooCommerce Stripe Gateway plugin also includes a function named wc_stripe so don't include if that plugin
		 * is installed to prevent conflicts.
		 */
		if ( ! function_exists( 'wc_stripe' ) ) {
			if ( ( defined( 'WC_STRIPE_VERSION' ) && version_compare( WC_STRIPE_VERSION, '4.5.4', '<' ) )
			     || ! in_array( 'woocommerce-gateway-stripe/woocommerce-gateway-stripe.php',
					(array) get_option( 'active_plugins', array() ),
					true )
			        && ! ( is_admin() && ! isset( $_GET['activate'], $_GET['plugin'] ) ) ) {
				/**
				 * Returns the global instance of the WPP_Manager.
				 *
				 * @return WPP_Manager
				 * @deprecated 3.2.8
				 * @package    Stripe/Functions
				 */
				function wc_stripe() {
					if ( function_exists( 'wc_deprecated_function' ) ) {
						wc_deprecated_function( 'wc_stripe', '3.2.8', 'stripe_wpp' );
					}

					return stripe_wpp();
				}
			}
		}

		\PaymentWps\CartFlows\Stripe\Main::init();
		\PaymentWps\WooFunnels\Stripe\Main::init();
	}

	/**
	 * Function that is hooked in to the WordPress init action.
	 */
	public function init() {
	}

	public function includes() {
		
		include_once WPP_PAYMENT_FILE_PATH . 'includes/class-wpp-install.php';
		//include_once WPP_PAYMENT_FILE_PATH . 'includes/class-wpp-update.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/class-wpp-rest-api.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/class-wpp-gateway.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/class-wpp-payment-balance.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/class-wpp-utils.php';

		if ( is_admin() ) {
			include_once WPP_PAYMENT_FILE_PATH . 'includes/admin/class-wpp-admin-menus.php';
			include_once WPP_PAYMENT_FILE_PATH . 'includes/admin/class-wpp-admin-assets.php';
			include_once WPP_PAYMENT_FILE_PATH . 'includes/admin/class-wpp-admin-settings.php';
			include_once WPP_PAYMENT_FILE_PATH . 'includes/admin/meta-boxes/class-wpp-admin-order-metaboxes.php';
			include_once WPP_PAYMENT_FILE_PATH . 'includes/admin/meta-boxes/class-wpp-admin-meta-box-product-data.php';
		}
	}

	/**
	 * Function that is hooked in to the WordPress admin_init action.
	 */
	public function admin_init() {
	}

	public function woocommerce_dependencies() {
		// load functions
		include_once WPP_PAYMENT_FILE_PATH . 'includes/wpp-functions.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/wpp-webhook-functions.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/wpp-hooks.php';

		// traits
		include_once WPP_PAYMENT_FILE_PATH . 'includes/traits/wpp-settings-trait.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/traits/wpp-controller-traits.php';

		// load factories
		include_once WPP_PAYMENT_FILE_PATH . 'includes/class-wpp-payment-factory.php';

		// load gateways
		include_once WPP_PAYMENT_FILE_PATH . 'includes/abstract/abstract-wpp-payment-gateway-stripe.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/gateways/class-wpp-payment-gateway-stripe-cc.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/gateways/class-wpp-payment-gateway-stripe-applepay.php';
		//include_once WPP_PAYMENT_FILE_PATH . 'includes/gateways/class-wpp-payment-gateway-stripe-googlepay.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/gateways/class-wpp-payment-gateway-stripe-ach.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/gateways/class-wpp-payment-gateway-stripe-payment-request.php';

		// tokens
		include_once WPP_PAYMENT_FILE_PATH . 'includes/abstract/abstract-wpp-token-stripe.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/tokens/class-wpp-payment-token-stripe-cc.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/tokens/class-wpp-payment-token-stripe-applepay.php';
		//include_once WPP_PAYMENT_FILE_PATH . 'includes/tokens/class-wpp-payment-token-stripe-googlepay.php';

		// main classes
		include_once WPP_PAYMENT_FILE_PATH . 'includes/class-wpp-frontend-scripts.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/class-wpp-field-manager.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/class-wpp-rest-api.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/class-wpp-customer-manager.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/class-wpp-gateway-conversions.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/class-wpp-redirect-handler.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/class-wpp-product-gateway-option.php';

		// settings
		include_once WPP_PAYMENT_FILE_PATH . 'includes/abstract/abstract-wpp-settings.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/admin/settings/class-wpp-api-settings.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/admin/settings/class-wpp-advanced-settings.php';
		include_once WPP_PAYMENT_FILE_PATH . 'includes/admin/settings/class-wpp-account-settings.php';

		include_once WPP_PAYMENT_FILE_PATH . 'includes/class-wpp-api-request-filter.php';

		// shortcodes
		include_once WPP_PAYMENT_FILE_PATH . 'includes/class-wpp-shortcodes.php';

		if ( is_admin() ) {
			include_once WPP_PAYMENT_FILE_PATH . 'includes/admin/class-wpp-admin-notices.php';
			include_once WPP_PAYMENT_FILE_PATH . 'includes/admin/class-wpp-admin-user-edit.php';
		}

		$this->payment_gateways = apply_filters(
			'wpp_payment_payment_gateways',
			array(
				'WPP_Gateway_Stripe_CC',
				'WPP_Gateway_Stripe_ApplePay',
				'WPP_Gateway_Stripe_GooglePay',
				'WPP_Gateway_Stripe_Payment_Request',
				'WPP_Gateway_Stripe_ACH',
			)
		);

		$api_class      = apply_filters( 'wpp_rest_api_class', 'WPP_Rest_API' );
		$this->rest_api = new $api_class();

		if ( $this->is_request( 'frontend' ) && class_exists( 'WPP_Payment_Frontend_Scripts' ) ) {
			$this->scripts = new WPP_Payment_Frontend_Scripts();
		}

		// allow other plugins to provide their own settings classes.
		$setting_classes = apply_filters( 'wpp_setting_classes', array(
			'api_settings'      => 'WPP_API_Settings',
			'account_settings'  => 'WPP_Account_Settings',
			'advanced_settings' => 'WPP_Advanced_Settings'
		) );
		foreach ( $setting_classes as $id => $class_name ) {
			if ( class_exists( $class_name ) ) {
				$this->{$id} = new $class_name();
			}
		}

		new WPP_Payment_API_Request_Filter( $this->advanced_settings );
	}

	/**
	 * Return the plugin template path.
	 */
	public function template_path() {
		return 'wc-stripe-payments';
	}

	/**
	 * Return the plguins default directory path for template files.
	 */
	public function default_template_path() {
		return WPP_PAYMENT_FILE_PATH . 'templates/';
	}

	/**
	 *
	 * @return string
	 */
	public function rest_uri() {
		return 'wpp-stripe/v1/';
	}

	/**
	 *
	 * @return string
	 */
	public function rest_url() {
		return get_rest_url( null, $this->rest_uri() );
	}

	/**
	 *
	 * @return WPP_Payment_Frontend_Scripts
	 */
	public function scripts() {
		if ( is_null( $this->scripts ) ) {
			$this->scripts = new WPP_Payment_Frontend_Scripts();
		}

		return $this->scripts;
	}

	public function payment_gateways() {
		return $this->payment_gateways;
	}

	/**
	 * Schedule actions required by the plugin
	 *
	 */
	public function scheduled_actions() {
		if ( function_exists( 'WC' ) ) {
			if ( method_exists( WC(), 'queue' ) && ! WC()->queue()->get_next( 'wpp_payment_remove_order_locks' ) ) {
				WC()->queue()->schedule_recurring( strtotime( 'today midnight' ), DAY_IN_SECONDS, 'wpp_payment_remove_order_locks' );
			}
		}
	}

	/**
	 * @param string $type
	 *
	 * @return bool
	 */
	public function is_request( $type ) {
		if ( ! did_action( 'before_woocommerce_init' ) ) {
			return false;
		}
		switch ( $type ) {
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) && ! WPP_Rest_API::is_wp_rest_request();
			default:
				return true;
		}
	}

}

/**
 * Returns the global instance of the WPP_Manager. This function replaces
 * the wc_stripe function as of version 3.2.8
 *
 * @return WPP_Manager
 * @package Stripe/Functions
 */
function stripe_wpp() {
	return WPP_Manager::instance();
}


// load singleton
stripe_wpp();
