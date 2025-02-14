(function ($, wpp_payment) {

    var PaymentRequest;

    // Product page functionality
    if ($(document.body).is('.single-product')) {
        /**
         * [PaymentRequest description]
         */
        PaymentRequest = function () {
            wpp_payment.BaseGateway.call(this, wpp_payment_payment_request_params);
            window.addEventListener('hashchange', this.hashchange.bind(this));
            this.old_qty = this.get_quantity();
        }

        PaymentRequest.prototype = $.extend({}, wpp_payment.BaseGateway.prototype, wpp_payment.ProductGateway.prototype, wpp_payment.PaymentRequest.prototype);

        PaymentRequest.prototype.initialize = function () {
            if (!$(this.container).length) {
                return setTimeout(this.initialize.bind(this), 1000);
            }
            wpp_payment.ProductGateway.call(this);
            wpp_payment.PaymentRequest.prototype.initialize.call(this);
        }

        /**
         * [canMakePayment description]
         * @return {[type]} [description]
         */
        PaymentRequest.prototype.canMakePayment = function () {
            wpp_payment.PaymentRequest.prototype.canMakePayment.apply(this, arguments).then(function () {
                $(document.body).on('change', '[name="quantity"]', this.add_to_cart.bind(this));
                $(this.container).parent().parent().addClass('active');
                if (!this.is_variable_product()) {
                    this.cart_calculation();
                } else {
                    if (this.variable_product_selected()) {
                        this.cart_calculation(this.get_product_data().variation.variation_id);
                        $(this.container).removeClass('processingFoundVariation');
                    } else {
                        this.disable_payment_button();
                    }
                }
            }.bind(this))
        }

        /**
         * [add_to_cart description]
         */
        PaymentRequest.prototype.add_to_cart = function (e) {
            this.disable_payment_button();
            this.old_qty = this.get_quantity();
            var variation = this.get_product_data().variation;
            if (!this.is_variable_product() || this.variable_product_selected()) {
                this.cart_calculation(variation.variation_id).then(function () {
                    if (this.is_variable_product() && $(this.container).is('.processingFoundVariation')) {
                        this.createPaymentRequest();
                        this.createPaymentRequestButton();
                        wpp_payment.PaymentRequest.prototype.canMakePayment.apply(this, arguments).then(function () {
                            this.enable_payment_button();
                            $(this.container).removeClass('processingFoundVariation');
                        }.bind(this));
                    } else {
                        this.enable_payment_button();
                    }
                }.bind(this));
            }
        }

        PaymentRequest.prototype.cart_calculation = function () {
            return wpp_payment.ProductGateway.prototype.cart_calculation.apply(this, arguments).then(function () {
                this.paymentRequest.update(this.get_payment_request_update({
                    total: {
                        pending: false
                    }
                }));
            }.bind(this)).catch(function () {

            }.bind(this));
        }

        PaymentRequest.prototype.create_button = function () {
            $('#wpp-payment-request-container').empty();
            wpp_payment.PaymentRequest.prototype.create_button.apply(this, arguments);
            this.$button = $('#wpp-payment-request-container');
        }

        PaymentRequest.prototype.button_click = function (e) {
            if (this.$button.is('.disabled')) {
                e.preventDefault();
            } else if (this.get_quantity() == 0) {
                e.preventDefault();
                this.submit_error(this.params.messages.invalid_amount);
            }
        }

        PaymentRequest.prototype.found_variation = function () {
            $(this.container).addClass('processingFoundVariation');
            wpp_payment.ProductGateway.prototype.found_variation.apply(this, arguments);
        }

        /**
         * [block description]
         * @return {[type]} [description]
         */
        PaymentRequest.prototype.block = function () {
            $.blockUI({
                message: this.adding_to_cart ? this.params.messages.add_to_cart : null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        }

    }

    // Cart page functionality
    if ($(document.body).is('.woocommerce-cart')) {
        /**
         * [PaymentRequest description]
         */
        PaymentRequest = function () {
            wpp_payment.BaseGateway.call(this, wpp_payment_payment_request_params);
            window.addEventListener('hashchange', this.hashchange.bind(this));
        }

        PaymentRequest.prototype = $.extend({}, wpp_payment.BaseGateway.prototype, wpp_payment.CartGateway.prototype, wpp_payment.PaymentRequest.prototype);

        PaymentRequest.prototype.initialize = function () {
            wpp_payment.CartGateway.call(this);
            wpp_payment.PaymentRequest.prototype.initialize.call(this);
        }

        PaymentRequest.prototype.canMakePayment = function () {
            wpp_payment.PaymentRequest.prototype.canMakePayment.apply(this, arguments).then(function () {
                $(this.container).addClass('active').parent().addClass('active');
            }.bind(this))
        }

        /**
         * [updated_html description]
         * @return {[type]} [description]
         */
        PaymentRequest.prototype.updated_html = function () {
            if (!$(this.container).length) {
                this.can_pay = false;
            }
            if (this.can_pay) {
                this.initialize();
            }
        }

        PaymentRequest.prototype.button_click = function (e) {
            this.paymentRequest.update(this.get_payment_request_update({
                total: {
                    pending: false
                }
            }));
        }

        /**
         * Called when the cart has been emptied
         * @param  {[type]} e [description]
         * @return {[type]}   [description]
         */
        PaymentRequest.prototype.cart_emptied = function (e) {
            this.can_pay = false;
        }
    }

    // Checkout page functionality
    if ($(document.body).is('.woocommerce-checkout')) {
        /**
         * [PaymentRequest description]
         */
        PaymentRequest = function () {
            wpp_payment.BaseGateway.call(this, wpp_payment_payment_request_params);
            window.addEventListener('hashchange', this.hashchange.bind(this));
        }

        PaymentRequest.prototype = $.extend({}, wpp_payment.BaseGateway.prototype, wpp_payment.CheckoutGateway.prototype, wpp_payment.PaymentRequest.prototype);

        PaymentRequest.prototype.initialize = function () {
            wpp_payment.CheckoutGateway.call(this);
            $('form.checkout').on('change', '.form-row:not(.address-field) .input-text', this.update_payment_request.bind(this));
            if ($(this.container).length) {
                wpp_payment.PaymentRequest.prototype.initialize.call(this);
            }
        }
        /**
         * [canMakePayment description]
         * @return {[type]} [description]
         */
        PaymentRequest.prototype.canMakePayment = function () {
            wpp_payment.PaymentRequest.prototype.canMakePayment.apply(this, arguments).then(function () {
                this.show_icons();
                if (this.banner_enabled()) {
                    $(this.banner_container).empty().show().append('<div id="wpp-payment-request-banner"></div>');
                    $(this.banner_container).show().parent().parent().addClass('active');
                    var elements = this.stripe.elements();
                    var button = elements.create("paymentRequestButton", {
                        paymentRequest: this.paymentRequest,
                        style: {
                            paymentRequestButton: {
                                type: this.params.button.type,
                                theme: this.params.button.theme,
                                height: this.params.button.height
                            }
                        }
                    });
                    button.on('click', this.banner_checkout.bind(this));
                    button.mount("#wpp-payment-request-banner");
                }
            }.bind(this))
        }

        /**
         * [create_button description]
         * @return {[type]} [description]
         */
        PaymentRequest.prototype.create_button = function () {
            if (this.$button) {
                this.$button.remove();
            }
            this.$button = $('<div id="wpp-payment-request-container"></div>');
            $('#place_order').after(this.$button);
            wpp_payment.PaymentRequest.prototype.create_button.call(this);
            this.trigger_payment_method_selected();
        }

        /**
         * [updated_checkout description]
         * @return {[type]} [description]
         */
        PaymentRequest.prototype.updated_checkout = function () {
            if ($(this.container).length) {
                wpp_payment.PaymentRequest.prototype.initialize.call(this);
            }
        }

        /**
         * [button_click description]
         * @param  {[type]} e [description]
         * @return {[type]}   [description]
         */
        PaymentRequest.prototype.banner_checkout = function (e) {
            this.set_payment_method(this.gateway_id);
            this.set_use_new_option(true);
        }

        PaymentRequest.prototype.on_token_received = function () {
            wpp_payment.CheckoutGateway.prototype.on_token_received.apply(this, arguments);
            this.fields.toFormFields();
            if (this.payment_request_options.requestShipping) {
                this.maybe_set_ship_to_different();
            }
            if (this.checkout_fields_valid()) {
                this.get_form().submit();
            }
        }

        PaymentRequest.prototype.update_payment_request = function () {
            if ($(this.container).length) {
                wpp_payment.PaymentRequest.prototype.initialize.call(this);
            }
        }

        PaymentRequest.prototype.show_icons = function () {
            if ($(this.container).length) {
                $(this.container).find('.wpp-payment-paymentRequest-icon.gpay').show();
            }
        }
    }

    new PaymentRequest();

}(jQuery, window.wpp_payment))