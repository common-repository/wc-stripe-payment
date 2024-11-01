<p>
	<b><?php esc_html_e( 'Charge Status', 'wc-stripe-payments' ); ?>:</b>&nbsp;<?php echo esc_html_e(ucfirst( str_replace( '_', ' ', $status ) ) ); ?></p>
<?php

if ( $status === 'pending' && $order->get_meta( '_authorization_exp_at' ) ) :
	$date = new DateTime( '@' . $order->get_meta( '_authorization_exp_at' ) );
	?>
<p>
	<b><?php esc_html_e( 'Authorization Expires', 'wc-stripe-payments' ); ?>:</b>&nbsp;<?php echo esc_html_e(date_format( $date, 'M d Y, h:i A e' )); ?>
<?php endif; ?>
<?php

switch ( $status ) {
	case 'succeeded':
	case 'faied':
		?>
		
<p><?php esc_html_e( 'There are no actions available at this time.', 'wc-stripe-payments' ); ?></p>
		<?php
		return;
}
$can_settle = $status === 'pending';
?>
<div id="wpp-payment-actions">
	<div class="wpp-payment-buttons-container">
		<?php if ( $can_settle ) : ?>
		<button type="button" class="button capture-charge"><?php esc_html_e( 'Capture Charge', 'wc-stripe-payments' ); ?></button>
		<?php endif; ?>
	</div>
	<div class="wc-order-data-row wpp-order-capture-charge"
		style="display: none;">
		<div class="wpp-order-capture-charge-container">
			<table class="wpp-order-capture-charge">
				<tr>
					<td class="label"><?php esc_html_e( 'Total available to capture', 'wc-stripe-payments' ); ?>:</td>
					<td class="total"><?php echo wc_price( $order->get_total() ); ?></td>
				</tr>
				<tr>
					<td class="label"><?php esc_html_e( 'Amount To Capture', 'wc-stripe-payments' ); ?>:</td>
					<td class="total"><input type="text" id="worldpay_capture_amount"
						name="capture_amount" class="wc_input_price" />
						<div class="clear"></div></td>
				</tr>
			</table>
		</div>
		<div class="clear"></div>
		<div class="capture-actions">
			<button type="button" class="button button-primary do-api-capture"><?php esc_html_e( 'Capture', 'wc-stripe-payments' ); ?></button>
			<button type="button" class="button cancel-action"><?php esc_html_e( 'Cancel', 'wc-stripe-payments' ); ?></button>
		</div>
		<div class="clear"></div>
	</div>
</div>
