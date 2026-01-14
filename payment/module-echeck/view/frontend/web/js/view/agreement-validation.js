define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/additional-validators',
    'CyberSource_ECheck/js/model/agreement-validator'
], function (Component, additionalValidators, agreementValidator) {
    'use strict';

    additionalValidators.registerValidator(agreementValidator);

    return Component.extend({});
});
