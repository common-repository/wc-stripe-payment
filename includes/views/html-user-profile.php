<?php
/**
 * @var WP_User $user
 */
?>
<div class="wpp-payment-user-info">
	<h2><?php esc_html_e( 'Stripe Customer ID\'s', 'wc-stripe-payments' ); ?></h2>
	<p><?php esc_html_e( 'If you change a customer ID, the customer\'s payment methods will be imported from your Stripe account.' ); ?></p>
	<p><?php esc_html_e( 'If you remove a customer ID, the customer\'s payment methods will be removed from the WC payment methods table.' ); ?></p>
	<table class="form-table">
		<tbody>
			<tr>
				<th><?php esc_html_e( 'Live ID', 'wc-stripe-payments' ); ?></th>
				<td><input type="text" id="wp_payment_live_id"
					name="wp_payment_live_id"
					value="<?php echo wpp_payment_get_customer_id( $user->ID, 'live' ); ?>" />
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Test ID', 'wc-stripe-payments' ); ?></th>
				<td><input type="text" id="wp_payment_test_id"
					name="wp_payment_test_id"
					value="<?php echo wpp_payment_get_customer_id( $user->ID, 'test' ); ?>" />
				</td>
			</tr>
		</tbody>
	</table>
	<h2><?php esc_html_e( 'Stripe Live Payment Methods', 'wc-stripe-payments' ); ?></h2>
	<?php if ( $payment_methods['live'] ) : ?>
	<table class="wpp-payment-methods">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Payment Gateway', 'wc-stripe-payments' ); ?></th>
				<th><?php esc_html_e( 'Payment Method', 'wc-stripe-payments' ); ?></th>
				<th><?php esc_html_e( 'Token', 'wc-stripe-payments' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'wc-stripe-payments' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $payment_methods['live'] as $token ) : ?>
				<tr>
				<td><?php esc_html_e( $token->get_gateway_id() ); ?></td>
				<td><?php esc_html_e( $token->get_payment_method_title() ); ?></td>
				<td><?php esc_html_e( $token->get_token() ); ?></td>
				<td><input type="checkbox" name="payment_methods[live][]"
					value="<?php echo esc_attr($token->get_id()); ?>" /></td>
			</tr>
			<?php endforeach; ?>
			<tr>
				<th><?php esc_html_e( 'Action', 'delete' ); ?></th>
				<td><select name="live_payment_method_actions">
						<option value="none" selected><?php esc_html_e( 'No Action', 'wc-stripe-payments' ); ?></option>
						<option value="delete"><?php esc_html_e( 'Delete', 'wc-stripe-payments' ); ?></option>
				</select></td>
			</tr>
		</tbody>
	</table>
	<?php else : ?>
		<?php esc_html_e( 'No live payment methods saved', 'wc-stripe-payments' ); ?>
	<?php endif; ?>
	<h2><?php esc_html_e( 'Stripe Test Payment Methods', 'wc-stripe-payments' ); ?></h2>
	<?php if ( $payment_methods['test'] ) : ?>
	<table class="wpp-payment-methods">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Payment Gateway', 'wc-stripe-payments' ); ?></th>
				<th><?php esc_html_e( 'Payment Method', 'wc-stripe-payments' ); ?></th>
				<th><?php esc_html_e( 'Token', 'wc-stripe-payments' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'wc-stripe-payments' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $payment_methods['test'] as $token ) : ?>
				<tr>
				<td><?php esc_html_e($token->get_gateway_id() ); ?></td>
				<td><?php esc_html_e($token->get_payment_method_title()); ?></td>
				<td><?php esc_html_e($token->get_token()); ?></td>
				<td><input type="checkbox" name="payment_methods[test][]"
					value="<?php echo esc_attr($token->get_id()); ?>" /></td>
			</tr>
			<?php endforeach; ?>
			<tr>
				<th><?php esc_html_e( 'Action', 'delete' ); ?></th>
				<td><select name="test_payment_method_actions">
						<option value="none" selected><?php esc_html_e( 'No Action', 'wc-stripe-payments' ); ?></option>
						<option value="delete"><?php esc_html_e( 'Delete', 'wc-stripe-payments' ); ?></option>
				</select></td>
			</tr>
		</tbody>
	</table>
	<?php else : ?>
		<?php esc_html_e( 'No test payment methods saved', 'wc-stripe-payments' ); ?>
	<?php endif; ?>
	<?php printf( __( '%1$snote:%2$s payment methods will be deleted in Stripe if you use the delete action.', 'wc-stripe-payments' ), '<strong>', '</strong>' ); ?>
</div>
