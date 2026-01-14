/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See LICENSE.txt for license details.
 */
/*browser:true*/
/*global define*/
define([
    'jquery',
    'underscore',
    'Magento_Vault/js/view/payment/method-renderer/vault'
], function ($, _, VaultComponent) {
    'use strict';

    return VaultComponent.extend({
        defaults: {
            template: 'CyberSource_PayPal/payment/vault',
            additionalData: {}
        },

        /**
         * Get PayPal payer email
         * @returns {String}
         */
        getPayerEmail: function () {
            return this.details.email;
        },
        /**
         * Get masked billing agreement ID
         * @returns {String}
         */
        getMaskedToken: function(){
            return this.maskedToken;
        },
        /**
         * Get type of payment
         * @returns {String}
         */
        getPaymentIconSrc: function () {
            return window.checkoutConfig.payment[this.getPaymentProviderCode()].paymentAcceptanceMarkSrc;
        },
        /**
         * Get payment method data
         * @returns {Object}
         */
        getData: function () {
            var data = {
                'method': this.code,
                'additional_data': {
                    'public_hash': this.publicHash
                }
            };

            data['additional_data'] = _.extend(data['additional_data'], this.additionalData);

            return data;
        },

        getPaymentProviderCode: function () {
            return 'cybersourcepaypal';
        }
    });
});
