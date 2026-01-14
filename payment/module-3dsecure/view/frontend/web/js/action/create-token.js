define([
    'jquery',
    'mage/url',
    'Magento_Ui/js/model/messageList',
    'mage/cookies'
], function ($, urlBuilder, globalMessageList ) {
    'use strict';

    return function (messageContainer, data,accountNumber,cardExpMonth, cardExpYear, cardType) {
        var deferred = $.Deferred();
        var messages = messageContainer || globalMessageList;

        data = $.extend(data || {}, {
            accountNumber,
            cardExpMonth,
             cardExpYear,
             cardType,
            'form_key': $.mage.cookies.get('form_key'),
            'ScreenHeight': window.screen.height,
            'ScreenWidth': window.screen.width,
            'TimeDifference': new Date().getTimezoneOffset(),
            'ColorDepth': window.screen.colorDepth,
            'JavaEnabled': navigator.javaEnabled(),
            'JavaScriptEnabled': typeof window !== 'undefined'&& typeof window.document !== 'undefined'&& typeof window.document.createElement === 'function',
            'Language': navigator.language,
            'AcceptContent': window.navigator.userAgent,
        });

        $.ajax(
            urlBuilder.build('cybersource3ds/cca/requestToken', {}),
            {'data': data, 'method': 'POST'}
        ).then(
            function (response) {
                if (!response.success) {
                    messages.addErrorMessage({message: response.error_msg});
                    deferred.reject();
                    return;
                }
                var cardinalCollectionForm = document.getElementById('cardinal_collection_form');
                cardinalCollectionForm.setAttribute('action', response.deviceDataCollectionURL);
                var cardinal_collection_form_input = document.getElementById('cardinal_collection_form_input')
                cardinal_collection_form_input.value = response.accessToken;
                if (cardinalCollectionForm) { 
                    cardinalCollectionForm.submit();
                    window.addEventListener("message", function(event) {
                        if (event.origin === response.sandbox || event.origin === response.production) {
                            console.log(event.data);
                        } 
                    });            
                }
                
                deferred.resolve(response.accessToken);
            }
        ).fail(function () {
            deferred.reject();
        });
        return deferred.promise();
    };
});
