/*
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

define([
    'jquery'
], function ($) {
    'use strict';

    return {
        weChatPayModalContainer: $('#weChatPayQr'),

        startLoader: function () {
            this.weChatPayModalContainer.closest('.modal-inner-wrap').find('button').prop('disabled', true);
        },

        stopLoader: function () {
            this.weChatPayModalContainer.closest('.modal-inner-wrap').find('button').prop('disabled', false);
        }
    };
});
