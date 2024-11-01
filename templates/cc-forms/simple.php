<?php
/**
 * @version 3.0.0
 * @var WPP_Gateway_Stripe_CC $gateway
 */
?>
<div class="wpp-payment-simple-form">
    <div class="row">
        <div class="field">
            <div id="stripe-card-number" class="input empty"></div>
            <label for="stripe-card-number"
                   data-tid=""><?php esc_html_e( 'Card Number', 'wc-stripe-payments' ) ?></label>
            <div class="baseline"></div>
        </div>
    </div>
    <div class="row">
        <div class="field half-width">
            <div id="stripe-exp" class="input empty"></div>
            <label for="stripe-exp"
                   data-tid=""><?php esc_html_e( 'Expiration', 'wc-stripe-payments' ) ?></label>
            <div class="baseline"></div>
        </div>
        <div class="field half-width cvc">
            <div id="stripe-cvv" class="input empty"></div>
            <label for="stripe-cvv"
                   data-tid=""><?php esc_html_e( 'CVV', 'wc-stripe-payments' ) ?></label>
            <div class="baseline"></div>
        </div>
    </div>
	<?php if ( $gateway->postal_enabled() ): ?>
        <div class="row">
            <div class="field postalCode" tabindex="-1">
                <input type="text" id="stripe-postal-code" class="input empty"
                       value="<?php echo esc_attr( WC()->checkout()->get_value( 'billing_postcode' ) ) ?>"/>
                <label><?php esc_html_e( 'ZIP', 'wc-stripe-payments' ) ?></label>
                <div class="baseline"></div>
            </div>
        </div>
	<?php endif; ?>
</div>
<style type="text/css">
    .wpp-payment-simple-form {
        background-color: #fff;
        padding: 10px 0;
    }

    .wpp-payment-simple-form .StripeElement,
    .wc-wpp_stripe_cc-container .wpp-payment-simple-form .StripeElement {
        padding-left: 0px;
    }

    .wpp-payment-simple-form * {
        font-family: Source Code Pro, Consolas, Menlo, monospace;
        font-size: 16px;
        font-weight: 500;
    }

    .wpp-payment-simple-form .postalCode label {

    }

    .wpp-payment-simple-form .row {
        display: -ms-flexbox;
        display: flex;
        margin: 0 5px 10px;
    }

    .stripe-small .wpp-payment-simple-form .row {
        flex-wrap: wrap;
    }

    .wpp-payment-simple-form .field {
        position: relative;
        width: 100%;
        height: 50px;
        margin: 0 10px;
    }

    .wpp-payment-simple-form .field.half-width {
        width: 50%;
    }

    .stripe-small .wpp-payment-simple-form .field.half-width {
        width: 100%;
    }

    .wpp-payment-simple-form .field.quarter-width {
        width: calc(25% - 10px);
    }

    .wpp-payment-simple-form .baseline {
        position: absolute;
        width: 100%;
        height: 1px;
        left: 0;
        bottom: 0;
        background-color: #cfd7df;
        transition: background-color 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
    }

    .wpp-payment-simple-form label,
    .woocommerce-checkout .woocommerce-checkout #payment ul.payment_methods li .wpp-payment-simple-form label {
        position: absolute;
        width: 100%;
        left: 0;
        bottom: 8px;
        color: #cfd7df;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        transform-origin: 0 50%;
        cursor: text;
        transition-property: color, transform;
        transition-duration: 0.3s;
        transition-timing-function: cubic-bezier(0.165, 0.84, 0.44, 1);
        margin-bottom: 0;
        padding: 0;
    }

    .wpp-payment-simple-form .input {
        position: absolute;
        width: 100%;
        left: 0;
        bottom: 0;
        padding-bottom: 7px;
        color: #32325d;
        background-color: transparent;
    }

    .stripe-small .wpp-payment-simple-form .cvc {
        margin-top: 10px;
    }

    .wpp-payment-simple-form #stripe-postal-code {
        height: 40px;
        padding: 0;
        margin: 0;
        box-shadow: none;
        border: none;
        outline: none;
    }

    .wpp-payment-simple-form input#stripe-postal-code:focus {
        outline: none;
    }

    .wpp-payment-simple-form .input::-webkit-input-placeholder {
        color: transparent;
        transition: color 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
    }

    .wpp-payment-simple-form .input::-moz-placeholder {
        color: transparent;
        transition: color 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
    }

    .wpp-payment-simple-form .input:-ms-input-placeholder {
        color: transparent;
        transition: color 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
    }

    .wpp-payment-simple-form .input.StripeElement {
        opacity: 0;
        transition: opacity 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
        will-change: opacity;
    }

    .wpp-payment-simple-form .input.focused,
    .wpp-payment-simple-form .input:not(.empty) {
        opacity: 1;
    }

    .wpp-payment-simple-form .input.focused::-webkit-input-placeholder,
    .wpp-payment-simple-form .input:not(.empty)::-webkit-input-placeholder {
        color: #cfd7df;
    }

    .wpp-payment-simple-form .input.focused::-moz-placeholder,
    .wpp-payment-simple-form .input:not(.empty)::-moz-placeholder {
        color: #cfd7df;
    }

    .wpp-payment-simple-form .input.focused:-ms-input-placeholder,
    .wpp-payment-simple-form .input:not(.empty):-ms-input-placeholder {
        color: #cfd7df;
    }

    .wpp-payment-simple-form .input.focused + label,
    .wpp-payment-simple-form .input:not(.empty) + label {
        color: #aab7c4;
        transform: scale(0.85) translateY(-25px);
        cursor: default;
    }

    .wpp-payment-simple-form .input.focused + label {
        color: #24b47e;
    }

    .wpp-payment-simple-form .input.invalid + label {
        color: #ffa27b;
    }

    .wpp-payment-simple-form .input.focused + label + .baseline {
        background-color: #24b47e;
    }

    .wpp-payment-simple-form .input.focused.invalid + label + .baseline {
        background-color: #e25950;
    }

    .wpp-payment-simple-form input, .wpp-payment-simple-form button {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        outline: none;
        border-style: none;
    }

    .wpp-payment-simple-form input:-webkit-autofill {
        -webkit-text-fill-color: #e39f48;
        transition: background-color 100000000s;
        -webkit-animation: 1ms void-animation-out;
    }

    .wpp-payment-simple-form .StripeElement--webkit-autofill {
        background: transparent !important;
    }

    .wpp-payment-simple-form input, .wpp-payment-simple-form button {
        -webkit-animation: 1ms void-animation-out;
    }

    .wpp-payment-simple-form button {
        display: block;
        width: calc(100% - 30px);
        height: 40px;
        margin: 40px 15px 0;
        background-color: #24b47e;
        border-radius: 4px;
        color: #fff;
        text-transform: uppercase;
        font-weight: 600;
        cursor: pointer;
    }

    .wpp-payment-simple-form .error svg {
        margin-top: 0 !important;
    }

    .wpp-payment-simple-form .error svg .base {
        fill: #e25950;
    }

    .wpp-payment-simple-form .error svg .glyph {
        fill: #fff;
    }

    .wpp-payment-simple-form .error .message {
        color: #e25950;
    }

    .wpp-payment-simple-form .success .icon .border {
        stroke: #abe9d2;
    }

    .wpp-payment-simple-form .success .icon .checkmark {
        stroke: #24b47e;
    }

    .wpp-payment-simple-form .success .title {
        color: #32325d;
        font-size: 16px !important;
    }

    .wpp-payment-simple-form .success .message {
        color: #8898aa;
        font-size: 13px !important;
    }

    .wpp-payment-simple-form .success .reset path {
        fill: #24b47e;
    }
</style>