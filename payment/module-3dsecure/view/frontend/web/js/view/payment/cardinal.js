define([
    'jquery',
    'CyberSource_ThreeDSecure/js/action/create-token'
], function ($, getTokenAction) {
    return {
        init: function (debug) {
            if (!debug) {
                return;
            }
        },
        setup: function (messageContainer, data, accountNumber, cardExpMonth, cardExpYear, cardType ) {
            var setupDeferred = $.Deferred();
            getTokenAction(messageContainer, data,accountNumber,cardExpMonth, cardExpYear, cardType).done(function (token) {
                setupDeferred.resolve(token);
            }).fail(function () {
                setupDeferred.reject();
            });

            return setupDeferred.promise();
        },
    };
});
