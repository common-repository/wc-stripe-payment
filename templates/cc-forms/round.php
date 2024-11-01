<?php
/**
 * @version 3.0.0
 * @var WPP_Gateway_Stripe_CC $gateway
 */
?>
<div class="wpp-payment-round-form">
    <div class="fieldset">
        <div id="stripe-card-number" class="field empty"></div>
        <div id="stripe-exp" class="field empty third-width"></div>
        <div id="stripe-cvv" class="field empty third-width"></div>
		<?php if ( $gateway->postal_enabled() ): ?>
            <input id="stripe-postal-code" class="field empty third-width"
                   placeholder="94107" value="<?php echo esc_attr( WC()->checkout()->get_value( 'billing_postcode' ) ) ?>">
		<?php endif; ?>
    </div>
</div>
<style type="text/css">
    .wpp-payment-round-form .StripeElement {
        box-shadow: none !important;
    }

    .wpp-payment-round-form {
        padding: 10px 0;
        background-color: transparent;
    }

    #stripe-postal-code {
        line-height: 0;
    }

    .wpp-payment-round-form * {
        font-family: Quicksand, Open Sans, Segoe UI, sans-serif;
        font-size: 16px;
        font-weight: 600;
    }

    .wpp-payment-round-form .fieldset {
        margin: 15px;
        padding: 0;
        border-style: none;
        display: -ms-flexbox;
        display: flex;
        -ms-flex-flow: row wrap;
        flex-flow: row wrap;
        -ms-flex-pack: justify;
    }

    .wc-wpp_stripe_cc-container .wpp-payment-round-form .field.StripeElement,
    .wpp-payment-round-form .field {
        position: relative;
        padding: 10px 20px 11px;
        background-color: #7488aa;
        border-radius: 20px;
        width: 100%;
    }

    .stripe-small .wpp-payment-round-form .field {
        width: 100% !important;
    }

    .wpp-payment-round-form .field:nth-child(3) {
        margin-left: 5px;
        margin-right: 5px;
    }

    .stripe-small .wpp-payment-round-form .field:nth-child(n+3) {
        margin-left: 0px;
    }

    .wpp-payment-round-form .field.half-width {
        width: calc(50% - (5px / 2));
    }

    .wpp-payment-round-form .field.third-width {
        width: calc(33% - (5px / 3));
    }

    .wpp-payment-round-form .field + .field {
        margin-top: 10px;
    }

    .wpp-payment-round-form .field.focused, .wpp-payment-round-form .field:focus {
        color: #424770;
        background-color: #f6f9fc;
    }

    .wpp-payment-round-form .field.invalid {
        background-color: #fa755a;
    }

    .wpp-payment-round-form .field.invalid.focused {
        background-color: #f6f9fc;
    }

    .wpp-payment-round-form .field.focused::-webkit-input-placeholder,
    .wpp-payment-round-form .field:focus::-webkit-input-placeholder {
        color: #cfd7df;
    }

    .wpp-payment-round-form .field.focused::-moz-placeholder,
    .wpp-payment-round-form .field:focus::-moz-placeholder {
        color: #cfd7df;
    }

    .wpp-payment-round-form .field.focused:-ms-input-placeholder,
    .wpp-payment-round-form .field:focus:-ms-input-placeholder {
        color: #cfd7df;
    }

    .wpp-payment-round-form input, .wpp-payment-round-form button {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        outline: none;
        border-style: none;
    }

    .wpp-payment-round-form input {
        color: #fff;
    }

    .wpp-payment-round-form input::-webkit-input-placeholder {
        color: #9bacc8;
    }

    .wpp-payment-round-form input::-moz-placeholder {
        color: #9bacc8;
    }

    .wpp-payment-round-form input:-ms-input-placeholder {
        color: #9bacc8;
    }

    .wpp-payment-round-form button {
        display: block;
        width: calc(100% - 30px);
        height: 40px;
        margin: 0 15px;
        background-color: #fcd669;
        border-radius: 20px;
        color: #525f7f;
        font-weight: 600;
        text-transform: uppercase;
        cursor: pointer;
    }

    .wpp-payment-round-form button:active {
        background-color: #f5be58;
    }

    .wpp-payment-round-form .error svg .base {
        fill: #fa755a;
    }

    .wpp-payment-round-form .error svg .glyph {
        fill: #fff;
    }

    .wpp-payment-round-form .error .message {
        color: #fff;
    }

    .wpp-payment-round-form .success .icon .border {
        stroke: #fcd669;
    }

    .wpp-payment-round-form .success .icon .checkmark {
        stroke: #fff;
    }

    .wpp-payment-round-form .success .title {
        color: #fff;
    }

    .wpp-payment-round-form .success .message {
        color: #9cabc8;
    }

    .wpp-payment-round-form .success .reset path {
        fill: #fff;
    }
</style>