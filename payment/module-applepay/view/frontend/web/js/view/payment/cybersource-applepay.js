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
        if (window.ApplePaySession && window.ApplePaySession.canMakePayments) {
            rendererList.push({
                type: 'cybersource_applepay',
                component: 'CyberSource_ApplePay/js/view/payment/method-renderer/cybersource-applepay'
            });
        }
        /** Add view logic here if needed */
        return Component.extend({});
    }
);


