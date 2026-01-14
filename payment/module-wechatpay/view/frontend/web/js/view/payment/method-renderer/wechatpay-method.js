/*
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery'
    ],
    function (Component, $) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'CyberSource_WeChatPay/payment/method-form',
                code: 'cybersourcewechatpay'
            },

            redirectAfterPlaceOrder: false,

            afterPlaceOrder: function () {
                $(window).trigger('wechat:orderCreated');
            },

            getCode: function () {
                return this.code;
            },

            getCheckoutConfigField: function (field) {
                return window.checkoutConfig.payment[this.getCode()][field];
            },

            getTitle: function () {
                return this.getCheckoutConfigField('title');
            },

            getIconUrl: function () {
                return this.getCheckoutConfigField('iconUrl');
            },

            getMaxStatusRequests: function () {
                return this.getCheckoutConfigField('maxStatusRequests');
            },

            getCheckStatusFrequency: function () {
                return this.getCheckoutConfigField('checkStatusFrequency');
            },

            getPopupMessageDelay: function () {
                return this.getCheckoutConfigField('popupMessageDelay');
            },

            getPaymentFailureUrl: function () {
                return this.getCheckoutConfigField('failureRedirectUrl');
            }
        });
    }
);
