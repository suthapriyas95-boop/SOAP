/**
 * Copyright © Magento, Inc. All rights reserved.
 * See LICENSE.txt for license details.
 */

define([
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'CyberSource_PayPal/js/action/set-payment-method',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/customer-data'
], function ($, Component, setPaymentMethodAction, additionalValidators, quote, customerData) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'CyberSource_PayPal/payment/paypal-express-bml'
        },

        /** Open window with  */
        showAcceptanceWindow: function (data, event) {
            window.open(
                $(event.currentTarget).attr('href'),
                'olcwhatispaypal',
                'toolbar=no, location=no,' +
                ' directories=no, status=no,' +
                ' menubar=no, scrollbars=yes,' +
                ' resizable=yes, ,left=0,' +
                ' top=0, width=400, height=350'
            );

            return false;
        },

        /** Returns payment acceptance mark link path */
        getPaymentAcceptanceMarkHref: function () {
            return this.getMethodConfig().paymentAcceptanceMarkHref;
        },

        /** Returns payment acceptance mark image path */
        getPaymentAcceptanceMarkSrc: function () {
            return this.getMethodConfig().paymentAcceptanceMarkSrc;
        },

        /** Returns payment information data */
        getData: function () {
            var parent = this._super(),
                additionalData = {};

            return $.extend(true, parent, {
                'additional_data': additionalData
            });
        },

        /** Redirect to paypal */
        continueToPayPal: function () {
            var self = this;
            if (additionalValidators.validate()) {
                //update payment method information if additional data was changed
                this.selectPaymentMethod();
                setPaymentMethodAction(this.messageContainer).done(
                    function () {
                        customerData.invalidate(['cart']);
                        $.mage.redirect(
                            self.getMethodConfig().redirectUrl[quote.paymentMethod().method]
                        );
                    }
                );

                return false;
            }
        },

        getMethodConfig: function () {
            return window.checkoutConfig.payment.cybersourcepaypal;
        }
    });
});
