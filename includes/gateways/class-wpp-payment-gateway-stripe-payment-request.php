<?php
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WPP_Payment_Gateway_Stripe' ) ) {
	return;
}

/**
 * This gateway is provided so merchants can accept Chrome Payments, Microsoft Pay, etc.
 *
 * @author WpPayments
 * @package Stripe/Gateways
 *
 */
class WPP_Gateway_Stripe_Payment_Request extends WPP_Payment_Gateway_Stripe {

	use WPP_Payment_Intent_Trait;

	protected $payment_method_type = 'card';

	public function __construct() {
		$this->id                 = 'wpp_stripe_payment_request';
		$this->tab_title          = __( 'PaymentRequest Gateway', 'wc-stripe-payments' );
		$this->template_name      = 'payment-request.php';
		$this->token_type         = 'Stripe_CC';
		$this->method_title       = __( 'Stripe Payment Request', 'wc-stripe-payments' );
		$this->method_description = __( 'Gateway that renders based on the user\'s browser. Chrome payment methods, Microsoft pay, etc.', 'wc-stripe-payments' );
		$this->has_digital_wallet = true;
		parent::__construct();
	}

	public function init_supports() {
		parent::init_supports();
		$this->supports[] = 'wp_payment_cart_checkout';
		$this->supports[] = 'wp_payment_product_checkout';
		$this->supports[] = 'wpp_payment_banner_checkout';
		$this->supports[] = 'wp_payment_mini_cart_checkout';
	}

	public function get_icon() {
		return wpp_payment_template_html( 'payment-request-icons.php' );
	}

	public function enqueue_product_scripts( $scripts ) {
		$this->enqueue_checkout_scripts( $scripts );
	}

	public function enqueue_cart_scripts( $scripts ) {
		$this->enqueue_checkout_scripts( $scripts );
	}

	public function enqueue_checkout_scripts( $scripts ) {
		$scripts->enqueue_script(
			'payment-request',
			$scripts->assets_url( 'js/frontend/payment-request.js' ),
			array(
				$scripts->get_handle( 'wpp-payment' ),
				$scripts->get_handle( 'external' ),
			),
			stripe_wpp()->version(),
			true
		);

		$scripts->localize_script( 'payment-request', $this->get_localized_params() );
	}

	public function get_localized_params() {
		return array_merge_recursive(
			parent::get_localized_params(),
			array(
				'button'   => array(
					'type'   => $this->get_option( 'button_type' ),
					'theme'  => $this->get_option( 'button_theme' ),
					'height' => $this->get_button_height(),
				),
				'icons'    => array( 'chrome' => stripe_wpp()->assets_url( 'img/chrome.svg' ) ),
				'messages' => array(
					'invalid_amount' => __( 'Please update you product quantity before paying.', 'wc-stripe-payments' ),
					'add_to_cart'    => __( 'Adding to cart...', 'wc-stripe-payments' ),
					'choose_product' => __( 'Please select a product option before updating quantity.', 'wc-stripe-payments' ),
				),
			)
		);
	}

	public function get_button_height() {
		$value = $this->get_option( 'button_height' );
		$value .= strpos( $value, 'px' ) === false ? 'px' : '';

		return $value;
	}

	public function has_enqueued_scripts( $scripts ) {
		return wp_script_is( $scripts->get_handle( 'payment-request' ) );
	}

	public function process_admin_options() {

		$this->init_settings();

		$post_data = $this->get_post_data();
		
		foreach ( $this->get_form_fields() as $key => $field ) {
			if ( 'title' !== $this->get_field_type( $field ) ) {
				try {
					$get_field_value 		= $this->get_field_value( $key, $field, $post_data );
					$this->settings[ $key ] = $get_field_value;

					if( $key == 'enabled' ) {
						(new WPP_Advanced_Settings)->update_option('stripe_payment_request_enabled', $get_field_value);
					}

				} catch ( Exception $e ) {
					$this->add_error( $e->getMessage() );
				}
			}
		}

	
		return update_option( $this->get_option_key(), apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings ), 'yes' );
	}
}
