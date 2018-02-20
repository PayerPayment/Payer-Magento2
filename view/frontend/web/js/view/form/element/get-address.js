/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
  'jquery',
  'uiComponent',
  'ko',
  'mage/url'

], function ($, Component, ko, url) {
  'use strict';

  payerCheckout.requestUri = url.build('payer/checkout/getaddress/getaddress');
  return Component.extend({
    defaults: {
      template: 'Payer_Checkout/form/element/get-address',
      isLoading: false
    },

    getFormKey: function () {
      return window.checkoutConfig.formKey;
    },

    addEventListener: function () {
      document.addEventListener('payerCheckout.gotAddress', function (e) {
        var change = new Event('change');
        var data = payerCheckout.addressData;
        if ('success' === data.status) {
          document.querySelector('#get-address-message').innerHTML = '';

          var elementSelectors = {
            '[name="firstname"]': data.first_name,
            '[name="lastname"]':  data.last_name,
            '[name="company"]':   data.organisation,
            '[name="street[0]"]': data.address_1,
            '[name="street[1]"]': data.address_2,
            '[name="city"]':      data.city,
            '[name="postcode"]':  data.zip_code,
            '[name="vat_id"]':    data.identity_number
          };

          Object.keys(elementSelectors).forEach(function(key) {
            document.querySelector(key).value = elementSelectors[key]
            document.querySelector(key).dispatchEvent(change);
          });

          var selectBox = document.querySelector('[name="country_id"]');
          var countries = selectBox.options;
          for (var currentCountry, j = 0; currentCountry = countries[j]; j++) {
            currentCountry = currentCountry.value.toLowerCase();
            if (currentCountry == data.country) {
              countries[j].checked = 'checked';
              selectBox.selectedIndex = j;
            }
            selectBox.dispatchEvent(change);
          }
        } else {
          document.querySelector('#get-address-message').innerHTML = data.status;
        }
      }, false);
    }()

  });
});

if (typeof payerCheckout === 'undefined') {
  var payerCheckout = {};
}

payerCheckout.buildGetAddressRequest = function() {
  var addressForm         = document.querySelector('.form-getaddress');
  var ssn                 = addressForm.ssn.value;
  var zip                 = addressForm.zip.value;
  var form_key            = addressForm.form_key.value;

  payerCheckout.doGetAddressRequest(ssn,zip,form_key);
};