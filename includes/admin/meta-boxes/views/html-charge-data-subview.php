<?php
/**
 * @var \Stripe\Charge $charge
 */
?>
<?php if ( ! $order->has_status( 'cancelled' ) ) : ?>
	<?php if ( ( $charge->status === 'pending' && ! $charge->captured ) || ( $charge->status === 'succeeded' && ! $charge->captured ) ) : ?>
        <div class="charge-actions">
            <h2><?php esc_html_e( 'Actions', 'wc-stripe-payments' ); ?></h2>
            <div>
                <input type="text" class="wc_input_price" name="capture_amount"
                       value="<?php echo esc_attr($order->get_total()); ?>"
                       placeholder="<?php esc_html_e( 'capture amount', 'wc-stripe-payments' ); ?>"/>
                <button class="button button-secondary do-api-capture"><?php esc_html_e( 'Capture', 'wc-stripe-payments' ); ?></button>
                <button class="button button-secondary do-api-cancel"><?php esc_html_e( 'Void', 'wc-stripe-payments' ); ?></button>
            </div>
        </div>
	<?php endif; ?>
<?php endif; ?>
<div class="data-container">
    <div class="charge-data column-6">
        <h3><?php esc_html_e( 'Charge Data', 'wc-stripe-payments' ); ?></h3>
        <div class="metadata">
            <label><?php esc_html_e( 'Mode', 'wc-stripe-payments' ); ?></label>:&nbsp;
			<?php $charge->livemode ? esc_html_e( 'Live', 'wc-stripe-payments' ) : esc_html_e( 'Test', 'wc-stripe-payments' ); ?>
        </div>
        <div class="metadata">
            <label><?php esc_html_e( 'Status', 'wc-stripe-payments' ); ?></label>:&nbsp;
			<?php echo esc_attr($charge->status); ?>
        </div>
		<?php if ( ( $payment_intent_id = $order->get_meta( '_payment_intent_id', true ) ) ) : ?>
            <div class="metadata">
                <label><?php esc_html_e( 'Payment Intent', 'wc-stripe-payments' ); ?></label>:&nbsp;
				<?php echo esc_attr($payment_intent_id); ?>
            </div>
		<?php endif; ?>
		<?php if ( isset( $charge->customer ) ) : ?>
            <div class="metadata">
                <label><?php esc_html_e( 'Customer', 'wc-stripe-payments' ); ?></label>:&nbsp;
				<?php echo esc_attr($charge->customer); ?>
            </div>
		<?php endif; ?>
    </div>
    <div class="payment-data column-6">
        <h3><?php esc_html_e( 'Payment Method', 'wc-stripe-payments' ); ?></h3>
        <div class="metadata">
            <label><?php esc_html_e( 'Title', 'wc-stripe-payments' ); ?></label>:&nbsp;
			<?php echo esc_attr($order->get_payment_method_title()); ?>
        </div>
        <div class="metadata">
            <label><?php esc_html_e( 'Type', 'wc-stripe-payments' ); ?></label>:&nbsp;
			<?php echo esc_attr($charge->payment_method_details->type); ?>
        </div>
		<?php if ( isset( $charge->payment_method_details->card ) ) : ?>
            <div class="metadata">
                <label><?php esc_html_e( 'Exp', 'wc-stripe-payments' ); ?>:&nbsp;</label>
				<?php printf( '%02d / %s', $charge->payment_method_details->card->exp_month, $charge->payment_method_details->card->exp_year ); ?>
            </div>
            <div class="metadata">
                <label><?php esc_html_e( 'Fingerprint', 'wc-stripe-payments' ); ?>:&nbsp;</label>
				<?php echo esc_html_e($charge->payment_method_details->card->fingerprint); ?>
            </div>
            <div class="metadata">
                <label><?php esc_html_e( 'CVC check', 'wc-stripe-payments' ); ?>:&nbsp;</label>
				<?php echo esc_html_e($charge->payment_method_details->card->checks->cvc_check); ?>
            </div>
            <div class="metadata">
                <label><?php esc_html_e( 'Postal check', 'wc-stripe-payments' ); ?>:&nbsp;</label>
				<?php echo esc_html_e($charge->payment_method_details->card->checks->address_postal_code_check); ?>
            </div>
            <div class="metadata">
                <label><?php esc_html_e( 'Street check', 'wc-stripe-payments' ); ?>:&nbsp;</label>
				<?php echo esc_html_e($charge->payment_method_details->card->checks->address_line1_check); ?>
            </div>
		<?php endif; ?>
    </div>
    <div class="payment-data column-6">
        <h3><?php esc_html_e( 'Riska Data', 'wc-stripe-payments' ); ?></h3>
		<?php if ( isset( $charge->outcome->risk_score ) ) { ?>
            <div class="metadata">
                <label><?php esc_html_e( 'Score', 'wc-stripe-payments' ); ?></label>
				<?php echo esc_html_e($charge->outcome->risk_score); ?>
            </div>
		<?php } ?>
        <div class="metadata">
            <label><?php esc_html_e( 'Level', 'wc-stripe-payments' ); ?></label>
			<?php echo esc_html_e($charge->outcome->risk_level); ?>
        </div>
    </div>
</div>
