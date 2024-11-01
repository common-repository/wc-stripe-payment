<tr valign="top">
	<th scope="row" class="titledesc"><label
		for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data );  // WPCS: XSS ok. get_tooltip_html() escaped via WooCommerce.  ?></label>
	</th>
	<td class="forminp
	<?php
	if ( $data['active'] ) {
		?>
  active<?php } ?>">
		<fieldset>
			<legend class="screen-reader-text">
				<span><?php echo wp_kses_post( $data['title'] ); ?></span>
			</legend>
			<label for="<?php echo esc_attr( $field_key ); ?>"> <a href="<?php echo esc_url($data['connect_url']); ?>" type="submit" class="wps-stripe-connect stripe-connect <?php echo esc_attr( $data['class'] ); ?>" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" value="<?php echo esc_attr( $field_key ); ?>" <?php echo $this->get_custom_attribute_html( $data ); // WPCS: XSS ok. get_custom_attribute_html() escaped via WooCommerce. ?>><?php echo wp_kses_post( $data['label'] ); ?></a>
			</label>
			<?php echo $this->get_description_html( $data ); // WPCS: XSS ok. get_description_html() escaped via WooCommerce. ?>
		</fieldset>
	</td>
</tr>
