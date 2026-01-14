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

        rendererList.push(
            {
                type: 'cybersourceklarna',
                component: 'CyberSource_KlarnaFinancial/js/view/payment/method-renderer/cybersource-klarna'
            }
        );

        /** Add view logic here if needed */
        return Component.extend({});
    }
);
