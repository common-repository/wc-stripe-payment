<?php
/**
 * @version 3.3.11
 *
 * @var WPP_Gateway_Stripe_CC $gateway
 */
?>
<?php if ( $gateway->is_custom_form_active() ): ?>
    <div id="wpp-payment-cc-custom-form">
		<?php wpp_payment_template( $gateway->get_custom_form_template(), [ 'gateway' => $gateway ] ) ?>
    </div>
<?php else: ?>
    <div id="wpp-payment-card-element"></div>
<?php endif; ?>
<?php if ( $gateway->show_save_source() ): ?>
    <div class="wpp-payment-save-source"
	     <?php if ( ! is_user_logged_in() && ! WC()->checkout()->is_registration_required() ): ?>style="display:none"<?php endif ?>>
        <label class="checkbox">
            <input type="checkbox" id="<?php echo esc_attr($gateway->save_source_key); ?>" name="<?php echo esc_attr($gateway->save_source_key); ?>" value="yes"/>
            <span class="save-source-checkbox"></span>
        </label>
        <label class="save-source-label"><?php esc_html_e( 'Save Card', 'wc-stripe-payments' ) ?></label>
    </div>
<?php endif; ?>