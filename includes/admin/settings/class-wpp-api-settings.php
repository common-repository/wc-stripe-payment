<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @author WpPayments
 * @package Stripe/Classes
 *
 */
class WPP_API_Settings extends WPP_Settings_API {

	public function __construct() {
		$this->id        = 'wpp_api';
		$this->tab_title = __( 'API Settings', 'wc-stripe-payments' );
		parent::__construct();
	}

	public function hooks() {
		parent::hooks();
		add_action( 'woocommerce_update_options_checkout_' . $this->id, array( $this, 'process_admin_options' ) );
		add_filter( 'wppayment_admin_settings_tabs', array( $this, 'admin_nav_tab' ) );
		add_action( 'wpp_stripe_settings_checkout_' . $this->id, array( $this, 'admin_options' ) );
	}

	/**
	 * Return the name of the option in the WP DB.
	 *
	 * @since 2.6.0
	 * @return string
	 */
	public function get_option_key() {
		return $this->plugin_id . 'wpp_api_settings';
	}

	public function init_form_fields() {
		$this->form_fields = array(
			'title'                => array(
				'type'  => 'title',
				'title' => __( 'API Settings', 'wc-stripe-payments' ),
			),
			'test_mode_keys'       => array(
				'type'        => 'description',
				'description' => __( 'When test mode is enabled you can manually enter your API keys or go through the connect process. Live mode requires that you click the Connect button.',
					'wc-stripe-payments' ),
			),
			'stripe_connect'       => array(
				'type'        => 'stripe_connect',
				'title'       => __( 'Connect Your Stripe Account', 'wc-stripe-payments' ),
				'label'       => __( 'Click to Connect', 'wc-stripe-payments' ),
				'class'       => 'do-stripe-connect',
				'description' => __( 'We make it easy to connect Stripe to your site. Click the Connect button to go through our connect flow.', 'wc-stripe-payments' ),
			),

			'stripe_cc_enabled'           => array(
				'title'       => __( 'Enable Credit Cards', 'wc-stripe-payments' ),
				'type'        => 'checkbox',
				'default'     => 'yes',
				'value'       => 'yes',
				'desc_tip'    => true,
				'description' => __( 'If enabled, your site can accept credit card payments through Stripe.', 'wc-stripe-payments' ),
			),

			'stripe_applepay_enabled'           => array(
				'title'       => __( 'Enable Apple Pay', 'wc-stripe-payments' ),
				'type'        => 'checkbox',
				'default'     => 'yes',
				'value'       => 'yes',
				'desc_tip'    => true,
				'description' => __( 'If enabled, your site can accept credit card payments through Stripe.', 'wc-stripe-payments' ),
			),
			
			/*
			'stripe_googlepay_enabled'           => array(
				'title'       => __( 'Enable Google Pay', 'wc-stripe-payments' ),
				'type'        => 'checkbox',
				'default'     => 'yes',
				'value'       => 'yes',
				'desc_tip'    => true,
				'description' => __( 'If enabled, your site can accept credit card payments through Stripe.', 'wc-stripe-payments' ),
			),
			*/

			'stripe_payment_request_enabled'           => array(
				'title'       => __( 'Enable PaymentRequest Gateway', 'wc-stripe-payments' ),
				'type'        => 'checkbox',
				'default'     => 'yes',
				'value'       => 'yes',
				'desc_tip'    => true,
				'description' => __( 'If enabled, your site can accept credit card payments through Stripe.', 'wc-stripe-payments' ),
			),

			'stripe_ach_enabled'           => array(
				'title'       => __( 'Enable ACH', 'wc-stripe-payments' ),
				'type'        => 'checkbox',
				'default'     => 'yes',
				'value'       => 'yes',
				'desc_tip'    => true,
				'description' => __( 'If enabled, your site can accept credit card payments through Stripe.', 'wc-stripe-payments' ),
			),


			'account_id'           => array(
				'type'        => 'paragraph',
				'title'       => __( 'Account ID', 'wc-stripe-payments' ),
				'text'        => '',
				'class'       => '',
				'default'     => '',
				'desc_tip'    => true,
				'description' => __( 'This is your Stripe Connect ID and serves as a unique identifier.', 'wc-stripe-payments' ),
			),
			
		);
		

		if ( $this->get_option( 'account_id' ) ) {
			$this->form_fields['account_id']['text']       = $this->get_option( 'account_id' );
			$this->form_fields['stripe_connect']['description']
			                                               = sprintf( __( '%s Your Stripe account has been connected. You can now accept Live and Test payments. You can Re-Connect if you want to recycle your API keys for security.',
				'wc-stripe-payments' ),
				'<span class="dashicons dashicons-yes stipe-connect-active"></span>' );
			$this->form_fields['stripe_connect']['active'] = true;
		} else {
			unset( $this->form_fields['account_id'] );
			// don't show the live connection test unless connect process has been completed.
			unset( $this->form_fields['connection_test_live'] );
		}

	}

	public function generate_stripe_connect_html( $key, $data ) {
		$field_key           = $this->get_field_key( $key );
		$data                = wp_parse_args(
			$data,
			array(
				'class'       => '',
				'style'       => '',
				'description' => '',
				'desc_tip'    => false,
				'css'         => '',
				'active'      => false,
			)
		);
		$data['connect_url'] = $this->get_connect_url();
		if ( $data['active'] ) {
			$data['label'] = __( 'Click To Re-Connect', 'wc-stripe-payments' );
		}
		ob_start();
		include stripe_wpp()->plugin_path() . 'includes/admin/views/html-stripe-connect.php';

		return ob_get_clean();
	}

	public function admin_options() {
		// Check if user is being returned from Stripe Connect
		if ( isset( $_GET['_stripe_connect_nonce'] ) && wp_verify_nonce( $_GET['_stripe_connect_nonce'], 'stripe-connect' ) ) {
			if ( isset( $_GET['error'] ) ) {
				$error = json_decode( base64_decode( sanitize_text_field( $_GET['error'] ) ) );
				// $error = sanitize_post( $error );
				if ( property_exists( $error, 'message' ) ) {
					$message = sanitize_text_field($error->message);
				} elseif ( property_exists( $error, 'raw' ) ) {
					$message = sanitize_text_field($error->raw->message);
				} else {
					$message = __( 'Please try again.', 'wc-stripe-payments' );
				}
				wpp_payment_log_error( sprintf( 'Error connecting to Stripe account. Reason: %s', $message ) );
				$this->add_error( sprintf( __( 'We were not able to connect your Stripe account. Reason: %s', 'wc-stripe-payments' ), $message ) );
			} elseif ( isset( $_GET['response'] ) ) {

				$response = json_decode( base64_decode( sanitize_text_field($_GET['response']) ) );
				$response = sanitize_post( $response );
				
				// save the token to the api settings
				$this->settings['account_id']    = $response->live->stripe_user_id;
				$this->settings['refresh_token'] = $response->live->refresh_token;

				$this->settings['secret_key_live']      = $response->live->access_token;
				$this->settings['publishable_key_live'] = $response->live->stripe_publishable_key;

				$this->settings['secret_key_test']      = $response->test->access_token;
				$this->settings['publishable_key_test'] = $response->test->stripe_publishable_key;

				$this->settings['mode'] 				= empty( stripe_wpp()->api_settings->get_option( 'mode' ) ) ? 'test' : stripe_wpp()->api_settings->get_option( 'mode' );
				
				update_option( $this->get_option_key(), $this->settings );

				delete_option( 'wpp_payments_connect_notice' );

				// create webhooks
				$this->create_webhooks();

				/**
				 * @param array                  $response
				 * @param WPP_API_Settings $this
				 *
				 */
				do_action( 'wpp_payment_connect_settings', $response, $this );

				$this->init_form_fields();

				?>
                <div class="updated inline notice-success is-dismissible ">
                    <p>
						<?php esc_html_e( 'Your Stripe account has been connected to your WooCommerce store. You may now accept payments in Live and Test mode.', 'wc-stripe-payments' ) ?>
                    </p>
                </div>
				<?php
			}
		}
		parent::admin_options();
	}

	public function get_connect_url() {
		return \Stripe\OAuth::authorizeUrl( array(
			'response_type'  => 'code',
			'client_id'      => stripe_wpp()->client_id,
			'stripe_landing' => 'login',
			'always_prompt'  => 'true',
			'scope'          => 'read_write',
			'state'          => base64_encode(
				wp_json_encode(
					array(
						'plugin_url' => add_query_arg( '_stripe_connect_nonce', wp_create_nonce( 'stripe-connect' ), admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wpp_api' ) )
					)
				)
			)
		) );
	}

	public function localize_settings() {
		return parent::localize_settings(); // TODO: Change the autogenerated stub
	}

	public function delete_webhook_settings( $mode ) {
		unset( $this->settings["webhook_secret_{$mode}"], $this->settings["webhook_id_{$mode}"] );
		update_option( $this->get_option_key(), $this->settings );
	}

	/**
	 * @param string $mode
	 * @param array  $events
	 *
	 * @return bool|\Stripe\WebhookEndpoint
	 * @throws \Stripe\Exception\ApiErrorException
	 */
	public function create_webhook( $mode, $events = array() ) {
		$client = WPP_Stripe_Gateway::load();
		$url    = get_rest_url( null, '/wpp-stripe/v1/webhook' );
		if ( ! in_array( '*', $events ) ) {
			$events = apply_filters(
				'wpp_payment_webhooks_events',
				array_values( array_unique( array_merge( array(
					'charge.failed',
					'charge.succeeded',
					'source.chargeable',
					'payment_intent.succeeded',
					'charge.refunded',
					'charge.dispute.created',
					'charge.dispute.closed',
					'review.opened',
					'review.closed'
				), $events ) ) )
			);
		}
		$webhook = $client->mode( $mode )->webhookEndpoints->create( array(
			'api_version'    => '2020-08-27',
			'url'            => $url,
			'enabled_events' => $events,
		) );
		if ( is_wp_error( $webhook ) ) {
			wpp_payment_log_error( sprintf( 'Error creating Stripe webhook. Mode: %1$s. Reason: %2$s', $mode, $webhook->get_error_message() ) );
		} else {
			$this->settings["webhook_secret_{$mode}"] = $webhook['secret'];
			$this->settings["webhook_id_{$mode}"]     = $webhook['id'];
			update_option( $this->get_option_key(), $this->settings );
		}

		return $webhook;;
	}

	private function create_webhooks() {
		foreach ( array( 'live', 'test' ) as $mode ) {
			$this->create_webhook( $mode );
		}
	}

	public function get_webhook_id( $mode ) {
		return $this->get_option( "webhook_id_{$mode}", null );
	}

	public function process_admin_options() {

		$this->init_settings();

		$post_data = $this->get_post_data();
		
		
		//$stripe_cc_enabled  = (new WPP_Advanced_Settings)->get_option('stripe_cc_enabled');

		foreach ( $this->get_form_fields() as $key => $field ) {
			if ( 'title' !== $this->get_field_type( $field ) ) {
				try {
					$get_field_value = $this->get_field_value( $key, $field, $post_data );
					$this->settings[ $key ] = $get_field_value;

					switch ($key) {
						case 'stripe_cc_enabled':
							(new WPP_Gateway_Stripe_CC)->update_option('enabled', $get_field_value);
							break;

						case 'stripe_applepay_enabled':
							(new WPP_Gateway_Stripe_ApplePay)->update_option('enabled', $get_field_value);
							break;
							
						case 'stripe_googlepay_enabled':
							(new WPP_Gateway_Stripe_GooglePay)->update_option('enabled', $get_field_value);
							break;
							
						case 'stripe_payment_request_enabled':
							(new WPP_Gateway_Stripe_Payment_Request)->update_option('enabled', $get_field_value);
							break;			

						case 'stripe_ach_enabled':
							(new WPP_Gateway_Stripe_ACH)->update_option('enabled', $get_field_value);
							break;			
						
						default:
							# code...
							break;
					}

				} catch ( Exception $e ) {
					$this->add_error( $e->getMessage() );
				}
			}
		}
	
		return update_option( $this->get_option_key(), apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings ), 'yes' );
	}

}
