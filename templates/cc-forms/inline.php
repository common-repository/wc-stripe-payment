<?php
/**
 * @version 3.0.0
 * @var WPP_Gateway_Stripe_CC $gateway
 */
?>
<div class="wpp-payment-inline-form">
    <div class="row">
        <label for="stripe-card-number"><?php esc_html_e( 'Card Number', 'wc-stripe-payments' ) ?></label>
        <div id="stripe-card-number" class="input"></div>
    </div>
    <div class="row">
        <label for="stripe-exp"><?php esc_html_e( 'Exp Date', 'wc-stripe-payments' ) ?></label>
        <div id="stripe-exp" class="input"></div>
    </div>
    <div class="row">
        <label for="stripe-cvv"><?php esc_html_e( 'CVV', 'wc-stripe-payments' ) ?></label>
        <div id="stripe-cvv" class="input"></div>
    </div>
	<?php if ( $gateway->postal_enabled() ): ?>
        <div class="row">
            <label for="stripe-postal-code"><?php esc_html_e( 'ZIP', 'wc-stripe-payments' ) ?></label>
            <input type="text" id="stripe-postal-code" class="input empty" placeholder="78703"
                   value="<?php echo esc_attr( WC()->checkout()->get_value( 'billing_postcode' ) ) ?>"/>
        </div>
	<?php endif; ?>
</div>
<style type="text/css">
    .wpp-payment-inline-form {
        background-color: #fff;
        padding: 0;
    }

    .wpp-payment-inline-form #wpp-payment-card {
        top: 10px;
    }

    #stripe-card-number {
        position: relative;
    }

    .wpp-payment-inline-form * {
        font-family: Roboto, Open Sans, Segoe UI, sans-serif;
        font-size: 16px;
        font-weight: 500;
    }

    .payment_method_wpp_stripe_cc .wpp-payment-inline-form fieldset {
        margin: 0;;
        padding: 0;
        border-top: 1px solid #829fff;
        border-bottom: 1px solid #829fff;
    }

    .wc-wpp_stripe_cc-container .wpp-payment-inline-form .StripeElement {
        padding: 0;
    }

    .wpp-payment-inline-form .row {
        display: -ms-flexbox;
        display: flex;
        -ms-flex-align: center;
        align-items: center;
        margin: 0 !important;
        flex-flow: row wrap;
        width: 100%;
    }

    .wpp-payment-inline-form .row {
        border-bottom: 1px solid #819efc;
    }

    .wpp-payment-inline-form label,
    .woocommerce-checkout .woocommerce-checkout #payment ul.payment_methods li .wpp-payment-inline-form label {
        width: 110px;
        min-width: 110px;
        padding: 11px 0;
        color: #91b5c1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        margin: 0;
    }

    .wpp-payment-inline-form input, .wpp-payment-inline-form button {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        outline: none;
        border-style: none;
    }

    .wpp-payment-inline-form input:-webkit-autofill {
        -webkit-text-fill-color: #fce883;
        transition: background-color 100000000s;
        -webkit-animation: 1ms void-animation-out;
    }

    .wpp-payment-inline-form .StripeElement--webkit-autofill {
        background: transparent !important;
    }

    .wpp-payment-inline-form .StripeElement {
        width: 100%;
        padding: 11px 15px 11px 0;
        box-shadow: none;
    }

    .wpp-payment-inline-form input,
    .wpp-payment-inline-form .input {
        width: 100%;
        padding: 11px 15px 11px 0;
        background-color: transparent;
        -webkit-animation: 1ms void-animation-out;
        box-shadow: none;
        border: none;
        color: #819efc;
    }

    .wpp-payment-inline-form input:focus {
        color: #819efc;
    }

    .wpp-payment-inline-form input::-webkit-input-placeholder {
        color: #87bbfd;
    }

    .wpp-payment-inline-form input::-moz-placeholder {
        color: #87bbfd;
    }

    .wpp-payment-inline-form input:-ms-input-placeholder {
        color: #87bbfd;
    }

    .wpp-payment-inline-form button {
        display: block;
        width: calc(100% - 30px);
        height: 40px;
        margin: 40px 15px 0;
        background-color: #f6a4eb;
        box-shadow: 0 6px 9px rgba(50, 50, 93, 0.06), 0 2px 5px rgba(0, 0, 0, 0.08),
        inset 0 1px 0 #ffb9f6;
        border-radius: 4px;
        color: #fff;
        font-weight: 600;
        cursor: pointer;
    }

    .wpp-payment-inline-form button:active {
        background-color: #d782d9;
        box-shadow: 0 6px 9px rgba(50, 50, 93, 0.06), 0 2px 5px rgba(0, 0, 0, 0.08),
        inset 0 1px 0 #e298d8;
    }

    #stripe-postal-code:focus {
        background: transparent;
    }

    .wpp-payment-inline-form .error svg .base {
        fill: #fff;
    }

    .wpp-payment-inline-form .error svg .glyph {
        fill: #6772e5;
    }

    .wpp-payment-inline-form .error .message {
        color: #fff;
    }

    .wpp-payment-inline-form .success .icon .border {
        stroke: #87bbfd;
    }

    .wpp-payment-inline-form .success .icon .checkmark {
        stroke: #fff;
    }

    .wpp-payment-inline-form .success .title {
        color: #fff;
    }

    .wpp-payment-inline-form .success .message {
        color: #9cdbff;
    }

    .wpp-payment-inline-form .success .reset path {
        fill: #fff;
    }

    .stripe-small .wpp-payment-inline-form .row {
        flex-wrap: wrap;
    }

    @media screen and (max-width: 490px) {
        .wpp-payment-inline-form .row {
            flex-wrap: wrap;
        }

        .wpp-payment-inline-form label {
            width: 100%;
            display: none;
        }
    }
</style>