<?php

defined( 'ABSPATH' ) || exit();

/**
 * Processes the charge via webhooks for local payment methods like P24, EPS, etc.
 *
 * @param \Stripe\Source  $source
 * @param WP_REST_Request $request
 *
 * @package Stripe/Functions
 */
function wpp_payment_process_source_chargeable( $source, $request ) {
	if ( isset( $source->metadata['order_id'] ) ) {
		$order = wc_get_order( wpp_payment_filter_order_id( $source->metadata['order_id'], $source ) );
	} else {
		// try finding order using source.
		$order = wpp_payment_get_order_from_source_id( $source->id );
	}
	if ( ! $order ) {
		/**
		 * If the order ID metadata is empty, it's possible the source became chargeable before
		 * the plugin had a chance to update the order ID. Schedule a cron job to execute in 60 seconds
		 * so the plugin can update the order ID and the charge can be processed.
		 */
		if ( empty( $source->metadata['order_id'] ) ) {
			if ( method_exists( WC(), 'queue' ) && ! doing_action( 'wpp_payment_retry_source_chargeable' ) ) {
				WC()->queue()->schedule_single( time() + MINUTE_IN_SECONDS, 'wpp_payment_retry_source_chargeable', array( $source->id ) );
			}
		} else {
			wpp_payment_log_error( sprintf( 'Could not create a charge for source %s. No order ID was found in your WordPress database.', $source->id ) );
		}

		return;
	}

	/**
	 *
	 * @var WPP_Payment_Gateway_Stripe $payment_method
	 */
	$payment_method = WC()->payment_gateways()->payment_gateways()[ $order->get_payment_method() ];

	// if the order has a transaction ID, then a charge has already been created.
	if ( $payment_method->has_order_lock( $order ) || ( $transaction_id = $order->get_transaction_id() ) ) {
		wpp_payment_log_info( sprintf( 'source.chargeable event received. Charge has already been created for order %s. Event exited.', $order->get_id() ) );

		return;
	}
	$payment_method->set_order_lock( $order );
	$payment_method->set_new_source_token( $source->id );
	$result = $payment_method->payment_object->process_payment( $order );

	if ( ! is_wp_error( $result ) && $result->complete_payment ) {
		$payment_method->payment_object->payment_complete( $order, $result->charge );
	}
}

/**
 * When the charge has succeeded, the order should be completed.
 *
 * @param \Stripe\Charge  $charge
 * @param WP_REST_Request $request
 *
 * @package Stripe/Functions
 */
function wpp_payment_process_charge_succeeded( $charge, $request ) {
	// charges that belong to a payment intent can be  skipped
	// because the payment_intent.succeeded event will be called.
	if ( $charge->payment_intent ) {
		return;
	}
	$order = wc_get_order( wpp_payment_filter_order_id( $charge->metadata['order_id'], $charge ) );
	if ( ! $order ) {
		wpp_payment_log_error( sprintf( 'Could not complete payment for charge %s. No order ID %s was found in your WordPress database.', $charge->id, $charge->metadata['order_id'] ) );

		return;
	}

	/**
	 *
	 * @var WPP_Payment_Gateway_Stripe $payment_method
	 */
	$payment_method = WC()->payment_gateways()->payment_gateways()[ $order->get_payment_method() ];
	/**
	 * Make sure the payment method is asynchronous because synchronous payments are handled via the source.chargeable event which processes the payment.
	 * This event is relevant for payment methods that receive a charge.succeeded event at some arbitrary amount of time
	 * after the source is chargeable.
	 */
	if ( $payment_method instanceof WPP_Payment_Gateway_Stripe && ! $payment_method->synchronous ) {
		// If the order's charge status is not equal to charge status from Stripe, then complete_payment.
		if ( $order->get_meta( '_wpp_payment_charge_status' ) != $charge->status ) {
			// want to prevent plugin from processing capture_charge since charge has already been captured.
			remove_action( 'woocommerce_order_status_completed', 'wpp_payment_order_status_completed' );

			if ( stripe_wpp()->advanced_settings->is_fee_enabled() ) {
				// retrieve the balance transaction
				$balance_transaction = WPP_Stripe_Gateway::load()->mode( wpp_payment_order_mode( $order ) )->balanceTransactions->retrieve( $charge->balance_transaction );
				if ( ! is_wp_error( $balance_transaction ) ) {
					$charge->balance_transaction = $balance_transaction;
				}
			}
			// call payment complete so shipping, emails, etc are triggered.
			$payment_method->payment_object->payment_complete( $order, $charge );
			$order->add_order_note( __( 'Charge.succeeded webhook received. Payment has been completed.', 'wc-stripe-payments' ) );
		}
	}
}

/**
 *
 * @param \Stripe\PaymentIntent $intent
 * @param WP_REST_Request       $request
 *
 * @package Stripe/Functions
 */
function wpp_payment_process_payment_intent_succeeded( $intent, $request ) {
	$order = wc_get_order( wpp_payment_filter_order_id( $intent->metadata['order_id'], $intent ) );
	if ( ! $order ) {
		wpp_payment_log_error( sprintf( 'Could not complete payment for payment_intent %s. No order ID was found in your WordPress database.', $intent->id ) );

		return;
	}
	$payment_method = WC()->payment_gateways()->payment_gateways()[ $order->get_payment_method() ];
}

/**
 *
 * @param \Stripe\Charge  $charge
 * @param WP_REST_Request $request
 *
 * @package Stripe/Functions
 */
function wpp_payment_process_charge_failed( $charge, $request ) {
	$order = wc_get_order( wpp_payment_filter_order_id( $charge->metadata['order_id'], $charge ) );

	if ( $order ) {
		$payment_methods = WC()->payment_gateways()->payment_gateways();
		if ( isset( $payment_methods[ $order->get_payment_method() ] ) ) {
			/**
			 *
			 * @var WPP_Payment_Gateway_Stripe $payment_method
			 */
			$payment_method = $payment_methods[ $order->get_payment_method() ];
			// only update order status if this is an asynchronous payment method,
			// and there is no completed date on the order. If there is a complete date it
			// means payment_complete was called on the order at some point
			if ( ! $payment_method->synchronous && ! $order->get_date_completed() ) {
				$order->update_status( apply_filters( 'wpp_payment_charge_failed_status', 'failed' ), $charge->failure_message );
			}
		}
	}
}

/**
 * Function that processes the charge.refund webhook. If the refund is created in the Stripe dashboard, a
 * refund will be created in the WC system to keep WC and Stripe in sync.
 *
 * @param \Stripe\Charge $charge
 *
 */
function wpp_payment_process_create_refund( $charge ) {
	$mode  = $charge->livemode ? 'live' : 'test';
	$order = null;
	// get the order ID from the charge
	if ( isset( $charge->metadata['order_id'] ) ) {
		$order = wc_get_order( absint( $charge->metadata['order_id'] ) );
	} else {
		// charge didn't have order ID for whatever reason, so get order from charge ID
		$order = wpp_payment_get_order_from_transaction( $charge->id );
	}
	try {
		if ( ! $order ) {
			throw new Exception( sprintf( 'Could not match order with charge %s.', $charge->id ) );
		}
		// get the list of refunds and loop through them. Find the refund that doesn't have the required metadata attributes.
		foreach ( $charge->refunds as $refund ) {
			/**
			 * @var \Stripe\Refund $refund
			 */
			// refund was not created via WC
			if ( ! isset( $refund->metadata['order_id'], $refund->metadata['created_via'] ) ) {
				$args = array(
					'amount'         => wpp_payment_remove_number_precision( $refund->amount, $order->get_currency() ),
					'order_id'       => $order->get_id(),
					'reason'         => $refund->reason,
					'refund_payment' => false
				);
				// if the order has been fully refunded, items should be re-stocked
				if ( $order->get_total() == ( $args['amount'] + $order->get_total_refunded() ) ) {
					$args['restock_items'] = true;
					$line_items            = array();
					foreach ( $order->get_items() as $item_id => $item ) {
						$line_items[ $item_id ] = array(
							'qty' => $item->get_quantity()
						);
					}
					$args['line_items'] = $line_items;
				}
				// create the refund
				$result = wc_create_refund( $args );

				// Update the refund in Stripe with metadata
				if ( ! is_wp_error( $result ) ) {
					$client = WPP_Stripe_Gateway::load( $mode );
					$order->add_order_note( sprintf( __( 'Order refunded in Stripe. Amount: %s', 'wc-stripe-payments' ), $result->get_formatted_refund_amount() ) );
					$client->refunds->update( $refund->id, array(
						'metadata' => array(
							'order_id'    => $order->get_id(),
							'created_via' => 'stripe_dashboard'
						)
					) );
					if ( stripe_wpp()->advanced_settings->is_fee_enabled() ) {
						// retrieve the charge but with expanded objects so fee and net can be calculated.
						$charge = $client->charges->retrieve( $charge->id, array( 'expand' => array( 'balance_transaction', 'refunds.data.balance_transaction' ) ) );
						if ( ! is_wp_error( $charge ) ) {
							WPP_Utils::add_balance_transaction_to_order( $charge, $order, true );
						}
					}
				} else {
					throw new Exception( $result->get_error_message() );
				}
			}
		}
	}
	catch ( Exception $e ) {
		wpp_payment_log_error( sprintf( 'Error processing refund webhook. Error: %s', $e->getMessage() ) );
	}
}

/**
 * @param $source_id
 *
 * @throws \Stripe\Exception\ApiErrorException
 */
function wpp_payment_retry_source_chargeable( $source_id ) {
	$source = WPP_Stripe_Gateway::load()->sources->retrieve( $source_id );
	if ( ! is_wp_error( $source ) ) {
		wpp_payment_log_info( sprintf( 'Processing source.chargeable via scheduled action. Source ID %s', $source_id ) );
		wpp_payment_process_source_chargeable( $source, null );
	}
}

/**
 * @param Stripe\Dispute $dispute
 */
function wpp_payment_charge_dispute_created( $dispute ) {
	if ( stripe_wpp()->advanced_settings->is_dispute_created_enabled() ) {
		$order = wpp_payment_get_order_from_transaction( $dispute->charge );
		if ( ! $order ) {
			wpp_payment_log_info( sprintf( 'No order found for charge %s. Dispute %s', $dispute->charge, $dispute->id ) );
		} else {
			$current_status = $order->get_status();
			$message        = sprintf( __( 'A dispute has been created for charge %1$s. Dispute status: %2$s.', 'wc-stripe-payments' ), $dispute->charge, strtoupper( $dispute->status ) );
			$order->update_status( apply_filters( 'wpp_payment_dispute_created_order_status', stripe_wpp()->advanced_settings->get_option( 'dispute_created_status', 'on-hold' ), $dispute, $order ),
				$message );

			// update the dispute with metadata that can be used later
			WPP_Stripe_Gateway::load( wpp_payment_order_mode( $order ) )->disputes->update( $dispute->id, array(
				'metadata' => array(
					'order_id'          => $order->get_id(),
					'prev_order_status' => $current_status
				)
			) );
			// @todo send an email to the admin so they know a dispute was created
		}
	}
}

/**
 * @param Stripe\Dispute $dispute
 */
function wpp_payment_charge_dispute_closed( $dispute ) {
	if ( stripe_wpp()->advanced_settings->is_dispute_closed_enabled() ) {
		if ( isset( $dispute->metadata['order_id'] ) ) {
			$order = wc_get_order( absint( $dispute->metadata['order_id'] ) );
		} else {
			$order = wpp_payment_get_order_from_transaction( $dispute->charge );
		}
		if ( ! $order ) {
			return wpp_payment_log_info( sprintf( 'No order found for charge %s. Dispute %s', $dispute->charge, $dispute->id ) );
		}
		$message = sprintf( __( 'Dispute %1$s has been closed. Result: %2$s.', 'wc-stripe-payments' ), $dispute->id, $dispute->status );
		switch ( $dispute->status ) {
			case 'won':
				//set the order's status back to what it was before the dispute
				if ( isset( $dispute->metadata['prev_order_status'] ) ) {
					$status = $dispute->metadata['prev_order_status'];
				} else {
					$status = $order->needs_processing() ? 'processing' : 'completed';
				}
				$order->update_status( $dispute->metadata['prev_order_status'], $message );
				break;
			case 'lost':
				$order->update_status( apply_filters( 'wpp_payment_dispute_closed_order_status', 'failed', $dispute, $order ), $message );
		}
	}
}

/**
 * @param Stripe\Review $review
 */
function wpp_payment_review_opened( $review ) {
	if ( stripe_wpp()->advanced_settings->is_review_opened_enabled() ) {
		$order = wpp_payment_get_order_from_transaction( $review->charge );
		if ( $order ) {
			$status = $order->get_status();
			$order->update_meta_data( '_stripe_prev_status', $status );
			$message = sprintf( __( 'A review has been opened for charge %1$s. Reason: %2$s.', 'wc-stripe-payments' ), $review->charge, strtoupper( $review->reason ) );
			$order->update_status( apply_filters( 'wpp_payment_review_opened_order_status', 'on-hold', $review, $order ), $message );
		}
	}
}

/**
 * @param Stripe\Review $review
 */
function wpp_payment_review_closed( $review ) {
	if ( stripe_wpp()->advanced_settings->is_review_closed_enabled() ) {
		$order = wpp_payment_get_order_from_transaction( $review->charge );
		if ( $order ) {
			$status = $order->get_meta( '_stripe_prev_status' );
			if ( ! $status ) {
				$status = $order->needs_processing() ? 'processing' : 'completed';
			}
			$order->delete_meta_data( '_stripe_prev_status' );
			$message = sprintf( __( 'A review has been closed for charge %1$s. Reason: %2$s.', 'wc-stripe-payments' ), $review->charge, strtoupper( $review->reason ) );
			$order->update_status( $status, $message );
		}
	}
}
