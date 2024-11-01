(function ($, wpp_payment) {

    /**
     *
     * @param container
     * @constructor
     */
    function MiniCart(params) {
        this.message_container = '.widget_shopping_cart_content';
        wpp_payment.BaseGateway.call(this, params, container);
    }

    MiniCart.prototype.on_token_received = function () {
        this.block();
        this.block_cart();
        wpp_payment.BaseGateway.prototype.on_token_received.apply(this, arguments);
    }

    MiniCart.prototype.block_cart = function () {
        $(this.container).closest('.widget_shopping_cart_content').find('.wpp-payment-overlay').addClass('active');
    }

    MiniCart.prototype.unblock_cart = function () {
        $(this.container).closest('.widget_shopping_cart_content').find('.wpp-payment-overlay').removeClass('active');
    }

    /*------------------------- GPay -------------------------*/
    function GPay(params) {
        MiniCart.apply(this, arguments);
    }

    GPay.prototype = Object.assign({}, wpp_payment.BaseGateway.prototype, MiniCart.prototype, wpp_payment.GooglePay.prototype);

    GPay.prototype.initialize = function () {
        this.createPaymentsClient();
        this.isReadyToPay().then(function () {
            this.$button.find('.gpay-button').addClass('button');
            this.append_button();
        }.bind(this));
    }

    /**
     * @return {[type]}
     */
    GPay.prototype.create_button = function () {
        wpp_payment.GooglePay.prototype.create_button.apply(this, arguments);
        this.append_button();
    }

    GPay.prototype.append_button = function () {
        $(this.container).find('.wpp-payment-gpay-mini-cart').empty();
        $(this.container).find('.wpp-payment-gpay-mini-cart').append(this.$button).show();
    }

    /*------------------------- ApplePay -------------------------*/
    function ApplePay(params) {
        MiniCart.apply(this, arguments);
    }

    ApplePay.prototype = Object.assign({}, wpp_payment.BaseGateway.prototype, MiniCart.prototype, wpp_payment.ApplePay.prototype);


    ApplePay.prototype.initialize = function () {
        wpp_payment.ApplePay.prototype.initialize.apply(this, arguments);
    }

    ApplePay.prototype.append_button = function () {
        $(this.container).find('.wpp-payment-applepay-mini-cart').empty();
        $(this.container).find('.wpp-payment-applepay-mini-cart').append(this.$button).show();
    }

    /*------------------------- PaymentRequest -------------------------*/
    function PaymentRequest(params) {
        MiniCart.apply(this, arguments);
    }

    PaymentRequest.prototype = Object.assign({}, wpp_payment.BaseGateway.prototype, MiniCart.prototype, wpp_payment.PaymentRequest.prototype);

    PaymentRequest.prototype.initialize = function () {
        wpp_payment.PaymentRequest.prototype.initialize.apply(this, arguments);
    }

    PaymentRequest.prototype.create_button = function () {
        this.append_button();
    }

    PaymentRequest.prototype.append_button = function () {
        $(this.container).find('.wpp-payment-request-mini-cart').empty().show();
        this.paymentRequestButton.mount($(this.container).find('.wpp-payment-request-mini-cart').first()[0]);
    }

    /*-------------------------------------------------------------------------*/

    var gateways = [], container = null;

    if (typeof wpp_stripe_googlepay_mini_cart_params !== 'undefined') {
        gateways.push([GPay, wpp_stripe_googlepay_mini_cart_params]);
    }
    if (typeof wpp_stripe_applepay_mini_cart_params !== 'undefined') {
        gateways.push([ApplePay, wpp_stripe_applepay_mini_cart_params]);
    }
    if (typeof wpp_stripe_payment_request_mini_cart_params !== 'undefined') {
        gateways.push([PaymentRequest, wpp_stripe_payment_request_mini_cart_params]);
    }
    
    function load_mini_cart() {
        $('.widget_shopping_cart_content').each(function (idx, el) {
            if ($(el).find('.wpp_payment_mini_cart_payment_methods').length) {
                var $parent = $(el).parent();
                if ($parent.length) {
                    var class_name = 'wpp-payment-mini-cart-idx-' + idx;
                    $parent.addClass(class_name);
                    $parent.find('.widget_shopping_cart_content').prepend('<div class="wpp-payment-overlay"></div>');
                    container = '.' + class_name + ' .widget_shopping_cart_content p.woocommerce-mini-cart__buttons';
                    gateways.forEach(function (gateway) {
                        new gateway[0](gateway[1]);
                    })
                }
            }
        });
    }

    $(document.body).on('wc_fragments_refreshed wc_fragments_loaded', load_mini_cart);

}(jQuery, window.wpp_payment));