define([], function () {
        'use strict';

        return {
            isEnabled: function (methodCode) {
                return this.getConfig(methodCode, '3ds_enabled');
            },
            getConfig: function (methodCode, param) {
                if (typeof window.checkoutConfig.payment[methodCode][param] === 'undefined') {
                    return false;
                }
                return Boolean(window.checkoutConfig.payment[methodCode][param]);
            }
        };
    }
);
