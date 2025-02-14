<?php

defined( 'ABSPATH' ) || exit();

require_once( WPP_PAYMENT_FILE_PATH . 'includes/abstract/abstract-wpp-stripe-payment.php' );

/**
 *
 *
 * @author WpPayments
 * @package Stripe/Classes
 */
class WPP_Payment_Intent extends WPP_Payment {

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WPP_Payment::process_payment()
	 */
	public function process_payment( $order ) {
		// first check to see if a payment intent can be used
		if ( ( $intent = $this->can_use_payment_intent( $order ) ) ) {
			$intent_id = $intent['id'];
			if ( $this->can_update_payment_intent( $order ) ) {
				$intent = $this->gateway->paymentIntents->update( $intent_id, $this->get_payment_intent_args( $order, false ) );
			} else {
				$intent = $this->gateway->paymentIntents->retrieve( $intent_id, apply_filters( 'wpp_payment_payment_intent_retrieve_args', null, $order, $intent_id ) );
			}
		} else {
			$intent = $this->gateway->paymentIntents->create( $this->get_payment_intent_args( $order ) );
		}

		if ( is_wp_error( $intent ) ) {
			$this->add_payment_failed_note( $order, $intent );

			return $intent;
		}

		// always update the order with the payment intent.
		$order->update_meta_data( '_payment_intent_id', $intent->id );
		$order->update_meta_data( '_payment_method_token', $intent->payment_method );
		$order->update_meta_data( '_wpp_payment_mode', wpp_payment_mode() );
		// serialize the intent and save to the order. The intent will be used to analyze if anything
		// has changed.
		$order->update_meta_data( '_payment_intent', $intent->jsonSerialize() );
		$order->save();

		if ( $intent->status === 'requires_confirmation' ) {
			$intent = $this->gateway->paymentIntents->confirm(
				$intent->id,
				apply_filters( 'wpp_payment_payment_intent_confirmation_args', $this->payment_method->get_payment_intent_confirmation_args( $intent, $order ), $intent, $order )
			);
			if ( is_wp_error( $intent ) ) {
				$this->add_payment_failed_note( $order, $intent );

				return $intent;
			}
		}

		// the intent was processed.
		if ( $intent->status === 'succeeded' || $intent->status === 'requires_capture' ) {
			$charge = $intent->charges->data[0];
			if ( isset( $intent->setup_future_usage, $intent->customer, $charge->payment_method_details ) && 'off_session' === $intent->setup_future_usage ) {
				$this->payment_method->save_payment_method( $intent->payment_method, $order, $charge->payment_method_details );
			}
			// remove metadata that's no longer needed
			$order->delete_meta_data( '_payment_intent' );

			return (object) array(
				'complete_payment' => true,
				'charge'           => $charge,
			);
		}
		if ( in_array( $intent->status, array( 'requires_action', 'requires_payment_method', 'requires_source_action', 'requires_source' ), true ) ) {
			// If the payment method isn't synchronous, set its status to on-hold so if the customer
			// skips the redirect and the payment takes 1 or more days, the payment won't be cancelled
			// due to the WooCommerce pending payment status.
			if ( ! $this->payment_method->synchronous ) {
				$order->update_status( 'on-hold' );
			}

			return (object) array(
				'complete_payment' => false,
				'redirect'         => $this->payment_method->get_payment_intent_checkout_url( $intent, $order ),
			);
		}
	}

	public function scheduled_subscription_payment( $amount, $order ) {
		$args = $this->get_payment_intent_args( $order );

		$args['confirm']        = true;
		$args['off_session']    = true;
		$args['payment_method'] = $this->payment_method->get_order_meta_data( '_payment_method_token', $order );

		if ( ( $customer = $this->payment_method->get_order_meta_data( '_wpp_payment_customer', $order ) ) ) {
			$args['customer'] = $customer;
		}

		$intent = $this->gateway->paymentIntents->mode( wpp_payment_order_mode( $order ) )->create( $args );

		if ( is_wp_error( $intent ) ) {
			return $intent;
		} else {
			$order->update_meta_data( '_payment_intent_id', $intent->id );

			$charge = $intent->charges->data[0];

			if ( $intent->status === 'succeeded' || $intent->status === 'requires_capture' ) {
				return (object) array(
					'complete_payment' => true,
					'charge'           => $charge,
				);
			} else {
				return (object) array(
					'complete_payment' => false,
					'charge'           => $charge,
				);
			}
		}
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WPP_Payment::process_pre_order_payment()
	 */
	public function process_pre_order_payment( $order ) {
		$args = $this->get_payment_intent_args( $order );

		$args['confirm']        = true;
		$args['off_session']    = true;
		$args['payment_method'] = $this->payment_method->get_order_meta_data( '_payment_method_token', $order );

		if ( ( $customer = $this->payment_method->get_order_meta_data( '_wpp_payment_customer', $order ) ) ) {
			$args['customer'] = $customer;
		}

		$intent = $this->gateway->paymentIntents->mode( wpp_payment_order_mode( $order ) )->create( $args );

		if ( is_wp_error( $intent ) ) {
			return $intent;
		} else {
			$order->update_meta_data( '_payment_intent_id', $intent->id );

			$charge = $intent->charges->data[0];

			if ( $intent->status === 'succeeded' || $intent->status === 'requires_capture' ) {
				return (object) array(
					'complete_payment' => true,
					'charge'           => $charge,
				);
			} else {
				return (object) array(
					'complete_payment' => false,
					'charge'           => $charge,
				);
			}
		}
	}

	/**
	 * Compares the order's saved intent to the order's attributes.
	 * If there is a delta, then the payment intent can be updated. The intent should
	 * only be updated if this is the checkout page.
	 *
	 * @param WC_Order $order
	 */
	public function can_update_payment_intent( $order ) {
		if ( ! is_checkout() || defined( 'redirect_handler' ) || defined( 'processing_payment' ) ) {
			return false;
		}
		$intent = $order->get_meta( '_payment_intent' );
		if ( $intent ) {
			$order_hash  = implode(
				'_',
				array(
					wpp_payment_add_number_precision( $order->get_total(), $order->get_currency() ),
					wpp_payment_get_customer_id( $order->get_user_id() ),
					$this->payment_method->get_payment_method_from_request(),
					$this->payment_method->get_payment_method_type(),
				)
			);
			$intent_hash = implode(
				'_',
				array(
					$intent['amount'],
					$intent['customer'],
					$intent['payment_method'],
					isset( $intent['payment_method_types'] ) ? $intent['payment_method_types'][0] : '',
				)
			);

			return $order_hash !== $intent_hash;
		}

		return false;
	}

	/**
	 *
	 * @param WC_Order $order
	 */
	public function get_payment_intent_args( $order, $new = true ) {
		$this->add_general_order_args( $args, $order );

		if ( $new ) {
			$args['confirmation_method'] = $this->payment_method->get_confirmation_method( $order );
			$args['capture_method']      = $this->payment_method->get_option( 'charge_type' ) === 'capture' ? 'automatic' : 'manual';
			$args['confirm']             = false;
		}

		if ( stripe_wpp()->advanced_settings->is_email_receipt_enabled() && ( $email = $order->get_billing_email() ) ) {
			$args['receipt_email'] = $email;
		}

		if ( ( $customer_id = wpp_payment_get_customer_id( $order->get_customer_id() ) ) ) {
			$args['customer'] = $customer_id;
		}

		if ( $this->payment_method->should_save_payment_method( $order )
		     || ( $this->payment_method->supports( 'add_payment_method' )
		          && apply_filters( 'wpp_payment_force_save_payment_method',
					false,
					$order,
					$this->payment_method ) ) ) {
			$args['setup_future_usage'] = 'off_session';
		}

		$args['payment_method_types'][] = $this->payment_method->get_payment_method_type();

		$this->payment_method->add_stripe_order_args( $args, $order );

		/**
		 * @param array                    $args
		 * @param WC_Order                 $order
		 * @param WPP_Payment_Intent $this
		 */
		return apply_filters( 'wpp_payment_intent_args', $args, $order, $this );
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WPP_Payment::capture_charge()
	 */
	public function capture_charge( $amount, $order ) {
		$payment_intent = $this->payment_method->get_order_meta_data( '_payment_intent_id', $order );
		if ( empty( $payment_intent ) ) {
			$charge         = $this->gateway->charges->mode( wpp_payment_order_mode( $order ) )->retrieve( $order->get_transaction_id() );
			$payment_intent = $charge->payment_intent;
			$order->update_meta_data( '_payment_intent_id', $payment_intent );
			$order->save();
		}
		$params = apply_filters( 'wpp_payment_payment_intent_capture_args', array( 'amount_to_capture' => wpp_payment_add_number_precision( $amount, $order->get_currency() ) ), $amount, $order );

		$result = $this->gateway->mode( wpp_payment_order_mode( $order ) )->paymentIntents->capture( $payment_intent, $params );
		if ( ! is_wp_error( $result ) ) {
			return $result->charges->data[0];
		}

		return $result;
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WPP_Payment::void_charge()
	 */
	public function void_charge( $order ) {
		// fetch the intent and check its status
		$payment_intent = $this->gateway->paymentIntents->mode( wpp_payment_order_mode( $order ) )->retrieve( $order->get_meta( '_payment_intent_id' ) );
		if ( is_wp_error( $payment_intent ) ) {
			return $payment_intent;
		}
		$statuses = array( 'requires_payment_method', 'requires_capture', 'requires_confirmation', 'requires_action' );
		if ( 'canceled' !== $payment_intent->status ) {
			if ( in_array( $payment_intent->status, $statuses ) ) {
				return $this->gateway->paymentIntents->mode( wpp_payment_order_mode( $order ) )->cancel( $payment_intent->id );
			} elseif ( 'succeeded' === $payment_intent->status ) {
				return $this->process_refund( $order, $order->get_total() - $order->get_total_refunded() );
			}
		}
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WPP_Payment::get_payment_method_from_charge()
	 */
	public function get_payment_method_from_charge( $charge ) {
		return $charge->payment_method;
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WPP_Payment::add_order_payment_method()
	 */
	public function add_order_payment_method( &$args, $order ) {
		$args['payment_method'] = $this->payment_method->get_payment_method_from_request();
		if ( empty( $args['payment_method'] ) ) {
			unset( $args['payment_method'] );
		}
	}

	/**
	 *
	 * @param WC_Order $order
	 */
	public function can_use_payment_intent( $order ) {
		$intent = $order->get_meta( '_payment_intent' );

		if ( $intent ) {
			if ( $intent['confirmation_method'] != $this->payment_method->get_confirmation_method( $order ) ) {
				return false;
			}
			if ( ! empty( $intent['payment_method_types'] ) && ! in_array( $this->payment_method->get_payment_method_type(), $intent['payment_method_types'] ) ) {
				return false;
			}

			// compare the active environment to the order's environment
			if ( wpp_payment_order_mode( $order ) != wpp_payment_mode() ) {
				return false;
			}

			return $intent;
		}

		return false;
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WPP_Payment::can_void_charge()
	 */
	public function can_void_order( $order ) {
		return $order->get_meta( '_payment_intent_id' );
	}

}
