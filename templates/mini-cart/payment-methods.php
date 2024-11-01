<?php
/**
 * @version 3.2.0
 * @var WPP_Payment_Gateway_Stripe[] $gateways
 */
?>
<input type="hidden" class="wpp_payment_mini_cart_payment_methods"/>
<?php foreach ( $gateways as $gateway ) : ?>
	<?php $gateway->mini_cart_fields() ?>
<?php endforeach; ?>



