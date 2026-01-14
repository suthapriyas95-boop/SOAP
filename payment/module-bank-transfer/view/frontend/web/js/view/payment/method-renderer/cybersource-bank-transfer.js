define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/set-billing-address',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function ($, ko, quote, Component, setBillingAddress, additionalValidators, fullScreenLoader) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'CyberSource_BankTransfer/payment/cybersource-bank-transfer',
                code: ''
            },
            getCode: function () {
                return this.code;
            },
            getTitle: function () {
                return window.checkoutConfig.payment[this.getCode()].title;
            },
            isActive: function () {
                return window.checkoutConfig.payment[this.getCode()].active;
            },
            getPlaceOrderUrl: function () {
                return window.checkoutConfig.payment[this.getCode()].placeOrderUrl;
            },
            getBankCode: function(){
                return window.checkoutConfig.payment[this.getCode()].bankCode;
            },
            continueCybersource: function () {
                var that = this;
                if (!additionalValidators.validate()) {
                    return;
                }
                fullScreenLoader.startLoader();
                setBillingAddress(this.messageContainer).done(function () {
                    var params = {
                        bank: that.getBankCode(),
                        form_key: $.cookie('form_key')
                    };

                    if (quote.guestEmail) {
                        params.guestEmail = quote.guestEmail;
                    }

                    $.post(
                        that.getPlaceOrderUrl(),
                        params,
                        function (response) {

                            if (response.error) {
                                that.messageContainer.addErrorMessage({message: response.error});
                                fullScreenLoader.stopLoader();
                                return;
                            }

                            if (response.redirect_url) {
                                window.location.href = response.redirect_url;
                            }
                        },
                        'json'
                    );
                });
            }
        });
    }
);


