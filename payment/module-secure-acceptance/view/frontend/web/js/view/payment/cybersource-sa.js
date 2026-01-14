/*
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */
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

        var saType = window.checkoutConfig.payment.chcybersource.sa_type;

        if (window.checkoutConfig.payment.chcybersource.iframe_post || saType === 'flex') {
            return Component.extend({});
        }

        if (
            window.checkoutConfig.payment.chcybersource.silent_post
            || !window.checkoutConfig.payment.chcybersource.use_iframe
        ) {
            rendererList.push(
                {
                    type: 'chcybersource',
                    component: 'CyberSource_SecureAcceptance/js/view/payment/method-renderer/sa/redirect'
                }
            );
            return Component.extend({});
        }

        rendererList.push(
            {
                type: 'chcybersource',
                component: 'CyberSource_SecureAcceptance/js/view/payment/method-renderer/sa/iframe'
            }
        );
        return Component.extend({});
    }
);
