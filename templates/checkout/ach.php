<?php
/**
 * @version 3.0.5
 * @var WPP_Gateway_Stripe_ACH $gateway
 */
wpp_payment_hidden_field ( $gateway->metadata_key );
?>
<div id="wpp-payment-ach-container">
	<?php if('sandbox' === $gateway->get_plaid_environment()):?>
	<p><?php esc_html_e('sandbox testing credentials', 'wc-stripe-payments')?>:</p>
	<p><strong><?php esc_html_e('username', 'wc-stripe-payments')?></strong>:&nbsp;user_good</p>
	<p><strong><?php esc_html_e('password', 'wc-stripe-payments')?></strong>:&nbsp;pass_good</p>
	<p><strong><?php esc_html_e('pin', 'wc-stripe-payments')?></strong>:&nbsp;credential_good&nbsp;(<?php esc_html_e('when required', 'wc-stripe-payments')?>)</p>
	<?php endif;?>
</div>