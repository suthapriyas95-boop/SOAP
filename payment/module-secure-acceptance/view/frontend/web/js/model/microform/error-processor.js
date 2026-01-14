define([
    'mage/url',
    'Magento_Ui/js/model/messageList',
    'mage/translate'
], function (url, globalMessageList, $t) {
    'use strict';

    var messageMap = {
        'VALIDATION_ERROR': $t('Validation error.'),
        'DECRYPTION_ERROR': $t('Decryption error.'),
        'TOKENIZATION_ERROR': $t('Tokenization error.'),
        'RESOURCE_QUOTA_EXCEEDED': $t('Resource quota exceeded. Please refresh the page.'),
        'INTERNAL_ERROR': $t('Server-side error.')
    };

    return {
        /**
         * @param {Object} err
         * @param {Object} messageContainer
         */
        process: function (err, messageContainer) {
            messageContainer = messageContainer || globalMessageList;

            /* capture context expired  error message */
			if(err && err.reason && err.reason === 'CREATE_TOKEN_VALIDATION_SERVERSIDE'){
                messageContainer.addErrorMessage({
                    message: $t('Your Session has expired. Please wait for the form to be reloaded.')
                });
                window.location.reload();
                return;
            }

            if (
                !err.details ||
                !err.details.responseStatus ||
                !err.details.responseStatus.reason ||
                typeof messageMap[err.details.responseStatus.reason] === 'undefined'
            ) {
                messageContainer.addErrorMessage({
                    message: $t('Unknown error.')
                });
                return;
            }

            messageContainer.addErrorMessage({
                message: messageMap[err.details.responseStatus.reason]
            });

            if (typeof err.details.responseStatus.details === 'undefined') {
                return;
            }

            err.details.responseStatus.details.forEach(function (detail) {
                messageContainer.addErrorMessage({
                    message: $t(detail)
                });
            });

        }
    };
});
