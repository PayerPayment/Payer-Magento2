/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'ko',
        'mage/translate',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/error-processor',
        'mage/url',
    ],
    function (
        $,
        ko,
        $t,
        Component,
        errorProcessor,
        url
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Payer_Checkout/payment/payer_checkout_bank-form',
            },
            redirectAfterPlaceOrder: false,
            initialize: function () {
                return this._super().initObservable();
            },
            /** Initialize all knockout observables */
            initObservable: function () {
                this.payerForm       = ko.observable([]);

                return this._super();
            },
            /**
             * Get icons
             */
            getIcons: function() {
                return window.checkoutConfig.payment.payer_checkout_bank.icons;
            },
            /**
             * After successful order, fetch and post payer form
             */
            afterPlaceOrder: function() {
                var self = this;

                var data = {
                    method: self.getCode(),
                    form_key: $.mage.cookies.get('form_key')
                }

                $.ajax({
                    url: window.checkoutConfig.payment.payer_checkout_bank.redirectOnSuccessUrl,
                    context: this,
                    method: 'POST',
                    data: data,
                    success: function(response) {
                        self.payerForm(response.form);
                        $('#payer-payment-form').submit();
                    },
                    error: function(response) {
                        fullscreenLoader.stopLoader();
                        errorProcessor.process(response, this.messageContainer);
                    }
                });
            },
        });
    }
);

