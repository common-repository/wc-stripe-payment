(function ($, wpp_payment) {

    /**
     * @constructor
     */
    function ApplePay() {
        wpp_payment.BaseGateway.call(this, wpp_payment_applepay_cart_params);
    }

    /**
     * [prototype description]
     * @type {[type]}
     */
    ApplePay.prototype = $.extend({}, wpp_payment.BaseGateway.prototype, wpp_payment.CartGateway.prototype, wpp_payment.ApplePay.prototype);

    ApplePay.prototype.initialize = function () {
        wpp_payment.CartGateway.call(this);
        wpp_payment.ApplePay.prototype.initialize.call(this);
        this.canMakePayment().then(function () {
            $(this.container).addClass('active').parent().addClass('active');
            this.add_cart_totals_class();
        }.bind(this));
    }

    /**
     * @return {[type]}
     */
    ApplePay.prototype.append_button = function () {
        $('#wpp-payment-applepay-container').append(this.$button);
    }

    /**
     * @return {[type]}
     */
    ApplePay.prototype.updated_html = function () {
        if (!$(this.container).length) {
            this.can_pay = false;
        }
        if (this.can_pay) {
            this.create_button();
            $(this.container).show().addClass('active').parent().addClass('active');
            this.add_cart_totals_class();
        }
    }

    /**
     * Called when the cart has been emptied
     * @param  {[type]} e [description]
     * @return {[type]}   [description]
     */
    ApplePay.prototype.cart_emptied = function (e) {
        this.can_pay = false;
    }

    new ApplePay();

}(jQuery, window.wpp_payment))