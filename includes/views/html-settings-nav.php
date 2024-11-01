<?php
global $current_section;
$tabs       = apply_filters( 'wppayment_admin_settings_tabs', array() );
$last       = count( $tabs );
$idx        = 0;
$tab_active = false;
?>
<div class="wpp-payment-settings-logo">
    <img
            src="<?php echo esc_url(stripe_wpp()->assets_url() . 'img/logo.png'); ?>"/>
</div>
<div class="stripe-settings-nav">
	<?php foreach ( $tabs as $id => $tab ) : $idx ++ ?>
        <a class="nav-tab <?php if ( $current_section === $id || ( ! $tab_active && $last === $idx ) ) {
			echo 'nav-tab-active';
			$tab_active = true;
		} ?>"
           href="<?php echo esc_url(admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $id )); ?>"><?php echo esc_attr( $tab ); ?></a>
	<?php endforeach; ?>
	<a class="nav-tab" href="<?php echo esc_url($this->wpp_stripe_doc_url()); ?>" target="_blank"><?php esc_html_e( 'Documentation', 'wc-stripe-payments' ); ?></a>
</div>
<div class="clear"></div>
