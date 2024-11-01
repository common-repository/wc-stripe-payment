<?php
return array(
	'desc1'            => array(
		'type'        => 'description',
		'description' => '<p><a target="_blank" href="https://pay.google.com/business/console">' . __( 'GPay Business Console', 'wc-stripe-payments' ) .
		                 '</a></p>' .
		                 '<p><a target="_blank" href="https://wppayments.org/docs/google-pay/">Testing GPay</a></p>' .
		                 __( 'When test mode is enabled, Google Pay will work without a merchant ID, allowing you to capture the necessary screenshots the Google API team needs to approve your integration request.', 'wc-stripe-payments' ),
	),
	'desc2'            => array(
		'type'        => 'description',
		'description' => sprintf(
			'<p>%s</p>',
			sprintf(
				__( 'If you don\'t want to request a Google Merchant ID, you can use the %1$sPayment Request Gateway%2$s which has a Google Pay integration through Stripe via the Chrome browser.', 'wc-stripe-payments' ),
				'<a target="_blank" href="' .
				admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wpp_stripe_payment_request' ) . '">',
				'</a>'
			)
		),
	),
	'enabled'          => array(
		'title'       => __( 'Enabled', 'wc-stripe-payments' ),
		'type'        => 'checkbox',
		'default'     => 'no',
		'value'       => 'yes',
		'desc_tip'    => true,
		'description' => __( 'If enabled, your site can accept Google Pay payments through Stripe.', 'wc-stripe-payments' ),
	),
	'general_settings' => array(
		'type'  => 'title',
		'title' => __( 'General Settings', 'wc-stripe-payments' ),
	),
	'merchant_id'      => array(
		'type'        => 'text',
		'title'       => __( 'Merchant ID', 'wc-stripe-payments' ),
		'default'     => '',
		'description' => __( 'Your Google Merchant ID is given to you by the Google API team once you register for Google Pay. While testing in TEST mode you can leave this value blank and Google Pay will work.', 'wc-stripe-payments' ),
	),
	'title_text'       => array(
		'type'        => 'text',
		'title'       => __( 'Title', 'wc-stripe-payments' ),
		'default'     => __( 'Google Pay', 'wc-stripe-payments' ),
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
		'default'     => 'gpay_name',
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
		'description' => __( 'This option determines whether the customer\'s funds are capture immediately or authorized and can be captured at a later date.', 'wc-stripe-payments' ),
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
	'merchant_name'    => array(
		'type'        => 'text',
		'title'       => __( 'Merchant Name', 'wc-stripe-payments' ),
		'default'     => get_bloginfo( 'name' ),
		'description' => __( 'The name of your business as it appears on the Google Pay payment sheet.', 'wc-stripe-payments' ),
		'desc_tip'    => true,
	),
	'icon'             => array(
		'title'       => __( 'Icon', 'wc-stripe-payments' ),
		'type'        => 'select',
		'options'     => array(
			'googlepay_round_outline' => __( 'With Rounded Outline', 'wc-stripe-payments' ),
			'googlepay_outline'       => __( 'With Outline', 'wc-stripe-payments' ),
			'googlepay_standard'      => __( 'Standard', 'wc-stripe-payments' ),
		),
		'default'     => 'googlepay_round_outline',
		'desc_tip'    => true,
		'description' => __( 'This is the icon style that appears next to the gateway on the checkout page. Google\'s API team typically requires the With Outline option on the checkout page for branding purposes.', 'wc-stripe-payments' ),
	),
	'button_section'   => array(
		'type'  => 'title',
		'title' => __( 'Button Options', 'wc-stripe-payments' ),
	),
	'button_color'     => array(
		'title'       => __( 'Button Color', 'wc-stripe-payments' ),
		'type'        => 'select',
		'class'       => 'gpay-button-option button-color',
		'options'     => array(
			'black' => __( 'Black', 'wc-stripe-payments' ),
			'white' => __( 'White', 'wc-stripe-payments' ),
		),
		'default'     => 'black',
		'description' => __( 'The button color of the GPay button.', 'wc-stripe-payments' ),
	),
	'button_style'     => array(
		'title'       => __( 'Button Style', 'wc-stripe-payments' ),
		'type'        => 'select',
		'class'       => 'gpay-button-option button-style',
		'options'     => array(
			'long'  => __( 'Long', 'wc-stripe-payments' ),
			'short' => __( 'Short', 'wc-stripe-payments' ),
		),
		'default'     => 'long',
		'description' => __( 'The button style of the GPay button.', 'wc-stripe-payments' ),
	),
	'button_render'    => array(
		'type'        => 'button_demo',
		'title'       => __( 'Button Design', 'wc-stripe-payments' ),
		'id'          => 'gpay-button',
		'description' => __( 'If you can\'t see the Google Pay button, try switching to a Chrome browser.', 'wc-stripe-payments' ),
	),
);
