if (typeof payerCheckout === 'undefined') {
  var payerCheckout = {};
}

payerCheckout.addressData = [];
payerCheckout.gotAddress = document.createEvent('Event');
payerCheckout.gotAddress.initEvent('payerCheckout.gotAddress', true, true);

payerCheckout.getAddress = function () {
  payerCheckout.buildGetAddressRequest();
};

payerCheckout.doGetAddressRequest = function (ssn, zip, form_key) {
  var requestUri = payerCheckout.requestUri + '?in=' + ssn +
    '&zip=' + zip +
    '&form_key=' + form_key
    + '&';
  var getAddressRequest = new XMLHttpRequest();
  getAddressRequest.onreadystatechange = function () {
    if (this.readyState == 4 && this.status == 200) {
      payerCheckout.addressData = JSON.parse(this.responseText);
      document.dispatchEvent(payerCheckout.gotAddress);
    }
  };
  getAddressRequest.open("GET", requestUri, true);
  getAddressRequest.send();
};