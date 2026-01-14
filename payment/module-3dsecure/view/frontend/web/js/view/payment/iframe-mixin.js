define([
    'jquery',
    'mage/utils/wrapper',
    'CyberSource_ThreeDSecure/js/view/payment/payer-authentication',
    'CyberSource_ThreeDSecure/js/view/payment/pa-enabler'
], function ($, wrapper, pa, Enabler) {
    'use strict';

    return function (Component) {

        if (!Enabler.isEnabled('chcybersource')) {
            return Component;
        }

        return Component.extend({
            initialize: function () {
                this._super();
                pa.initialize(this.getCode());
            },
            placeOrder: function () {
                pa.placeOrder(this, this._super.bind(this), this.creditCardNumber(), this.creditCardType(),this.creditCardExpMonth(), this.creditCardExpYear());
            },
            getData: function () {
                var data = this._super();

                pa.visitData(this, data);
                return data;
            }
        });

    };
});
