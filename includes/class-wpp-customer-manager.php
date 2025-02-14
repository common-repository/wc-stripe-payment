<?php
defined( 'ABSPATH' ) || exit();

/**
 * Class that manages customer creation and custom updates.
 *
 * @package Stripe/Classes
 * @author WpPayments
 *
 */
class WPP_Payment_Customer_Manager {

	private static $_instance;

	public static function instance() {
		if ( ! self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {
		add_action( 'woocommerce_checkout_update_customer', array( $this, 'checkout_update_customer' ), 10, 2 );
		add_action( 'wp_loaded', array( $this, 'wp_loaded' ) );
	}

	/**
	 *
	 * @param WC_Customer $customer
	 * @param array $data
	 */
	public function checkout_update_customer( $customer, $data ) {
		if ( $this->has_stripe_customer_id( $customer ) ) {
			if ( $this->should_update_customer( $customer ) ) {
				$result = $this->update_customer( $customer );
				if ( is_wp_error( $result ) ) {
					// add info to log. This error isn't added to wc_add_notice because we don't want a user update to
					// interfere with payment being processed.
					wpp_payment_log_error( sprintf( __( 'Error while saving customer. Reason: %s', 'wc-stripe-payments' ), $result->get_error_message() ) );
				}
			}
		} else {
			// create the customer
			$response = $this->add_new_customer( $customer );
			if ( ! is_wp_error( $response ) ) {
				wpp_payment_save_customer( $response->id, $customer->get_id() );
			} else {
				wc_add_notice( sprintf( __( 'Error while saving customer. Reason: %s', 'wc-stripe-payments' ), $response->get_error_message() ), 'error' );
			}
		}
	}

	/**
	 *
	 * @param WC_Customer $customer
	 *
	 * @return \Stripe\Customer|WP_Error
	 */
	public function add_new_customer( $customer ) {
		return WPP_Stripe_Gateway::load()->customers->create( apply_filters( 'wpp_payment_customer_args', $this->get_customer_args( $customer ) ) );
	}

	/**
	 *
	 * @param WC_Customer $customer
	 */
	public function update_customer( $customer ) {
		return WPP_Stripe_Gateway::load()->customers->update(
			wpp_payment_get_customer_id( $customer->get_id() ),
			apply_filters( 'wpp_payment_update_customer_args', $this->get_customer_args( $customer, 'update' ) )
		);
	}

	/**
	 * Check if the Stripe customer has been created for the WC user.
	 * If there is no Stripe customer then
	 * create one and save it to the WC user.
	 */
	public function wp_loaded() {
		$customer = WC()->customer;
		if ( $customer && $customer->get_id() ) {
			if ( ! $this->has_stripe_customer_id( $customer ) ) {
				$response = $this->add_new_customer( $customer );
				if ( ! is_wp_error( $response ) ) {
					wpp_payment_save_customer( $response->id, $customer->get_id() );
				}
			}
		}
	}

	/**
	 *
	 * @param WC_Customer $customer
	 */
	private function has_stripe_customer_id( $customer ) {
		$id = wpp_payment_get_customer_id( $customer->get_id() );

		// this customer may have an ID from another plugin. Check that too.
		if ( empty( $id ) ) {
			$id = get_user_option( '_stripe_customer_id', $customer->get_id() );
			if ( ! empty( $id ) ) {
				// validate that this customer exists in the Stripe gateway
				$response = WPP_Stripe_Gateway::load()->customers->retrieve( $id );
				if ( ! is_wp_error( $response ) ) {
					// id exists so save customer ID to this plugin's format.
					wpp_payment_save_customer( $id, $customer->get_id() );

					// load this customer's payment methods
					$this->wpp_product_methods_sync( $id, $customer->get_id() );
				} else {
					$id = '';
				}
			}
		}

		return ! empty( $id );
	}

	/**
	 * Syncs the WC database payment methods with the payment methods stored in Stripe.
	 *
	 * @param string $customer_id
	 * @param int $user_id
	 *
	 */
	public static function wpp_product_methods_sync( $customer_id, $user_id, $mode = '' ) {
		$payment_methods = WPP_Stripe_Gateway::load()->paymentMethods->mode( $mode )->all( array(
			'customer' => $customer_id,
			'type'     => 'card',
		) );
		if ( ! is_wp_error( $payment_methods ) ) {
			foreach ( $payment_methods->data as $payment_method ) {
				/**
				 * @var \Stripe\PaymentMethod $payment_method
				 */
				if ( ! WPP_Payment_Token_Stripe::token_exists( $payment_method->id, $user_id ) ) {
					$payment_gateways = WC()->payment_gateways()->payment_gateways();
					$gateway_id       = null;
					$gateway_id       = 'wpp_stripe_cc';
					if ( isset( $payment_method->card->wallet->type ) ) {
						switch ( $payment_method->card->wallet->type ) {
							case 'google_pay':
								$gateway_id = 'wpp_stripe_googlepay';
								break;
							case 'apple_pay':
								$gateway_id = 'wpp_stripe_applepay';
								break;
						}
					}
					/**
					 *
					 * @var WPP_Gateway_Stripe_CC $payment_gateway
					 */ {
						$payment_gateway = $payment_gateways[ $gateway_id ];
					}
					$token = $payment_gateway->get_payment_token( $payment_method->id, $payment_method );
					$token->set_environment( $payment_method->livemode ? 'live' : 'test' );
					$token->set_user_id( $user_id );
					$token->set_customer_id( $customer_id );
					$token->save();
				}
			}
		} else {
			wpp_payment_log_info( sprintf( 'Payment methods for customer %s could not be synced. Reason: %s', $customer_id, $payment_methods->get_error_message() ) );
		}
	}

	/**
	 * Returns true if the customer should be updated in Stripe.
	 *
	 * @param WC_Customer $customer
	 *
	 * @return bool
	 */
	private function should_update_customer( $customer ) {
		$changes = $customer->get_changes();
		if ( ! empty( $changes['billing'] ) ) {
			return array_intersect_key( $changes['billing'], array_flip( $this->get_attribute_keys() ) );
		}

		return false;
	}

	/**
	 * Returns an array of user attributes.
	 *
	 * @return array
	 */
	private function get_attribute_keys() {
		return apply_filters( 'wpp_payments_get_customer_attribute_keys', array(
			'first_name',
			'last_name',
			'email',
			'address_1',
			'address_2',
			'country',
			'state',
			'postcode'
		) );
	}

	/**
	 * Return an array of args used to create or update a customer.
	 *
	 * @param WC_Customer $customer
	 * @param string $context
	 *
	 * @return array
	 */
	private function get_customer_args( $customer, $context = 'create' ) {
		$args = array(
			'email'   => $customer->get_email(),
			'name'    => sprintf( '%s %s', $customer->get_first_name(), $customer->get_last_name() ),
			'phone'   => $customer->get_billing_phone(),
			'address' => array(
				'city'        => $customer->get_billing_city(),
				'country'     => $customer->get_billing_country(),
				'line1'       => $customer->get_billing_address_1(),
				'line2'       => $customer->get_billing_address_2(),
				'postal_code' => $customer->get_billing_postcode(),
				'state'       => $customer->get_billing_state()
			)
		);
		if ( 'create' === $context ) {
			$args['metadata'] = array(
				'user_id'  => $customer->get_id(),
				'username' => $customer->get_username(),
				'website'  => get_site_url(),
			);
		}

		return $args;
	}
}

WPP_Payment_Customer_Manager::instance();
