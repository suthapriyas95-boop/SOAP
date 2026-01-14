/*
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

define([
        'jquery',
        'underscore',
        'Magento_Checkout/js/view/payment/default',
        'googlePayApi',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/payment/additional-validators'
    ], function ($, _, Component, googlePayApi, quote, additionalValidators) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'CyberSource_GooglePay/payment/method-form'
            },
            paymentsClient: null,
            isButtonInitialized: false,
            initialize: function () {
                this._super();
            },
            getData: function () {
                var data = {
                    'method': this.item.method,
                    'additional_data': null
                };

                if (this.paymentToken) {
                    data = $.extend({}, data, {
                        'additional_data': {
                            paymentToken: this.paymentToken
                        }
                    })
                }

                return data;
            },
            getEnvironment: function () {
                return window.checkoutConfig.payment[this.getCode()].environment;
            },
            getGatewayId: function () {
                return window.checkoutConfig.payment[this.getCode()].gatewayId;
            },
            getGatewayMerchantId: function () {
                return window.checkoutConfig.payment[this.getCode()].gatewayMerchantId;
            },
            getMerchantInfo: function () {
                var merchantInfo = {};

                if (window.checkoutConfig.payment[this.getCode()].merchantName) {
                    merchantInfo['merchantName'] = window.checkoutConfig.payment[this.getCode()].merchantName;
                }

                if (window.checkoutConfig.payment[this.getCode()].merchantId) {
                    merchantInfo['merchantId'] = window.checkoutConfig.payment[this.getCode()].merchantId;
                }

                return merchantInfo;
            },
            getBasePaymentMethod: function () {
                return {
                    type: 'CARD',
                    parameters: {
                        allowedAuthMethods: ["PAN_ONLY", "CRYPTOGRAM_3DS"],
                        allowedCardNetworks: window.checkoutConfig.payment[this.getCode()].cardTypes
                    }
                }
            },
            getTransactionInfo: function () {
                return {
                    countryCode: 'US',
                    currencyCode: quote.totals()['base_currency_code'],
                    totalPriceStatus: 'FINAL',
                    totalPrice: quote.totals()['base_grand_total'].toString()
                };
            },
            getGooglePayClient: function () {
                if (this.paymentsClient === null) {
                    this.paymentsClient = new googlePayApi.PaymentsClient({environment: this.getEnvironment()});
                }
                return this.paymentsClient;
            },
            containerAfterRender: function (element, viewModel) {
                _.defer(viewModel.initPayment.bind(viewModel));
            },
            getIsReadyToPayRequest: function () {
                return $.extend(
                    {},
                    {
                        apiVersion: 2,
                        apiVersionMinor: 0
                    },
                    {
                        allowedPaymentMethods: [this.getBasePaymentMethod()]
                    }
                );
            },
            getPaymentDataRequest: function () {
                var paymentDataRequest = $.extend({}, {
                    apiVersion: 2,
                    apiVersionMinor: 0
                });
                paymentDataRequest.allowedPaymentMethods = [$.extend(
                    {},
                    this.getBasePaymentMethod(),
                    {
                        tokenizationSpecification: {
                            type: 'PAYMENT_GATEWAY',
                            parameters: {
                                'gateway': this.getGatewayId(),
                                'gatewayMerchantId': this.getGatewayMerchantId()
                            }
                        }
                    }
                )];
                paymentDataRequest.transactionInfo = this.getTransactionInfo();
                paymentDataRequest.merchantInfo = this.getMerchantInfo();
                return paymentDataRequest;
            },
            initPayment: function () {
                this.getGooglePayClient()
                    .isReadyToPay(this.getIsReadyToPayRequest())
                    .then(function (response) {
                        if (!response.result) {
                            return;
                        }
                        this.addButton();
                        this.prefetchPaymentData();
                    }.bind(this))
                    .catch(function (err) {
                        console.error(err);
                    });
            },
            addButton: function () {
                if (this.isButtonInitialized) {
                    return;
                }

                var button = this.getGooglePayClient()
                    .createButton(
                        {onClick: this.buttonClickHandler.bind(this)}
                    );

                document.getElementById('opc_googlepaybuttoncontainer').appendChild(button);
                this.isButtonInitialized = true;
            },
            prefetchPaymentData: function () {
                var paymentDataRequest = this.getPaymentDataRequest();
                paymentDataRequest.transactionInfo = {
                    totalPriceStatus: 'FINAL',
                    currencyCode: quote.totals()['base_currency_code'],
                    totalPrice: quote.totals()['base_grand_total'].toString()
                };
                this.getGooglePayClient().prefetchPaymentData(paymentDataRequest);
            },
            buttonClickHandler: function () {
                var paymentRequest = this.getPaymentDataRequest();

                if (!this.validate() || !additionalValidators.validate()) {
                    return;
                }

                paymentRequest.transactionInfo = this.getTransactionInfo();

                this.getGooglePayClient().loadPaymentData(paymentRequest)
                    .then(function (paymentResponse) {
                        this.paymentToken = paymentResponse.paymentMethodData.tokenizationData.token;
                        this.placeOrder();
                    }.bind(this))
                    .catch(function (err) {
                        console.error(err);
                    });
            }
        });
    }
);
