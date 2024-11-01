<?php

defined( 'ABSPATH' ) || exit();

/**
 * Actions
 */
add_action( 'woocommerce_payment_token_deleted', 'wpp_payment_woocommerce_payment_token_deleted', 10, 2 );
add_action( 'woocommerce_order_status_cancelled', 'wpp_payment_order_cancelled', 10, 2 );
add_action( 'woocommerce_order_status_completed', 'wpp_payment_order_status_completed', 10, 2 );
add_action( 'wpp_payment_remove_order_locks', 'wpp_payment_remove_order_locks' );
add_action( 'wpp_payment_retry_source_chargeable', 'wpp_payment_retry_source_chargeable' );

/**
 * * Webhook Actions ***
 */
add_action( 'wpp_payment_webhooks_source_chargeable', 'wpp_payment_process_source_chargeable', 10, 2 );
add_action( 'wpp_payment_webhooks_charge_succeeded', 'wpp_payment_process_charge_succeeded', 10, 2 );
add_action( 'wpp_payment_webhooks_charge_failed', 'wpp_payment_process_charge_failed', 10, 2 );
add_action( 'wpp_payment_webhooks_payment_intent_succeeded', 'wpp_payment_process_payment_intent_succeeded', 10, 2 );
add_action( 'wpp_payment_webhooks_charge_refunded', 'wpp_payment_process_create_refund' );
add_action( 'wpp_payment_webhooks_charge_dispute_created', 'wpp_payment_charge_dispute_created', 10, 1 );
add_action( 'wpp_payment_webhooks_charge_dispute_closed', 'wpp_payment_charge_dispute_closed', 10, 1 );
add_action( 'wpp_payment_webhooks_review_opened', 'wpp_payment_review_opened', 10, 1 );
add_action( 'wpp_payment_webhooks_review_closed', 'wpp_payment_review_closed', 10, 1 );

/**
 * Filters
 */
add_filter( 'wpp_payment_api_options', 'wpp_payment_api_options' );
add_filter( 'woocommerce_payment_gateways', 'wpp_payment_payment_gateways' );
add_filter( 'woocommerce_available_payment_gateways', 'wpp_payment_available_payment_gateways' );
add_action( 'woocommerce_process_shop_subscription_meta', 'wpp_payment_process_shop_subscription_meta', 10, 2 );
add_filter( 'woocommerce_payment_complete_order_status', 'wpp_payment_complete_order_status', 10, 3 );
add_filter( 'woocommerce_get_customer_payment_tokens', 'wpp_payment_get_customer_payment_tokens', 10, 3 );
add_filter( 'woocommerce_credit_card_type_labels', 'wpp_payment_credit_card_labels' );
