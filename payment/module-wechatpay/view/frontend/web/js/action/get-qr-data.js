/*
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

define([
    'jquery',
    'mage/url',
    'Magento_Ui/js/model/messageList',
    'mage/cookies'
], function ($, urlBuilder, globalMessageList) {
    'use strict';

    return function (orderId) {
        var deferred = $.Deferred();

        $.ajax({
            url: urlBuilder.build('cybersourcewechatpay/payment/getQrData', {}),
            data: { form_key: $.cookie('form_key'), order_id: orderId },
            type: 'POST'
        }).then(
            function (response) {
                if (response.success) {
                    return deferred.resolve(response);
                }
                globalMessageList.addErrorMessage({ message: response.error_msg });
                deferred.reject();
            }
        ).fail(function () {
            deferred.reject();
        });

        return deferred.promise();
    };
});
