define([
    'jquery',
    'mage/cookies'
], function ($) {
    'use strict';
    return function (data, orderSaveUrl) {
        var deferred = $.Deferred();
        $.ajax(
            orderSaveUrl,
            {
                'data': $.extend({}, data, {form_key: FORM_KEY}),
                'method': 'POST'
            }

        ).then(
            function (response) {
                if (response.error) {
                    deferred.reject(response);
                    return;
                }
                deferred.resolve(response.token, response.placeOrderUrl);
            }

        ).fail(function (response) {
            deferred.reject(response);
        });
        return deferred.promise();
    };
});