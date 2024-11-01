<div id="stripe_product_data"
     class="panel woocommerce_wpp_panel woocommerce_options_panel hidden">
    <p>
		<?php esc_html_e( 'In this section you can control which gateways are displayed on the product page.', 'wc-stripe-payments' ); ?>
    </p>
    <div class="options_group">
        <input type="hidden" id="wpp_payment_update_product"
               name="wpp_payment_update_product"/>
        <table class="wpp-payment-product-table wc_gateways">
            <thead>
            <tr>
                <th></th>
                <th><?php esc_html_e( 'Method', 'wc-stripe-payments' ); ?></th>
                <th><?php esc_html_e( 'Enabled', 'wc-stripe-payments' ); ?></th>
                <th><?php esc_html_e( 'Charge Type', 'wc-stripe-payments' ); ?>
            </thead>
            <tbody class="ui-sortable">
			<?php foreach ( self::get_payment_gateways() as $gateway ) : ?>
                <tr data-gateway_id="<?php echo esc_attr($gateway->id); ?>">
                    <td class="sort">
                        <div class="wc-item-reorder-nav">
                            <button type="button" class="wc-move-up" tabindex="0"
                                    aria-hidden="false"
                                    aria-label="<?php /* Translators: %s Payment gateway name. */
							        echo esc_attr( sprintf( __( 'Move the "%s" payment method up', 'woocommerce' ), $gateway->get_method_title() ) ); ?>"><?php esc_html_e( 'Move up', 'woocommerce' ); ?></button>
                            <button type="button" class="wc-move-down" tabindex="0"
                                    aria-hidden="false"
                                    aria-label="<?php /* Translators: %s Payment gateway name. */
							        echo esc_attr( sprintf( __( 'Move the "%s" payment method down', 'woocommerce' ), $gateway->get_method_title() ) ); ?>"><?php esc_html_e( 'Move down', 'woocommerce' ); ?></button>
                            <input type="hidden" name="stripe_gateway_order[]"
                                   value="<?php echo esc_attr( $gateway->id ); ?>"/>
                        </div>
                    </td>
                    <td>
						<?php echo esc_attr($gateway->get_method_title()); ?>
                    </td>
                    <td>
                        <a class="wpp-payment-product-gateway-enabled" href="#">
                            <span class="woocommerce-input-toggle woocommerce-input-toggle--<?php if ( ! self::get_product_option( $gateway->id )->enabled() ) { ?>disabled<?php } else { ?>enabled<?php } ?>"></span>
                        </a>
                    </td>
                    <td class="capture-type">
                        <select name="stripe_capture_type[]" class="wc-enhanced-select"
                                style="width: 100px">
                            <option value="capture"
								<?php selected( 'capture', self::get_product_option( $gateway->id )->get_option( 'charge_type' ) ); ?>><?php esc_html_e( 'Capture', 'wc-stripe-payments' ); ?></option>
                            <option value="authorize"
								<?php selected( 'authorize', self::get_product_option( $gateway->id )->get_option( 'charge_type' ) ); ?>><?php esc_html_e( 'Authorize', 'wc-stripe-payments' ); ?></option>
                        </select>
                    </td>
                </tr>
			<?php endforeach; ?>
            </tbody>
        </table>
		<?php
		woocommerce_wp_select(
			array(
				'id'          => 'wpp_payment_btn_position',
				'value'       => ( ( $position = $product_object->get_meta( 'wpp_payment_btn_position' ) ) ? $position : 'bottom' ),
				'label'       => __( 'Button Position', 'wc-stripe-payments' ),
				'options'     => array(
					'bottom' => __( 'Below add to cart', 'wc-stripe-payments' ),
					'top'    => __( 'Above add to cart', 'wc-stripe-payments' ),
				),
				'desc_tip'    => true,
				'description' => __(
					'The location of the payment buttons in relation to the Add to Cart button.',
					'wc-stripe-payments'
				),
			)
		);
		?>
    </div>
    <p>
        <button class="button button-secondary wpp-payment-save-product-data"><?php esc_html_e( 'Save', 'wc-stripe-payments' ); ?></button>
        <span class="spinner"></span>
    </p>
</div>
