(function ($, wpp_payment) {

    /**
     * @construct
     */
    function GPay() {
        this.can_pay = false;
        wpp_payment.BaseGateway.call(this, wpp_payment_googlepay_checkout_params);
        wpp_payment.CheckoutGateway.call(this);
        window.addEventListener('hashchange', this.hashchange.bind(this));
        this.has3DSecureParams();
    }

    /**
     * [prototype description]
     * @type {[type]}
     */
    GPay.prototype = $.extend({}, wpp_payment.BaseGateway.prototype, wpp_payment.CheckoutGateway.prototype, wpp_payment.GooglePay.prototype);

    /**
     * @return {[type]}
     */
    GPay.prototype.initialize = function () {
        if (!$(this.container).length) {
            return;
        }
        // create payments client
        this.createPaymentsClient();
        this.isReadyToPay().then(function () {
            $(this.container).show();
            if (this.banner_enabled()) {
                var $button = $(this.paymentsClient.createButton({
                    onClick: this.banner_checkout.bind(this),
                    buttonColor: this.params.button_color,
                    buttonType: this.params.button_style
                }));
                $(this.banner_container).show().parent().parent().addClass('active');
                $(this.banner_container).empty().append($button);
            }
        }.bind(this))
    }

    /**
     * @return {[type]}
     */
    GPay.prototype.create_button = function () {
        wpp_payment.GooglePay.prototype.create_button.apply(this, arguments);
        $('#place_order').after(this.$button);
        this.trigger_payment_method_selected();
    }

    /**
     * @return {[type]}
     */
    GPay.prototype.updated_checkout = function () {
        this.initialize();
    }

    /**
     * [banner_checkout description]
     * @return {[type]} [description]
     */
    GPay.prototype.banner_checkout = function () {
        this.set_payment_method(this.gateway_id);
        this.set_use_new_option(true);
        wpp_payment.GooglePay.prototype.start.apply(this, arguments);
    }

    GPay.prototype.on_token_received = function () {
        wpp_payment.CheckoutGateway.prototype.on_token_received.apply(this, arguments);
        if (this.payment_request_options.shippingAddressRequired) {
            this.maybe_set_ship_to_different();
        }
        this.fields.toFormFields({update_shipping_method: false});
        if (this.checkout_fields_valid()) {
            this.get_form().submit();
        }
    }

    GPay.prototype.payment_data_updated = function (response) {
        this.populate_billing_fields(response.address);
        this.fields.toFormFields({update_shipping_method: false});
    }

    new GPay();

}(jQuery, window.wpp_payment))