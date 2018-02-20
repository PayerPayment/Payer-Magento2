/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'payer_checkout_invoice',
                component: 'Payer_Checkout/js/view/payment/method-renderer/payer_checkout_invoice-method'
            },
            {
                type: 'payer_checkout_installment',
                component: 'Payer_Checkout/js/view/payment/method-renderer/payer_checkout_installment-method'
            },
            {
                type: 'payer_checkout_card',
                component: 'Payer_Checkout/js/view/payment/method-renderer/payer_checkout_card-method'
            },
            {
                type: 'payer_checkout_bank',
                component: 'Payer_Checkout/js/view/payment/method-renderer/payer_checkout_bank-method'
            },
            {
                type: 'payer_checkout_swish',
                component: 'Payer_Checkout/js/view/payment/method-renderer/payer_checkout_swish-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);