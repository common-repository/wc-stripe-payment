<?php
/**
 * @version 3.2.8
 */
?>
<div class="wpp-payment-paymentRequest-icons-container">
    <img class="wpp-payment-paymentRequest-icon gpay"
         src="<?php echo esc_url( stripe_wpp()->assets_url( 'img/googlepay_round_outline.svg' ) ) ?>" style="display: none"/>
    <img class="wpp-payment-paymentRequest-icon microsoft-pay"
         src="<?php echo esc_url( stripe_wpp()->assets_url( 'img/microsoft_pay.svg' ) ) ?>" style="display: none"/>
</div>