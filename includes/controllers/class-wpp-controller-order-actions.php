<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @author WpPayments
 * @package Stripe/Controllers
 *
 */
class WPP_Controller_Order_Actions extends WPP_Rest_Controller {

	use WPP_Controller_MF_Trait;

	protected $namespace = 'order~action';

	public function register_routes() {
		register_rest_route(
			$this->rest_uri(),
			'capture',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'capture' ),
				'permission_callback' => array( $this, 'order_actions_permission_check' ),
				'args'                => array(
					'order_id' => array(
						'required'          => true,
						'type'              => 'int',
						'validate_callback' => array( $this, 'validate_order_id' ),
					),
					'amount'   => array(
						'required' => true,
						'type'     => 'float',
					),
				),
			)
		);
		register_rest_route(
			$this->rest_uri(),
			'void',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'void' ),
				'permission_callback' => array( $this, 'order_actions_permission_check' ),
				'args'                => array(
					'order_id' => array(
						'required'          => true,
						'type'              => 'number',
						'validate_callback' => array(
							$this,
							'validate_order_id',
						),
					),
				),
			)
		);
		register_rest_route(
			$this->rest_uri(),
			'pay',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'process_payment' ),
				'permission_callback' => array( $this, 'order_actions_permission_check' ),
				'args'                => array(
					'order_id' => array(
						'required'          => true,
						'type'              => 'number',
						'validate_callback' => array(
							$this,
							'validate_order_id',
						),
					),
				),
			)
		);
		register_rest_route(
			$this->rest_uri(),
			'customer-payment-methods',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'customer_payment_methods' ),
				'permission_callback' => array( $this, 'order_actions_permission_check' ),
				'args'                => array(
					'customer_id' => array(
						'required' => true,
						'type'     => 'number',
					),
				),
			)
		);
		register_rest_route(
			$this->rest_uri(),
			'charge-view',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'charge_view' ),
				'permission_callback' => array( $this, 'order_actions_permission_check' ),
				'args'                => array(
					'order_id' => array(
						'required' => true,
						'type'     => 'number',
					),
				),
			)
		);
	}

	/**
	 * Return true if the order_id is a valid post.
	 *
	 * @param int $order_id
	 */
	public function validate_order_id( $order_id ) {
		return null !== get_post( $order_id );
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function capture( $request ) {
		$order_id = $request->get_param( 'order_id' );
		$order    = wc_get_order( $order_id );
		$amount   = $request->get_param( 'amount' );
		if ( ! is_numeric( $amount ) ) {
			return new WP_Error(
				'invalid_data',
				__( 'Invalid amount entered.', 'wc-stripe-payments' ),
				array(
					'success' => false,
					'status'  => 200,
				)
			);
		}
		try {
			/**
			 *
			 * @var WPP_Payment_Gateway_Stripe $gateway
			 */
			$gateway = WC()->payment_gateways()->payment_gateways()[ $order->get_payment_method() ];
			$result  = $gateway->capture_charge( $amount, $order );
			if ( is_wp_error( $result ) ) {
				throw new Exception( $result->get_error_message() );
			}

			return rest_ensure_response( array() );
		} catch ( Exception $e ) {
			return new WP_Error( 'capture-error', $e->getMessage(), array( 'status' => 200 ) );
		}
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function void( $request ) {
		$order_id = $request->get_param( 'order_id' );
		$order    = wc_get_order( $order_id );
		/**
		 * When the order's status is set to cancelled, the wpp_payment_order_cancelled
		 * function is called, which voids the charge.
		 */
		$order->update_status( 'cancelled' );

		return rest_ensure_response( array() );
	}

	/**
	 * Process a payment as an admin.
	 *
	 * @param WP_REST_Request $request
	 */
	public function process_payment( $request ) {
		$order_id     = $request->get_param( 'order_id' );
		$payment_type = $request->get_param( 'payment_type' );
		$order        = wc_get_order( $order_id );
		$use_token    = $payment_type === 'token';
		try {
			// perform some validations
			if ( $order->get_total() == 0 ) {
				if ( ! wpp_is_subscription_active() ) {
					throw new Exception( __( 'Order total must be greater than zero.', 'wc-stripe-payments' ) );
				} else {
					if ( ! wcs_order_contains_subscription( $order ) ) {
						throw new Exception( __( 'Order total must be greater than zero.', 'wc-stripe-payments' ) );
					}
				}
			}
			// update the order's customer ID if it has changed.
			if ( $order->get_customer_id() != $request->get_param( 'customer_id' ) ) {
				$order->set_customer_id( $request->get_param( 'customer_id' ) );
			}

			if ( $order->get_transaction_id() ) {
				throw new Exception( sprintf( __( 'This order has already been processed. Transaction ID: %1$s. Payment method: %2$s', 'wc-stripe-payments' ), $order->get_transaction_id(), $order->get_payment_method_title() ) );
			}
			if ( ! $use_token ) {
				// only credit card payments are allowed for one off payments as an admin.
				$payment_method = 'wpp_stripe_cc';
			} elseif ( $payment_type === 'token' ) {
				$token_id = intval( $request->get_param( 'payment_token_id' ) );
				$token    = WC_Payment_Tokens::get( $token_id );
				if ( $token->get_user_id() !== $order->get_customer_id() ) {
					throw new Exception( __( 'Order customer Id and payment method customer Id do not match.', 'wc-stripe-payments' ) );
				}
				$payment_method = $token->get_gateway_id();
			}
			/**
			 *
			 * @var WPP_Payment_Gateway_Stripe $gateway
			 */
			$gateway = WC()->payment_gateways()->payment_gateways()[ $payment_method ];
			// temporarily set the charge type of the gateway to whatever the admin has selected.
			$gateway->settings['charge_type'] = $request->get_param( 'wpp_payment_charge_type' );
			// set the payment gateway to the order.
			$order->set_payment_method( $gateway->id );
			$order->save();
			if ( ! $use_token ) {
				$_POST[ $gateway->token_key ] = $request->get_param( 'payment_nonce' );
			} else {
				$gateway->set_payment_method_token( $token->get_token() );
			}

			// set intent attribute off_session. Stripe requires confirm to be true to use off session.
			add_filter( 'wpp_payment_intent_args', function ( $args ) {
				$args['confirm']     = true;
				$args['off_session'] = true;

				return $args;
			} );

			$result = $gateway->process_payment( $order_id );

			if ( isset( $result['result'] ) && $result['result'] === 'success' ) {
				return rest_ensure_response( array( 'success' => true ) );
			} else {
				// create a new order since updates to the order were made during the process_payment call.
				$order = wc_get_order( $order_id );
				$order->update_status( 'pending' );

				return new WP_Error(
					'order-error',
					$this->get_error_messages(),
					array(
						'status'  => 200,
						'success' => false,
					)
				);
			}
		} catch ( Exception $e ) {
			return new WP_Error( 'order-error', '<div class="woocommerce-error">' . $e->getMessage() . '</div>', array( 'status' => 200 ) );
		}
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function customer_payment_methods( $request ) {
		$customer_id = $request->get_param( 'customer_id' );
		$tokens      = array();
		foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
			if ( $gateway instanceof WPP_Payment_Gateway_Stripe ) {
				$tokens = array_merge( $tokens, WC_Payment_Tokens::get_customer_tokens( $customer_id, $gateway->id ) );
			}
		}

		return rest_ensure_response(
			array(
				'payment_methods' => array_map(
					function ( $payment_method ) {
						return $payment_method->to_json();
					},
					$tokens
				),
			)
		);
	}

	/**
	 *
	 * @param WP_REST_Request $request
	 */
	public function charge_view( $request ) {
		$order = wc_get_order( absint( $request->get_param( 'order_id' ) ) );
		/**
		 *
		 * @var WPP_Payment_Gateway_Stripe $payment_method
		 */
		$payment_method = WC()->payment_gateways()->payment_gateways()[ $order->get_payment_method() ];
		try {
			// fetch the charge so data is up to date.
			$charge = WPP_Stripe_Gateway::load( wpp_payment_order_mode( $order ) )->charges->retrieve( $order->get_transaction_id() );

			if ( is_wp_error( $charge ) ) {
				throw new Exception( $charge->get_error_message() );
			}
			$order->update_meta_data( '_wpp_payment_charge_status', $charge->status );
			$order->save();
			ob_start();
			include stripe_wpp()->plugin_path() . 'includes/admin/meta-boxes/views/html-charge-data-subview.php';
			$html = ob_get_clean();

			return rest_ensure_response(
				array(
					'data' => array(
						'order_id'     => $order->get_id(),
						'order_number' => $order->get_order_number(),
						'charge'       => $charge->jsonSerialize(),
						'html'         => $html,
					),
				)
			);
		} catch ( Exception $e ) {
			return new WP_Error( 'charge-error', $e->getMessage(), array( 'status' => 200 ) );
		}
	}

	/**
	 * @param $request
	 */
	public function order_actions_permission_check( $request ) {
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			return new WP_Error(
				'permission-error',
				__( 'You do not have permissions to access this resource.', 'wc-stripe-payments' ),
				array(
					'status' => 403,
				)
			);
		}

		return true;
	}
}
