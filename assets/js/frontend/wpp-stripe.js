(function (window, $) {
    window.wpp_payment = {};
    var stripe = null;

    if (typeof wpp_payment_checkout_fields === 'undefined') {
        window.wpp_payment_checkout_fields = [];
    }

    wpp_payment.BaseGateway = function (params, container) {
        this.params = params;
        this.gateway_id = this.params.gateway_id;
        this.container = typeof container === 'undefined' ? 'li.payment_method_'.concat(this.gateway_id) : container;

        if (!$(this.container).length) {
            this.container = '.payment_method_'.concat(this.gateway_id);
        }

        this.token_selector = this.params.token_selector;
        this.saved_method_selector = this.params.saved_method_selector;
        this.payment_token_received = false;
        this.stripe = stripe;
        this.fields = checkoutFields;
        this.elements = stripe.elements($.extend({}, {
            locale: 'auto'
        }, this.get_element_options()));
        this.initialize();
    };

    wpp_payment.BaseGateway.prototype.get_page = function () {
        var page = wpp_payment_params_v3.page;
        if ('cart' === page && $(document.body).is('.woocommerce-checkout')) {
            page = 'checkout';
        }
        return page;
    };

    wpp_payment.BaseGateway.prototype.set_nonce = function (value) {
        this.fields.set(this.gateway_id + '_token_key', value);
        $(this.token_selector).val(value);
    };

    wpp_payment.BaseGateway.prototype.set_intent = function (value) {
        this.fields.set(this.gateway_id + '_payment_intent_key', value);
        $('#' + this.gateway_id + '_payment_intent_key').val(value);
    };

    wpp_payment.BaseGateway.prototype.get_element_options = function () {
        return this.params.elementOptions;
    };

    wpp_payment.BaseGateway.prototype.initialize = function () {
    };


    wpp_payment.BaseGateway.prototype.create_button = function () {
    };

    wpp_payment.BaseGateway.prototype.is_gateway_selected = function () {
        return $('[name="payment_method"]:checked').val() === this.gateway_id;
    };

    wpp_payment.BaseGateway.prototype.is_saved_method_selected = function () {
        return this.is_gateway_selected() && $('[name="' + this.gateway_id + '_payment_type_key"]:checked').val() === 'saved';
    };

    wpp_payment.BaseGateway.prototype.has_checkout_error = function () {
        return $('#wpp_payment_checkout_error').length > 0 && this.is_gateway_selected();
    };

    wpp_payment.BaseGateway.prototype.submit_error = function (error) {
        var message = this.get_error_message(error);

        if (message.indexOf('</ul>') < 0) {
            var classes = (function () {
                var classes = 'woocommerce-NoticeGroup';
                if (this.is_current_page('checkout')) {
                    classes += ' woocommerce-NoticeGroup-checkout';
                }
                return classes;
            }.bind(this)());
            message = '<div class="' + classes + '"><ul class="woocommerce-error"><li>' + message + '</li></ul></div>';
        }
        var custom_message = $(document.body).triggerHandler('wpp_payment_submit_error', [message, error, this]);
        message = typeof custom_message === 'undefined' ? message : custom_message;
        this.submit_message(message);
    };

    wpp_payment.BaseGateway.prototype.submit_error_code = function (code) {
        console.log(code);
    };

    wpp_payment.BaseGateway.prototype.get_error_message = function (message) {
        if (typeof message == 'object') {
            if (message.code && wpp_payment_messages[message.code]) {
                message = wpp_payment_messages[message.code];
            } else {
                message = message.message;
            }
        }

        return message;
    };

    wpp_payment.BaseGateway.prototype.submit_message = function (message) {
        $('.woocommerce-error, .woocommerce-message, .woocommerce-info').remove();
        var $container = $(this.message_container);

        if ($container.closest('form').length) {
            $container = $container.closest('form');
        }

        $container.prepend(message);
        $container.removeClass('processing').unblock();
        $container.find('.input-text, select, input:checkbox').blur();

        if ($.scroll_to_notices) {
            $.scroll_to_notices($container);
        } else {
            $('html, body').animate({
                scrollTop: $container.offset().top - 100
            }, 1000);
        }
    };

    wpp_payment.BaseGateway.prototype.get_billing_details = function () {
        var details = {
            name: this.get_customer_name('billing'),
            address: {
                city: this.fields.get('billing_city', null),
                country: this.fields.get('billing_country', null),
                line1: this.fields.get('billing_address_1', null),
                line2: this.fields.get('billing_address_2', null),
                postal_code: this.fields.get('billing_postcode', null),
                state: this.fields.get('billing_state', null)
            }
        }
        if (!details.name || details.name === ' ') {
            delete details.name;
        }
        if (this.fields.get('billing_email') != '') {
            details.email = this.fields.get('billing_email');
        }
        if (this.fields.get('billing_phone') != '') {
            details.phone = this.fields.get('billing_phone');
        }
        return details;
    }

    wpp_payment.BaseGateway.prototype.get_first_name = function (prefix) {
        return $('#' + prefix + '_first_name').val();
    };

    wpp_payment.BaseGateway.prototype.get_last_name = function (prefix) {
        return $('#' + prefix + '_last_name').val();
    };

    wpp_payment.BaseGateway.prototype.get_shipping_prefix = function () {
        if (this.needs_shipping() && $('[name="ship_to_different_address"]').length > 0 && $('[name="ship_to_different_address"]').is(':checked')) {
            return 'shipping';
        }
        return 'billing';
    }

    wpp_payment.BaseGateway.prototype.should_save_method = function () {
        return $('#' + this.gateway_id + '_save_source_key').is(':checked');
    };

    wpp_payment.BaseGateway.prototype.is_add_payment_method_page = function () {
        return this.get_page() === 'add_payment_method' || $(document.body).hasClass('woocommerce-add-payment-method');
    };

    wpp_payment.BaseGateway.prototype.is_change_payment_method = function () {
        return this.get_page() === 'change_payment_method';
    };

    wpp_payment.BaseGateway.prototype.get_selected_payment_method = function () {
        return $(this.saved_method_selector).val();
    };

    wpp_payment.BaseGateway.prototype.needs_shipping = function () {
        return this.get_gateway_data().needs_shipping;
    };

    wpp_payment.BaseGateway.prototype.get_currency = function () {
        return this.get_gateway_data().currency;
    };

    wpp_payment.BaseGateway.prototype.get_gateway_data = function () {
        var data = $(this.container).find(".woocommerce_".concat(this.gateway_id, "_gateway_data")).data('gateway');
        if (typeof data === 'undefined' && this.is_current_page('checkout')) {
            data = $('form.checkout').find(".woocommerce_".concat(this.gateway_id, "_gateway_data")).data('gateway');
            if (typeof data === 'undefined') {
                data = $('.woocommerce_' + this.gateway_id + '_gateway_data').data('gateway');
            }
        }
        return data;
    };

    wpp_payment.BaseGateway.prototype.set_gateway_data = function (data) {
        $(this.container).find(".woocommerce_".concat(this.gateway_id, "_gateway_data")).data('gateway', data);
    };

    wpp_payment.BaseGateway.prototype.get_customer_name = function (prefix) {
        return this.fields.get(prefix + '_first_name') + ' ' + this.fields.get(prefix + '_last_name');
    };

    wpp_payment.BaseGateway.prototype.get_customer_email = function () {
        return this.fields.get('billing_email');
    };

    wpp_payment.BaseGateway.prototype.get_address_field_hash = function (prefix) {
        var params = ['_first_name', '_last_name', '_address_1', '_address_2', '_postcode', '_city', '_state', '_country'];
        var hash = '';

        for (var i = 0; i < params.length; i++) {
            hash += this.fields.get(prefix + params[i]) + '_';
        }

        return hash;
    };

    wpp_payment.BaseGateway.prototype.block = function () {
        if ($().block) {
            $.blockUI({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        }
    }

    wpp_payment.BaseGateway.prototype.unblock = function () {
        if ($().block) {
            $.unblockUI();
        }
    };

    wpp_payment.BaseGateway.prototype.get_form = function () {
        return $(this.token_selector).closest('form');
    };

    wpp_payment.BaseGateway.prototype.get_total_price = function () {
        return this.get_gateway_data().total;
    };

    wpp_payment.BaseGateway.prototype.get_total_price_cents = function () {
        return this.get_gateway_data().total_cents;
    };

    wpp_payment.BaseGateway.prototype.set_total_price = function (total) {
        var data = this.get_gateway_data();
        data.total = total;
        this.set_gateway_data(data);
    };

    wpp_payment.BaseGateway.prototype.set_total_price_cents = function (total) {
        var data = this.get_gateway_data();
        data.total_cents = total;
        this.set_gateway_data(data);
    };

    wpp_payment.BaseGateway.prototype.set_payment_method = function (payment_method) {
        $('[name="payment_method"][value="' + payment_method + '"]').prop("checked", true).trigger('click');
    };

    wpp_payment.BaseGateway.prototype.set_selected_shipping_methods = function (shipping_methods) {
        this.fields.set('shipping_method', shipping_methods);

        if (shipping_methods && $('[name^="shipping_method"]').length) {
            for (var i in shipping_methods) {
                var method = shipping_methods[i];
                $('[name="shipping_method[' + i + ']"][value="' + method + '"]').prop("checked", true).trigger('change');
            }
        }
    };

    wpp_payment.BaseGateway.prototype.on_token_received = function (paymentMethod) {
        this.payment_token_received = true;
        this.set_nonce(paymentMethod.id);
        this.process_checkout();
    };

    wpp_payment.BaseGateway.prototype.createPaymentRequest = function () {
        try {
            this.payment_request_options = this.get_payment_request_options();
            this.paymentRequest = stripe.paymentRequest(this.payment_request_options);
            if (this.payment_request_options.requestShipping) {
                this.paymentRequest.on('shippingaddresschange', this.update_shipping_address.bind(this));
                this.paymentRequest.on('shippingoptionchange', this.update_shipping_method.bind(this));
            }

            this.paymentRequest.on('paymentmethod', this.on_payment_method_received.bind(this));
        } catch (err) {
            this.submit_error(err.message);
            return;
        }
    };

    wpp_payment.BaseGateway.prototype.get_payment_request_options = function () {
        var options = {
            country: this.params.country_code,
            currency: this.get_currency().toLowerCase(),
            total: {
                amount: this.get_total_price_cents(),
                label: this.params.total_label,
                pending: true
            },
            requestPayerName: (function () {
                if (this.is_current_page('checkout')) {
                    // if billing address is already filled out, then don't request it in wallet
                    return !this.is_valid_address(this.get_address_object('billing'), 'billing', ['email', 'phone']);
                }
                return true;
            }.bind(this)()),
            requestPayerEmail: this.fields.requestFieldInWallet('billing_email'),
            requestPayerPhone: this.fields.requestFieldInWallet('billing_phone'),
            requestShipping: (function () {
                if (this.needs_shipping()) {
                    var prefix = this.get_shipping_prefix();
                    if ((this.is_current_page('checkout') && !this.is_valid_address(this.get_address_object(prefix), prefix, ['email', 'phone'])) || !this.is_current_page('checkout')) {
                        return true;
                    }
                }
                return false;
            }.bind(this))()
        };
        var displayItems = this.get_display_items(),
            shippingOptions = this.get_shipping_options();

        if (displayItems) {
            options.displayItems = displayItems;
        }

        if (options.requestShipping && shippingOptions) {
            options.shippingOptions = shippingOptions;
        }

        return options;
    };

    wpp_payment.BaseGateway.prototype.get_payment_request_update = function (data) {
        var options = {
            currency: this.get_currency().toLowerCase(),
            total: {
                amount: parseInt(this.get_total_price_cents()),
                label: this.params.total_label,
                pending: true
            }
        };
        var displayItems = this.get_display_items(),
            shippingOptions = this.get_shipping_options();

        if (displayItems) {
            options.displayItems = displayItems;
        }

        if (this.payment_request_options.requestShipping && shippingOptions) {
            options.shippingOptions = shippingOptions;
        }

        if (data) {
            options = $.extend(true, {}, options, data);
        }

        return options;
    };

    wpp_payment.BaseGateway.prototype.get_display_items = function () {
        return this.get_gateway_data().items;
    };

    wpp_payment.BaseGateway.prototype.set_display_items = function (items) {
        var data = this.get_gateway_data();
        data.items = items;
        this.set_gateway_data(data);
    };

    wpp_payment.BaseGateway.prototype.get_shipping_options = function () {
        return this.get_gateway_data().shipping_options;
    };

    wpp_payment.BaseGateway.prototype.set_shipping_options = function (items) {
        var data = this.get_gateway_data();
        data.shipping_options = items;
        this.set_gateway_data(data);
    };

    wpp_payment.BaseGateway.prototype.map_address = function (address) {
        return {
            city: address.city,
            postcode: address.postalCode,
            state: address.region,
            country: address.country
        };
    };

    wpp_payment.BaseGateway.prototype.on_payment_method_received = function (paymentResponse) {
        try {
            this.payment_response = paymentResponse;
            this.populate_checkout_fields(paymentResponse);
            paymentResponse.complete("success");
            this.on_token_received(paymentResponse.paymentMethod);
        } catch (err) {
            window.alert(err);
        }
    };

    wpp_payment.BaseGateway.prototype.populate_checkout_fields = function (data) {
        this.set_nonce(data.paymentMethod.id);
        this.update_addresses(data);
    };

    wpp_payment.BaseGateway.prototype.update_addresses = function (data) {
        if (data.payerName) {
            this.fields.set('name', data.payerName, 'billing');
        }

        if (data.payerEmail) {
            this.fields.set('email', data.payerEmail, 'billing');
        }

        if (data.payerPhone) {
            this.fields.set('phone', data.payerPhone, 'billing');
            if (this.fields.exists('shipping_phone') && this.fields.isEmpty('shipping_phone')) {
                this.fields.set('shipping_phone', data.payerPhone);
            }
        }

        if (data.shippingAddress) {
            this.populate_shipping_fields(data.shippingAddress);
        }

        if (data.paymentMethod.billing_details.address) {
            this.populate_billing_fields(data.paymentMethod.billing_details.address);
        }
    };

    wpp_payment.BaseGateway.prototype.populate_address_fields = function (address, prefix) {
        for (var k in address) {
            if (null !== address[k]) {
                this.fields.set(k, address[k], prefix);
            }
        }
    }

    wpp_payment.BaseGateway.prototype.populate_billing_fields = function (address) {
        this.populate_address_fields(address, 'billing');
    }

    wpp_payment.BaseGateway.prototype.populate_shipping_fields = function (address) {
        this.populate_address_fields(address, 'shipping');
    }

    wpp_payment.BaseGateway.prototype.get_address_fields = function () {
        return ['first_name', 'last_name', 'country', 'address_1', 'address_2', 'city', 'state', 'postcode', 'phone', 'email'];
    }

    wpp_payment.BaseGateway.prototype.get_address_object = function (prefix) {
        var address = {};
        this.get_address_fields().forEach(function (k) {
            address[k] = this.fields.get(k, prefix);
        }.bind(this));
        return address;
    }

    wpp_payment.BaseGateway.prototype.is_current_page = function (page) {
        if (Array.isArray(page)) {
            return page.indexOf(this.get_page()) > -1;
        }
        return this.get_page() === page;
    }

    wpp_payment.BaseGateway.prototype.is_valid_address = function (address, prefix, exclude) {
        if ($.isEmptyObject(address)) {
            return false;
        }

        var mappings = this.get_address_fields();
        if (typeof exclude !== 'undefined') {
            exclude.forEach(function (k) {
                if (mappings.indexOf(k) > -1) {
                    mappings.splice(mappings.indexOf(k), 1);
                }
            });
        }
        for (var i = 0; i < mappings.length; i++) {
            var k = mappings[i];
            var required = this.fields.required(prefix + '_' + k);
            if (required) {
                if (!address[k] || typeof address[k] === 'undefined' || !this.fields.isValid(k, address[k], address)) {
                    return false;
                }
            }
        }
        return true;
    }

    wpp_payment.BaseGateway.prototype.ajax_before_send = function (xhr) {
        if (this.params.user_id > 0) {
            xhr.setRequestHeader('X-WP-Nonce', this.params.rest_nonce);
        }
    };

    wpp_payment.BaseGateway.prototype.process_checkout = function () {
        return new Promise(function () {
            this.block();
            $.ajax({
                url: this.params.routes.checkout,
                method: 'POST',
                dataType: 'json',
                data: $.extend({}, this.serialize_fields(), {
                    payment_method: this.gateway_id,
                    page_id: this.get_page(),
                    currency: this.get_currency(),
                }),
                beforeSend: this.ajax_before_send.bind(this)
            }).done(function (result) {
                if (result.reload) {
                    window.location.reload();
                    return;
                }

                if (result.result === 'success') {
                    window.location = result.redirect;
                } else {
                    if (result.messages) {
                        this.submit_error(result.messages);
                    }

                    this.unblock();
                }
            }.bind(this)).fail(function (xhr, textStatus, errorThrown) {
                this.unblock();
                this.submit_error(errorThrown);
            }.bind(this));
        }.bind(this));
    };

    wpp_payment.BaseGateway.prototype.process_payment = function (order_id, order_key) {
        $.ajax({
            method: 'POST',
            url: this.params.routes.checkout_payment,
            dataType: 'json',
            data: $.extend({}, this.fields.toJson(), {order_id: order_id, order_key: order_key}),
            beforeSend: this.ajax_before_send.bind(this)
        }).done(function (result) {
            if (result.result && result.result === 'success') {
                window.location = result.redirect;
            } else {
                if (result.reload) {
                    return window.location.reload();
                }
                if (result.messages) {
                    this.payment_token_received = false;
                    this.submit_error(result.messages);
                } else {
                    this.submit_error(wc_checkout_params.i18n_checkout_error);
                }
            }
        }.bind(this)).fail(function () {

        }.bind(this))
    }

    wpp_payment.BaseGateway.prototype.handle_card_action = function (obj) {
        try {
            this.stripe.handleCardAction(obj.client_secret).then(function (result) {
                if (result.error) {
                    this.payment_token_received = false;
                    this.submit_error(result.error);
                    this.sync_payment_intent(obj.order_id, obj.client_secret).catch(function (response) {
                        this.submit_error(response.message);
                    }.bind(this));
                    return;
                }
                if (this.is_current_page('order_pay')) {
                    this.get_form().submit();
                } else {
                    this.process_payment(obj.order_id, obj.order_key);
                }
            }.bind(this)).catch(function (error) {
                this.submit_error(error.message);
            }.bind(this))
            return false;
        } catch (err) {

        }
    }

    wpp_payment.BaseGateway.prototype.handle_card_setup = function (obj) {
        try {
            this.stripe.confirmCardSetup(obj.client_secret).then(function (result) {
                if (result.error) {
                    this.payment_token_received = false;
                    return this.submit_error(result.error);
                }
                if (this.is_current_page('order_pay')) {
                    this.get_form().submit();
                } else {
                    this.process_payment(obj.order_id, obj.order_key);
                }
            }.bind(this)).catch(function (error) {
                this.submit_error(error.message);
            }.bind(this))
            return false;
        } catch (err) {

        }
    }

    wpp_payment.BaseGateway.prototype.hashchange = function (e) {
        var match = e.newURL.match(/response=(.*)/);
        if (match) {
            try {
                var obj = JSON.parse(window.atob(decodeURIComponent(match[1])));
                if (obj && obj.hasOwnProperty('client_secret') && obj.gateway_id === this.gateway_id) {
                    history.pushState({}, '', window.location.pathname);
                    if (obj.type === 'intent') {
                        this.handle_card_action(obj);
                    } else {
                        this.handle_card_setup(obj);
                    }
                }
            } catch (err) {

            }
        }
        return true;
    }

    /**
     * [sync_payment_intent description]
     * @param  {[type]} order_id      [description]
     * @param  {[type]} client_secret [description]
     * @return {[type]}               [description]
     */
    wpp_payment.BaseGateway.prototype.sync_payment_intent = function (order_id, client_secret) {
        return new Promise(function (resolve, reject) {
            // call intent api
            $.when($.ajax({
                method: 'POST',
                dataType: 'json',
                url: this.params.routes.sync_intent,
                data: {order_id: order_id, client_secret: client_secret},
                beforeSend: this.ajax_before_send.bind(this)
            })).done(function (response) {
                if (response.code) {
                    reject(response);
                } else {
                    resolve(response);
                }
            }).fail(function (xhr, textStatus, errorThrown) {
                this.submit_error(errorThrown);
            }.bind(this));
        }.bind(this))
    }

    wpp_payment.BaseGateway.prototype.serialize_form = function ($form) {
        var formData = $form.find('input').filter(function (i, e) {
                if ($(e).is('[name^="add-to-cart"]')) {
                    return false;
                }

                return true;
            }.bind(this)).serializeArray(),
            data = {};

        for (var i in formData) {
            var obj = formData[i];
            data[obj.name] = obj.value;
        }

        data.payment_method = this.gateway_id;
        return data;
    };

    wpp_payment.BaseGateway.prototype.serialize_fields = function () {
        return $.extend({}, this.fields.toJson(), $(document.body).triggerHandler('wpp_payment_process_checkout_data', [this, this.fields]));
    };

    wpp_payment.BaseGateway.prototype.map_shipping_methods = function (shippingData) {
        var methods = {};

        if (shippingData !== "default") {
            var matches = shippingData.match(/^([\w+]):(.+)$/);

            if (matches.length > 1) {
                methods[matches[1]] = matches[2];
            }
        }

        return methods;
    };

    wpp_payment.BaseGateway.prototype.maybe_set_ship_to_different = function () {
        // if shipping and billing address are different,
        // set the ship to different address option.
        if ($('[name="ship_to_different_address"]').length) {
            $('[name="ship_to_different_address"]').prop('checked', this.get_address_field_hash("billing") !== this.get_address_field_hash("shipping")).trigger('change');
        }
    };

    wpp_payment.BaseGateway.prototype.update_shipping_address = function (ev) {
        return new Promise(function (resolve, reject) {
            $.ajax({
                url: this.params.routes.shipping_address,
                method: 'POST',
                dataType: 'json',
                data: {
                    address: this.map_address(ev.shippingAddress),
                    payment_method: this.gateway_id,
                    page_id: this.get_page(),
                    currency: this.get_currency()
                },
                beforeSend: this.ajax_before_send.bind(this)
            }).done(function (response) {
                if (response.code) {
                    ev.updateWith(response.data.newData);
                    reject(response.data);
                } else {
                    ev.updateWith(response.data.newData);
                    this.fields.set('shipping_method', response.data.shipping_method);
                    resolve(response.data);
                }
            }.bind(this)).fail(function () {
            }.bind(this));
        }.bind(this));
    };

    wpp_payment.BaseGateway.prototype.update_shipping_method = function (ev) {
        return new Promise(function (resolve, reject) {
            $.ajax({
                url: this.params.routes.shipping_method,
                method: 'POST',
                dataType: 'json',
                data: {
                    shipping_method: ev.shippingOption.id,
                    payment_method: this.gateway_id,
                    currency: this.get_currency(),
                    page_id: this.get_page()
                },
                beforeSend: this.ajax_before_send.bind(this)
            }).done(function (response) {
                if (response.code) {
                    ev.updateWith(response.data.newData);
                    reject(response.data);
                } else {
                    this.set_selected_shipping_methods(response.data.shipping_methods);
                    ev.updateWith(response.data.newData);
                    resolve(response.data);
                }
            }.bind(this)).fail(function (xhr, textStatus, errorThrown) {
                this.submit_error(errorThrown);
            }.bind(this));
        }.bind(this));
    };
    /********** Checkout Gateway ********/

    wpp_payment.CheckoutGateway = function () {
        this.message_container = 'li.payment_method_' + this.gateway_id;
        this.banner_container = 'li.banner_payment_method_' + this.gateway_id;
        $(document.body).on('update_checkout', this.update_checkout.bind(this));
        $(document.body).on('updated_checkout', this.updated_checkout.bind(this));
        $(document.body).on('updated_checkout', this.container_styles.bind(this));
        $(document.body).on('checkout_error', this.checkout_error.bind(this));
        $(this.token_selector).closest('form').on('checkout_place_order_' + this.gateway_id, this.checkout_place_order.bind(this)); // events for showing gateway payment buttons

        $(document.body).on('wpp_payment_new_method_' + this.gateway_id, this.on_show_new_methods.bind(this));
        $(document.body).on('wpp_payment_saved_method_' + this.gateway_id, this.on_show_saved_methods.bind(this));
        $(document.body).on('wpp_payment_payment_method_selected', this.on_payment_method_selected.bind(this));

        if (this.banner_enabled()) {
            if ($('.woocommerce-billing-fields').length) {
                $('.wpp-payment-banner-checkout').css('max-width', $('.woocommerce-billing-fields').outerWidth(true));
            }
        }

        this.container_styles();

        this.hasOrderReviewParams();
    };

    wpp_payment.CheckoutGateway.prototype.container_styles = function () {
        if (!this.params.description) {
            $(this.container).addClass('wpp-payment-no-desc');
        }
        if (!$(this.container).find('.wpp-payment-saved-methods').length) {
            $(this.container).find('.payment_box').addClass('wpp-payment-no-methods');
        }
    }

    wpp_payment.CheckoutGateway.prototype.hasOrderReviewParams = function () {
        var params = window.location.search;
        var match = params.match(/_stripe_order_review=(.+)/);

        if (match && match.length > 1) {
            try {
                var obj = JSON.parse(window.atob(decodeURIComponent(match[1])));
                if (this.gateway_id === obj.payment_method) {
                    $(function () {
                        this.payment_token_received = true;
                        this.set_nonce(obj.payment_nonce);
                        this.set_use_new_option(true);
                    }.bind(this));
                    history.pushState({}, '', window.location.pathname);
                }
            } catch (err) {
            }
        }
    };

    wpp_payment.CheckoutGateway.prototype.has3DSecureParams = function () {
        if (this.is_current_page('order_pay') || this.is_change_payment_method()) {
            if (window.location.hash && typeof window.location.hash === 'string') {
                var match = window.location.hash.match(/response=(.*)/);
                if (match) {
                    try {
                        var obj = JSON.parse(window.atob(decodeURIComponent(match[1])));
                        if (obj && obj.hasOwnProperty('client_secret') && obj.gateway_id === this.gateway_id) {
                            $(function () {
                                this.set_payment_method(this.gateway_id);
                                this.set_use_new_option(true);
                                this.set_nonce(obj.pm);
                                if (obj.save_method === true) {
                                    this.set_save_payment_method(true);
                                }
                                $('[name="terms"]').prop('checked', true);
                            }.bind(this));
                            history.pushState({}, '', window.location.pathname + window.location.search);
                            this.handle_card_action(obj);
                        }
                    } catch (err) {

                    }
                }
            }
        }
    }

    wpp_payment.CheckoutGateway.prototype.update_shipping_address = function () {
        return wpp_payment.BaseGateway.prototype.update_shipping_address.apply(this, arguments).then(function (data) {
            // populate the checkout fields with the address
            this.populate_address_fields(data.address, this.get_shipping_prefix());
            this.fields.toFormFields({update_shipping_method: false});
        }.bind(this));
    }

    /**
     * Called on the WC updated_checkout event
     */
    wpp_payment.CheckoutGateway.prototype.updated_checkout = function () {
    };

    /**
     * Called on the WC update_checkout event
     */
    wpp_payment.CheckoutGateway.prototype.update_checkout = function () {
    };
    /**
     * Called on the WC checkout_error event
     */


    wpp_payment.CheckoutGateway.prototype.checkout_error = function () {
        if (this.has_checkout_error()) {
            this.payment_token_received = false;
            this.payment_response = null;
            this.show_payment_button();
            this.hide_place_order();
        }
    };

    wpp_payment.CheckoutGateway.prototype.is_valid_checkout = function () {
        if ($('[name="terms"]').length && $('[name="terms"]').is(':visible')) {
            if (!$('[name="terms"]').is(':checked')) {
                return false;
            }
        }

        return true;
    };

    wpp_payment.CheckoutGateway.prototype.get_payment_method = function () {
        return $('[name="payment_method"]:checked').val();
    };

    wpp_payment.CheckoutGateway.prototype.set_use_new_option = function (bool) {
        $('#' + this.gateway_id + '_use_new').prop('checked', bool).trigger('change');
    };

    wpp_payment.CheckoutGateway.prototype.checkout_place_order = function () {
        if (!this.is_valid_checkout()) {
            this.submit_error(this.params.messages.terms);
            return false;
        } else if (this.is_saved_method_selected()) {
            return true;
        }

        return this.payment_token_received;
    };

    wpp_payment.CheckoutGateway.prototype.on_token_received = function (paymentMethod) {
        this.payment_token_received = true;
        this.set_nonce(paymentMethod.id);
        this.hide_payment_button();
        this.show_place_order();
    };

    wpp_payment.CheckoutGateway.prototype.block = function () {
        if ($().block) {
            $('form.checkout').block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        }

    };

    wpp_payment.CheckoutGateway.prototype.unblock = function () {
        if ($().block) {
            $('form.checkout').unblock();
        }
    };

    wpp_payment.CheckoutGateway.prototype.hide_place_order = function () {
        $('#place_order').addClass('wpp-dnone');
    };

    wpp_payment.CheckoutGateway.prototype.show_place_order = function () {
        $('#place_order').removeClass('wpp-dnone');
    };

    wpp_payment.CheckoutGateway.prototype.on_show_new_methods = function () {
        if (this.payment_token_received) {
            this.show_place_order();
            this.hide_payment_button();
        } else {
            this.hide_place_order();
            this.show_payment_button();
        }
    };

    wpp_payment.CheckoutGateway.prototype.on_show_saved_methods = function () {
        this.hide_payment_button();
        this.show_place_order();
    };

    wpp_payment.CheckoutGateway.prototype.show_payment_button = function () {
        if (this.$button) {
            this.$button.show();
        }
    };

    wpp_payment.CheckoutGateway.prototype.hide_payment_button = function () {
        if (this.$button) {
            this.$button.hide();
        }
    };

    wpp_payment.CheckoutGateway.prototype.trigger_payment_method_selected = function () {
        this.on_payment_method_selected(null, $('[name="payment_method"]:checked').val());
    };

    wpp_payment.CheckoutGateway.prototype.on_payment_method_selected = function (e, payment_method) {
        if (payment_method === this.gateway_id) {
            if (this.payment_token_received || this.is_saved_method_selected()) {
                this.hide_payment_button();
                this.show_place_order();
            } else {
                this.show_payment_button();
                this.hide_place_order();
            }
        } else {
            this.hide_payment_button();

            if (payment_method && payment_method.indexOf('stripe_') < 0) {
                this.show_place_order();
            }
        }
    };

    wpp_payment.CheckoutGateway.prototype.banner_enabled = function () {
        return this.params.banner_enabled === '1';
    };

    wpp_payment.CheckoutGateway.prototype.checkout_fields_valid = function () {
        if (['checkout', 'order_pay'].indexOf(this.get_page()) < 0) {
            return true;
        }

        var valid = true;

        if (!(valid = this.fields.validateFields('billing'))) {
            this.submit_error(this.params.messages.required_field);
        } else if (this.needs_shipping() && $('#ship-to-different-address-checkbox').is(':checked')) {
            if (!(valid = this.fields.validateFields('shipping'))) {
                this.submit_error(this.params.messages.required_field);
            }
        } else if (!(valid = this.is_valid_checkout())) {
            this.submit_error(this.params.messages.terms);
        }

        return valid;
    };

    wpp_payment.CheckoutGateway.prototype.cart_contains_subscription = function () {
        return typeof wpp_payment_cart_contains_subscription !== 'undefined' && wpp_payment_cart_contains_subscription === true;
    }

    wpp_payment.CheckoutGateway.prototype.set_save_payment_method = function (bool) {
        $('[name="' + this.gateway_id + '_save_source_key' + '"]').prop('checked', bool);
    }

    /************** Product Gateway ***************/

    wpp_payment.ProductGateway = function () {
        this.message_container = 'div.product'; // events

        $('form.cart').on('found_variation', this.found_variation.bind(this));
        $('form.cart').on('reset_data', this.reset_variation_data.bind(this));
        this.buttonWidth = $('form.cart div.quantity').outerWidth(true) + $('.single_add_to_cart_button').outerWidth();
        var marginLeft = $('.single_add_to_cart_button').css('marginLeft');

        if (marginLeft) {
            this.buttonWidth += parseInt(marginLeft.replace('px', ''));
        }

        $(this.container).css('max-width', this.buttonWidth + 'px');
    };

    wpp_payment.ProductGateway.prototype.get_quantity = function () {
        return parseInt($('[name="quantity"]').val());
    };

    wpp_payment.ProductGateway.prototype.set_rest_nonce = function (e, nonce) {
        this.params.rest_nonce = nonce;
    };

    wpp_payment.ProductGateway.prototype.found_variation = function (e, variation) {
        var data = this.get_gateway_data();
        data.product.price = variation.display_price;
        data.needs_shipping = !variation.is_virtual;
        data.product.variation = variation;
        this.set_gateway_data(data);
    };

    wpp_payment.ProductGateway.prototype.reset_variation_data = function () {
        var data = this.get_product_data();
        data.variation = false;
        this.set_product_data(data);
        this.disable_payment_button();
    };

    wpp_payment.ProductGateway.prototype.disable_payment_button = function () {
        if (this.$button) {
            this.get_button().prop('disabled', true).addClass('disabled');
        }
    };

    wpp_payment.ProductGateway.prototype.enable_payment_button = function () {
        if (this.$button) {
            this.get_button().prop('disabled', false).removeClass('disabled');
        }
    };

    wpp_payment.ProductGateway.prototype.get_button = function () {
        return this.$button;
    };

    wpp_payment.ProductGateway.prototype.is_variable_product = function () {
        return $('[name="variation_id"]').length > 0;
    };

    wpp_payment.ProductGateway.prototype.variable_product_selected = function () {
        var val = $('input[name="variation_id"]').val();
        return !!val && "0" != val;
    };

    wpp_payment.ProductGateway.prototype.get_product_data = function () {
        return this.get_gateway_data().product;
    };

    wpp_payment.ProductGateway.prototype.set_product_data = function (product) {
        var data = this.get_gateway_data();
        data.product = product;
        this.set_gateway_data(data);
    };

    wpp_payment.ProductGateway.prototype.get_form = function () {
        return $(this.container).closest('form');
    }

    wpp_payment.ProductGateway.prototype.add_to_cart = function () {
        return new Promise(function (resolve, reject) {
            this.block();
            var data = {
                product_id: this.get_product_data().id,
                variation_id: this.is_variable_product() ? $('[name="variation_id"]').val() : 0,
                qty: $('[name="quantity"]').val(),
                payment_method: this.gateway_id,
                currency: this.get_currency(),
                page_id: this.get_page()
            };
            var fields = this.get_form().find(':not([name="add-to-cart"],[name="quantity"],[name^="attribute_"],[name="variation_id"])').serializeArray();
            if (fields) {
                for (var i in fields) {
                    data[fields[i].name] = fields[i].value;
                }
            }
            $.ajax({
                url: this.params.routes.add_to_cart,
                method: 'POST',
                dataType: 'json',
                data: $.extend({}, data, this.get_product_variations()),
                beforeSend: this.ajax_before_send.bind(this)
            }).done(function (response) {
                this.unblock();

                if (response.code) {
                    this.submit_error(response.message);
                    reject(response);
                } else {
                    this.set_total_price(response.data.total);
                    this.set_total_price_cents(response.data.totalCents);
                    this.set_display_items(response.data.displayItems);
                    resolve(response.data);
                }
            }.bind(this)).fail(function (xhr, textStatus, errorThrown) {
                this.unblock();
                this.submit_error(errorThrown);
            }.bind(this));
        }.bind(this));
    };

    wpp_payment.ProductGateway.prototype.cart_calculation = function (variation_id) {
        return new Promise(function (resolve, reject) {
            $.ajax({
                url: this.params.routes.cart_calculation,
                method: 'POST',
                dataType: 'json',
                data: $.extend({}, {
                    product_id: this.get_product_data().id,
                    variation_id: this.is_variable_product() && variation_id ? variation_id : 0,
                    qty: $('[name="quantity"]').val(),
                    currency: this.get_currency(),
                    payment_method: this.gateway_id
                }, this.get_product_variations()),
                beforeSend: this.ajax_before_send.bind(this)
            }).done(function (response) {
                if (response.code) {
                    this.cart_calculation_error = true;
                    reject(response);
                } else {
                    this.set_total_price(response.data.total);
                    this.set_total_price_cents(response.data.totalCents);
                    this.set_display_items(response.data.displayItems);
                    resolve(response.data);
                }
            }.bind(this)).fail(function () {
            }.bind(this));
        }.bind(this));
    };

    wpp_payment.ProductGateway.prototype.get_product_variations = function () {
        var variations = {};
        if (this.is_variable_product()) {
            $('.variations [name^="attribute_"]').each(function (index, el) {
                var $el = $(el);
                var name = $el.data('attribute_name') || $el.attr('name');
                variations[name] = $el.val();
            });
        }
        return variations;
    }

    /************* Cart Gateway *************/

    wpp_payment.CartGateway = function () {
        this.message_container = 'div.woocommerce';

        $(document.body).on('updated_wc_div', this.updated_html.bind(this));
        $(document.body).on('updated_cart_totals', this.updated_html.bind(this));
        $(document.body).on('wc_cart_emptied', this.cart_emptied.bind(this));
    };

    wpp_payment.CartGateway.prototype.submit_error = function (message) {
        this.submit_message(this.get_error_message(message));
    };

    wpp_payment.CartGateway.prototype.updated_html = function (e) {
    };

    wpp_payment.CartGateway.prototype.cart_emptied = function (e) {
    };

    wpp_payment.CartGateway.prototype.add_cart_totals_class = function () {
        $('.cart_totals').addClass('stripe_cart_gateway_active');
    };

    /************* Google Pay Mixins **************/

    wpp_payment.GooglePay = function () {
    };

    var googlePayBaseRequest = {
        apiVersion: 2,
        apiVersionMinor: 0
    };

    var baseCardPaymentMethod = {
        type: 'CARD',
        parameters: {
            allowedAuthMethods: ["PAN_ONLY"],
            allowedCardNetworks: ["AMEX", "DISCOVER", "INTERAC", "JCB", "MASTERCARD", "VISA"],
            assuranceDetailsRequired: true
        }
    };

    /**
     * Populate the WC checkout fields.
     * @param  {[type]}
     * @return {[type]}
     */
    wpp_payment.GooglePay.prototype.update_addresses = function (paymentData) {
        if (paymentData.paymentMethodData.info.billingAddress) {
            var billing_address = paymentData.paymentMethodData.info.billingAddress;
            if (this.is_current_page('checkout') && this.is_valid_address(this.billing_address_object, 'billing', ['phone', 'email'])) {
                billing_address = {phoneNumber: billing_address.phoneNumber};
            }
            this.populate_billing_fields(billing_address);
            if (billing_address.phoneNumber && this.fields.exists('shipping_phone') && this.fields.isEmpty('shipping_phone')) {
                this.fields.set('shipping_phone', billing_address.phoneNumber);
            }
        }

        if (paymentData.shippingAddress) {
            this.populate_shipping_fields(paymentData.shippingAddress);
        }

        if (paymentData.email) {
            this.fields.set('email', paymentData.email, 'billing');
        }
    };

    wpp_payment.GooglePay.prototype.map_address = function (address) {
        return {
            city: address.locality,
            postcode: address.postalCode,
            state: address.administrativeArea,
            country: address.countryCode
        };
    };

    wpp_payment.GooglePay.prototype.update_payment_data = function (data) {
        return new Promise(function (resolve, reject) {
            var shipping_method = data.shippingOptionData.id == 'default' ? null : data.shippingOptionData.id;
            $.when($.ajax({
                url: this.params.routes.payment_data,
                dataType: 'json',
                method: 'POST',
                data: {
                    address: this.map_address(data.shippingAddress),
                    shipping_method: shipping_method,
                    page_id: this.get_page(),
                    currency: this.get_currency()
                },
                beforeSend: this.ajax_before_send.bind(this)
            })).done(function (response) {
                if (response.code) {
                    reject(response.data.data);
                } else {
                    resolve(response.data);
                }
            }.bind(this)).fail(function () {
                reject();
            }.bind(this));
        }.bind(this));
    };

    wpp_payment.GooglePay.prototype.on_payment_data_changed = function (address) {
        return new Promise(function (resolve) {
            this.update_payment_data(address).then(function (response) {
                resolve(response.paymentRequestUpdate);
                this.set_selected_shipping_methods(response.shipping_methods);
                this.payment_data_updated(response, address);
            }.bind(this))['catch'](function (data) {
                resolve(data);
            }.bind(this));
        }.bind(this));
    };

    wpp_payment.GooglePay.prototype.payment_data_updated = function (response) {
    };

    wpp_payment.GooglePay.prototype.get_merchant_info = function () {
        var options = {
            merchantId: this.params.merchant_id,
            merchantName: this.params.merchant_name
        };

        if (this.params.environment === 'TEST') {
            delete options.merchantId;
        }

        return options;
    };

    wpp_payment.GooglePay.prototype.get_payment_options = function () {
        var options = {
            environment: this.params.environment,
            merchantInfo: this.get_merchant_info(),
            paymentDataCallbacks: {
                onPaymentAuthorized: function onPaymentAuthorized() {
                    return new Promise(function (resolve) {
                        resolve({
                            transactionState: "SUCCESS"
                        });
                    }.bind(this));
                }
            }
        };

        if (this.needs_shipping()) {
            var prefix = this.get_shipping_prefix();
            if ((this.is_current_page('checkout') && !this.is_valid_address(this.get_address_object(prefix), prefix, ['email', 'phone'])) || !this.is_current_page('checkout')) {
                options.paymentDataCallbacks.onPaymentDataChanged = this.on_payment_data_changed.bind(this);
            }
        }

        return options;
    };

    wpp_payment.GooglePay.prototype.build_payment_request = function () {
        var request = $.extend({}, googlePayBaseRequest, {
            emailRequired: this.fields.requestFieldInWallet('billing_email'),
            merchantInfo: this.get_merchant_info(),
            allowedPaymentMethods: [$.extend({
                type: "CARD",
                tokenizationSpecification: {
                    type: "PAYMENT_GATEWAY",
                    parameters: {
                        gateway: 'stripe',
                        "stripe:version": "2018-10-31",
                        "stripe:publishableKey": this.params.api_key
                    }
                }
            }, baseCardPaymentMethod)],
            shippingAddressRequired: (function () {
                if (this.needs_shipping()) {
                    var prefix = this.get_shipping_prefix();
                    if ((this.is_current_page('checkout') && !this.is_valid_address(this.get_address_object(prefix), prefix, ['email', 'phone'])) || !this.is_current_page('checkout')) {
                        return true;
                    }
                }
                return false;
            }.bind(this))(),
            transactionInfo: {
                countryCode: this.params.processing_country,
                currencyCode: this.get_currency(),
                totalPriceStatus: "ESTIMATED",
                totalPrice: this.get_total_price().toString(),
                displayItems: this.get_display_items(),
                totalPriceLabel: this.params.total_price_label
            }
        });
        request.allowedPaymentMethods[0].parameters.billingAddressRequired = (function () {
            if (this.is_current_page('checkout')) {
                var address = this.billing_address_object = this.get_address_object('billing');
                // phone is in address object, so if phone is required and empty, request address. If address is not filled out, then request it.
                if (this.fields.requestFieldInWallet('billing_phone') || !this.is_valid_address(address, 'billing', ['email'])) {
                    return true;
                }
                return false;
            }
            return true;
        }.bind(this)());
        if (request.allowedPaymentMethods[0].parameters.billingAddressRequired) {
            request.allowedPaymentMethods[0].parameters.billingAddressParameters = {
                format: "FULL",
                phoneNumberRequired: this.fields.requestFieldInWallet('billing_phone')
            };
        }

        if (request.shippingAddressRequired) {
            request.shippingAddressParameters = {};
            request.shippingOptionRequired = true;
            request.shippingOptionParameters = {
                shippingOptions: this.get_shipping_options()
            };
            request.callbackIntents = ["SHIPPING_ADDRESS", "SHIPPING_OPTION", "PAYMENT_AUTHORIZATION"];
        } else {
            request.callbackIntents = ["PAYMENT_AUTHORIZATION"];
        }
        this.payment_request_options = request;
        return request;
    };

    wpp_payment.GooglePay.prototype.createPaymentsClient = function () {
        this.paymentsClient = new google.payments.api.PaymentsClient(this.get_payment_options());
    };

    wpp_payment.GooglePay.prototype.isReadyToPay = function () {
        return new Promise(function (resolve) {
            var isReadyToPayRequest = $.extend({}, googlePayBaseRequest);
            isReadyToPayRequest.allowedPaymentMethods = [baseCardPaymentMethod];
            this.paymentsClient.isReadyToPay(isReadyToPayRequest).then(function () {
                this.can_pay = true;
                this.create_button();
                resolve();
            }.bind(this))["catch"](function (err) {
                this.submit_error(err);
            }.bind(this));
        }.bind(this));
    };

    wpp_payment.GooglePay.prototype.create_button = function () {
        if (this.$button) {
            this.$button.remove();
        }

        this.$button = $(this.paymentsClient.createButton({
            onClick: this.start.bind(this),
            buttonColor: this.params.button_color,
            buttonType: this.params.button_style,
            buttonSizeMode: this.params.button_size_mode
        }));
        this.$button.addClass('gpay-button-container');
    };

    wpp_payment.GooglePay.prototype.start = function () {
        // always recreate the paymentClient to ensure latest data is used.
        this.createPaymentsClient();
        this.paymentsClient.loadPaymentData(this.build_payment_request()).then(function (paymentData) {
            var data = JSON.parse(paymentData.paymentMethodData.tokenizationData.token);
            this.update_addresses(paymentData);
            // convert token to payment method
            this.stripe.createPaymentMethod({
                type: 'card',
                card: {token: data.id},
                billing_details: this.get_billing_details()
            }).then(function (result) {
                if (result.error) {
                    return this.submit_error(result.error);
                }
                this.on_token_received(result.paymentMethod);
            }.bind(this));
        }.bind(this))["catch"](function (err) {
            if (err.statusCode === "CANCELED") {
                return;
            }

            if (err.statusMessage && err.statusMessage.indexOf("paymentDataRequest.callbackIntent") > -1) {
                this.submit_error_code("DEVELOPER_ERROR_WHITELIST");
            } else {
                this.submit_error(err.statusMessage);
            }
        }.bind(this));
    };

    wpp_payment.ApplePay = function () {
    };

    wpp_payment.ApplePay.prototype.initialize = function () {
        this.createPaymentRequest();
        this.canMakePayment();
    };

    wpp_payment.ApplePay.prototype.get_payment_request_options = function () {
        return $.extend({}, wpp_payment.BaseGateway.prototype.get_payment_request_options.apply(this, arguments), {
            disableWallets: ['googlePay']
        });
    }

    wpp_payment.ApplePay.prototype.create_button = function () {
        if (this.$button) {
            this.$button.remove();
        }

        this.$button = $(this.params.button);
        this.$button.on('click', this.start.bind(this));
        this.append_button();
    };

    wpp_payment.ApplePay.prototype.canMakePayment = function () {
        return new Promise(function (resolve) {
            this.paymentRequest.canMakePayment().then(function (result) {
                if (result && result.applePay) {
                    this.can_pay = true;
                    this.create_button();
                    $(this.container).show();
                    resolve(result);
                }
            }.bind(this));
        }.bind(this));
    };

    wpp_payment.ApplePay.prototype.start = function (e) {
        e.preventDefault();
        this.paymentRequest.update(this.get_payment_request_update({
            total: {
                pending: false
            }
        }));
        this.paymentRequest.show();
    };

    /*********** PaymentRequest *********/
    wpp_payment.PaymentRequest = function () {
    };

    wpp_payment.PaymentRequest.prototype.initialize = function () {
        this.createPaymentRequest();
        this.createPaymentRequestButton();
        this.canMakePayment();
        this.paymentRequestButton.on('click', this.button_click.bind(this));
    };

    wpp_payment.PaymentRequest.prototype.button_click = function (event) {
    };

    wpp_payment.PaymentRequest.prototype.createPaymentRequestButton = function () {
        if (this.paymentRequestButton) {
            this.paymentRequestButton.destroy();
        }
        this.paymentRequestButton = this.elements.create("paymentRequestButton", {
            paymentRequest: this.paymentRequest,
            style: {
                paymentRequestButton: {
                    type: this.params.button.type,
                    theme: this.params.button.theme,
                    height: this.params.button.height
                }
            }
        });
    };

    wpp_payment.PaymentRequest.prototype.canMakePayment = function () {
        return new Promise(function (resolve) {
            var paymentRequest = this.paymentRequest;
            this.paymentRequest.canMakePayment().then(function (result) {
                if (result && !result.applePay && paymentRequest === this.paymentRequest) {
                    this.can_pay = true;
                    this.create_button();
                    $(this.container).show();
                    resolve(result);
                }
            }.bind(this));
        }.bind(this));
    };

    wpp_payment.PaymentRequest.prototype.create_button = function () {
        this.paymentRequestButton.mount('#wpp-payment-request-container');
    };

   
    wpp_payment.CheckoutFields = function (params, page) {
        this.params = params;
        this.page = page;
        this.session_values = this.supportsSessionStorage() && sessionStorage.getItem('wpp_payment_checkout_fields') ? JSON.parse(sessionStorage.getItem('wpp_payment_checkout_fields')) : {};
        this.fields = new Map(Object.keys(this.params).map(function (k) {
            if (this.params[k].value === null) {
                if (k in this.session_values && this.session_values[k] !== null) {
                    this.params[k].value = this.session_values[k];
                } else {
                    this.params[k].value = "";
                }
            }
            return [k, this.params[k].value];
        }.bind(this)));

        if ('checkout' === page || ('cart' === page && $(document.body).is('.woocommerce-checkout')) || $('form.woocommerce-checkout').length > 0) {
            $(document.body).on('updated_checkout', this.updated_checkout.bind(this));
            $('form.checkout').on('change', '.input-text, select', this.onChange.bind(this));
            $('form.checkout').on('change', '[name="ship_to_different_address"]', this.on_ship_to_address_change.bind(this));
            this.init_i18n();
            if ($('[name="ship_to_different_address"]').is(':checked')) {
                this.update_required_fields($('#shipping_country').val(), 'shipping_country');
            } else {
                this.update_required_fields($('#billing_country').val(), 'billing_country');
            }
        }
    };

    wpp_payment.CheckoutFields.prototype.supportsSessionStorage = function () {
        if ('sessionStorage' in window && window.sessionStorage !== null) {
            return ['getItem', 'setItem'].reduce(function (exists, method) {
                return !exists ? exists : method in sessionStorage;
            }.bind(this), true);
        }
        return false;
    }

    wpp_payment.CheckoutFields.prototype.init_i18n = function () {
        if (typeof wc_address_i18n_params !== 'undefined') {
            this.locales = JSON.parse(wc_address_i18n_params.locale.replace(/&quot;/g, '"'));
        } else {
            this.locales = null;
        }
    };

    /**
     * Triggered when the WC checkout.js updated_checkout event is fired.
     */
    wpp_payment.CheckoutFields.prototype.updated_checkout = function () {
        this.syncCheckoutFieldsWithDOM();
    }

    wpp_payment.CheckoutFields.prototype.syncCheckoutFieldsWithDOM = function () {
        for (var k in this.params) {
            if ($('#' + k).length) {
                this.fields.set(k, $('#' + k).val());
            }
        }
    }

    wpp_payment.CheckoutFields.prototype.onChange = function (e) {
        try {
            var name = e.currentTarget.name,
                value = e.currentTarget.value;
            this.fields.set(name, value);

            if (name === 'billing_country' || name === 'shipping_country') {
                this.update_required_fields(value, name);
            }
            if (this.supportsSessionStorage()) {
                sessionStorage.setItem('wpp_payment_checkout_fields', JSON.stringify(this.toJson()));
            }
        } catch (err) {
            console.log(err);
        }
    };

    wpp_payment.CheckoutFields.prototype.update_required_fields = function (country, name) {
        if (this.locales) {
            var prefix = name.indexOf('billing_') > -1 ? 'billing_' : 'shipping_';
            var locale = typeof this.locales[country] !== 'undefined' ? this.locales[country] : this.locales['default'];
            var fields = $.extend(true, {}, this.locales['default'], locale);

            for (var k in fields) {
                var k2 = prefix + k;

                if (this.params[k2]) {
                    this.params[k2] = $.extend(true, {}, this.params[k2], fields[k]);
                }
            }
        }
    };

    wpp_payment.CheckoutFields.prototype.on_ship_to_address_change = function (e) {
        if ($(e.currentTarget).is(':checked')) {
            this.update_required_fields($('#shipping_country').val(), 'shipping_country');
        }
    };

    wpp_payment.CheckoutFields.prototype.requestFieldInWallet = function (key) {
        if ('checkout' === this.page) {
            return this.required(key) && this.isEmpty(key);
        } else if ('order_pay' === this.page) {
            return false;
        }

        return this.required(key);
    };

    wpp_payment.CheckoutFields.prototype.set = function (k, v, prefix) {
        if (this[k] && typeof this[k] === 'function') {
            this[k]().set.call(this, v, prefix);
        } else {
            this.fields.set(k, v);
        }
    };

    wpp_payment.CheckoutFields.prototype.get = function (k, prefix) {
        var value;
        if (this[k] && typeof this[k] === 'function') {
            value = this[k]().get.call(this, prefix);
        } else {
            value = this.fields.get(k);

            if (typeof value === 'undefined' || value === null || value === '') {
                if (typeof prefix !== 'undefined') {
                    value = prefix;
                }
            }
        }

        return typeof value === 'undefined' ? '' : value;
    };

    wpp_payment.CheckoutFields.prototype.required = function (k) {
        if (this.params[k]) {
            if (typeof this.params[k].required !== 'undefined') {
                return this.params[k].required;
            }
        }

        return false;
    };

    wpp_payment.CheckoutFields.prototype.exists = function (k) {
        return k in this.params;
    }

    wpp_payment.CheckoutFields.prototype.isEmpty = function (k) {
        if (this.fields.has(k)) {
            var value = this.fields.get(k);
            return typeof value === 'undefined' || value === null || typeof value === 'string' && value.trim().length === 0;
        }

        return true;
    };

    wpp_payment.CheckoutFields.prototype.isValid = function (k) {
        if (this[k] && typeof this[k] === 'function') {
            return this[k]().isValid.apply(this, Array.prototype.slice.call(arguments, 1));
        }
    }

    wpp_payment.CheckoutFields.prototype.first_name = function () {
        return {
            set: function (v, prefix) {
                this.fields.set(prefix + '_first_name', v);
            },
            get: function (prefix) {
                return this.fields.get(prefix + '_first_name');
            },
            isValid: function (v) {
                return typeof v === 'string' && v.length > 0;
            }
        }
    }

    wpp_payment.CheckoutFields.prototype.last_name = function () {
        return {
            set: function (v, prefix) {
                this.fields.set(prefix + '_last_name', v);
            },
            get: function (prefix) {
                return this.fields.get(prefix + '_last_name');
            },
            isValid: function (v) {
                return typeof v === 'string' && v.length > 0;
            }
        }
    }

    wpp_payment.CheckoutFields.prototype.address_1 = function () {
        return {
            set: function set(v, prefix) {
                this.fields.set(prefix + '_address_1', v);
            },
            get: function get(prefix) {
                return this.fields.get(prefix + '_address_1');
            },
            isValid: function (v) {
                return typeof v === 'string' && v.length > 0;
            }
        };
    };

    wpp_payment.CheckoutFields.prototype.address_2 = function () {
        return {
            set: function set(v, prefix) {
                this.fields.set(prefix + '_address_2', v);
            },
            get: function get(prefix) {
                return this.fields.get(prefix + '_address_2');
            },
            isValid: function (v) {
                return typeof v === 'string' && v.length > 0;
            }
        };
    };

    wpp_payment.CheckoutFields.prototype.name = function () {
        return {
            set: function set(v, prefix) {
                this.fields.set(prefix + '_full_name', v);
                var name = v.split(" ");
                if (name.length > 1) {
                    var last_name = name.pop();
                    this.fields.set(prefix + '_first_name', name.join(' '));
                    this.fields.set(prefix + '_last_name', last_name);
                } else if (name.length == 1) {
                    this.fields.set(prefix + '_first_name', name[0]);
                }
            },
            get: function get(prefix) {
                return this.fields.get(prefix + '_first_name') + ' ' + this.fields.get(prefix + '_last_name');
            }
        };
    };

    wpp_payment.CheckoutFields.prototype.email = function () {
        return {
            set: function set(v, prefix) {
                this.fields.set(prefix + '_email', v);
            },
            get: function get(prefix) {
                return this.fields.get(prefix + '_email');
            },
            isValid: function (v) {
                return typeof v === 'string' && v.length > 0;
            }
        };
    };

    wpp_payment.CheckoutFields.prototype.phone = function () {
        return {
            set: function set(v, prefix) {
                this.fields.set(prefix + '_phone', v);
            },
            get: function get(prefix) {
                return this.fields.get(prefix + '_phone');
            },
            isValid: function (v) {
                return typeof v === 'string' && v.length > 0;
            }
        };
    };

    wpp_payment.CheckoutFields.prototype.country = function () {
        return {
            set: function set(v, prefix) {
                this.fields.set(prefix + '_country', v);
            },
            get: function get(prefix) {
                return this.fields.get(prefix + '_country');
            },
            isValid: function (v) {
                return typeof v === 'string' && v.length === 2;
            }
        };
    };

    wpp_payment.CheckoutFields.prototype.state = function () {
        return {
            set: function set(v, prefix) {
                v = v.toUpperCase();
                if (v.length > 2 && this.page === 'checkout') {
                    $('#' + prefix + '_state option').each(function () {
                        var $option = $(this);
                        var state = $option.text().toUpperCase();
                        if (v === state) {
                            v = $option.val();
                        }
                    });
                }
                this.fields.set(prefix + '_state', v);
            },
            get: function get(prefix) {
                return this.fields.get(prefix + '_state');
            },
            isValid: function (v) {
                return typeof v === 'string' && v.length > 0;
            }
        };
    };

    wpp_payment.CheckoutFields.prototype.city = function () {
        return {
            set: function set(v, prefix) {
                this.fields.set(prefix + '_city', v);
            },
            get: function get(prefix) {
                return this.fields.get(prefix + '_city');
            },
            isValid: function (v) {
                return typeof v === 'string' && v.length > 0;
            }
        };
    };

    wpp_payment.CheckoutFields.prototype.postcode = function () {
        return {
            set: function set(v, prefix) {
                this.fields.set(prefix + '_postcode', v);
            },
            get: function get(prefix) {
                return this.fields.get(prefix + '_postcode');
            },
            isValid: function (v) {
                return typeof v === 'string' && v.length > 0;
            }
        };
    };

    wpp_payment.CheckoutFields.prototype.recipient = function () {
        return wpp_payment.CheckoutFields.prototype.name.apply(this, arguments);
    }

    wpp_payment.CheckoutFields.prototype.payerName = function () {
        return wpp_payment.CheckoutFields.prototype.name.apply(this, arguments);
    };

    wpp_payment.CheckoutFields.prototype.payerEmail = function () {
        return wpp_payment.CheckoutFields.prototype.email.apply(this, arguments);
    };

    wpp_payment.CheckoutFields.prototype.payerPhone = function () {
        return wpp_payment.CheckoutFields.prototype.phone.apply(this, arguments);
    };

    wpp_payment.CheckoutFields.prototype.phoneNumber = function () {
        return wpp_payment.CheckoutFields.prototype.phone.apply(this, arguments);
    };

    wpp_payment.CheckoutFields.prototype.countryCode = function () {
        return wpp_payment.CheckoutFields.prototype.country.apply(this, arguments);
    };

    wpp_payment.CheckoutFields.prototype.address1 = function () {
        return wpp_payment.CheckoutFields.prototype.address_1.apply(this, arguments);
    };

    wpp_payment.CheckoutFields.prototype.address2 = function () {
        return wpp_payment.CheckoutFields.prototype.address_2.apply(this, arguments);
    };

    wpp_payment.CheckoutFields.prototype.line1 = function () {
        return wpp_payment.CheckoutFields.prototype.address_1.apply(this, arguments);
    };

    wpp_payment.CheckoutFields.prototype.line2 = function () {
        return wpp_payment.CheckoutFields.prototype.address_2.apply(this, arguments);
    };

    wpp_payment.CheckoutFields.prototype.addressLine = function () {
        return {
            set: function set(v, prefix) {
                if (v.length > 0) {
                    this.fields.set(prefix + '_address_1', v[0]);
                }

                if (v.length > 1) {
                    this.fields.set(prefix + '_address_2', v[1]);
                }
            },
            get: function get(prefix) {
                return [this.fields.get(prefix + '_address_1'), this.fields.get(prefix + '_address_2')];
            },
            isValid: function (v) {
                if (v.length > 0) {
                    return typeof v[0] === 'string' && v[0].length > 0;
                }
                return false;
            }
        };
    };

    wpp_payment.CheckoutFields.prototype.region = function () {
        return wpp_payment.CheckoutFields.prototype.state.apply(this, arguments);
    };

    wpp_payment.CheckoutFields.prototype.administrativeArea = function () {
        return wpp_payment.CheckoutFields.prototype.state.apply(this, arguments);
    };

    wpp_payment.CheckoutFields.prototype.locality = function () {
        return wpp_payment.CheckoutFields.prototype.city.apply(this, arguments);
    };

    wpp_payment.CheckoutFields.prototype.postal_code = function () {
        return wpp_payment.CheckoutFields.prototype.postcode.apply(this, arguments);
    }

    wpp_payment.CheckoutFields.prototype.postalCode = function () {
        return wpp_payment.CheckoutFields.prototype.postcode.apply(this, arguments);
    };

    wpp_payment.CheckoutFields.prototype.toJson = function () {
        var data = {};
        this.fields.forEach(function (value, key) {
            data[key] = value;
        });
        return data;
    };

    wpp_payment.CheckoutFields.prototype.toFormFields = function (args) {
        var changes = [];
        this.fields.forEach(function (value, key) {
            var name = '[name="' + key + '"]';

            if ($(name).length && value !== '') {
                if ($(name).val() !== value && $(name).is('select')) {
                    changes.push(name);
                }

                $(name).val(value);
            }
        });
        if (changes.length > 0) {
            $(changes.join(',')).trigger('change');
        }
        if (typeof args !== 'undefined') {
            $(document.body).trigger('update_checkout', args);
        }
    };

    wpp_payment.CheckoutFields.prototype.validateFields = function (prefix) {
        for (var k in this.params) {
            var field = this.params[k];
            if (k.indexOf(prefix) > -1 && field.required) {
                if ($('#' + k).length && $('#' + k).is(':visible')) {
                    var val = $('#' + k).val();
                    if (typeof val === 'undefined' || val === null || val.length === 0) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    try {
        stripe = Stripe(wpp_payment_params_v3.api_key, (function () {
            if (wpp_payment_params_v3.mode === 'test' && wpp_payment_params_v3.account === '') {
                return {};
            }
            return {stripeAccount: wpp_payment_params_v3.account};
        }()));
    } catch (error) {
        window.alert(error);
        console.log(error);
        return;
    }

    var checkoutFields = new wpp_payment.CheckoutFields(wpp_payment_checkout_fields, wpp_payment_params_v3.page);
})(window, jQuery);
