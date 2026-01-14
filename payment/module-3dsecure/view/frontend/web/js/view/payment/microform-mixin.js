define([
    'jquery',
    'mage/utils/wrapper',
    'CyberSource_ThreeDSecure/js/view/payment/payer-authentication',
    'CyberSource_ThreeDSecure/js/view/payment/pa-enabler',
    'CyberSource_ThreeDSecure/js/model/jwt-decode'
], function ($, wrapper, pa, Enabler, jwtDecode) {
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
            placeOrderContinue: function (data, event, _super) {
                var cardBin;

                if (!this.microformResponse) {
                    pa.placeOrder(this, _super, cardBin);
                    return;
                }

                var jwt = jwtDecode(this.microformResponse);
                if (jwt && jwt.data && jwt.data.number) {
                    cardBin = jwt.data.number.substr(0, 6);
                }

                pa.placeOrder(this, _super, cardBin);
            },
            getData: function () {
                var data = this._super();

                pa.visitData(this, data);
                return data;
            }
        });

    };
});
