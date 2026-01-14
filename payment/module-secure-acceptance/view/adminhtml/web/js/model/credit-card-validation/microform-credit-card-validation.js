define([
    'jquery',
    'jquery/ui',
    'jquery/validate',
    'mage/translate'
], function ($) {
    'use strict';

    $.validator.addMethod(
        'microform-card-valid',
        function (value, element, params) {
            var input = $(params.selector), valid;

            if (!input.length && params.selector) {
                return false;
            }

            valid = String(input.attr('data-valid')).toLowerCase() === "true";

            input.removeClass(params.errorClass);

            if (!valid) {
                input.addClass(params.errorClass);
            }

            return valid;
        },
        $.mage.__('Please enter a valid card number.')
    );

    $.validator.addMethod(
        'microform-card-valid-type',
        function (value, element, params) {
            var input = $(params.selector), valid;

            if (!input.length && params.selector) {
                return false;
            }

            valid = String(input.attr('valid-card-type')).toLowerCase() === "true";
            input.removeClass(params.errorClass);

            if (!valid) {
                input.addClass(params.errorClass);
            }

            return valid;
        },
        $.mage.__('The card entered is not supported. Please use an alternative card from the supported card type(s).')
    );

    $.validator.addMethod(
        'microform-card-valid-cvn',
        function (value, element, params) {
            var input = $(params.selector), valid;

            if (!input.length && params.selector) {
                return false;
            }
            valid = String(input.attr('data-valid')).toLowerCase() === "true";
            input.removeClass(params.errorClass);

            if (!valid) {
                input.addClass(params.errorClass);
            }

            return valid;
        },
        $.mage.__('Please enter a valid number in this field.')
    );

    return $.validator;
});
