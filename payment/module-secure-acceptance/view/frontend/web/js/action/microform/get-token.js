define([
    'jquery',
    'mage/url',
    'Magento_Ui/js/model/messageList',
    'mage/cookies'
], function ($, urlBuilder, globalMessageList) {
    'use strict';

    return function (messageContainer, data) {
        var deferred = $.Deferred();
        var messages = messageContainer || globalMessageList;

        $.ajax(
            urlBuilder.build('cybersource/microform/tokenRequest', {}),
            {
                'data': $.extend({}, data, {form_key: $.cookie('form_key')}),
                'method': 'POST'
            }
        ).then(
            function (response) {
                if (!response.success) {
                    messages.addErrorMessage({message: response.error_msg});
                    deferred.reject();
                    return;
                }
                deferred.resolve(response.token);
            }
        ).fail(function () {
            deferred.reject();
        });

        return deferred.promise();
    };
});
