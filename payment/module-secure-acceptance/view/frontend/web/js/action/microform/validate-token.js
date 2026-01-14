define([
    'jquery',
    'mage/url',
    'Magento_Ui/js/model/messageList'
], function ($, urlBuilder, messageList) {
    'use strict';

    return function () {
        var ajaxUrl = urlBuilder.build('cybersource/microform/validateToken');

        $.ajax({
            url: ajaxUrl,
            method: 'POST'
        })
        .done(function (response) {
            // Ensure checkoutFlowType is SA_FLEX_MICROFORM before checking clientLibrary fields
            if (response.success && response.checkoutFlowType === 'SA_FLEX_MICROFORM') {
                if (!response.clientLibrary || !response.clientLibraryIntegrity) {
                    messageList.addErrorMessage({ message: 'Something went wrong due to unable to build token.' });
                }
            } else if (response.error) {
                messageList.addErrorMessage({ message: response.error_msg });
            }
        })
    };
});
