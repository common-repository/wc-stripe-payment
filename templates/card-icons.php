<?php
/**
 * @version 3.2.15
 */
?>
<span class="wpp-payment-card-icons-container">
	<?php foreach ( $icons as $icon => $url ): ?>
        <img class="wpp-payment-card-icon <?php echo esc_attr( $icon ) ?>"
             src="<?php echo esc_url( $url ) ?>"/>
	<?php endforeach; ?>
</span>