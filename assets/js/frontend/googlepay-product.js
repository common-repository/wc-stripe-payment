(function ($, wpp_payment) {

    /**
     * @constructor
     */
    function GPay() {
        wpp_payment.BaseGateway.call(this, wpp_payment_googlepay_product_params);
        window.addEventListener('hashchange', this.hashchange.bind(this));
    }

    /**
     * [prototype description]
     * @type {[type]}
     */
    GPay.prototype = $.extend({}, wpp_payment.BaseGateway.prototype, wpp_payment.ProductGateway.prototype, wpp_payment.GooglePay.prototype);

    /**
     * @return {[type]}
     */
    GPay.prototype.initialize = function () {
        if (!$(this.container).length) {
            return setTimeout(this.initialize.bind(this), 1000);
        }
        wpp_payment.ProductGateway.call(this);
        this.createPaymentsClient();
        this.isReadyToPay().then(function () {
            $(this.container).show();
            $(this.container).parent().parent().addClass('active');
        }.bind(this))
    }

    /**
     * @return {[type]}
     */
    GPay.prototype.create_button = function () {
        wpp_payment.GooglePay.prototype.create_button.apply(this, arguments);
        $('#wpp-payment-googlepay-container').append(this.$button);

        // check for variations
        if (this.is_variable_product()) {
            if (!this.variable_product_selected()) {
                this.disable_payment_button();
            } else {
                this.enable_payment_button();
            }
        }
    }

    /**
     * @return {[type]}
     */
    GPay.prototype.get_button = function () {
        return this.$button.find('button');
    }

    /**
     * @return {[type]}
     */
    GPay.prototype.start = function () {
        if (this.get_quantity() > 0) {
            this.add_to_cart().then(function () {
                this.set_shipping_options(arguments[0].shippingOptions);
                wpp_payment.GooglePay.prototype.start.apply(this, arguments);
            }.bind(this))
        } else {
            this.submit_error(this.params.messages.invalid_amount);
        }
    }

    GPay.prototype.found_variation = function () {
        wpp_payment.ProductGateway.prototype.found_variation.apply(this, arguments);
        this.enable_payment_button();
    }

    new GPay();

}(jQuery, wpp_payment))