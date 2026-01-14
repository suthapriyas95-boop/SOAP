/*
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

define([
    'uiComponent',
    'Magento_Ui/js/modal/modal',
    'CyberSource_WeChatPay/js/action/get-qr-data',
    'CyberSource_WeChatPay/js/action/check-status',
    'CyberSource_WeChatPay/js/model/loader',
    'jquery',
    'ko',
    'mage/validation',
    'mage/translate'
], function (Component, modalWidget, getQrDataAction, checkStatusAction, loader, $, ko) {
    'use strict';

    var requestsCount = 0;

    return Component.extend({

        weChatPayModalContainer: null,
        modal: null,

        defaults: {
            maxStatusRequests:  0,
            checkStatusFrequency: 5,
            isCheckout: false,
            popupMessageDelay: 3,
            orderId: null
        },

        initialize: function(config, element) {

            this._super();

            this.weChatPayModalContainer = $(element);

            var buttons = [];

            if (this.isCheckout) {
                buttons.push({
                    text: $.mage.__('Cancel'),
                    click: () => {
                        loader.startLoader();
                        this.cancelPayment();
                    }
                });

            } else {
                buttons.push({
                    text: $.mage.__('Cancel'),
                    click: () => {
                        this.weChatPayModalContainer.modal('closeModal');
                    }
                });
            }

            if (this.maxStatusRequests > 0) {
                buttons.push({
                    text: $.mage.__('Confirm'),
                    class: 'action primary',
                    click: () => {
                        loader.startLoader();
                        this.addMessage($.mage.__('Your payment is being processed. Please do not close or refresh the browser window.'), 'notice');
                        this.startStatusCheck();
                    }
                });
            }

            this.modal = modalWidget(
                {
                    type: 'popup',
                    responsive: true,
                    innerScroll: false,
                    clickableOverlay: false,
                    title: $.mage.__('WeChat Pay'),
                    trigger: '[data-trigger=wechatpayqr]',
                    buttons: buttons,
                    opened: () => {
                        var appendPromise = this.appendQrData();
                        if (!this.maxStatusRequests) {
                            appendPromise.done(() => this.startStatusCheck());
                        }
                        if (this.isCheckout) {
                            this.modal.modal.find('.modal-header .action-close').remove();
                        }
                    }
                },
                this.weChatPayModalContainer
            );

            $(window).on('wechat:orderCreated', () => this.openWeChatPayQrModal());

            return this;
        },

        appendQrData: function() {

            if (this.weChatPayModalContainer.find('.qr-wrapper iframe').length > 0) {
                return;
            }

            loader.startLoader();

            return getQrDataAction(this.orderId)
                .then(
                    (response) => {
                        var qrIframe = $("<iframe></iframe>");
                        qrIframe.attr('sandbox', 'allow-forms allow-scripts').attr("src", response.qr_url);
                        qrIframe.appendTo(this.weChatPayModalContainer.find('.qr-wrapper'));
                        $(this.weChatPayModalContainer.find('.qr-notice')).html(response.qr_notice);
                        loader.stopLoader();
                    }
                );
        },

        startStatusCheck: function (final, cancel) {

            return checkStatusAction(this.orderId, final, cancel).then(
                function (response) {
                    requestsCount++;

                    if (!response.success) {
                        loader.stopLoader();
                        return this.addMessage(response.error_msg, 'error');
                    }

                    if (response.is_settled) {
                        loader.stopLoader();
                        this.addMessage(response.status_msg, 'success');
                        return this.complete(this.getsuccessUrl());
                    }

                    if (response.is_failed) {
                        loader.stopLoader();
                        this.addMessage(response.status_msg, 'error');
                        return this.complete(this.getfailureUrl());
                    }

                    if (this.maxStatusRequests) {
                        this.addMessage(response.status_msg, 'notice');
                    }

                    if (this.maxStatusRequests > 0 && requestsCount >= this.maxStatusRequests) {
                        loader.stopLoader();
                        requestsCount = 0;
                        return;
                    }

                    window.setTimeout(() => {
                            this.startStatusCheck(requestsCount === this.maxStatusRequests - 1);
                        },
                        this.checkStatusFrequency * 1000
                    );
                }.bind(this)
            );
        },

        cancelPayment: function () {
            this.startStatusCheck(false, true);
        },

        openWeChatPayQrModal: function() {
            this.weChatPayModalContainer.modal('openModal');
        },

        addMessage: function (message, type) {
            this.weChatPayModalContainer.html(
                "<span class='message " + type + "'>" + message + "</span>"
            );
        },
        getsuccessUrl: function () {
            return window.checkoutConfig.payment['cybersourcewechatpay'].successUrl;
        },
        getfailureUrl: function () {
            return window.checkoutConfig.payment['cybersourcewechatpay'].failureUrl;
        },

        complete: function (redirectUrl) {
            window.setTimeout(
                function () {
                    if (!this.isCheckout) {
                        window.location.reload();
                        return;
                    }
                    window.location = redirectUrl;
                }.bind(this),
                this.popupMessageDelay * 1000
            );

            this.modal.modal.find('button').prop('disabled', true);
        }
    });
});
