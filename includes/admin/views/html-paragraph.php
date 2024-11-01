<tr valign="top">
	<th scope="row" class="titledesc"><label
		for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?> <?php echo $this->get_tooltip_html( $data );  // WPCS: XSS ok. get_tooltip_html() escaped via WooCommerce.  ?></label>
	</th>
	<td class="forminp">
		<fieldset>
			<legend class="screen-reader-text">
				<span><?php echo wp_kses_post( $data['title'] ); ?></span>
			</legend>
			<p class="<?php echo esc_attr( $data['class'] ); ?>"
				<?php echo $this->get_custom_attribute_html( $data ); ?>><?php echo esc_html($data['text']); ?></p>
			<?php echo $this->get_description_html( $data ); // WPCS: XSS ok. get_description_html() escaped via WooCommerce. ?>
		</fieldset>
	</td>
</tr>
