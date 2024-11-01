<?php
/**
 * @version 3.0.0
 * 
 * @var WPP_Payment_Gateway_Stripe[] $gateways
 */
?>
<div class="wpp-payment-banner-checkout">
	<span class="banner-title"><?php esc_html_e('Express Checkout', 'wc-stripe-payments')?></span>
	<ul class="wpp_payment_checkout_banner_gateways" style="list-style: none">
		<?php foreach($gateways as $gateway):?>
			<li class="wpp-payment-checkout-banner-gateway banner_payment_method_<?php echo esc_attr($gateway->id);?>">
				
			</li>
		<?php endforeach;?>
	</ul>
</div>