/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See LICENSE.txt for license details.
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

        if (!window.checkoutConfig.payment.chcybersource.iframe_post && saType !== 'flex') {
            return Component.extend({});
        }

        if (saType === 'flex') {
            rendererList.push(
                {
                    type: 'chcybersource',
                    component: 'CyberSource_SecureAcceptance/js/view/payment/method-renderer/microform'
                }
            );
            return Component.extend({});
        }

        if (window.checkoutConfig.payment.chcybersource.silent_post) {
            rendererList.push(
                {
                    type: 'chcybersource',
                    component: 'CyberSource_SecureAcceptance/js/view/payment/method-renderer/iframe'
                }
            );
            return Component.extend({});
        }

        if (window.checkoutConfig.payment.chcybersource.use_iframe) {
            rendererList.push(
                {
                    type: 'chcybersource',
                    component: 'CyberSource_SecureAcceptance/js/view/payment/method-renderer/hosted/iframe'
                }
            );
            return Component.extend({});
        }

        rendererList.push(
            {
                type: 'chcybersource',
                component: 'CyberSource_SecureAcceptance/js/view/payment/method-renderer/hosted/redirect'
            }
        );
        return Component.extend({});
    }
);
