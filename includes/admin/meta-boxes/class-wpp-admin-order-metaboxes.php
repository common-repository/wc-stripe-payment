<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @package Stripe/Admin
 * @author WpPayments
 *
 */
class WPP_Admin_Order_Metaboxes {

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ), 10, 2 );
	}

	/**
	 *
	 * @param string  $post_type
	 * @param WP_Post $post
	 */
	public static function add_meta_boxes( $post_type, $post ) {
		// only add meta box if shop_order and Stripe gateway was used.
		if ( $post_type !== 'shop_order' ) {
			return;
		}

		add_action( 'woocommerce_admin_order_data_after_order_details', array( __CLASS__, 'pay_order_section' ) );

		$order          = wc_get_order( $post->ID );
		$payment_method = $order->get_payment_method();
		if ( $payment_method ) {
			$gateways = WC()->payment_gateways()->payment_gateways();
			if ( isset( $gateways[ $payment_method ] ) ) {
				$gateway = WC()->payment_gateways()->payment_gateways()[ $payment_method ];
				if ( $gateway instanceof WPP_Payment_Gateway_Stripe ) {
					add_action( 'woocommerce_admin_order_data_after_billing_address', array( __CLASS__, 'charge_data_view' ) );
					add_action( 'woocommerce_admin_order_totals_after_total', array( __CLASS__, 'stripe_fee_view' ) );
				}
			}
		}
		self::enqueue_scripts();
	}

	/**
	 *
	 * @param WC_Order $order
	 */
	public static function charge_data_view( $order ) {
		if ( ( $transaction_id = $order->get_transaction_id() ) ) {
			include 'views/html-order-charge-data.php';
		}
	}

	/**
	 *
	 * @param WC_Order $order
	 */
	public static function pay_order_section( $order ) {
		if ( $order->get_type() === 'shop_order'
		     && $order->has_status( apply_filters( 'wpp_payment_pay_order_statuses', array(
				'pending',
				'auto-draft'
			), $order ) ) ) {
			include 'views/html-order-pay.php';
			$payment_methods = array();
			foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
				if ( $gateway instanceof WPP_Payment_Gateway_Stripe ) {
					$payment_methods = array_merge( $payment_methods, WC_Payment_Tokens::get_customer_tokens( $order->get_user_id(), $gateway->id ) );
				}
			}
			wp_enqueue_script( 'wpp-payment-elements', 'https://js.stripe.com/v3/', array(), stripe_wpp()->version, true );
			wp_localize_script(
				'wpp-payment-elements',
				'wpp_payment_order_pay_params',
				array(
					'api_key'         => wpp_payment_get_publishable_key(),
					'payment_methods' => array_map(
						function ( $payment_method ) {
							return $payment_method->to_json();
						},
						$payment_methods
					),
					'order_status'    => $order->get_status(),
					'messages'        => array(
						'order_status' => __( 'You must create the order before payment can be processed.', 'wc-stripe-payments' )
					)
				)
			);
			wp_enqueue_script( 'wpp-payment-admin-modals', stripe_wpp()->assets_url( 'js/admin/modals.js' ), array(
				'wc-backbone-modal',
				'jquery-blockui'
			), stripe_wpp()->version, true );
		}
	}

	public static function stripe_fee_view( $order_id ) {
		if ( stripe_wpp()->advanced_settings->is_active( 'stripe_fee' ) ) {
			$order = wc_get_order( $order_id );
			$fee   = WPP_Utils::display_fee( $order );
			$net   = WPP_Utils::display_net( $order );
			if ( $fee && $net ) {
				?>
                <tr>
                    <td class="label wpp-payment-fee"><?php esc_html_e( 'Stripe Fee', 'wc-stripe-payments' ) ?>:</td>
                    <td width="1%"></td>
                    <td><?php echo esc_attr( $fee ); ?></td>
                </tr>
                <tr>
                    <td class="label wpp-payment-net"><?php esc_html_e( 'Net payout', 'wc-stripe-payments' ) ?></td>
                    <td width="1%"></td>
                    <td class="total"><?php echo esc_attr($net) ?></td>
                </tr>
				<?php
			}
		}
	}

	public static function enqueue_scripts() {
		wp_enqueue_script( 'wpp-payment-order-metabox', stripe_wpp()->assets_url( 'js/admin/meta-boxes-order.js' ), array(
			'jquery',
			'jquery-blockui'
		), stripe_wpp()->version(), true );

		wp_localize_script(
			'wpp-payment-order-metabox',
			'wpp_payment_order_metabox_params',
			array(
				'_wpnonce' => wp_create_nonce( 'wp_rest' ),
				'routes'   => array(
					'charge_view'     => WPP_Rest_API::get_endpoint( stripe_wpp()->rest_api->order_actions->rest_uri( 'charge-view' ) ),
					'capture'         => WPP_Rest_API::get_endpoint( stripe_wpp()->rest_api->order_actions->rest_uri( 'capture' ) ),
					'void'            => WPP_Rest_API::get_endpoint( stripe_wpp()->rest_api->order_actions->rest_uri( 'void' ) ),
					'pay'             => WPP_Rest_API::get_endpoint( stripe_wpp()->rest_api->order_actions->rest_uri( 'pay' ) ),
					'payment_methods' => WPP_Rest_API::get_endpoint( stripe_wpp()->rest_api->order_actions->rest_uri( 'customer-payment-methods' ) ),
				),
			)
		);
	}

}

WPP_Admin_Order_Metaboxes::init();
