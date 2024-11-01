<?php
/**
 * @version 3.1.5
 * 
 * @var WPP_Payment_Gateway_Stripe $gateway
 */
?>
<?php if(($desc = $gateway->get_description())):?>
	<div class="wpp-payment-gateway-desc<?php if($tokens):?> has_tokens<?php endif;?>">
		<?php echo wpautop( wptexturize( $desc ) )?>
	</div>
<?php endif;?>

<div class="wc-<?php echo esc_attr($gateway->id);?>-container wpp-payment-gateway-container<?php if($tokens):?> has_tokens<?php endif;?>">
	<?php if($tokens):?>
	<input type="radio" class="wpp-payment-type"
		id="<?php echo esc_attr($gateway->id);?>_use_new"
		name="<?php echo esc_attr($gateway->payment_type_key);?>" value="new" />
	<label for="<?php echo esc_attr($gateway->id);?>_use_new"
		class="wpp-payment-label-payment-type"><?php echo esc_html($gateway->get_new_method_label());?></label>
<?php endif;?>
	<div class="wc-<?php echo esc_attr($gateway->id)?>-new-method-container"
		<?php if($tokens):?> style="display: none" <?php endif;?>>
		<?php wpp_payment_template('checkout/' . $gateway->template_name, array('gateway' => $gateway))?>
	</div>
	<?php
	if ($tokens) :
		$gateway->saved_payment_methods ( $tokens );
	
	endif;
	?>
</div>