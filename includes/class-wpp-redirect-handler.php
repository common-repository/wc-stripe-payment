<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @author WpPayments
 * @package Stripe/Classes
 *
 */
class WPP_Payment_Redirect_Handler {

	public static function init() {
		add_action( 'get_header', array( __CLASS__, 'maybe_restore_cart' ), 100 );
	}

	/**
	 */
	public static function process_redirect() {
		if ( isset( $_GET['source'] ) ) {
			$result        = WPP_Stripe_Gateway::load()->sources->retrieve( wc_clean( sanitize_text_field($_GET['source']) ) );
			$client_secret = isset( $_GET['client_secret'] ) ? sanitize_text_field($_GET['client_secret']) : '';
		} else {
			$result        = WPP_Stripe_Gateway::load()->paymentIntents->retrieve( wc_clean( sanitize_text_field($_GET['payment_intent']) ) );
			$client_secret = isset( $_GET['payment_intent_client_secret'] ) ? sanitize_text_field($_GET['payment_intent_client_secret']) : '';
		}
		if ( is_wp_error( $result ) ) {
			wc_add_notice( sprintf( __( 'Error retrieving payment source. Reason: %s', 'wc-stripe-payments' ), $result->get_error_message() ), 'error' );
		} elseif ( ! hash_equals( $client_secret, $result->client_secret ) ) {
			wc_add_notice( __( 'This request is invalid. Please try again.', 'wc-stripe-payments' ), 'error' );
		} else {
			define( 'redirect_handler', true );
			$order_id = $result->metadata['order_id'];
			$order    = wc_get_order( wpp_payment_filter_order_id( $order_id, $result ) );

			/**
			 *
			 */
			$payment_method = WC()->payment_gateways()->payment_gateways()[ $order->get_payment_method() ];
			$redirect       = $payment_method->get_return_url( $order );

			if ( in_array( $result->status, array( 'requires_action', 'pending' ) ) ) {
				if ( $result->status === 'pending' ) {
					$order->update_status( 'on-hold' );
				} else {
					return;
				}
			} elseif ( in_array( $result->status, array( 'requires_payment_method', 'failed' ) ) ) {
				wc_add_notice( __( 'Payment authorization failed. Please select another payment method.', 'wc-stripe-payments' ), 'error' );
				if ( $result instanceof \Stripe\PaymentIntent ) {
					$order->update_meta_data( '_payment_intent', $result->jsonSerialize() );
				} else {
					$order->delete_meta_data( 'wpp_payment_source_id' );
				}
				$order->update_status( 'failed', __( 'Payment authorization failed.', 'wc-stripe-payments' ) );

				return;
			} elseif ( 'chargeable' === $result->status ) {
				if ( ! $payment_method->has_order_lock( $order ) && ! $order->get_transaction_id() ) {
					$payment_method->set_order_lock( $order );
					$payment_method->set_new_source_token( $result->id );
					$result = $payment_method->process_payment( $order_id );
					// we don't release the order lock so there aren't conflicts with the source.chargeable webhook
					if ( $result['result'] === 'success' ) {
						$redirect = $result['redirect'];
					}
				}
			} elseif ( in_array( $result->status, array( 'succeeded', 'requires_capture' ) ) ) {
				if ( ! $payment_method->has_order_lock( $order ) ) {
					$payment_method->set_order_lock( $order );
					$result = $payment_method->process_payment( $order_id );
					if ( $result['result'] === 'success' ) {
						$redirect = $result['redirect'];
					}
				}
			}
			wp_safe_redirect( $redirect );
			exit();
		}
	}

	public static function maybe_restore_cart() {
		global $wp;
		if ( isset( $wp->query_vars['order-received'] ) && isset( $_GET['wp_payment_product_checkout'] ) ) {
			add_action( 'woocommerce_cart_emptied', 'wpp_payment_restore_cart_after_product_checkout' );
		}
	}

}

WPP_Payment_Redirect_Handler::init();
