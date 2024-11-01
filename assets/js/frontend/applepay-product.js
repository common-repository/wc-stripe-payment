(function ($, wpp_payment) {

    function ApplePay() {
        wpp_payment.BaseGateway.call(this, wpp_payment_applepay_product_params);
        this.old_qty = this.get_quantity();
    }

    /**
     * [prototype description]
     * @type {[type]}
     */
    ApplePay.prototype = $.extend({}, wpp_payment.BaseGateway.prototype, wpp_payment.ProductGateway.prototype, wpp_payment.ApplePay.prototype);

    ApplePay.prototype.initialize = function () {
        if (!$('.wpp_payment_product_payment_methods ' + this.container).length) {
            setTimeout(this.initialize.bind(this), 1000);
            return;
        }
        this.container = '.wpp_payment_product_payment_methods ' + this.container;
        wpp_payment.ProductGateway.call(this);
        wpp_payment.ApplePay.prototype.initialize.call(this);
    }

    /**
     * @return {[type]}
     */
    ApplePay.prototype.canMakePayment = function () {
        wpp_payment.ApplePay.prototype.canMakePayment.call(this).then(function () {
            $(document.body).on('change', '[name="quantity"]', this.add_to_cart.bind(this));
            $(this.container).parent().parent().addClass('active');
            if (!this.is_variable_product()) {
                this.cart_calculation();
            } else {
                if (this.variable_product_selected()) {
                    this.cart_calculation(this.get_product_data().variation.variation_id);
                } else {
                    this.disable_payment_button();
                }
            }
        }.bind(this))
    }

    /**
     * @param  {[type]}
     * @return {[type]}
     */
    ApplePay.prototype.start = function (e) {
        if (this.get_quantity() === 0) {
            e.preventDefault();
            this.submit_error(this.params.messages.invalid_amount);
        } else {
            wpp_payment.ApplePay.prototype.start.apply(this, arguments);
        }
    }

    /**
     * @return {[type]}
     */
    ApplePay.prototype.append_button = function () {
        $('#wpp-payment-applepay-container').append(this.$button);
    }

    ApplePay.prototype.add_to_cart = function () {
        this.disable_payment_button();
        this.old_qty = this.get_quantity();
        var variation = this.get_product_data().variation;
        if (!this.is_variable_product() || this.variable_product_selected()) {
            this.cart_calculation(variation.variation_id).then(function () {
                if (this.is_variable_product()) {
                    this.createPaymentRequest();
                    wpp_payment.ApplePay.prototype.canMakePayment.apply(this, arguments).then(function () {
                        this.enable_payment_button();
                    }.bind(this));
                } else {
                    this.enable_payment_button();
                }
            }.bind(this));
        }
    }

    new ApplePay();

}(jQuery, wpp_payment))