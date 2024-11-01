import {useState} from '@wordpress/element';
import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {getSettings, isTestMode} from '../util';
import {PaymentMethodLabel, PaymentMethod} from '../../components/checkout';
import SavedCardComponent from '../saved-card-component';
import {useCreateLinkToken, useInitializePlaid, useProcessPayment} from './hooks';
import {useProcessCheckoutError} from "../hooks";
import {__} from '@wordpress/i18n';

const getData = getSettings('stripe_ach_data');

const ACHPaymentContent = (
    {
        getData,
        eventRegistration,
        components,
        emitResponse,
        onSubmit,
        ...props
    }) => {
    const {responseTypes} = emitResponse;
    const {onPaymentProcessing, onCheckoutAfterProcessingWithError} = eventRegistration;
    const {ValidationInputError} = components;
    const [validationError, setValidationError] = useState(false);

    const linkToken = useCreateLinkToken({setValidationError});

    useProcessCheckoutError({
        responseTypes,
        subscriber: onCheckoutAfterProcessingWithError
    });

    const openLinkPopup = useInitializePlaid({
        getData,
        linkToken,
        onSubmit
    });

    useProcessPayment({
        openLinkPopup,
        onPaymentProcessing,
        responseTypes,
        paymentMethod: getData('name')
    });
    return (
        <>
            {isTestMode && <ACHTestModeCredentials/>}
            {validationError && <ValidationInputError errorMessage={validationError}/>}
        </>
    )
}

const ACHTestModeCredentials = () => {
    return (
        <div className='wpp-payment-blocks-ach__creds'>
            <label className='wpp-payment-blocks-ach__creds-label'>{__('Test Credentials', 'wc-stripe-payments')}</label>
            <div className='wpp-payment-blocks-ach__username'>
                <div>
                    <strong>{__('username', 'wc-stripe-payments')}</strong>: user_good
                </div>
                <div>
                    <strong>{__('password', 'wc-stripe-payments')}</strong>: pass_good
                </div>
                <div>
                    <strong>{__('pin', 'wc-stripe-payments')}</strong>: credential_good
                </div>
            </div>
        </div>
    );
}

registerPaymentMethod({
    name: getData('name'),
    label: <PaymentMethodLabel title={getData('title')}
                               paymentMethod={getData('name')}
                               icons={getData('icons')}/>,
    ariaLabel: 'ACH Payment',
    canMakePayment: ({cartTotals}) => cartTotals.currency_code === 'USD',
    content: <PaymentMethod
        getData={getData}
        content={ACHPaymentContent}/>,
    savedTokenComponent: <SavedCardComponent getData={getData}/>,
    edit: <ACHPaymentContent getData={getData}/>,
    placeOrderButtonLabel: getData('placeOrderButtonLabel'),
    supports: {
        showSavedCards: getData('showSavedCards'),
        showSaveOption: false,
        features: getData('features')
    }
})