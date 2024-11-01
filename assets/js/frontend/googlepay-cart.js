(function ($, wpp_payment) {

    /**
     * @constructor
     */
    function GPay() {
        wpp_payment.BaseGateway.call(this, wpp_payment_googlepay_cart_params);
        wpp_payment.CartGateway.call(this);
        window.addEventListener('hashchange', this.hashchange.bind(this));
    }

    /**
     * [prototype description]
     * @type {[type]}
     */
    GPay.prototype = $.extend({}, wpp_payment.BaseGateway.prototype, wpp_payment.CartGateway.prototype, wpp_payment.GooglePay.prototype);

    /**
     * @return {[type]}
     */
    GPay.prototype.initialize = function () {
        this.createPaymentsClient();
        this.isReadyToPay().then(function () {
            $(this.container).show().addClass('active').parent().addClass('active');
            this.add_cart_totals_class();
        }.bind(this))
    }

    /**
     * @return {[type]}
     */
    GPay.prototype.create_button = function () {
        wpp_payment.GooglePay.prototype.create_button.apply(this, arguments);
        $('#wpp-payment-googlepay-container').append(this.$button);
    }

    /**
     * @return {[type]}
     */
    GPay.prototype.updated_html = function () {
        if (this.can_pay) {
            this.create_button();
            $(this.container).show().addClass('active').parent().addClass('active');
            this.add_cart_totals_class();
        }
    }

    /**
     * @param  {[type]}
     * @return {[null]}
     */
    GPay.prototype.payment_data_updated = function (response, event) {
        if (event.callbackTrigger === "SHIPPING_ADDRESS") {
            $(document.body).trigger('wc_update_cart');
        }
    }

    new GPay();

}(jQuery, window.wpp_payment))