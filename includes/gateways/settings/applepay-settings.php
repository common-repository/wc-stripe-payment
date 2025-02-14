<?php
return array(
	'desc'                 => array(
		'type'        => 'description',
		'description' => sprintf( '<div class="wpp-payment-register-domain"><button class="button button-secondary api-register-domain">%s</button></div><p>%s</p>', __( 'Register Domain', 'wc-stripe-payments' ), sprintf( __( 'This plugin attemps to add the domain association file to your server automatically when you click the Register Domain button. If that fails due to file permssions, you must add the <strong>%1$s.well-known/apple-developer-merchantid-domain-association%2$s</strong> file to your domain  and register your domain within the Stripe Dashboard.', 'wc-stripe-payments' ), '<a href="https://stripe.com/files/apple-pay/apple-developer-merchantid-domain-association">', '</a>' ) ) .
		                 '<p>' .
		                 __( 'In order for Apple Pay to display, you must test with an iOS device and have a payment method saved in the Apple Wallet.', 'wc-stripe-payments' ) .
		                 '</p>',
	),
	'enabled'              => array(
		'title'       => __( 'Enabled', 'wc-stripe-payments' ),
		'type'        => 'checkbox',
		'default'     => 'no',
		'value'       => 'yes',
		'desc_tip'    => true,
		'description' => __( 'If enabled, your site can accept Apple Pay payments through Stripe.', 'wc-stripe-payments' ),
	),
	'general_settings'     => array(
		'type'  => 'title',
		'title' => __( 'General Settings', 'wc-stripe-payments' ),
	),
	'title_text'           => array(
		'type'        => 'text',
		'title'       => __( 'Title', 'wc-stripe-payments' ),
		'default'     => __( 'Apple Pay', 'wc-stripe-payments' ),
		'desc_tip'    => true,
		'description' => __( 'Title of the Apple Pay gateway' ),
	),
	'description'          => array(
		'title'       => __( 'Description', 'wc-stripe-payments' ),
		'type'        => 'text',
		'default'     => '',
		'description' => __( 'Leave blank if you don\'t want a description to show for the gateway.', 'wc-stripe-payments' ),
		'desc_tip'    => true,
	),
	'method_format'        => array(
		'title'       => __( 'Credit Card Display', 'wc-stripe-payments' ),
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'options'     => wp_list_pluck( $this->get_method_formats(), 'example' ),
		'value'       => '',
		'default'     => 'type_ending_in',
		'desc_tip'    => true,
		'description' => __( 'This option allows you to customize how the credit card will display for your customers on orders, subscriptions, etc.' ),
	),
	'charge_type'          => array(
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
	'payment_sections'     => array(
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
	'order_status'         => array(
		'type'        => 'select',
		'title'       => __( 'Order Status', 'wc-stripe-payments' ),
		'default'     => 'default',
		'class'       => 'wc-enhanced-select',
		'options'     => array_merge( array( 'default' => __( 'Default', 'wc-stripe-payments' ) ), wc_get_order_statuses() ),
		'tool_tip'    => true,
		'description' => __( 'This is the status of the order once payment is complete. If <b>Default</b> is selected, then WooCommerce will set the order status automatically based on internal logic which states if a product is virtual and downloadable then status is set to complete. Products that require shipping are set to Processing. Default is the recommended setting as it allows standard WooCommerce code to process the order status.', 'wc-stripe-payments' ),
	),
	'button_section'       => array(
		'type'  => 'title',
		'title' => __( 'Button Settings', 'wc-stripe-payments' ),
	),
	'button_style'         => array(
		'type'        => 'select',
		'title'       => __( 'Button Design', 'wc-stripe-payments' ),
		'class'       => 'wc-enhanced-select',
		'default'     => 'apple-pay-button-black',
		'options'     => array(
			'apple-pay-button-black'           => __( 'Black Button', 'wc-stripe-payments' ),
			'apple-pay-button-white-with-line' => __( 'White With Black Line', 'wc-stripe-payments' ),
			'apple-pay-button-white'           => __( 'White Button', 'wc-stripe-payments' ),
		),
		'description' => __( 'This is the style for all Apple Pay buttons presented on your store.', 'wc-stripe-payments' ),
	),
	'button_type_checkout' => array(
		'title'   => __( 'Checkout button type', 'wc-stripe-payments' ),
		'type'    => 'select',
		'options' => array(
			'plain'     => __( 'Standard Button', 'wc-stripe-payments' ),
			'buy'       => __( 'Buy with Apple Pay', 'wc-stripe-payments' ),
			'check-out' => __( 'Checkout with Apple Pay', 'wc-stripe-payments' )
		),
		'default' => 'plain',
	),
	'button_type_cart'     => array(
		'title'   => __( 'Cart button type', 'wc-stripe-payments' ),
		'type'    => 'select',
		'options' => array(
			'plain'     => __( 'Standard Button', 'wc-stripe-payments' ),
			'buy'       => __( 'Buy with Apple Pay', 'wc-stripe-payments' ),
			'check-out' => __( 'Checkout with Apple Pay', 'wc-stripe-payments' )
		),
		'default' => 'plain',
	),
	'button_type_product'  => array(
		'title'   => __( 'Product button type', 'wc-stripe-payments' ),
		'type'    => 'select',
		'options' => array(
			'plain'     => __( 'Standard Button', 'wc-stripe-payments' ),
			'buy'       => __( 'Buy with Apple Pay', 'wc-stripe-payments' ),
			'check-out' => __( 'Checkout with Apple Pay', 'wc-stripe-payments' )
		),
		'default' => 'buy',
	),
);
