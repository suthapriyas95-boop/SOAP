define([
    'ko',
    'jquery',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'CyberSource_ECheck/js/model/agreements-modal'
], function (ko,
             $,
             Component,
             quote,
             agreementsModal) {
    'use strict';

    return Component.extend({

        defaults: {
            code: 'cybersourceecheck'
        },

        customerName: ko.computed(function() {
            if(quote.shippingAddress()){
                return quote.shippingAddress().firstname + " " + quote.shippingAddress().lastname;
            }
        }),

        getCode: function () {
            return this.code;
        },

        /**
         * Init modal window for rendered element
         *
         * @param {Object} element
         */
        initModal: function (element) {
            agreementsModal.createModal(element);
        },

        /**
         * Show agreement content in modal
         *
         * @param {Object} element
         */
        showContent: function (element) {
            agreementsModal.showModal();
        },

        getDate: function () {
            return window.checkoutConfig.payment[this.getCode()].localeDate;
        },

        getStorePhone: function () {
            return window.checkoutConfig.payment[this.getCode()].storePhone;
        },

        getQuote: function () {
            return quote;
        },

        isAgreementRequired: function () {
            return !!parseInt(window.checkoutConfig.payment[this.getCode()].agreementRequired);
        }

    });
});
