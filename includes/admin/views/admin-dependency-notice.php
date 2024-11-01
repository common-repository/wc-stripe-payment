<?php
/**
 * Admin View: Dep notice
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @var bool $has_woocommerce
 * @var bool $woocommerce_installed
 */
?>
<?php if ( ( !class_exists( 'WooCommerce' ) || ! in_array( 'woocommerce/woocommerce.php', array_keys( get_plugins() ), true ) ) && current_user_can( 'activate_plugins' ) ) : ?>
<div id="message" class="error">
    <h3><?php _e( 'WooCommerce Missing', 'wc-stripe-payments' ); ?></h3>
    <p>
        <?php
        

        if ( class_exists( 'WooCommerce' ) ) {
            $install_url = wp_nonce_url( add_query_arg( array( 'action' => 'activate', 'plugin' => urlencode( 'woocommerce/woocommerce.php' ) ), admin_url( 'plugins.php' ) ), 'activate-plugin_woocommerce/woocommerce.php' );
            $is_install  = false;
            echo sprintf( esc_html__( '%1$sPayments via Stripe for WooCommerce is inactive.%2$s The %3$sWooCommerce plugin%4$s must be active for Dokan to work. Please %5$sactivate WooCommerce &raquo;%6$s',  'wc-stripe-payments' ), '<strong>', '</strong>', '<a href="https://wordpress.org/plugins/woocommerce/">', '</a>', '<a href="' .  esc_url( $install_url ) . '">', '</a>' );
        }else{

            $install_url  = wp_nonce_url( add_query_arg( array( 'action' => 'install-plugin', 'plugin' => 'woocommerce' ), admin_url( 'update.php' ) ), 'install-plugin_woocommerce' );
            echo sprintf( esc_html__( '%1$sPayments via Stripe for WooCommerce is inactive.%2$s The %3$sWooCommerce plugin%4$s must be active for Payments via Stripe for WooCommerce to work. Please %5$sinstall WooCommerce &raquo;%6$s',  'wc-stripe-payments' ), '<strong>', '</strong>', '<a href="https://wordpress.org/plugins/woocommerce/">', '</a>', '<a href="' .  esc_url( $install_url ) . '">', '</a>' );

        }
        ?>
    </p>
</div>
<?php elseif ( version_compare( PHP_VERSION, '5.6', '<' ) ) : ?>
<div id="message" class="error">
    <p>
        <?php
        echo sprintf( __( 'Your PHP version is %s but Stripe requires version 5.6+.', 'wc-stripe-payments' ), PHP_VERSION );
        ?>
    </p>
</div>
<?php endif; ?>
