<tr valign="top">
	<th scope="row" class="titledesc"><label
		for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data );  // WPCS: XSS ok. get_tooltip_html() escaped via WooCommerce.  ?></label>
	</th>
	<td class="forminp">
		<fieldset>
			<legend class="screen-reader-text">
				<span><?php echo wp_kses_post( $data['title'] ); ?></span>
			</legend>
			<div class="wpp-payment-fee-container">
				<div class="wpp-payment-fee-col">
					<label><?php esc_html_e( 'Type', 'wc-stripe-payments' ); ?></label> <select class="select wc-enhanced-select ach-fee" name="<?php echo esc_attr( $field_key ); ?>[type]" id="<?php echo esc_attr( $field_key ); ?>[type]" style="<?php echo esc_attr( $data['css'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data ); // WPCS: XSS ok. get_custom_attribute_html() escaped via WooCommerce. ?>>
					<?php foreach ( (array) $data['options'] as $option_key => $option_value ) : ?>
						<option value="<?php echo esc_attr( $option_key ); ?>"
							<?php selected( (string) $option_key, esc_attr( $this->get_option( $key )['type'] ) ); ?>><?php echo esc_attr( $option_value ); ?></option>
					<?php endforeach; ?>
					</select>
				</div>
				<div class="wpp-payment-fee-col">
					<label><?php esc_html_e( 'Taxable', 'wc-stripe-payments' ); ?></label> <input
						type="checkbox"
						name="<?php echo esc_attr( $field_key ); ?>[taxable]"
						id="<?php echo esc_attr( $field_key ); ?>[taxable]" value="yes"
						<?php checked( $this->get_option( 'fee' )['taxable'], 'yes' ); ?> />
				</div>
				<div class="wpp-payment-fee-col">
					<label><?php esc_html_e( 'Value', 'wc-stripe-payments' ); ?></label> <input class="input-text regular-input <?php echo esc_attr( $data['class'] ); ?>" type="text" name="<?php echo esc_attr( $field_key ); ?>[value]" id="<?php echo esc_attr( $field_key ); ?>[value]" style="<?php echo esc_attr( $data['css'] ); ?>" value="<?php echo esc_attr( $this->get_option( $key )['value'] ); ?>" placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data ); // WPCS: XSS ok. get_custom_attribute_html() escaped via WooCommerce. ?> />
				</div>
			</div>
			<?php echo $this->get_description_html( $data ); // WPCS: XSS ok. get_description_html() escaped via WooCommerce. ?>
		</fieldset>
	</td>
</tr>
