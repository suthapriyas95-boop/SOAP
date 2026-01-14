/**
 * Copyright © Magento, Inc. All rights reserved.
 * See LICENSE.txt for license details.
 */

define([
    'CyberSource_PayPal/js/view/payment/method-renderer/paypal-express-abstract'
], function (Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'CyberSource_PayPal/payment/paypal-express-bml'
        },

        getCreditTitle: function () {
            return this.getMethodConfig().creditTitle;
        }
    });
});
