<?php
/**
 * @version 3.0.5
 */
$steps = $gateway->postal_enabled() ? 4 : 3;
stripe_wpp()->scripts()->enqueue_script( 'cc-forms', stripe_wpp()->assets_url( 'js/frontend/cc-forms.js' ), array( stripe_wpp()->scripts()->get_handle( 'credit-card' ) ) );
?>
<div class="wpp-payment-minimalist-form">
    <div class="wpp-payment-steps-container">
        <nav class="wpp-payment-steps" data-steps="<?php echo esc_attr( $steps ) ?>">
            <a href="" class="wpp-payment-back">
                <svg width="19" height="32" viewBox="0 0 19 32" xmlns="http://www.w3.org/2000/svg"><title>next</title>
                    <path fill="#495057" d="M5.657 15.556L18.385 2.828 15.555 0 0 15.556l15.556 15.557 2.83-2.83" fill-rule="evenodd"></path>
                </svg>
            </a>
            <span class="wpp-payment-step" data-text="%s / <?php echo esc_attr( $steps ) ?>">1 / <?php echo esc_attr( $steps ) ?></span>
            <a href="" class="wpp-payment-next">
                <svg width="19" height="32" viewBox="0 0 19 32" xmlns="http://www.w3.org/2000/svg"><title>prev</title>
                    <path fill="#495057" d="M12.727 15.556L0 2.828 2.828 0l15.557 15.556L2.828 31.113 0 28.283" fill-rule="evenodd"></path>
                </svg>
            </a>
        </nav>
    </div>
    <div class="field-container" data-index="1">
        <div id="stripe-card-number" class="stripe-input"></div>
        <label for="stripe-card-number" class="stripe-input--label"><?php esc_html_e( 'Card Number', 'wc-stripe-payments' ) ?></label>
    </div>
    <div class="field-container field-container--hidden" data-index="2">
        <div id="stripe-exp" class="stripe-input"></div>
        <label for="stripe-exp" class="stripe-input--label"><?php esc_html_e( 'Exp', 'wc-stripe-payments' ) ?></label>
    </div>
    <div class="field-container field-container--hidden" data-index="3">
        <div id="stripe-cvv" class="stripe-input"></div>
        <label for="stripe-cvv" class="stripe-input--label"><?php esc_html_e( 'CVV', 'wc-stripe-payments' ) ?></label>
    </div>
	<?php if ( $gateway->postal_enabled() ): ?>
        <div class="field-container field-container--hidden" data-index="4">
            <input id="stripe-postal-code" class="stripe-input StripeElement"/>
            <label for="stripe-postal-code" class="stripe-input--label"><?php esc_html_e( 'Postal', 'wc-stripe-payments' ) ?></label>
        </div>
	<?php endif; ?>
</div>
<style type="text/css">
    .wc-wpp_stripe_cc-new-method-container {
        padding-top: 20px;
    }

    .wpp-payment-minimalist-form .wpp-payment-steps-container {
        position: relative;
        height: 32px;
    }

    .wpp-payment-minimalist-form {
        position: relative;
        height: 82px;
    }

    .wpp-payment-minimalist-form .field-container {
        position: absolute;
        width: 100%;
        z-index: 999;
        opacity: 1;
        transition: all 500ms cubic-bezier(0.2, 1.3, 0.7, 1);
        -webkit-backface-visibility: hidden;
        -webkit-transform-style: preserve-3d;
        transform-style: preserve-3d;
        -webkit-transform-origin: bottom;
        transform-origin: bottom;
        border: 1px solid #dadada;
    }

    .wpp-payment-minimalist-form .field-container.field-container--hidden {
        opacity: 0;
        -webkit-transform: translate(0em, 0em) rotateX(180deg);
        transform: translate(0em, 0em) rotateX(180deg);
        z-index: -1;
    }

    .wpp-payment-minimalist-form .field-container.field-container--hidden label[for=stripe-postal-code] {
        display: none;
    }

    .wpp-payment-minimalist-form .field-container.field-container--hidden label[for=stripe-postal-code]:focus {
        background: white;
    }

    .wpp-payment-minimalist-form .field-container #stripe-postal-code {
        font-size: 30px;
    }

    .wpp-payment-minimalist-form .field-container label[for=stripe-postal-code] {
        z-index: 999;
    }

    .wpp-payment-minimalist-form .stripe-input {
        height: 50px;
        padding: 7px 12px 9px 12px;
        border-radius: unset;
        width: 100%;
    }

    .wpp-payment-minimalist-form .stripe-input.invalid {
        background: rgba(244, 67, 54, 0.5);
        -webkit-animation: error 0.5s cubic-bezier(0.2, 1.3, 0.7, 1);
        animation: error 0.5s cubic-bezier(0.2, 1.3, 0.7, 1);
    }

    .wpp-payment-minimalist-form .stripe-input.StripeElement--complete {
        background: rgba(76, 175, 80, 0.5);
        -webkit-animation: success 0.5s cubic-bezier(0.2, 1.3, 0.7, 1);
        animation: success 0.5s cubic-bezier(0.2, 1.3, 0.7, 1);
    }

    .wpp-payment-minimalist-form .stripe-input--label {
        color: #495057;
        position: absolute;
        top: 11px;
        left: 10px;
        transition: color 0.2s, -webkit-transform 0.2s cubic-bezier(0.2, 1.3, 0.7, 1);
        transition: transform 0.2s cubic-bezier(0.2, 1.3, 0.7, 1), color 0.2s;
        transition: transform 0.2s cubic-bezier(0.2, 1.3, 0.7, 1), color 0.2s, -webkit-transform 0.2s cubic-bezier(0.2, 1.3, 0.7, 1);
        -webkit-transform-origin: 0 0;
        transform-origin: 0 0;
        font-size: 30px;
        line-height: 1;
        margin: 0px;
        font-weight: 400;
    }

    .wpp-payment-minimalist-form .stripe-input.focused + .stripe-input--label,
    .wpp-payment-minimalist-form .stripe-input.invalid + .stripe-input--label,
    .wpp-payment-minimalist-form .stripe-input.StripeElement--complete + .stripe-input--label,
    .wpp-payment-minimalist-form .stripe-input:focus + .stripe-input--label {
        -webkit-transform: scale(0.6) translate(0px, -60px);
        transform: scale(0.6) translate(0em, -60px);
        transition: color 0.2s, -webkit-transform 0.2s cubic-bezier(0.2, 1.3, 0.7, 1);
        transition: transform 0.2s cubic-bezier(0.2, 1.3, 0.7, 1), color 0.2s;
        transition: transform 0.2s cubic-bezier(0.2, 1.3, 0.7, 1), color 0.2s, -webkit-transform 0.2s cubic-bezier(0.2, 1.3, 0.7, 1);
    }

    .wpp-payment-minimalist-form .wpp-payment-steps {
        position: absolute;
        z-index: 999;
        right: 20px;
        top: -10px;
        display: flex;
        display: -webkit-flex;
        display: -ms-flexbox;
        align-items: center;
    }

    .wpp-payment-minimalist-form .wpp-payment-steps a {
        display: inline-block;
        -webkit-transform: scale(0.8);
        transform: scale(0.8);
        transition: -webkit-transform 0.3s cubic-bezier(0.2, 1.3, 0.7, 1);
        transition: transform 0.3s cubic-bezier(0.2, 1.3, 0.7, 1);
        transition: transform 0.3s cubic-bezier(0.2, 1.3, 0.7, 1), -webkit-transform 0.3s cubic-bezier(0.2, 1.3, 0.7, 1);
    }

    .wpp-payment-minimalist-form .wpp-payment-steps a:hover {
        -webkit-transform: scale(0.9);
        transform: scale(0.9);
        transition: -webkit-transform 0.1s cubic-bezier(0.2, 1.3, 0.7, 1);
        transition: transform 0.1s cubic-bezier(0.2, 1.3, 0.7, 1);
        transition: transform 0.1s cubic-bezier(0.2, 1.3, 0.7, 1), -webkit-transform 0.1s cubic-bezier(0.2, 1.3, 0.7, 1);
    }

    .wpp-payment-minimalist-form .wpp-payment-steps .wpp-payment-next,
    .wpp-payment-minimalist-form .wpp-payment-steps .wpp-payment-back {
        text-decoration: none;
        box-shadow: none;
        display: flex;
        display: -webkit-flex;
        align-items: center;
    }

    .wpp-payment-minimalist-form .wpp-payment-steps .wpp-payment-step {
        margin: 0 5px;
    }

    @-webkit-keyframes error {
        0% {
            background: #282828;
            -webkit-transform: scale(1);
            transform: scale(1);
        }
        50% {
            background: #f44336;
            -webkit-transform: scale(1.1);
            transform: scale(1.1);
        }
        100% {
            background: rgba(244, 67, 54, 0.5);
            -webkit-transform: scale(1);
            transform: scale(1);
        }
    }

    @keyframes error {
        0% {
            background: #282828;
            -webkit-transform: scale(1);
            transform: scale(1);
        }
        50% {
            background: #f44336;
            -webkit-transform: scale(1.1);
            transform: scale(1.1);
        }
        100% {
            background: rgba(244, 67, 54, 0.5);
            -webkit-transform: scale(1);
            transform: scale(1);
        }
    }

    @-webkit-keyframes success {
        0% {
            background: #282828;
            -webkit-transform: scale(1);
            transform: scale(1);
        }
        50% {
            background: #3d8b40;
            -webkit-transform: scale(1.1);
            transform: scale(1.1);
        }
        100% {
            background: rgba(76, 175, 80, 0.5);
            -webkit-transform: scale(1);
            transform: scale(1);
        }
    }

    @keyframes success {
        0% {
            background: #282828;
            -webkit-transform: scale(1);
            transform: scale(1);
        }
        50% {
            background: #3d8b40;
            -webkit-transform: scale(1.1);
            transform: scale(1.1);
        }
        100% {
            background: rgba(76, 175, 80, 0.5);
            -webkit-transform: scale(1);
            transform: scale(1);
        }
    }

    @-webkit-keyframes inputIntro {
        0% {
            -webkit-transform: translate(0, 0.2em) rotateX(90deg) scale(0.9);
            transform: translate(0, 0.2em) rotateX(90deg) scale(0.9);
        }
        100% {
            -webkit-transform: translate(0, 0) rotateX(0) scale(1);
            transform: translate(0, 0) rotateX(0) scale(1);
        }
    }

    @keyframes inputIntro {
        0% {
            -webkit-transform: translate(0, 0.2em) rotateX(90deg) scale(0.9);
            transform: translate(0, 0.2em) rotateX(90deg) scale(0.9);
        }
        100% {
            -webkit-transform: translate(0, 0) rotateX(0) scale(1);
            transform: translate(0, 0) rotateX(0) scale(1);
        }
    }

    @media (max-width: 400px) {
        .wpp-payment-minimalist-form .stripe-input {
            padding: 15px 12px;
        }

        .wpp-payment-minimalist-form .stripe-input--label {
            font-size: 20px;
            top: 15px;
        }

        .wpp-payment-minimalist-form .wpp-payment-steps {
            right: 0;
        }
    }

    .wpp-payment-sm .wpp-payment-minimalist-form .stripe-input {
        padding: 15px 12px;
    }

    .wpp-payment-sm .wpp-payment-minimalist-form .stripe-input--label {
        font-size: 20px;
        top: 15px;
    }

    .wpp-payment-sm .wpp-payment-minimalist-form .wpp-payment-steps {
        right: 0;
    }
</style>