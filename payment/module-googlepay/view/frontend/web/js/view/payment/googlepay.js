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

        rendererList.push(
            {
                type: 'cybersource_googlepay',
                component: 'CyberSource_GooglePay/js/view/payment/method-renderer/googlepay-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
