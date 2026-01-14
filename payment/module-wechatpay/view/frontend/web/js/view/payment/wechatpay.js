/*
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

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
                type: 'cybersourcewechatpay',
                component: 'CyberSource_WeChatPay/js/view/payment/method-renderer/wechatpay-method'
            }
        );

        return Component.extend({});
    }
);
