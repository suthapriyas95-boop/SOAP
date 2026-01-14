define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'klarnaJsSDK',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/action/set-billing-address',
        'Magento_Checkout/js/action/set-shipping-information',
        'Magento_Checkout/js/model/full-screen-loader',
        'mage/url',
        'jquery/ui',
        'mage/translate'
    ],
    function (
        $,
        ko,
        Component,
        klarnaJsSDK,
        additionalValidators,
        quote,
        customerData,
        setBillingAddress,
        setShippingInformation,
        fullScreenLoader,
        urlBuilder
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                active: ko.observable(false),
                template: 'CyberSource_KlarnaFinancial/payment/cybersource-form',
                code: 'cybersourceklarna',
                grandTotalAmount: null,
                currencyCode: null,
                initErrorMessage: ko.observable(null),
                count: 0,
                imports: {
                    onActiveChange: 'active'
                }
            },
            initialized: ko.observable(false),
            initObservable: function () {
                var self = this;
                this._super()
                    .observe(['active']);
                this.grandTotalAmount = quote.totals()['base_grand_total'];
                this.currencyCode = quote.totals()['base_currency_code'];

                if (this.isActive() && !this.initialized) {
                    this.initKlarna();
                }

                quote.totals.subscribe(function () {

                    if (!self.isActive()) {
                        return;
                    }

                    var changeMade = false;

                    if (self.grandTotalAmount !== quote.totals()['base_grand_total']) {
                        self.grandTotalAmount = quote.totals()['base_grand_total'];
                        changeMade = true;
                    }

                    if (self.currencyCode !== quote.totals()['base_currency_code']) {
                        self.currencyCode = quote.totals()['base_currency_code'];
                        changeMade = true;
                    }

                    if (self.isActive() && changeMade) {
                        self.initKlarna(); //Amount changed therefore we need to reinitialize the library whenever they select Klarna to reflect new amount
                    }
                });

                return this;
            },
            updateSession: function () {
                $.ajax({
                    method: 'POST',
                    url: urlBuilder.build("cybersourceklarna/index/session"),
                    data: {
                        'form_key': $.cookie('form_key'),
                        'guestEmail': quote.guestEmail,
                        'updateToken': true
                    }
                });
            },
            onActiveChange: function (isActive) {

                if (isActive && !this.initialized()) {
                    this.initKlarna();
                }
                return;
            },
            getCode: function () {
                return this.code;
            },

            isActive: function () {
                var active = this.getCode() === this.isChecked();

                this.active(active);

                return active;
            },

            getTitle: function () {
                return window.checkoutConfig.payment[this.getCode()].title;
            },

            getData: function () {
                return {
                    'method': this.item.method
                };
            },

            isDeveloperMode: function () {
                return window.checkoutConfig.payment[this.getCode()].isDeveloperMode;
            },
            getPlaceOrderUrl: function () {
                return window.checkoutConfig.payment[this.getCode()].placeOrderUrl;
            },
            placeOrder: function () {
                var that = this;

                if (!this.validate() || !additionalValidators.validate()) {
                    return;
                }

                this.isPlaceOrderActionAllowed(false);
                fullScreenLoader.startLoader();

                setBillingAddress().then(this.updateSession()).then(function () {
                    Klarna.Payments.authorize(
                        {},
                        function (response) {
                            if (response.approved !== true) {
                                that.isPlaceOrderActionAllowed(true);
                                fullScreenLoader.stopLoader();
                                return;
                            }
                            var form = $(document.createElement('form'));
                            $(form).attr("action", that.getPlaceOrderUrl());
                            $(form).attr("method", "POST");
                            $(form).append($('<input/>').attr('name', 'authorizationToken').attr('value', response.authorization_token));
                            $(form).append($('<input/>').attr('name', 'quoteId').attr('value', quote.getQuoteId()));
                            $(form).append($('<input/>').attr('name', 'guestEmail').attr('value', quote.guestEmail));
                            $(form).append($('<input/>').attr('name', 'form_key').attr('value', $.cookie('form_key')));
                            $("body").append(form);
                            $(form).submit();
                            customerData.invalidate(['cart']);
                        }
                    );
                });
            },

            initKlarna: function () {
                var self = this;

                fullScreenLoader.startLoader();

                var sessionData = {
                    'form_key': $.cookie('form_key'),
                    'guestEmail': quote.guestEmail
                };


                $.ajax({
                    method: 'GET',
                    url: urlBuilder.build("cybersourceklarna/index/session"),
                    data: sessionData
                }).done(function (data) {
                    var processorToken = data.processorToken;

                    if (processorToken === "" || processorToken === null) {
                        self.showInitErrorMessage({message: $.mage.__('Klarna widget loading error. Please try again.')});
                        self.isPlaceOrderActionAllowed(false);
                        fullScreenLoader.stopLoader();
                        return false;
                    }
                    if (typeof data.message !== "undefined") {
                        self.showInitErrorMessage({message: data.message});
                        self.isPlaceOrderActionAllowed(false);
                        fullScreenLoader.stopLoader();
                        return false;
                    }

                    try {
                        Klarna.Payments.init({client_token: processorToken});
                        Klarna.Payments.load(
                            {
                                container: "#klarna_container"
                            },
                            function (res) {
                                fullScreenLoader.stopLoader();

                                if (!res['show_form']) {
                                    self.showInitErrorMessage({message: $.mage.__('Klarna is not available as a payment option.')});
                                    self.isPlaceOrderActionAllowed(false);
                                    return;
                                }
                                $('#klarna_container').show();
                                self.isPlaceOrderActionAllowed(true);
                                self.initialized(true);
                            }
                        );
                    }
                    catch (e) {
                        console.log(e);
                    }
                });
            },
            showInitErrorMessage: function(messageObj) {
                this.initErrorMessage(messageObj.message);
            }
        });
    }
);
