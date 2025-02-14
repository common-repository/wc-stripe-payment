import './style.scss';
import {registerCreditCardForm} from "@paymentplugins/stripe/util";
import {CardNumberElement, CardExpiryElement, CardCvcElement} from '@stripe/react-stripe-js';
import {__} from "@wordpress/i18n";

const Bootstrap = ({CardIcon, options, onChange}) => {
    return (
        <div className='wpp-payment-bootstrap-form'>
            <div className='row'>
                <div className='col-md-6 mb-3'>
                    <CardNumberElement className='md-form md-outline stripe-input' options={options['cardNumber']}
                                       onChange={onChange(CardNumberElement)}/>
                    <label htmlFor="stripe-card-number">{__('Card Number', 'wc-stripe-payments')}</label>
                    {CardIcon}
                </div>
                <div className='col-md-3 mb-3'>
                    <CardExpiryElement className='md-form md-outline stripe-input' options={options['cardExpiry']}
                                       onChange={onChange(CardExpiryElement)}/>
                    <label htmlFor="stripe-exp">{__('Exp', 'wc-stripe-payments')}</label>
                </div>
                <div className='col-md-3 mb-3'>
                    <CardCvcElement className="md-form md-outline stripe-input" options={options['cardCvc']}
                                    onChange={onChange(CardCvcElement)}/>
                    <label htmlFor="stripe-cvv">{__('CVV', 'wc-stripe-payments')}</label>
                </div>
            </div>
        </div>
    )
}

registerCreditCardForm({
    id: 'bootstrap',
    breakpoint: 475,
    component: <Bootstrap/>
})