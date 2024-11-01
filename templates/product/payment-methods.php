<?php
/**
 * @version 3.0.0
 */
?>
<div class="wpp-clear"></div>
<div class="wpp-payment-product-checkout-container <?php echo esc_attr($position);?>">
	<ul class="wpp_payment_product_payment_methods" style="list-style: none">
		<?php foreach($gateways as $gateway):?>
			<li class="payment_method_<?php echo esc_attr($gateway->id)?>">
				<div class="payment-box">
					<?php $gateway->product_fields()?>
				</div>
			</li>
		<?php endforeach;?>
	</ul>
</div>