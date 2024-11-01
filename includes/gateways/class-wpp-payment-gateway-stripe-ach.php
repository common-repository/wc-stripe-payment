<?php
defined( 'ABSPATH' ) || exit();

/**
 * Gateway that processes ACH payments.
 * Only available for U.S. based merchants at this time.
 *
 * @author WpPayments
 * @package Stripe/Gateways
 *
 */
class WPP_Gateway_Stripe_ACH extends WPP_Payment_Gateway_Stripe {

	use WPP_Payment_Charge_Trait;

	/**
	 *
	 * @var object
	 */
	public $metadata_key = '';

	public function __construct() {
		$this->synchronous        = false;
		$this->id                 = 'wpp_stripe_ach';
		$this->tab_title          = __( 'ACH', 'wc-stripe-payments' );
		$this->template_name      = 'ach.php';
		$this->token_type         = 'Stripe_ACH';
		$this->method_title       = __( 'ACH', 'wc-stripe-payments' );
		$this->method_description = __( 'ACH gateway that integrates with your Stripe account.', 'wc-stripe-payments' );
		$this->icon               = stripe_wpp()->assets_url( 'img/ach.svg' );
		$this->metadata_key       = $this->id . '_metadata';
		parent::__construct();
		$this->settings['charge_type'] = 'capture';
		$this->order_button_text       = $this->get_option( 'order_button_text' );
	}

	public static function init() {
		add_action( 'woocommerce_checkout_update_order_review', array( __CLASS__, 'update_order_review' ) );
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Payment_Gateway::is_available()
	 */
	public function is_available() {
		$is_available = parent::is_available();
		global $wp;
		if ( isset( $wp->query_vars['order-pay'] ) ) {
			$order = wc_get_order( absint( $wp->query_vars['order-pay'] ) );

			return $is_available && $order->get_currency() === 'USD';
		}

		return $is_available && get_woocommerce_currency() === 'USD';
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WPP_Payment_Gateway_Stripe::init_supports()
	 */
	public function init_supports() {
		parent::init_supports();
		unset( $this->supports['add_payment_method'] );
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WPP_Payment_Gateway_Stripe::enqueue_checkout_scripts()
	 */
	public function enqueue_checkout_scripts( $scripts ) {
		$scripts->register_script( 'plaid', 'https://cdn.plaid.com/link/v2/stable/link-initialize.js', array(), null );
		$scripts->enqueue_script(
			'ach',
			$scripts->assets_url( 'js/frontend/ach-payments.js' ),
			array(
				$scripts->get_handle( 'external' ),
				$scripts->get_handle( 'wpp-payment' ),
				$scripts->get_handle( 'plaid' ),
			)
		);
		$scripts->localize_script( 'ach', $this->get_localized_params() );
	}

	public function get_localized_params() {
		return array_merge_recursive(
			parent::get_localized_params(),
			array(
				'env'          => $this->get_plaid_environment(),
				'client_name'  => $this->get_option( 'client_name' ),
				'fees_enabled' => $this->fees_enabled(),
				'routes'       => array(
					'link_token' => WPP_Rest_API::get_endpoint( stripe_wpp()->rest_api->plaid->rest_uri( 'link-token' ) )
				)
			)
		);
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WPP_Payment_Gateway_Stripe_Charge::process_payment()
	 */
	public function process_payment( $order_id ) {
		// generate the access token first.
		if ( ! $this->use_saved_source() ) {
			try {
				$result = $this->fetch_access_token( $this->get_public_token() );

				$result = $this->fetch_bank_token( $result->access_token );

				$this->set_new_source_token( $result->stripe_bank_account_token );
			} catch ( Exception $e ) {
				wc_add_notice( sprintf( __( 'Error processing payment. Reason: %s', 'wc-stripe-payments' ), $e->getMessage() ), 'error' );
			}
		}
		add_filter(
			'wpp_order_meta_data',
			function ( $metadata ) {
				$metadata['plaid_id'] = $this->get_option( 'client_id' );

				return $metadata;
			}
		);

		return parent::process_payment( $order_id );
	}

	private function do_api_request( $uri, $body, $method = 'POST' ) {
		$response = wp_safe_remote_post(
			$this->get_plaid_url( $uri ),
			array(
				'headers'     => array( 'Content-Type' => 'application/json' ),
				'body'        => wp_json_encode( $body ),
				'data_format' => 'body',
			)
		);
		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		} else {
			$body = json_decode( $response['body'] );
			if ( $response['response']['code'] > 299 ) {
				throw new Exception( $body->error_message );
			} else {
				return $body;
			}
		}
	}

	private function fetch_access_token( $public_token ) {
		$env = $this->get_plaid_environment();

		return $this->do_api_request(
			'item/public_token/exchange',
			array(
				'client_id'    => $this->get_option( 'client_id' ),
				'secret'       => $this->get_option( "{$env}_secret" ),
				'public_token' => $public_token
			)
		);
	}

	private function fetch_bank_token( $access_token ) {
		$env = $this->get_plaid_environment();

		return $this->do_api_request(
			'processor/stripe/bank_account_token/create',
			array(
				'client_id'    => $this->get_option( 'client_id' ),
				'secret'       => $this->get_option( "{$env}_secret" ),
				'access_token' => $access_token,
				'account_id'   => $this->get_metadata()['account_id'],
			)
		);
	}

	/**
	 * @return mixed|null
	 * @throws Exception
	 */
	public function fetch_link_token() {
		$env = $this->get_plaid_environment();

		return $this->do_api_request(
			'link/token/create',
			array(
				'client_id'     => $this->get_option( 'client_id' ),
				'secret'        => $this->get_option( "{$env}_secret" ),
				'client_name'   => $this->get_option( 'client_name' ),
				'language'      => 'en',
				'country_codes' => array( 'US' ),
				'user'          => array(
					'client_user_id' => WC()->session->get_customer_id()
				),
				'products'      => array( 'auth' )
			)
		);
	}

	/**
	 * Return the base plaid api url.
	 *
	 * @return string
	 */
	private function get_base_url() {
		$url = '';
		switch ( $this->get_plaid_environment() ) {
			case 'production':
				$url = 'https://production.plaid.com/';
				break;
			case 'sandbox':
				$url = 'https://sandbox.plaid.com/';
				break;
			case 'development':
				$url = 'https://development.plaid.com/';
				break;
		}

		return $url;
	}

	private function get_plaid_url( $uri ) {
		return sprintf( '%s%s', $this->get_base_url(), $uri );
	}

	public function get_plaid_environment() {
		return $this->get_option( 'environment' );
	}

	private function get_metadata() {
		return isset( $_POST[ $this->metadata_key ] ) ? json_decode( stripslashes( $_POST[ $this->metadata_key ] ), true ) : null;
	}

	private function get_public_token() {
		return $this->get_new_source_token();
	}

	public function get_saved_methods_label() {
		return __( 'Saved Banks', 'wc-stripe-payments' );
	}

	public function get_new_method_label() {
		return __( 'New Bank', 'wc-stripe-payments' );
	}

	public function generate_ach_fee_html( $key, $data ) {
		$field_key = $this->get_field_key( $key );
		$defaults  = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => 'max-width: 150px; min-width: 150px;',
			'placeholder'       => '',
			'type'              => 'text',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
			'options'           => array(),
		);
		$data      = wp_parse_args( $data, $defaults );
		ob_start();
		include stripe_wpp()->plugin_path() . 'includes/admin/views/html-ach-fee.php';

		return ob_get_clean();
	}

	public function validate_ach_fee_field( $key, $value ) {
		$value = empty( $value ) ? array(
			'type'    => 'none',
			'taxable' => 'no',
			'value'   => '0',
		) : $value;
		if ( ! isset( $value['taxable'] ) ) {
			$value['taxable'] = 'no';
		}

		return $value;
	}

	/**
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function validate_environment_field( $key, $value ) {
		if ( 'test' == wpp_payment_mode() && 'development' == $value ) {
			WC_Admin_Settings::add_error( __( 'You must set the API mode to live in order to enable the Plaid development environment.', 'wc-stripe-payments' ) );
		}

		return $value;
	}

	public function fees_enabled() {
		$fee = $this->get_option(
			'fee',
			array(
				'type'  => 'none',
				'value' => '0',
			)
		);

		return ! empty( $fee ) && $fee['type'] != 'none';
	}

	/**
	 *
	 * @param WC_Cart $cart
	 */
	public function after_calculate_totals( $cart ) {
		remove_action( 'woocommerce_after_calculate_totals', array( $this, 'after_calculate_totals' ) );

		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'calculate_fees' ) );

		WC()->session->set( 'wpp_cart_total', $cart->total );
		$cart->calculate_totals();
	}

	/**
	 *
	 * @param WC_Cart $cart
	 */
	public function calculate_fees( $cart ) {
		remove_action( 'woocommerce_cart_calculate_fees', array( $this, 'calculate_fees' ) );
		$fee     = $this->get_option( 'fee' );
		$taxable = $fee['taxable'] == 'yes';
		switch ( $fee['type'] ) {
			case 'amount':
				$cart->add_fee( __( 'ACH Fee' ), $fee['value'], $taxable );
				break;
			case 'percent':
				$cart->add_fee( __( 'ACH Fee' ), $fee['value'] * WC()->session->get( 'wpp_cart_total', 0 ), $taxable );
				break;
		}
		unset( WC()->session->wpp_cart_total );
	}

	public static function update_order_review() {
		if ( ! empty( $_POST['payment_method'] ) && wc_clean( $_POST['payment_method'] ) === 'wpp_stripe_ach' ) {
			$payment_method = new WPP_Gateway_Stripe_ACH();
			if ( $payment_method->fees_enabled() ) {
				add_action( 'woocommerce_after_calculate_totals', array( $payment_method, 'after_calculate_totals' ) );
			}
		}
	}

	/**
	 * Override so Plaid token can be converted to a Stripe bank token.
	 *
	 * {@inheritDoc}
	 *
	 * @see WPP_Payment_Gateway_Stripe::create_payment_method()
	 */
	public function create_payment_method( $id, $customer_id ) {
		if ( $this->is_change_payment_method_request() ) {
			$result = $this->fetch_access_token( $this->get_public_token() );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$result = $this->fetch_bank_token( $result->access_token );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$id = $result->stripe_bank_account_token;
		}

		return parent::create_payment_method( $id, $customer_id );
	}

	public function has_enqueued_scripts( $scripts ) {
		return wp_script_is( $scripts->get_handle( 'ach' ) );
	}


	public function process_admin_options() {

		$this->init_settings();

		$post_data = $this->get_post_data();
		
		foreach ( $this->get_form_fields() as $key => $field ) {
			if ( 'title' !== $this->get_field_type( $field ) ) {
				try {
					$get_field_value = $this->get_field_value( $key, $field, $post_data );
					$this->settings[ $key ] = $get_field_value;

					if( $key == 'enabled' ) {
						(new WPP_Advanced_Settings)->update_option('stripe_ach_enabled', $get_field_value);
					}

				} catch ( Exception $e ) {
					$this->add_error( $e->getMessage() );
				}
			}
		}

	
		return update_option( $this->get_option_key(), apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings ), 'yes' );
	}
}

WPP_Gateway_Stripe_ACH::init();
