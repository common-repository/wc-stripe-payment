<?php

return array(
	'desc'               => array(
		'type'        => 'description',
		'description' => sprintf( '<div>%s</div>', __( 'For US customers only.', 'wc-stripe-payments' ) ) .
		                 sprintf( '<p>%s</p>',
			                 sprintf( __( 'Read through our %1$sdocumentation%2$s to configure ACH payments', 'wc-stripe-payments' ),
				                 '<a target="_blank" href="https://wppayments.org/docs/ach-payments/">',
				                 '</a>' ) ),
	),
	'enabled'            => array(
		'title'       => __( 'Enabled', 'wc-stripe-payments' ),
		'type'        => 'checkbox',
		'default'     => 'no',
		'value'       => 'yes',
		'desc_tip'    => true,
		'description' => __( 'If enabled, your site can accept ACH payments through Stripe.', 'wc-stripe-payments' ),
	),
	'environment'        => array(
		'type'        => 'select',
		'title'       => __( 'Plaid Environment', 'wc-stripe-payments' ),
		'default'     => 'production',
		'options'     => array(
			'sandbox'     => __( 'Sandbox', 'wc-stripe-payments' ),
			'development' => __( 'Development', 'wc-stripe-payments' ),
			'production'  => __( 'Production', 'wc-stripe-payments' ),
		),
		'desc_tip'    => true,
		'description' => __( 'The active Plaid environment. You must set API mode to live to use Plaid\'s development environment.', 'wc-stripe-payments' ),
	),
	'plaid_keys'         => array(
		'type'  => 'title',
		'title' => __( 'Plaid Keys', 'wc-stripe-payments' ),
	),
	'client_id'          => array(
		'type'        => 'text',
		'title'       => __( 'Client ID' ),
		'default'     => '',
		'description' => __( 'ID that identifies your Plaid account.', 'wc-stripe-payments' ),
		'desc_tip'    => true,
	),
	'plaid_secrets'      => array(
		'type'  => 'title',
		'title' => __( 'Plaid Secrets', 'wc-stripe-payments' ),
	),
	'sandbox_secret'     => array(
		'title'       => __( 'Sandbox Secret', 'wc-stripe-payments' ),
		'type'        => 'password',
		'default'     => '',
		'description' => __( 'Key that acts as a password when connecting to Plaid\'s sandbox environment.', 'wc-stripe-payments' ),
		'desc_tip'    => true,
	),
	'development_secret' => array(
		'title'       => __( 'Development Secret', 'wc-stripe-payments' ),
		'type'        => 'password',
		'default'     => '',
		'description' => __( 'Development allows you to test real bank credentials with test transactions.', 'wc-stripe-payments' ),
		'desc_tip'    => true,
	),
	'production_secret'  => array(
		'title'       => __( 'Production Secret', 'wc-stripe-payments' ),
		'type'        => 'password',
		'default'     => '',
		'description' => __( 'Key that acts as a password when connecting to Plaid\'s production environment.', 'wc-stripe-payments' ),
		'desc_tip'    => true,
	),
	'general_settings'   => array(
		'type'  => 'title',
		'title' => __( 'General Settings', 'wc-stripe-payments' ),
	),
	'title_text'         => array(
		'type'        => 'text',
		'title'       => __( 'Title', 'wc-stripe-payments' ),
		'default'     => __( 'ACH Payment', 'wc-stripe-payments' ),
		'desc_tip'    => true,
		'description' => __( 'Title of the ACH gateway' ),
	),
	'description'        => array(
		'title'       => __( 'Description', 'wc-stripe-payments' ),
		'type'        => 'text',
		'default'     => '',
		'description' => __( 'Leave blank if you don\'t want a description to show for the gateway.', 'wc-stripe-payments' ),
		'desc_tip'    => true,
	),
	'order_button_text'  => array(
		'title'       => __( 'Order Button Text', 'wc-stripe-payments' ),
		'type'        => 'text',
		'default'     => __( 'Bank Payment', 'wc-stripe-payments' ),
		'description' => __( 'The text on the Place Order button that displays when the gateway is selected on the checkout page.', 'wc-stripe-payments' ),
		'desc_tip'    => true
	),
	'client_name'        => array(
		'type'        => 'text',
		'title'       => __( 'Client Name', 'wc-stripe-payments' ),
		'default'     => get_bloginfo( 'name' ),
		'description' => __( 'The name that appears on the ACH payment screen.', 'wc-stripe-payments' ),
		'desc_tip'    => true,
	),
	'method_format'      => array(
		'title'       => __( 'ACH Display', 'wc-stripe-payments' ),
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'options'     => wp_list_pluck( $this->get_method_formats(), 'example' ),
		'value'       => '',
		'default'     => 'type_ending_in',
		'desc_tip'    => true,
		'description' => __( 'This option allows you to customize how the credit card will display for your customers on orders, subscriptions, etc.' ),
	),
	'order_status'       => array(
		'type'        => 'select',
		'title'       => __( 'Order Status', 'wc-stripe-payments' ),
		'default'     => 'default',
		'class'       => 'wc-enhanced-select',
		'options'     => array_merge( array( 'default' => __( 'Default', 'wc-stripe-payments' ) ), wc_get_order_statuses() ),
		'tool_tip'    => true,
		'description' => __( 'This is the status of the order once payment is complete. If <b>Default</b> is selected, then WooCommerce will set the order status automatically based on internal logic which states if a product is virtual and downloadable then status is set to complete. Products that require shipping are set to Processing. Default is the recommended setting as it allows standard WooCommerce code to process the order status.',
			'wc-stripe-payments' ),
	),
	'fee'                => array(
		'title'       => __( 'ACH Fee', 'wc-stripe-payments' ),
		'type'        => 'ach_fee',
		'class'       => '',
		'value'       => '',
		'default'     => array(
			'type'    => 'none',
			'taxable' => 'no',
			'value'   => '0',
		),
		'options'     => array(
			'none'    => __( 'None', 'wc-stripe-payments' ),
			'amount'  => __( 'Amount', 'wc-stripe-payments' ),
			'percent' => __( 'Percentage', 'wc-stripe-payments' ),
		),
		'desc_tip'    => true,
		'description' => __( 'You can assign a fee to the order for ACH payments. Amount is a static amount and percentage is a percentage of the cart amount.', 'wc-stripe-payments' ),
	),
);
