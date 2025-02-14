<?php
return array(
	'desc'             => array(
		'type'        => 'description',
		'description' => __(
			'The PaymentRequest gateway uses your customer\'s browser to render payment options like Google Pay and Microsoft Pay. You can either use the Google Pay gateway for example, or this gateway.
						The difference is this gateway uses Stripe\'s PaymentRequest Button rather than render a Google Pay specific button.',
			'wc-stripe-payments'
		),
	),
	'enabled'          => array(
		'title'       => __( 'Enabled', 'wc-stripe-payments' ),
		'type'        => 'checkbox',
		'default'     => 'no',
		'value'       => 'yes',
		'desc_tip'    => true,
		'description' => __( 'If enabled, your site can accept Apple Pay payments through Stripe.', 'wc-stripe-payments' ),
	),
	'general_settings' => array(
		'type'  => 'title',
		'title' => __( 'General Settings', 'wc-stripe-payments' ),
	),
	'title_text'       => array(
		'type'        => 'text',
		'title'       => __( 'Title', 'wc-stripe-payments' ),
		'default'     => __( 'Browser Payments', 'wc-stripe-payments' ),
		'desc_tip'    => true,
		'description' => __( 'Title of the credit card gateway' ),
	),
	'description'      => array(
		'title'       => __( 'Description', 'wc-stripe-payments' ),
		'type'        => 'text',
		'default'     => '',
		'description' => __( 'Leave blank if you don\'t want a description to show for the gateway.', 'wc-stripe-payments' ),
		'desc_tip'    => true,
	),
	'method_format'    => array(
		'title'       => __( 'Credit Card Display', 'wc-stripe-payments' ),
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'options'     => wp_list_pluck( $this->get_method_formats(), 'example' ),
		'value'       => '',
		'default'     => 'type_ending_in',
		'desc_tip'    => true,
		'description' => __( 'This option allows you to customize how the credit card will display for your customers on orders, subscriptions, etc.' ),
	),
	'charge_type'      => array(
		'type'        => 'select',
		'title'       => __( 'Charge Type', 'wc-stripe-payments' ),
		'default'     => 'capture',
		'class'       => 'wc-enhanced-select',
		'options'     => array(
			'capture'   => __( 'Capture', 'wc-stripe-payments' ),
			'authorize' => __( 'Authorize', 'wc-stripe-payments' ),
		),
		'desc_tip'    => true,
		'description' => __( 'This option determines whether the customer\'s funds are captured immediately or authorized and can be captured at a later date.', 'wc-stripe-payments' ),
	),
	'payment_sections' => array(
		'type'        => 'multiselect',
		'title'       => __( 'Payment Sections', 'wc-stripe-payments' ),
		'class'       => 'wc-enhanced-select',
		'options'     => array(
			'product'         => __( 'Product Page', 'wc-stripe-payments' ),
			'cart'            => __( 'Cart Page', 'wc-stripe-payments' ),
			'mini_cart'       => __( 'Mini Cart', 'wc-stripe-payments' ),
			'checkout_banner' => __( 'Top of Checkout', 'wc-stripe-payments' ),
		),
		'default'     => array( 'product', 'cart' ),
		'description' => $this->get_payment_section_description(),
	),
	'order_status'     => array(
		'type'        => 'select',
		'title'       => __( 'Order Status', 'wc-stripe-payments' ),
		'default'     => 'default',
		'class'       => 'wc-enhanced-select',
		'options'     => array_merge( array( 'default' => __( 'Default', 'wc-stripe-payments' ) ), wc_get_order_statuses() ),
		'tool_tip'    => true,
		'description' => __( 'This is the status of the order once payment is complete. If <b>Default</b> is selected, then WooCommerce will set the order status automatically based on internal logic which states if a product is virtual and downloadable then status is set to complete. Products that require shipping are set to Processing. Default is the recommended setting as it allows standard WooCommerce code to process the order status.', 'wc-stripe-payments' ),
	),
	'button_section'   => array(
		'type'  => 'title',
		'title' => __( 'Button Settings', 'wc-stripe-payments' ),
	),
	'button_type'      => array(
		'type'        => 'select',
		'title'       => __( 'Type', 'wc-stripe-payments' ),
		'options'     => array(
			'default' => __( 'default', 'wc-stripe-payments' ),
			// 'donate' => __ ( 'donate', 'wc-stripe-payments' ),
			'buy'     => __( 'buy', 'wc-stripe-payments' ),
		),
		'default'     => 'buy',
		'desc_tip'    => true,
		'description' => __( 'This defines the type of button that will display.', 'wc-stripe-payments' ),
	),
	'button_theme'     => array(
		'type'        => 'select',
		'title'       => __( 'Theme', 'wc-stripe-payments' ),
		'options'     => array(
			'dark'          => __( 'dark', 'wc-stripe-payments' ),
			'light'         => __( 'light', 'wc-stripe-payments' ),
			'light-outline' => __( 'light-outline', 'wc-stripe-payments' ),
		),
		'default'     => 'dark',
		'desc_tip'    => true,
		'description' => __( 'This defines the color scheme for the button.', 'wc-stripe-payments' ),
	),
	'button_height'    => array(
		'type'        => 'text',
		'title'       => __( 'Height', 'wc-stripe-payments' ),
		'default'     => '40',
		'desc_tip'    => true,
		'description' => __( 'The height of the button. Max height is 64', 'wc-stripe-payments' ),
	),
);
