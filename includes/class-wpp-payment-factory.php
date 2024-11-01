<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @author WpPayments
 * @package Stripe/Classes
 *
 */
class WPP_Payment_Factory {

	private static $classes = array(
		'charge'         => 'WPP_Payment_Charge',
		'payment_intent' => 'WPP_Payment_Intent',
	);

	/**
	 *
	 * @param string $type
	 * @param WPP_Payment_Gateway_Stripe $payment_method
	 * @param WPP_Stripe_Gateway $gateway
	 */
	public static function load( $type, $payment_method, $gateway ) {
		$classes = apply_filters( 'wpp_payment_payment_classes', self::$classes );
		if ( ! isset( $classes[ $type ] ) ) {
			throw Exception( 'No class defined for type ' . $type );
		}
		$classname = $classes[ $type ];

		$args = func_get_args();

		if ( count( $args ) > 3 ) {
			$args     = array_slice( $args, 3 );
			$instance = new $classname( $payment_method, $gateway, ...$args );
		} else {
			$instance = new $classname( $payment_method, $gateway );
		}
		return $instance;
	}
}
