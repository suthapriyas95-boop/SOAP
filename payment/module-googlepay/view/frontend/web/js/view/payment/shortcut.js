/*
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

define([
        'jquery',
        'underscore',
        'uiComponent',
        'Magento_Customer/js/model/customer',
        'mage/url',
        'googlePayApi'
    ], function ($, _, Component, customer, urlBuilder, googlePayApi) {
        'use strict';

        return Component.extend({
            defaults: {
                active: false,
                shortcutContainerClass: '',
                requiresShipping: true,
                isCatalogProduct: false,
                countryCode: 'US',
                allowedCountries: [],
                reviewUrl: ''
            },
            paymentsClient: null,
            isButtonInitialized: false,
            initialize: function () {
                this._super();
                _.defer(this.initPayment.bind(this));
            },
            isRequiresShipping: function () {
                return this.requiresShipping;
            },
            getEnvironment: function () {
                return this.environment;
            },
            getGatewayId: function () {
                return this.gatewayId;
            },
            getGatewayMerchantId: function () {
                return this.gatewayMerchantId;
            },
            getMerchantInfo: function () {
                var merchantInfo = {};

                if (this.merchantName) {
                    merchantInfo['merchantName'] = this.merchantName;
                }

                if (this.merchantId) {
                    merchantInfo['merchantId'] = this.merchantId;
                }

                return merchantInfo;
            },
            getBasePaymentMethod: function () {
                return {
                    type: 'CARD',
                    parameters: {
                        allowedAuthMethods: ["PAN_ONLY", "CRYPTOGRAM_3DS"],
                        allowedCardNetworks: this.cardTypes,
                        billingAddressRequired: true,
                        billingAddressParameters: {
                            format: 'FULL',
                            phoneNumberRequired: true
                        }
                    }
                }
            },
            getTransactionInfo: function (totals) {
                var baseTransactionInfo = {
                    countryCode: this.countryCode
                };

                return $.extend({}, baseTransactionInfo, totals);
            },
            getGooglePayClient: function () {
                if (this.paymentsClient === null) {
                    var dataCallbacks = {
                        onPaymentAuthorized: this.paymentAuthorizedHandler.bind(this)
                    }

                    if (this.isRequiresShipping()) {
                        dataCallbacks.onPaymentDataChanged = this.paymentDataChangedHandler.bind(this)
                    }

                    this.paymentsClient = new googlePayApi.PaymentsClient({
                        environment: this.getEnvironment(),
                        paymentDataCallbacks: dataCallbacks
                    });
                }
                return this.paymentsClient;
            },
            containerAfterRender: function () {
                _.defer(this.initPayment.bind(this));
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
                var $deferred = $.Deferred();

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
                paymentDataRequest.merchantInfo = this.getMerchantInfo();
                paymentDataRequest.callbackIntents = ['PAYMENT_AUTHORIZATION'];

                $.post(
                    urlBuilder.build('cybersourcegooglepay/index/request'),
                    {
                        form_key: $.cookie('form_key')
                    }
                ).then(
                    function (result) {

                        if (!result.success) {
                            $deferred.reject();
                        }

                        paymentDataRequest.transactionInfo = this.getTransactionInfo(result.request.total);
                        paymentDataRequest.emailRequired = !customer.isLoggedIn();

                        if (this.isRequiresShipping()) {
                            paymentDataRequest.callbackIntents = ['SHIPPING_ADDRESS', 'SHIPPING_OPTION', 'PAYMENT_AUTHORIZATION'];
                            paymentDataRequest.shippingAddressRequired = true;
                            paymentDataRequest.shippingAddressParameters = {
                                allowedCountryCodes: this.allowedCountries,
                                phoneNumberRequired: true
                            };
                            paymentDataRequest.shippingOptionRequired = true;

                            if (result.request.rates) {
                                paymentDataRequest.shippingOptionParameters = {
                                    shippingOptions: result.request.rates,
                                    defaultSelectedOptionId: result.request.defaultSelectedOptionId
                                };
                            }
                        }

                        $deferred.resolve(paymentDataRequest);
                    }.bind(this)
                );

                return $deferred.promise();
            },
            initPayment: function () {
                this.getGooglePayClient()
                    .isReadyToPay(this.getIsReadyToPayRequest())
                    .then(function (response) {
                        if (!response.result) {
                            return;
                        }
                        this.addButton();
                        // this.prefetchPaymentData();
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

                $(this.shortcutContainerClass).append(button);
                this.isButtonInitialized = true;
            },
            prefetchPaymentData: function () {
                this.getPaymentDataRequest().then(function (paymentDataRequest) {
                    this.getGooglePayClient().prefetchPaymentData(paymentDataRequest);
                }.bind(this));
            },
            paymentAuthorizedHandler: function (paymentData) {
                var $deferred = $.Deferred();

                this.processPayment(paymentData)
                    .then(function (redirectUrl) {
                        $deferred.resolve({transactionState: 'SUCCESS'});
                        setTimeout(function () {
                           window.location.replace(urlBuilder.build(redirectUrl));
                        }, 100);
                    })
                    .fail(function (errorMessage) {
                        $deferred.resolve({
                            transactionState: 'ERROR',
                            error: {
                                intent: 'PAYMENT_AUTHORIZATION',
                                message: errorMessage,
                                reason: 'PAYMENT_DATA_INVALID'
                            }
                        });
                    });
                return $deferred.promise();
            },
            paymentDataChangedHandler: function (paymentData) {
                var newPaymentData = {}, $deferred = $.Deferred(), that = this;

                var requestData = {
                    form_key: $.cookie('form_key')
                };

                if (paymentData.callbackTrigger === "INITIALIZE" || paymentData.callbackTrigger === "SHIPPING_ADDRESS") {
                    requestData['shippingAddress'] = paymentData.shippingAddress;
                }
                if (paymentData.shippingOptionData && paymentData.shippingOptionData.id) {
                    requestData['shippingMethod'] = paymentData.shippingOptionData.id;
                }

                $.post(urlBuilder.build('cybersourcegooglepay/index/shipping'),
                    requestData
                ).then(function (result) {
                    if (!result.success) {
                        $deferred.reject({
                            reason: 'SHIPPING_ADDRESS_INVALID',
                            message: 'Unable to set shipping address',
                            intent: 'SHIPPING_ADDRESS'
                        });
                        return;
                    }

                    $.post(urlBuilder.build('cybersourcegooglepay/index/request'),
                        {
                            form_key: $.cookie('form_key')
                        }
                    ).done(function (result) {
                        if (!result.success) {
                            $deferred.reject({
                                reason: 'SHIPPING_OPTION_INVALID',
                                message: result.message,
                                intent: 'SHIPPING_OPTION'
                            });
                            return;
                        }

                        newPaymentData.newShippingOptionParameters = {
                            shippingOptions: result.request.rates
                        };

                        if (result.request.defaultSelectedOptionId) {
                            newPaymentData.newShippingOptionParameters.defaultSelectedOptionId = result.request.defaultSelectedOptionId;
                        }

                        newPaymentData.newTransactionInfo = that.getTransactionInfo(result.request.total);

                        $deferred.resolve(newPaymentData);
                    }).fail(function () {
                        $deferred.reject({
                            reason: 'SHIPPING_ADDRESS_INVALID',
                            message: 'Unable to set shipping address',
                            intent: 'SHIPPING_ADDRESS'
                        });
                    });
                }).fail(function () {
                    $deferred.reject({
                        reason: 'SHIPPING_ADDRESS_INVALID',
                        message: 'Unable to set shipping address',
                        intent: 'SHIPPING_ADDRESS'
                    });
                });

                return $deferred.promise();
            },
            buttonClickHandler: function () {
                var $form = $(this.shortcutContainerClass).closest('form');

                if (!this.isCatalogProduct) {
                    this.startPayment();
                    return;
                }

                // handle pdp placement
                if (!$form.valid()) {
                    return;
                }
                $(document)
                    .off('ajax:addToCart', this.startPayment.bind(this))
                    .on('ajax:addToCart', this.startPayment.bind(this));
                $form.submit();
            },
            startPayment: function(){
                this.getPaymentDataRequest()
                    .then(
                        function (paymentRequest) {
                            this.getGooglePayClient().loadPaymentData(paymentRequest);
                        }.bind(this)
                    );
            },
            processPayment: function (paymentData) {
                var $deferred = $.Deferred();

                $.post(urlBuilder.build('cybersourcegooglepay/index/place'),
                    {
                        form_key: $.cookie('form_key'),
                        token: paymentData.paymentMethodData.tokenizationData.token,
                        billingAddress: paymentData.paymentMethodData.info.billingAddress,
                        shippingAddress: paymentData.shippingAddress,
                        shippingMethod: paymentData.shippingOptionData.id,
                        email: paymentData.email
                    }
                ).then(function (response) {
                    if (response.status !== 200) {
                        $deferred.reject(response.message);
                        return;
                    }
                    $deferred.resolve(response.redirect_url);
                });

                return $deferred.promise();
            }
        })
    }
)
