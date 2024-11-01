<?php
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WPP_Payment_Gateway_Stripe' ) ) {
	return;
}

/**
 *
 * @package Stripe/Gateways
 * @author WpPayments
 *
 */
class WPP_Gateway_Stripe_ApplePay extends WPP_Payment_Gateway_Stripe {

	use WPP_Payment_Intent_Trait;

	protected $payment_method_type = 'card';

	public function __construct() {
		$this->id                 = 'wpp_stripe_applepay';
		$this->tab_title          = __( 'Apple Pay', 'wc-stripe-payments' );
		$this->template_name      = 'applepay.php';
		$this->token_type         = 'Stripe_ApplePay';
		$this->method_title       = __( 'Stripe Apple Pay', 'wc-stripe-payments' );
		$this->method_description = __( 'Apple Pay gateway that integrates with your Stripe account.', 'wc-stripe-payments' );
		$this->has_digital_wallet = true;
		parent::__construct();
		$this->icon = stripe_wpp()->assets_url( 'img/applepay.svg' );
	}

	public function init_supports() {
		parent::init_supports();
		$this->supports[] = 'wp_payment_cart_checkout';
		$this->supports[] = 'wp_payment_product_checkout';
		$this->supports[] = 'wpp_payment_banner_checkout';
		$this->supports[] = 'wp_payment_mini_cart_checkout';
	}

	public function enqueue_product_scripts( $scripts ) {
		$scripts->enqueue_script(
			'applepay-product',
			$scripts->assets_url( 'js/frontend/applepay-product.js' ),
			array(
				$scripts->get_handle( 'wpp-payment' )
			),
			stripe_wpp()->version(),
			true
		);
		$scripts->localize_script( 'applepay-product', $this->get_localized_params() );
	}

	public function enqueue_cart_scripts( $scripts ) {
		$scripts->enqueue_script(
			'applepay-cart',
			$scripts->assets_url( 'js/frontend/applepay-cart.js' ),
			array(
				$scripts->get_handle( 'wpp-payment' )
			),
			stripe_wpp()->version(),
			true
		);
		$scripts->localize_script( 'applepay-cart', $this->get_localized_params() );
	}

	public function enqueue_checkout_scripts( $scripts ) {
		$scripts->enqueue_script(
			'applepay-checkout',
			$scripts->assets_url( 'js/frontend/applepay-checkout.js' ),
			array(
				$scripts->get_handle( 'wpp-payment' )
			),
			stripe_wpp()->version(),
			true
		);
		$scripts->localize_script( 'applepay-checkout', $this->get_localized_params() );
	}

	public function get_localized_params() {
		return array_merge_recursive(
			parent::get_localized_params(),
			array(
				'messages' => array(
					'invalid_amount' => __( 'Please update you product quantity before using Apple Pay.', 'wc-stripe-payments' ),
					'choose_product' => __( 'Please select a product option before updating quantity.', 'wc-stripe-payments' ),
				),
				'button'   => wpp_payment_template_html(
					'applepay-button.php',
					array(
						'style'       => $this->get_option( 'button_style' ),
						'type'        => $this->get_button_type(),
						'button_type' => $this->get_applepay_button_style_type()
					)
				),
			)
		);
	}

	/**
	 * Returns the Apple Pay button type based on the current page.
	 *
	 * @return string
	 */
	protected function get_button_type() {
		if ( is_checkout() ) {
			return $this->get_option( 'button_type_checkout' );
		}
		if ( is_cart() ) {
			return $this->get_option( 'button_type_cart' );
		}
		if ( is_product() ) {
			return $this->get_option( 'button_type_product' );
		}
	}

	public function has_enqueued_scripts( $scripts ) {
		return wp_script_is( $scripts->get_handle( 'applepay-checkout' ) );
	}

	private function get_applepay_button_style_type() {
		$style = $this->get_option( 'button_style' );
		switch ( $style ) {
			case 'apple-pay-button-white':
				return 'white';
			case 'apple-pay-button-white-with-line':
				return 'white-outline';
			default:
				return 'black';
		}
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
						(new WPP_Advanced_Settings)->update_option('stripe_applepay_enabled', $get_field_value);
					}

				} catch ( Exception $e ) {
					$this->add_error( $e->getMessage() );
				}
			}
		}

	
		return update_option( $this->get_option_key(), apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings ), 'yes' );
	}
}
