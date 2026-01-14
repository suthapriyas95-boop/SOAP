var appleSession = null;

define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/model/quote',
        'Magento_Payment/js/view/payment/cc-form',
        'Magento_Checkout/js/action/set-billing-address',
        'CyberSource_ApplePay/js/action/set-payment-method',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url',
        'Magento_Checkout/js/checkout-data',
        'Magento_Ui/js/model/messageList'

    ],
    function (
        $,
        ko,
        quote,
        Component,
        setBillingAddress,
        setPaymentMethodAction,
        additionalValidators,
        urlBuilder,
        customerData,
        messageContainer

    ) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'CyberSource_ApplePay/payment/cybersource-applepay',
                code: 'cybersource_applepay',
                appleRequest: null,
                grandTotalAmount: null
            },
            initObservable: function () {
                this._super();
                var that = this;

                quote.totals.subscribe(function () {
                    if (!that.isActive()) {
                        return;
                    }
                    if (that.grandTotalAmount !== quote.totals()['base_grand_total']) {
                        that.preparePaymentRequest();
                    }
                });

                return this;
            },
            initialize: function () {
                this._super();
                this.preparePaymentRequest();
            },
            getCode: function () {
                return 'cybersource_applepay';
            },
            getTitle: function () {
                return window.checkoutConfig.payment[this.getCode()].title;
            },
            isActive: function () {
                return window.checkoutConfig.payment[this.getCode()].active;
            },
            preparePaymentRequest: function () {
                var that = this;
                this.grandTotalAmount = null;
                $.getJSON(urlBuilder.build('cybersourceapple/index/request'), function(data) {
                    if (data.request) {
                        that.appleRequest = data.request;
                        that.grandTotalAmount = that.getTotalAmount(data.request);
                    }
                });
            },
            getTotalAmount: function (request) {
                if (request && request.total && request.total.amount) {
                    return parseFloat(request.total.amount);
                }
                return null;
            },
            validateMerchant: function(e) { //ApplePayValidateMerchantEvent
                $.post(urlBuilder.build('cybersourceapple/index/validate'), {
                    url: e.validationURL,
                    form_key: $.cookie('form_key')
                }, function(data){
                    if (data.session) {
                        appleSession.completeMerchantValidation(data.session);
                    }
                }, 'json');
            },
            paymentAuthorized: function(e) { //ApplePayPaymentAuthorizedEvent
                $.post(urlBuilder.build('cybersourceapple/index/placeorder'), {
                    payment: e.payment,
                    guestEmail: customerData.getValidatedEmailValue(),
                    form_key: $.cookie('form_key')
                }, function(response) {

                    if (response.status === 200) {
                        appleSession.completePayment(window.ApplePaySession.STATUS_SUCCESS);
                    }
                    else{
                        appleSession.completePayment(window.ApplePaySession.STATUS_FAILURE);
                        messageContainer.addErrorMessage({
                            message: response.message
                        })
                    };

                    window.location.replace(urlBuilder.build(response.redirect_url));

                }, 'json');
            },
            applePayRequest: function()
            {
                if (additionalValidators.validate() && this.appleRequest) {
                    appleSession = new window.ApplePaySession(2, this.appleRequest);
                    appleSession.onvalidatemerchant = this.validateMerchant;
                    appleSession.onpaymentauthorized = this.paymentAuthorized;
                    appleSession.begin();
                }
            }
        });
    }
);


