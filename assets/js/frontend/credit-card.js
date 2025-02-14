(function ($, wpp_payment) {

    /**
     * Credit card class.
     *
     * @constructor
     */
    function CC() {
        wpp_payment.BaseGateway.call(this, wpp_payment_credit_card_params);
        wpp_payment.CheckoutGateway.call(this);
        window.addEventListener('hashchange', this.hashchange.bind(this));
        wpp_payment.credit_card = this;
        this.confirmedSetupIntent = false;
        this.has3DSecureParams();
        this.handle_create_account_change();
    }

    var elementClasses = {
        focus: 'focused',
        empty: 'empty',
        invalid: 'invalid'
    }

    CC.prototype = $.extend({}, wpp_payment.BaseGateway.prototype, wpp_payment.CheckoutGateway.prototype);

    CC.prototype.mappings = {
        cardNumber: '#stripe-card-number',
        cardExpiry: '#stripe-exp',
        cardCvc: '#stripe-cvv'
    }

    /**
     *
     */
    CC.prototype.initialize = function () {
        $(document.body).on('click', '#place_order', this.place_order.bind(this));
        $(document.body).on('change', '#createaccount', this.handle_create_account_change.bind(this));
        this.setup_card();

        if (this.can_create_setup_intent()) {
            this.create_setup_intent();
        }
    }

    /**
     *
     */
    CC.prototype.setup_card = function () {
        if (this.is_custom_form()) {
            var options = $.extend(true, {
                classes: elementClasses
            }, this.params.cardOptions);
            // create individual card sections
            ['cardNumber', 'cardExpiry', 'cardCvc'].forEach(function (type) {
                this[type] = this.elements.create(type, $.extend(true, {}, options, this.params.customFieldOptions[type]));
            }.bind(this));
            this.cardNumber.on('change', this.card_number_change.bind(this));
            this.cardNumber.on('change', this.on_input_change.bind(this));
            this.cardExpiry.on('change', this.on_input_change.bind(this));
            this.cardCvc.on('change', this.on_input_change.bind(this));
            if (this.fields.required('billing_postcode') && '' !== this.fields.get('billing_postcode')) {
                if ($('#stripe-postal-code').length > 0) {
                    $('#stripe-postal-code').val(this.fields.get('billing_postcode'));
                    this.validate_postal_field();
                }
            }
            $(document.body).on('change', '#billing_postcode', function (e) {
                var val = $('#billing_postcode').val();
                $('#stripe-postal-code').val(val).trigger('keyup');
            }.bind(this));
        } else {
            this.card = this.elements.create('card', $.extend(true, {}, {
                value: {
                    postalCode: this.fields.get('billing_postcode', '')
                },
                hidePostalCode: this.fields.required('billing_postcode'),
                iconStyle: 'default'
            }, this.params.cardOptions));
            $(document.body).on('change', '#billing_postcode', function (e) {
                if (this.card) {
                    this.card.update({value: $('#billing_postcode').val()});
                }
            }.bind(this));
        }
        // setup a timeout so CC element is always rendered.
        setInterval(this.create_card_element.bind(this), 2000);
    }

    CC.prototype.validate_postal_field = function () {
        if ($('#billing_postcode').length && $('#stripe-postal-code').length) {
            // validate postal code
            if (this.params.postal_regex[this.fields.get('billing_country')]) {
                var regex = this.params.postal_regex[this.fields.get('billing_country')],
                    postal = $('#stripe-postal-code').val(),
                    regExp = new RegExp(regex, "i");
                if (postal !== '') {
                    if (regExp.exec(postal) !== null) {
                        $('#stripe-postal-code').addClass('StripeElement--complete').removeClass('invalid');
                    } else {
                        $('#stripe-postal-code').removeClass('StripeElement--complete').addClass('invalid');
                    }
                } else {
                    $('#stripe-postal-code').removeClass('StripeElement--complete').removeClass('invalid');
                }
            } else {
                if ($('#stripe-postal-code').val() != 0) {
                    $('#stripe-postal-code').addClass('StripeElement--complete');
                } else {
                    $('#stripe-postal-code').removeClass('StripeElement--complete');
                }
            }
        } else if ($('#stripe-postal-code').length) {
            if ($('#stripe-postal-code').val() != '') {
                $('#stripe-postal-code').addClass('StripeElement--complete');
            } else {
                $('#stripe-postal-code').removeClass('StripeElement--complete');
            }
        }
    }

    /**
     *
     */
    CC.prototype.create_card_element = function () {
        if (this.is_custom_form()) {
            if ($('#wpp-payment-cc-custom-form').length && $('#wpp-payment-cc-custom-form').find('iframe').length == 0) {
                if ($(this.mappings.cardNumber).length) {
                    this.cardNumber.mount(this.mappings.cardNumber);
                    $(this.mappings.cardNumber).prepend(this.params.html.card_brand);
                }
                if ($(this.mappings.cardExpiry).length) {
                    this.cardExpiry.mount(this.mappings.cardExpiry);
                }
                if ($(this.mappings.cardCvc).length) {
                    this.cardCvc.mount(this.mappings.cardCvc);
                }
                if ($('#stripe-postal-code').length) {
                    $('#stripe-postal-code, .postalCode').on('focus', function (e) {
                        $('#stripe-postal-code').addClass('focused');
                    }.bind(this));
                    $('#stripe-postal-code, .postalCode').on('blur', function (e) {
                        $('#stripe-postal-code').removeClass('focused').trigger('keyup');
                    }.bind(this));
                    $('#stripe-postal-code').on('keyup', function (e) {
                        if ($('#stripe-postal-code').val() == 0) {
                            $('#stripe-postal-code').addClass('empty');
                        } else {
                            $('#stripe-postal-code').removeClass('empty');
                        }
                    }.bind(this));
                    $('#stripe-postal-code').on('change', this.validate_postal_field.bind(this));
                    $('#stripe-postal-code').trigger('change');
                }
            }
        } else {
            if ($('#wpp-payment-card-element').length) {
                if ($('#wpp-payment-card-element').find('iframe').length == 0) {
                    this.card.unmount();
                    this.card.mount('#wpp-payment-card-element');
                    this.card.update({
                        value: {
                            postalCode: this.fields.get('billing_postcode', '')
                        },
                        hidePostalCode: this.fields.required('billing_postcode')
                    });
                }
            }
        }
        if ($(this.container).outerWidth(true) < 450) {
            $(this.container).addClass('stripe-small-container');
        } else {
            $(this.container).removeClass('stripe-small-container');
        }
    }

    CC.prototype.place_order = function (e) {
        if (this.is_gateway_selected()) {
            if (this.can_create_setup_intent() && !this.is_saved_method_selected() && this.checkout_fields_valid()) {
                e.preventDefault();
                if (this.confirmedSetupIntent) {
                    return this.on_setup_intent_received(this.confirmedSetupIntent);
                }
                this.stripe.confirmCardSetup(this.client_secret, {
                    payment_method: {
                        card: this.is_custom_form() ? this.cardNumber : this.card,
                        billing_details: (function () {
                            if (this.is_current_page('checkout')) {
                                return this.get_billing_details();
                            }
                            return $.extend({}, this.is_custom_form() ? {address: {postal_code: $('#stripe-postal-code').val()}} : {});
                        }.bind(this)())
                    }
                }).then(function (result) {
                    if (result.error) {
                        this.submit_error(result.error);
                        return;
                    }
                    this.confirmedSetupIntent = result.setupIntent;
                    this.on_setup_intent_received(result.setupIntent);
                }.bind(this))
            } else {
                if (!this.payment_token_received && !this.is_saved_method_selected()) {
                    e.preventDefault();
                    if (this.checkout_fields_valid()) {
                        this.stripe.createPaymentMethod({
                            type: 'card',
                            card: this.is_custom_form() ? this.cardNumber : this.card,
                            billing_details: this.get_billing_details()
                        }).then(function (result) {
                            if (result.error) {
                                return this.submit_error(result.error);
                            }
                            this.on_token_received(result.paymentMethod);
                        }.bind(this))
                    }
                }
            }
        }
    }

    /**
     * @returns {boolean}
     */
    CC.prototype.checkout_place_order = function () {
        if (!this.is_saved_method_selected() && !this.payment_token_received) {
            this.place_order.apply(this, arguments);
            return false;
        }
        return wpp_payment.CheckoutGateway.prototype.checkout_place_order.apply(this, arguments);
    }

    /**
     *
     */
    CC.prototype.create_setup_intent = function () {
        return new Promise(function (resolve, reject) {
            // call intent api
            $.when($.ajax({
                method: 'POST',
                dataType: 'json',
                data: {payment_method: this.gateway_id},
                url: this.params.routes.setup_intent,
                beforeSend: this.ajax_before_send.bind(this)
            })).done(function (response) {
                if (response.code) {
                    this.submit_error(response.message);
                    resolve(response);
                } else {
                    this.client_secret = response.intent.client_secret;
                    resolve(response);
                }
            }.bind(this)).fail(function (xhr, textStatus, errorThrown) {
                this.submit_error(errorThrown);
            }.bind(this));
        }.bind(this))
    }

    /**
     *
     */
    CC.prototype.on_token_received = function (paymentMethod) {
        this.payment_token_received = true;
        this.set_nonce(paymentMethod.id);
        this.get_form().submit();
    }

    /**
     *
     */
    CC.prototype.on_setup_intent_received = function (setup_intent) {
        this.payment_token_received = true;
        this.set_nonce(setup_intent.payment_method);
        this.set_intent(setup_intent.id);
        this.get_form().submit();
    }

    /**
     *
     */
    CC.prototype.updated_checkout = function () {
        this.create_card_element();
        this.handle_create_account_change();
        if (this.can_create_setup_intent() && !this.client_secret) {
            this.create_setup_intent();
        }
    }

    /**
     *
     */
    CC.prototype.update_checkout = function () {
        this.clear_card_elements();
    }

    CC.prototype.show_payment_button = function () {
        wpp_payment.CheckoutGateway.prototype.show_place_order.apply(this, arguments);
    }

    /**
     * [Leave empty so that the place order button is not hidden]
     * @return {[type]} [description]
     */
    CC.prototype.hide_place_order = function () {

    }

    /**
     * Returns true if a custom form is being used.
     * @return {Boolean} [description]
     */
    CC.prototype.is_custom_form = function () {
        return this.params.custom_form === "1";
    }

    /**
     * [get_postal_code description]
     * @return {[type]} [description]
     */
    CC.prototype.get_postal_code = function () {
        if (this.is_custom_form()) {
            if ($('#stripe-postal-code').length > 0) {
                return $('#stripe-postal-code').val();
            }
            return this.fields.get('billing_postcode', null);
        }
        return this.fields.get('billing_postcode', null);
    }

    CC.prototype.card_number_change = function (data) {
        if (data.brand === "unknown") {
            $('#wpp-payment-card').removeClass('active');
        } else {
            $('#wpp-payment-card').addClass('active');
        }
        $('#wpp-payment-card').attr('src', this.params.cards[data.brand]);
    }

    CC.prototype.on_input_change = function (event) {
        if (event.complete) {
            var $elements = $('#wpp-payment-cc-custom-form').find('.StripeElement, #stripe-postal-code');
            var order = [];
            $elements.each(function (idx, el) {
                order.push('#' + $(el).attr('id'));
            }.bind(this));
            var selector = this.mappings[event.elementType];
            var idx = order.indexOf(selector);
            if (typeof order[idx + 1] !== 'undefined') {
                if (order[idx + 1] === '#stripe-postal-code') {
                    document.getElementById('stripe-postal-code').focus();
                } else {
                    for (var k in this.mappings) {
                        if (this.mappings[k] === order[idx + 1]) {
                            this[k].focus();
                        }
                    }
                }
            }
        }
    }

    CC.prototype.clear_card_elements = function () {
        var elements = ['cardNumber', 'cardExpiry', 'cardCvc'];
        for (var i = 0; i < elements.length; i++) {
            if (this[elements[i]]) {
                this[elements[i]].clear();
            }
        }
    }

    CC.prototype.checkout_error = function () {
        if (this.is_gateway_selected()) {
            this.payment_token_received = false;
        }
        wpp_payment.CheckoutGateway.prototype.checkout_error.call(this);
    }

    CC.prototype.get_billing_details = function () {
        var details = wpp_payment.BaseGateway.prototype.get_billing_details.call(this);
        details.address.postal_code = this.get_postal_code();
        return details;
    }

    CC.prototype.can_create_setup_intent = function () {
        return this.is_add_payment_method_page() || this.is_change_payment_method() ||
            (this.is_current_page('checkout') && this.cart_contains_subscription() && this.get_gateway_data() && this.get_total_price_cents() == 0) ||
            (this.is_current_page(['checkout', 'product']) && typeof wpp_payment_preorder_exists !== 'undefined') ||
            (this.is_current_page('order_pay') && 'pre_order' in this.get_gateway_data() && this.get_gateway_data().pre_order === true) ||
            (this.is_current_page('product') && this.get_total_price_cents() == 0);
    }

    CC.prototype.handle_create_account_change = function () {
        if ($('#createaccount').length) {
            if ($('#createaccount').is(':checked')) {
                $('.wpp-payment-save-source').show();
            } else {
                $('.wpp-payment-save-source').hide();
            }
        }
    }

    new CC();

}(jQuery, window.wpp_payment))