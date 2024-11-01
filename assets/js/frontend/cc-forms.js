(function($) {
	function minimalist() {
		this.index = 1;
		this.total_steps = $('.wpp-payment-steps').data('steps');
		this.updateSteppers();
		this.updateStyles();
		$(document.body).on('click', '.wpp-payment-back', this.prev.bind(this))
			.on('click', '.wpp-payment-next', this.next.bind(this))
			.on('updated_checkout', this.updated_checkout.bind(this));
	}

	minimalist.prototype.next = function(e) {
		e.preventDefault();
		this.index++;
		$('.wpp-payment-minimalist-form .field-container[data-index="' + this.index + '"]').removeClass('field-container--hidden');
		$('.wpp-payment-minimalist-form .field-container[data-index="' + (this.index - 1) + '"]').addClass('field-container--hidden');
		this.updateSteppers();
	}

	minimalist.prototype.prev = function(e) {
		e.preventDefault();
		this.index--;
		$('.wpp-payment-minimalist-form .field-container[data-index="' + (this.index + 1) + '"]').addClass('field-container--hidden');
		$('.wpp-payment-minimalist-form .field-container[data-index="' + this.index + '"]').removeClass('field-container--hidden');
		this.updateSteppers();
	}

	minimalist.prototype.updateText = function() {
		var text = $('.wpp-payment-step').data('text');
		$('.wpp-payment-step').text(text.replace('%s', this.index));
	}

	minimalist.prototype.updateSteppers = function() {
		if (this.index == 1) {
			$('.wpp-payment-back').hide();
		} else if (this.index == this.total_steps) {
			$('.wpp-payment-next').hide();
		} else {
			$('.wpp-payment-next').show();
			$('.wpp-payment-back').show();
		}
		this.updateText();
	}

	minimalist.prototype.updated_checkout = function() {
		$('.wpp-payment-minimalist-form .field-container[data-index="' + this.index + '"]').removeClass('field-container--hidden');
		this.updateSteppers();
		this.updateStyles();
	}

	minimalist.prototype.updateStyles = function() {
		if (wpp_payment.credit_card) {
			var width = $('ul.payment_methods').outerWidth();
			if ($('ul.payment_methods').outerWidth() < 400) {
				var options = {
					style: {
						base: {
							fontSize: '18px'
						}
					}
				};
				wpp_payment.credit_card.cardNumber.update(options);
				wpp_payment.credit_card.cardExpiry.update(options);
				wpp_payment.credit_card.cardCvc.update(options);
				$('ul.payment_methods').addClass('wpp-payment-sm');
			}
		}
	}

	new minimalist();
}(jQuery))