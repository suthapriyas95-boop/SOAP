/*
 * Copyright © 2021 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

define([
    'jquery',
    'mage/url',
    'Magento_Ui/js/model/messageList',
    'mage/cookies'
], function ($, urlBuilder, globalMessageList) {
    'use strict';

    return function () {
        var form = $('<form '
            + 'action="' + urlBuilder.build('cybersource/index/cancel', {}) + '" '
            + 'method="post">'
            + '</form>');
        $('body').append(form);
        form.submit();
    };
});
