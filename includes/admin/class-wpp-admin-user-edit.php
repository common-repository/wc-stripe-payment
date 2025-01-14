<?php
defined( 'ABSPATH' ) || exit();

/**
 *
 * @package Stripe/Admin
 * @author WpPayments
 *
 */
class WPP_Admin_User_Edit {

	public static function init() {
		add_action( 'edit_user_profile', array( __CLASS__, 'output' ) );
		add_action( 'show_user_profile', array( __CLASS__, 'output' ) );
		add_action( 'edit_user_profile_update', array( __CLASS__, 'save' ) );
		add_action( 'personal_options_update', array( __CLASS__, 'save' ) );
	}

	/**
	 *
	 * @param WP_User $user
	 */
	public static function output( $user ) {
		// enquue scripts
		wp_enqueue_style( 'wpp-payment-css' );

		remove_filter( 'woocommerce_get_customer_payment_tokens', 'wpp_payment_get_customer_payment_tokens' );
		// get payment methods for all environments.
		$tokens          = WC_Payment_Tokens::get_customer_tokens( $user->ID );
		$payment_methods = array(
			'live' => array(),
			'test' => array(),
		);
		foreach ( $tokens as $token ) {
			if ( $token instanceof WPP_Payment_Token_Stripe ) {
				if ( 'live' === $token->get_environment() ) {
					$payment_methods['live'][] = $token;
				} else {
					$payment_methods['test'][] = $token;
				}
			}
		}


		if ( current_user_can( 'manage_woocommerce' ) ) {
			include stripe_wpp()->plugin_path() . 'includes/admin/views/html-user-profile.php';
		}
	}

	/**
	 *
	 * @param int $user_id
	 */
	public static function save( $user_id ) {
		// only users with "manage_woocommerce" can update the user's Stripe customer ID's.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$modes = array( 'test', 'live' );
		if ( isset( $_POST['wp_payment_live_id'] ) ) {
			$old_live_id = wpp_payment_get_customer_id( $user_id, 'live' );
			wpp_payment_delete_customer( $user_id, 'live', true );
			wpp_payment_save_customer( wc_clean( sanitize_text_field($_POST['wp_payment_live_id']) ), $user_id, 'live' );
		}
		if ( isset( $_POST['wp_payment_test_id'] ) ) {
			$old_test_id = wpp_payment_get_customer_id( $user_id, 'test' );
			wpp_payment_delete_customer( $user_id, 'test', true );
			wpp_payment_save_customer( wc_clean( sanitize_text_field( $_POST['wp_payment_test_id'] ) ), $user_id, 'test' );
		}

		// check if admin want's to delete any payment methods
		foreach ( $modes as $mode ) {
			if ( isset( $_POST[ $mode . '_payment_method_actions' ] ) ) {
				switch ( wc_clean( $_POST[ $mode . '_payment_method_actions' ] ) ) {
					case 'delete':
						if ( isset( $_POST['payment_methods'], $_POST['payment_methods'][ $mode ] ) ) {
							$tokens = wc_clean( sanitize_text_field( $_POST['payment_methods'][ $mode ] ) );
							foreach ( $tokens as $token_id ) {
								WC_Payment_Tokens::delete( absint( $token_id ) );
							}
						}
						break;
				}
			}
		}

		$changes = array(
			'live' => $old_live_id !== wpp_payment_get_customer_id( $user_id, 'live' ),
			'test' => $old_test_id !== wpp_payment_get_customer_id( $user_id, 'test' ),
		);

		// this will prevent the payment method from being deleted in Stripe. We only want to remove the tokens
		// from the WC tables.
		remove_action( 'woocommerce_payment_token_deleted', 'wpp_payment_woocommerce_payment_token_deleted', 10 );

		// want results to return tokens for all modes
		remove_action( 'woocommerce_get_customer_payment_tokens', 'wpp_payment_get_customer_payment_tokens' );

		// if the value has changed, then remove old payment methods and import new ones.
		foreach ( $changes as $mode => $change ) {
			if ( $change ) {
				// Delete all current payment methods in WC then save new ones.
				$tokens = WC_Payment_Tokens::get_customer_tokens( $user_id );
				foreach ( $tokens as $token ) {
					if ( $token instanceof WPP_Payment_Token_Stripe ) {
						if ( $mode === $token->get_environment() ) {
							WC_Payment_Tokens::delete( $token->get_id() );
						}
					}
				}
				// import payment methods from Stripe.
				if ( ( $customer_id = wpp_payment_get_customer_id( $user_id, $mode ) ) ) {
					WPP_Payment_Customer_Manager::wpp_product_methods_sync( $customer_id, $user_id, $mode );
				}
			}
		}
	}
}

WPP_Admin_User_Edit::init();
