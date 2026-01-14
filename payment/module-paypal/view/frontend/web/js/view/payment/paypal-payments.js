/**
 * Copyright © Magento, Inc. All rights reserved.
 * See LICENSE.txt for license details.
 */

define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';

    rendererList.push(
        {
            type: 'cybersourcepaypal',
            component: 'CyberSource_PayPal/js/view/payment/method-renderer/paypal-express'
        },
        {
            type: 'cybersourcepaypal_credit',
            component: 'CyberSource_PayPal/js/view/payment/method-renderer/paypal-express-bml'
        }
    );

    /**
     * Add view logic here if needed
     **/
    return Component.extend({});
});
