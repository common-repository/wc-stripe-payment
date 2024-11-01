<?php
defined( 'ABSPATH' ) || exit();

require_once( WPP_PAYMENT_FILE_PATH . 'includes/abstract/abstract-wpp-stripe-payment.php' );

/**
 *
 * @package Stripe/Classes
 * @author WpPayments
 *
 */
class WPP_Payment_Charge extends WPP_Payment {

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WPP_Payment::process_payment()
	 */
	public function process_payment( $order ) {
		if ( $this->payment_method->should_save_payment_method( $order ) || ( $this->payment_method->supports( 'add_payment_method' ) && apply_filters( 'wpp_payment_force_save_payment_method', false, $order, $this->payment_method ) ) ) {
			$result = $this->payment_method->save_payment_method( $this->payment_method->get_new_source_token(), $order );
			if ( is_wp_error( $result ) ) {
				$this->add_payment_failed_note( $order, $result );

				return $result;
			}
		}

		$args = $this->get_order_charge_args( $args, $order );

		$charge = $this->gateway->charges->mode( wpp_payment_order_mode( $order ) )->create( $args );

		wpp_payment_log_info( 'Stripe charge: ' . print_r( $charge, true ) );

		if ( is_wp_error( $charge ) ) {
			$this->add_payment_failed_note( $order, $charge );

			return $charge;
		}

		return (object) array(
			'complete_payment' => true,
			'charge'           => $charge,
		);
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WPP_Payment::capture_charge()
	 */
	public function capture_charge( $amount, $order ) {
		return $this->gateway->charges->mode( wpp_payment_order_mode( $order ) )->capture(
			$order->get_transaction_id(),
			array(
				'amount' => wpp_payment_add_number_precision(
					$amount,
					$order->get_currency()
				),
			)
		);
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WPP_Payment::void_charge()
	 */
	public function void_charge( $order ) {
		return $this->gateway->refunds->mode( wpp_payment_order_mode( $order ) )->create( array( 'charge' => $order->get_transaction_id() ) );
	}

	public function scheduled_subscription_payment( $amount, $order ) {
		$this->get_order_charge_args( $args, $order );

		$args['source'] = $order->get_meta( '_payment_method_token' );

		if ( ( $customer_id = $order->get_meta( '_wpp_payment_customer' ) ) ) {
			$args['customer'] = $customer_id;
		} elseif ( ( $customer_id = wpp_payment_get_customer_id( $order->get_customer_id(), wpp_payment_order_mode( $order ) ) ) ) {
			$args['customer'] = $customer_id;
		}

		$charge = $this->gateway->charges->mode( wpp_payment_order_mode( $order ) )->create( $args );

		if ( is_wp_error( $charge ) ) {
			return $charge;
		} else {
			return (object) array(
				'complete_payment' => true,
				'charge'           => $charge,
			);
		}
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WPP_Payment::process_pre_order_payment()
	 */
	public function process_pre_order_payment( $order ) {
		$this->get_order_charge_args( $args, $order );

		$args['source'] = $order->get_meta( '_payment_method_token' );

		if ( ( $customer_id = $order->get_meta( '_wpp_payment_customer' ) ) ) {
			$args['customer'] = $customer_id;
		} elseif ( ( $customer_id = wpp_payment_get_customer_id( $order->get_customer_id(), wpp_payment_order_mode( $order ) ) ) ) {
			$args['customer'] = $customer_id;
		}

		$charge = $this->gateway->charges->mode( wpp_payment_order_mode( $order ) )->create( $args );

		if ( is_wp_error( $charge ) ) {
			return $charge;
		} else {
			return (object) array(
				'complete_payment' => true,
				'charge'           => $charge,
			);
		}
	}

	/**
	 *
	 * @param array $args
	 * @param WC_Order $order
	 */
	public function get_order_charge_args( &$args, $order ) {
		$this->add_general_order_args( $args, $order );

		if ( get_option( 'woocommerce_wpp_email_receipt', 'no' ) === 'yes' && ( $email = $order->get_billing_email() ) ) {
			$args['receipt_email'] = $email;
		}
		$args['capture'] = $this->payment_method->get_option( 'charge_type' ) === 'capture';

		$customer_id = wpp_payment_get_customer_id( $order->get_user_id() );

		// only add customer ID if user is paying with a saved payment method
		if ( $customer_id && $this->payment_method->use_saved_source() ) {
			$args['customer'] = $customer_id;
		}

		$this->payment_method->add_stripe_order_args( $args, $order );

		return apply_filters( 'wpp_payment_charge_order_args', $args, $order, $this->payment_method->id );
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WPP_Payment::get_payment_method_from_charge()
	 */
	public function get_payment_method_from_charge( $charge ) {
		return $charge->source->id;
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WPP_Payment::add_order_payment_method()
	 */
	public function add_order_payment_method( &$args, $order ) {
		$args['source'] = $this->payment_method->get_payment_method_from_request();
	}

	/**
	 *
	 * {@inheritDoc}
	 *
	 * @see WPP_Payment::can_void_charge()
	 */
	public function can_void_order( $order ) {
		return $order->get_transaction_id();
	}
}
