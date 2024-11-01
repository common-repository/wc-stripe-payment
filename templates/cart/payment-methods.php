<?php
/**
 * @version 3.0.9
 * 
 * @var WPP_Payment_Gateway_Stripe[] $gateways
 */
?>
<div class="wpp-payment-cart-checkout-container" <?php if($cart_total == 0){?>style="display: none"<?php }?>>
	<ul class="wpp_payment_cart_payment_methods" style="list-style: none">
		<?php if($after):?>
				<li class="wpp-payment-method or">
					<p class="wpp-payment-cart-or">
						&mdash;&nbsp;<?php esc_html_e('or', 'wc-stripe-payments')?>&nbsp;&mdash;
					</p>
				</li>
		<?php endif;?>
		<?php foreach($gateways as $gateway):?>
			<li
			class="wpp-payment-method payment_method_<?php echo esc_attr($gateway->id)?>">
			<div class="payment-box">
					<?php $gateway->cart_fields()?>
            </div>
		</li>
		<?php endforeach;?>
		<?php if(!$after):?>
				<li class="wpp-payment-method or">
					<p class="wpp-payment-cart-or">
						&mdash;&nbsp;<?php esc_html_e('or', 'wc-stripe-payments')?>&nbsp;&mdash;
					</p>
				</li>
		<?php endif;?>
	</ul>
</div>
