<?php

defined( 'ABSPATH' ) || exit();

/**
 */
class WPP_Advanced_Settings extends WPP_Settings_API {

	public function __construct() {
		$this->id        = 'wpp_advanced';
		$this->tab_title = __( 'Advanced Settings', 'wc-stripe-payments' );
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
			'title'                  => array(
				'type'  => 'title',
				'title' => __( 'Advanced Settings', 'wc-stripe-payments' ),
			),
			'settings_description'   => array(
				'type'        => 'description',
				'description' => __( 'This section provides advanced settings that allow you to configure functionality that fits your business process.', 'wc-stripe-payments' )
			),
			
			///////////////////////////////////////
			/*API Settings Data*/
			///////////////////////////////////////	

			'mode'                 => array(
				'type'        => 'select',
				'title'       => __( 'Mode', 'wc-stripe-payments' ),
				'class'       => 'wc-enhanced-select',
				'options'     => array(
					'test' => __( 'Test', 'wc-stripe-payments' ),
					'live' => __( 'Live', 'wc-stripe-payments' ),
				),
				'default'     => 'test',
				'desc_tip'    => true,
				'description' => __( 'The mode determines if you are processing test transactions or live transactions on your site. Test mode allows you to simulate payments so you can test your integration.',
					'wc-stripe-payments' ),
			),


			/*
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

			'stripe_googlepay_enabled'           => array(
				'title'       => __( 'Enable Google Pay', 'wc-stripe-payments' ),
				'type'        => 'checkbox',
				'default'     => 'yes',
				'value'       => 'yes',
				'desc_tip'    => true,
				'description' => __( 'If enabled, your site can accept credit card payments through Stripe.', 'wc-stripe-payments' ),
			),

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
	
			*/
			'publishable_key_test' => array(
				'title'             => __( 'Test Publishable Key', 'wc-stripe-payments' ),
				'type'              => 'text',
				'default'           => '',
				'desc_tip'          => true,
				'description'       => __( 'Your publishable key is used to initialize Stripe assets.', 'wc-stripe-payments' ),
				'custom_attributes' => array(
					'data-show-if' => array(
						'mode' => 'test'
					)
				)
			),
			'secret_key_test'      => array(
				'title'             => __( 'Test Secret Key', 'wc-stripe-payments' ),
				'type'              => 'password',
				'default'           => '',
				'desc_tip'          => true,
				'description'       => __( 'Your secret key is used to authenticate requests to Stripe.', 'wc-stripe-payments' ),
				'custom_attributes' => array(
					'data-show-if' => array(
						'mode' => 'test'
					)
				)
			),
			'connection_test_live' => array(
				'type'              => 'stripe_button',
				'title'             => __( 'Connection Test', 'wc-stripe-payments' ),
				'label'             => __( 'Connection Test', 'wc-stripe-payments' ),
				'class'             => 'wpp-payment-connection-test live-mode button-secondary',
				'description'       => __( 'Click this button to perform a connection test. If successful, your site is connected to Stripe.', 'wc-stripe-payments' ),
				'custom_attributes' => array(
					'data-show-if' => array(
						'mode' => 'live'
					)
				)
			),
			'connection_test_test' => array(
				'type'              => 'stripe_button',
				'title'             => __( 'Connection Test', 'wc-stripe-payments' ),
				'label'             => __( 'Connection Test', 'wc-stripe-payments' ),
				'class'             => 'wpp-payment-connection-test test-mode button-secondary',
				'description'       => __( 'Click this button to perform a connection test. If successful, your site is connected to Stripe.', 'wc-stripe-payments' ),
				'custom_attributes' => array(
					'data-show-if' => array(
						'mode' => 'test'
					)
				)
			),
			'webhook_button_test'  => array(
				'type'              => 'stripe_button',
				'title'             => __( 'Create Webhook', 'wc-stripe-payments' ),
				'label'             => __( 'Create Webhook', 'wc-stripe-payments' ),
				'class'             => 'wpp-payment-create-webhook test-mode button-secondary',
				'custom_attributes' => array(
					'data-show-if' => array(
						'mode' => 'test'
					)
				)
			),
			'webhook_button_live'  => array(
				'type'              => 'stripe_button',
				'title'             => __( 'Create Webhook', 'wc-stripe-payments' ),
				'label'             => __( 'Create Webhook', 'wc-stripe-payments' ),
				'class'             => 'wpp-payment-create-webhook live-mode button-secondary',
				'custom_attributes' => array(
					'data-show-if' => array(
						'mode' => 'live'
					)
				)
			),
			'webhook_url'          => array(
				'type'        => 'paragraph',
				'title'       => __( 'Webhook url', 'wc-stripe-payments' ),
				'class'       => 'wpp-payment-webhook',
				'text'        => stripe_wpp()->rest_api->webhook->rest_url( 'webhook' ),
				'desc_tip'    => true,
				'description' => __( '<strong>Important:</strong> the webhook url is called by Stripe when events occur in your account, like a source becomes chargeable. Use the Create Webhook button or add the webhook manually in your Stripe account.',
					'wc-stripe-payments' ),
			),
			'webhook_secret_live'  => array(
				'type'              => 'password',
				'title'             => __( 'Live Webhook Secret', 'wc-stripe-payments' ),
				'description'       => sprintf( __( 'The webhook secret is used to authenticate webhooks sent from Stripe. It ensures no 3rd party can send you events, pretending to be Stripe. %1$sWebhook guide%2$s',
					'wc-stripe-payments' ),
					'<a target="_blank" href="https://wppayments.org/docs/webhooks/">',
					'</a>' ),
				'custom_attributes' => array( 'data-show-if' => array( 'mode' => 'live' ) ),
			),
			'webhook_secret_test'  => array(
				'type'              => 'password',
				'title'             => __( 'Test Webhook Secret', 'wc-stripe-payments' ),
				'description'       => sprintf( __( 'The webhook secret is used to authenticate webhooks sent from Stripe. It ensures no 3rd party can send you events, pretending to be Stripe. %1$sWebhook guide%2$s',
					'wc-stripe-payments' ),
					'<a target="_blank" href="https://wppayments.org/docs/webhooks/">',
					'</a>' ),
				'custom_attributes' => array( 'data-show-if' => array( 'mode' => 'test' ) ),
			),
			'debug_log'            => array(
				'title'       => __( 'Debug Log', 'wc-stripe-payments' ),
				'type'        => 'checkbox',
				'desc_tip'    => true,
				'default'     => 'yes',
				'description' => __( 'When enabled, the plugin logs important errors and info that can help you troubleshoot potential issues.', 'wc-stripe-payments' ),
			),



			///////////////////////////////////////
			/*API Settings Data*/
			///////////////////////////////////////





			'stripe_fee'             => array(
				'title'       => __( 'Display Stripe Fee', 'wc-stripe-payments' ),
				'type'        => 'checkbox',
				'default'     => 'no',
				'value'       => 'yes',
				'desc_tip'    => true,
				'description' => __( 'If enabled, the Stripe fee will be displayed on the Order Details page. The fee and net payout are displayed in your Stripe account currency.',
					'wc-stripe-payments' )
			),
			'stripe_fee_currency'    => array(
				'title'             => __( 'Fee Display Currency', 'wc-stripe-payments' ),
				'type'              => 'checkbox',
				'default'           => 'no',
				'description'       => __( 'If enabled, the Stripe fee and payout will be displayed in the currency of the order. Stripe by default provides the fee and payout in the Stripe account\'s currency.',
					'wc-stripe-payments' ),
				'custom_attributes' => array(
					'data-show-if' => array(
						'stripe_fee' => true
					)
				)
			),
			'refund_cancel'          => array(
				'title'       => __( 'Refund On Cancel', 'wc-stripe-payments' ),
				'type'        => 'checkbox',
				'default'     => 'no',
				'value'       => 'yes',
				'desc_tip'    => true,
				'description' => __( 'If enabled, the plugin will process a payment cancellation or refund within Stripe when the order\'s status is set to cancelled.', 'wc-stripe-payments' )
			),
			'disputes'               => array(
				'title' => __( 'Dispute Settings', 'wc-stripe-payments' ),
				'type'  => 'title'
			),
			'dispute_created'        => array(
				'title'       => __( 'Dispute Created', 'wc-stripe-payments' ),
				'type'        => 'checkbox',
				'default'     => 'yes',
				'description' => __( 'If enabled, the plugin will listen for the <strong>charge.dispute.created</strong> webhook event and set the order\'s status to on-hold by default.',
					'wc-stripe-payments' )
			),
			'dispute_created_status' => array(
				'title'             => __( 'Disputed Created Order Status', 'wc-stripe-payments' ),
				'type'              => 'select',
				'default'           => 'wc-on-hold',
				'options'           => wc_get_order_statuses(),
				'description'       => __( 'The status assigned to an order when a dispute is created.', 'wc-stripe-payments' ),
				'custom_attributes' => array(
					'data-show-if' => array(
						'dispute_created' => true
					)
				)
			),
			'dispute_closed'         => array(
				'title'       => __( 'Dispute Closed', 'wc-stripe-payments' ),
				'type'        => 'checkbox',
				'default'     => 'yes',
				'description' => __( 'If enabled, the plugin will listen for the <strong>charge.dispute.closed</strong> webhook event and set the order\'s status back to status before the dispute was opened.',
					'wc-stripe-payments' )
			),
			'reviews'                => array(
				'title' => __( 'Review Settings', 'wc-stripe-payments' ),
				'type'  => 'title'
			),
			'review_created'         => array(
				'title'       => __( 'Review Created', 'wc-stripe-payments' ),
				'type'        => 'checkbox',
				'default'     => 'no',
				'description' => __( 'If enabled, the plugin will listen for the <strong>review.created</strong> webhook event and set the order\'s status to on-hold by default.',
					'wc-stripe-payments' )
			),
			'review_closed'          => array(
				'title'       => __( 'Review Closed', 'wc-stripe-payments' ),
				'type'        => 'checkbox',
				'default'     => 'no',
				'description' => __( 'If enabled, the plugin will listen for the <strong>review.closed</strong> webhook event and set the order\'s status back to the status before the review was opened.',
					'wc-stripe-payments' )
			),
			'email_title'            => array(
				'type'  => 'title',
				'title' => __( 'Stripe Email Options', 'wc-stripe-payments' )
			),
			'email_enabled'          => array(
				'type'        => 'checkbox',
				'title'       => __( 'Email Receipt', 'wc-stripe-payments' ),
				'default'     => 'no',
				'description' => __( 'If enabled, an email receipt will be sent to the customer by Stripe when the order is processed.',
					'wc-stripe-payments' ),
			)
		);


		foreach ( array( 'test', 'live' ) as $mode ) {
					$webhook_id = $this->get_webhook_id( $mode );
					if ( $webhook_id ) {
						$this->form_fields["webhook_button_{$mode}"]['title']       = __( 'Delete Webhook', 'wc-stripe-payments' );
						$this->form_fields["webhook_button_{$mode}"]['label']       = __( 'Delete Webhook', 'wc-stripe-payments' );
						$this->form_fields["webhook_button_{$mode}"]['class']       .= ' wpp-payment-delete-webhook';
						$this->form_fields["webhook_button_{$mode}"]['description'] = sprintf( __( '%1$s Webhook created. ID: %2$s' ),
							'<span class="dashicons dashicons-yes stripe-webhook-created"></span>',
							$webhook_id );
					}
				}
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


	public function is_fee_enabled() {
		return $this->is_active( 'stripe_fee' );
	}

	public function is_display_order_currency() {
		return $this->is_active( 'stripe_fee_currency' );
	}

	public function is_email_receipt_enabled() {
		return $this->is_active( 'email_enabled' );
	}

	public function is_refund_cancel_enabled() {
		return $this->is_active( 'refund_cancel' );
	}

	public function is_dispute_created_enabled() {
		return $this->is_active( 'dispute_created' );
	}

	public function is_dispute_closed_enabled() {
		return $this->is_active( 'dispute_closed' );
	}

	public function is_review_opened_enabled() {
		return $this->is_active( 'review_created' );
	}

	public function is_review_closed_enabled() {
		return $this->is_active( 'review_closed' );
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
	
}
