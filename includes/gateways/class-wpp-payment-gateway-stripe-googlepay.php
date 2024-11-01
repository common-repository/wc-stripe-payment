<?php
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'WPP_Payment_Gateway_Stripe' ) ) {
	return;
}

/**
 *
 * @author WpPayments
 * @package Stripe/Gateways
 */
class WPP_Gateway_Stripe_GooglePay extends WPP_Payment_Gateway_Stripe {

	use WPP_Payment_Intent_Trait;

	protected $payment_method_type = 'card';

	public function __construct() {
		$this->id                 = 'wpp_stripe_googlepay';
		$this->tab_title          = __( 'Google Pay', 'wc-stripe-payments' );
		$this->template_name      = 'googlepay.php';
		$this->token_type         = 'Stripe_GooglePay';
		$this->method_title       = __( 'Stripe Google Pay', 'wc-stripe-payments' );
		$this->method_description = __( 'Google Pay gateway that integrates with your Stripe account.', 'wc-stripe-payments' );
		$this->has_digital_wallet = true;
		parent::__construct();
		$this->icon = stripe_wpp()->assets_url( 'img/' . $this->get_option( 'icon' ) . '.svg' );
	}

	public function init_supports() {
		parent::init_supports();
		$this->supports[] = 'wp_payment_cart_checkout';
		$this->supports[] = 'wp_payment_product_checkout';
		$this->supports[] = 'wpp_payment_banner_checkout';
		$this->supports[] = 'wp_payment_mini_cart_checkout';
	}

	public function enqueue_checkout_scripts( $scripts ) {
		$scripts->enqueue_script(
			'googlepay-checkout',
			$scripts->assets_url( 'js/frontend/googlepay-checkout.js' ),
			array(
				$scripts->get_handle( 'wpp-payment' ),
				$scripts->get_handle( 'gpay' ),
			),
			stripe_wpp()->version(),
			true
		);
		$scripts->localize_script( 'googlepay-checkout', $this->get_localized_params() );
	}

	public function enqueue_product_scripts( $scripts ) {
		$scripts->enqueue_script(
			'googlepay-product',
			$scripts->assets_url( 'js/frontend/googlepay-product.js' ),
			array(
				$scripts->get_handle( 'wpp-payment' ),
				$scripts->get_handle( 'gpay' ),
			),
			stripe_wpp()->version(),
			true
		);
		$scripts->localize_script( 'googlepay-product', $this->get_localized_params() );
	}

	public function enqueue_cart_scripts( $scripts ) {
		$scripts->enqueue_script(
			'googlepay-cart',
			$scripts->assets_url( 'js/frontend/googlepay-cart.js' ),
			array(
				$scripts->get_handle( 'wpp-payment' ),
				$scripts->get_handle( 'gpay' ),
			),
			stripe_wpp()->version(),
			true
		);
		$scripts->localize_script( 'googlepay-cart', $this->get_localized_params() );
	}

	public function enqueue_admin_scripts() {
		wp_register_script( 'gpay-external', stripe_wpp()->scripts()->global_scripts['gpay'], array(), stripe_wpp()->version(), true );
		wp_enqueue_script(
			'wpp-payment-gpay-admin',
			stripe_wpp()->assets_url( 'js/admin/googlepay.js' ),
			array(
				'gpay-external',
				'wpp-admin-settings',
			),
			stripe_wpp()->version(),
			true
		);
	}

	public function get_localized_params() {
		$data = array_merge_recursive(
			parent::get_localized_params(),
			array(
				'environment'        => wpp_payment_mode() === 'test' ? 'TEST' : 'PRODUCTION',
				'merchant_id'        => wpp_payment_mode() === 'test' ? '' : $this->get_option( 'merchant_id' ),
				'merchant_name'      => $this->get_option( 'merchant_name' ),
				'processing_country' => WC()->countries ? WC()->countries->get_base_country() : wc_get_base_location()['country'],
				'button_color'       => $this->get_option( 'button_color' ),
				'button_style'       => $this->get_option( 'button_style' ),
				'button_size_mode'   => 'fill',
				'total_price_label'  => __( 'Total', 'wc-stripe-payments' ),
				'routes'             => array( 'payment_data' => WPP_Rest_API::get_endpoint( stripe_wpp()->rest_api->googlepay->rest_uri( 'shipping-data' ) ) ),
				'messages'           => array( 'invalid_amount' => __( 'Please update you product quantity before using Google Pay.', 'wc-stripe-payments' ) )
			)
		);

		return $data;
	}

	protected function get_display_item_for_cart( $price, $label, $type, ...$args ) {
		switch ( $type ) {
			case 'tax':
				$type = 'TAX';
				break;
			default:
				$type = 'LINE_ITEM';
				break;
		}

		return array(
			'label' => $label,
			'type'  => $type,
			'price' => wc_format_decimal( $price, 2 )
		);
	}

	protected function get_display_item_for_product( $product ) {
		return array(
			'label' => esc_attr( $product->get_name() ),
			'type'  => 'SUBTOTAL',
			'price' => wc_format_decimal( $product->get_price(), 2 )
		);
	}

	protected function get_display_item_for_order( $price, $label, $order, $type, ...$args ) {
		switch ( $type ) {
			case 'tax':
				$type = 'TAX';
				break;
			default:
				$type = 'LINE_ITEM';
				break;
		}

		return array(
			'label' => $label,
			'type'  => $type,
			'price' => wc_format_decimal( $price, 2 )
		);
	}

	public function get_formatted_shipping_methods( $methods = array() ) {
		$methods = parent::get_formatted_shipping_methods( $methods );
		if ( empty( $methods ) ) {
			// GPay does not like empty shipping methods. Make a temporary one;
			$methods[] = array(
				'id'          => 'default',
				'label'       => __( 'Waiting...', 'wc-stripe-payments' ),
				'description' => __( 'loading shipping methods...', 'wc-stripe-payments' ),
			);
		}

		return $methods;
	}

	public function get_formatted_shipping_method( $price, $rate, $i, $package, $incl_tax ) {
		return array(
			'id'          => $this->get_shipping_method_id( $rate->id, $i ),
			'label'       => $this->get_formatted_shipping_label( $price, $rate, $incl_tax ),
			'description' => ''
		);
	}

	/**
	 * @param float $price
	 * @param WC_Shipping_Rate $rate
	 * @param bool $incl_tax
	 *
	 * @return string|void
	 */
	protected function get_formatted_shipping_label( $price, $rate, $incl_tax ) {
		$label = sprintf( '%s: %s %s', esc_attr( $rate->get_label() ), number_format( $price, 2 ), get_woocommerce_currency() );
		if ( $incl_tax ) {
			if ( $rate->get_shipping_tax() > 0 && ! wc_prices_include_tax() ) {
				$label .= ' ' . WC()->countries->inc_tax_or_vat();
			}
		} else {
			if ( $rate->get_shipping_tax() > 0 && wc_prices_include_tax() ) {
				$label .= ' ' . WC()->countries->ex_tax_or_vat();
			}
		}

		return $label;
	}

	/**
	 * Return a formatted shipping method label.
	 * <strong>Example</strong>&nbsp;5 Day shipping: 5 USD
	 *
	 * @param WC_Shipping_Rate $rate
	 *
	 * @return
	 * @deprecated
	 *
	 */
	public function get_shipping_method_label( $rate ) {
		$incl_tax = wpp_payment_display_prices_including_tax();
		$price    = $incl_tax ? $rate->cost + $rate->get_shipping_tax() : $rate->cost;

		return $this->get_formatted_shipping_label( $price, $rate, $incl_tax );
	}

	public function add_to_cart_response( $data ) {
		$data['googlepay']['displayItems'] = $this->get_display_items();

		return $data;
	}

	/**
	 * @param array $deps
	 * @param $scripts
	 *
	 * @return array
	 */
	public function get_mini_cart_dependencies( $deps, $scripts ) {
		if ( $this->mini_cart_enabled() ) {
			$deps[] = $scripts->get_handle( 'gpay' );
		}

		return $deps;
	}

	public function has_enqueued_scripts( $scripts ) {
		return wp_script_is( $scripts->get_handle( 'googlepay-checkout' ) );
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
						(new WPP_Advanced_Settings)->update_option('stripe_googlepay_enabled', $get_field_value);
					}

				} catch ( Exception $e ) {
					$this->add_error( $e->getMessage() );
				}
			}
		}

	
		return update_option( $this->get_option_key(), apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings ), 'yes' );
	}
}
