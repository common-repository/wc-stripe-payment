<?php
/**
 * @version 3.0.6
 */
?>
<div class="wpp-payment-bootstrap-form">
	<div class="row">
		<div class="col-md-12 mb-3">
			<div id="stripe-card-number" class="md-form md-outline stripe-input"></div>
			<label for="stripe-card-number"><?php esc_html_e('Card Number', 'wc-stripe-payments')?></label>
		</div>
	</div>
	<div class="row">
		<div class="col-md-4 mb-3">
			<div id="stripe-exp" class="md-form md-outline stripe-input"></div>
			<label for="stripe-exp"><?php esc_html_e('Exp', 'wc-stripe-payments')?></label>
		</div>
		<div class="col-md-4 mb-3">
			<div id="stripe-cvv" class="md-form md-outline stripe-input"></div>
			<label for="stripe-cvv"><?php esc_html_e('CVV', 'wc-stripe-payments')?></label>
		</div>
		<?php if($gateway->postal_enabled()):?>
		<div class="col-md-4 mb-3">
			<input id="stripe-postal-code" class="md-form md-outline stripe-input empty"/>
			<label for="stripe-postal-code"><?php esc_html_e('ZIP', 'wc-stripe-payments')?></label>
		</div>
		<?php endif;?>
	</div>
</div>
<style type="text/css">
.wpp-payment-bootstrap-form #stripe-postal-code{
	width: 100%;
	padding: 10px 12px;
	min-height: 41px;
	font-weight: 500;
	font-size: 16px;
	line-height: normal;
	color: #495057;
	-webkit-appearance: none;
	margin: 0;
}
.wc-wpp_stripe_cc-container .wpp-payment-save-source .save-source-label{
	margin-top: 0 !important;
}
#stripe-postal-code:focused{
	background: transparent;
}
.wpp-payment-bootstrap-form input[id=stripe-postal-code]:not(.StripeElement--complete):not(.invalid):focus{
	box-shadow: inset 0 0 0 1.25px #4285f4;
	-webkit-box-shadow: inset 0 0 0 1.25px #4285f4;
	-moz-box-shadow: inset 0 0 0 1.25px #4285f4;
}
.wpp-payment-bootstrap-form input[id=stripe-postal-code].StripeElement--complete.focused{
	border-color: #1b9404;
	box-shadow: inset 0 0 0 1.25px #1b9404;
	-webkit-box-shadow: inset 0 0 0 1.25px #1b9404;
}
.wpp-payment-bootstrap-form #stripe-postal-code:not(.empty):not(.StripeElement--complete):not(.focused)+label{
	-webkit-transform: translateY(-150%);
    -ms-transform: translateY(-150%);
    transform: translateY(-150%);
    background: #fff;
    font-weight: 500;
    padding-right: 5px;
    padding-left: 5px;
    font-size: 12px;
    left: 20px;
    font-weight: 500;
}
.payment_box.payment_method_wpp_stripe_cc,
li.payment_method_wpp_stripe_cc .payment_box{
	background: #fff !important;
}
.wpp-payment-bootstrap-form{
	background: #fff !important;
	margin-top: 10px;
}
.wpp-payment-bootstrap-form .row {
    display: -ms-flexbox;
    display: flex;
    -ms-flex-wrap: wrap;
    flex-wrap: wrap;
    margin-right: -15px;
    margin-left: -15px;
    margin-bottom: 0px;
    margin-top: 0px;
}
.wpp-payment-bootstrap-form .mb-2, .my-2 {
    margin-bottom: .5rem!important;
}
.wpp-payment-bootstrap-form .mb-3,
.wpp-payment-bootstrap-form .my-3 {
  margin-bottom: 18px !important;
}
.wpp-payment-bootstrap-form .col-md-3,
.wpp-payment-bootstrap-form .col-md-4,
.wpp-payment-bootstrap-form .col-md-6, 
.wpp-payment-bootstrap-form .col-md-9,
.wpp-payment-bootstrap-form .col-md-12 {
    position: relative;
    width: 100%;
    padding-right: 15px;
    padding-left: 15px;
}
.wpp-payment-bootstrap-form .md-form+label {
    position: absolute;
    top: .65rem;
    left: 15px;
    -webkit-transition: .3s ease-out;
    -o-transition: .3s ease-out;
    transition: .3s ease-out;
    cursor: text;
    color: #757575;
    pointer-events: none;
    overflow: hidden;
}
.wpp-payment-bootstrap-form .md-form.md-outline+label,
.wpp-payment-bootstrap-form .md-form.md-outline.StripeElement+label {
    font-size: 16px;
    position: absolute !important;
    top: 50%;
    transition-property: color, transform;
    transform: translateY(-50%);
    -webkit-transform: translateY(-50%);
    -ms-transform: translateY(-50%);
    -moz-transform: translateY(-50%);
    padding-left: 12px;
    -webkit-transition: .3s ease-out;
    -o-transition: .3s ease-out;
    transition: .3s ease-out;
    cursor: text;
    color: #495057;
    font-weight: 300;
    margin: 0;
    pointer-events: none;
}
.wpp-payment-bootstrap-form .md-form.md-outline.focused+label{
	color: #4285f4
}
.wpp-payment-bootstrap-form .md-form.md-outline.focused+label,
.wpp-payment-bootstrap-form .md-form.md-outline.invalid+label,
.wpp-payment-bootstrap-form .md-form.md-outline.StripeElement--complete+label {
    -webkit-transform: translateY(-35px);
    -ms-transform: translateY(-35px);
    transform: translateY(-35px);
    background: #fff !important;
    font-weight: 500;
    padding-right: 5px;
    padding-left: 5px;
    font-size: 12px;
    left: 20px;
    font-weight: 500;
}
.wpp-payment-bootstrap-form .md-form.md-outline.invalid+label{
	color: #E25950;
}
.wpp-payment-bootstrap-form .md-form.md-outline.StripeElement--complete+label{
	color: #1b9404;
}
.wpp-payment-bootstrap-form .md-form.md-outline {
    position: relative;
    width: 100%;
}
.wpp-payment-bootstrap-form .stripe-input{
	-webkit-transition: all .3s;
    -o-transition: all .3s;
    transition: all .3s;
    outline: 0;
    -webkit-box-shadow: none;
    box-shadow: none;
    border: 1px solid #dadce0;
    -webkit-border-radius: 4px;
    border-radius: 4px;
    background-color: #fff !important;
    -webkit-box-sizing: border-box;
    box-sizing: border-box;
    height: 40px;
}
.wpp-payment-bootstrap-form .stripe-input.focused,
.wpp-payment-bootstrap-form .stripe-input.invalid{
	 border-color: #4285f4;
    -webkit-box-shadow: inset 0 0 0 0.5px #4285f4;
    box-shadow: inset 0 0 0 0.5px #4285f4
}
.wpp-payment-bootstrap-form .stripe-input.invalid{
	border-color: #E25950;
	box-shadow: inset 0 0 0 0.5px #E25950;
}
.wpp-payment-bootstrap-form .stripe-input.StripeElement--complete{
	border-color: #1b9404;
	box-shadow: inset 0 0 0 0.5px #1b9404;
	-webkit-box-shadow: inset 0 0 0 0.5px #1b9404;
}
@media (min-width: 768px){
	.wpp-payment-bootstrap-form .col-md-3 {
	    -ms-flex: 0 0 25%;
	    flex: 0 0 25%;
	    max-width: 25%;
	}
	.wpp-payment-bootstrap-form .col-md-4{
		-webkit-box-flex: 0;
    	-ms-flex: 0 0 33.33%;
    	flex: 0 0 33.33%;
    	max-width: 33.33%;
	}
	.wpp-payment-bootstrap-form .col-md-9{
   		-webkit-box-flex: 0;
    	-ms-flex: 0 0 75%;
    	flex: 0 0 75%;
    	max-width: 75%;
	}
	.wpp-payment-bootstrap-form .col-md-12{
   		-webkit-box-flex: 0;
    	-ms-flex: 0 0 100%;
    	flex: 0 0 100%;
    	max-width: 100%;
	}
}
.stripe-small-container .wpp-payment-bootstrap-form .col-md-3,
.stripe-small-container .wpp-payment-bootstrap-form .col-md-4,
.stripe-small-container .wpp-payment-bootstrap-form .col-md-6,
.stripe-small-container .wpp-payment-bootstrap-form .col-md-9,
.stripe-small-container .wpp-payment-bootstrap-form .col-md-12{
    -ms-flex: 0 0 100%;
	 flex: 0 0 100%;
	 max-width: 100%;
}
</style>