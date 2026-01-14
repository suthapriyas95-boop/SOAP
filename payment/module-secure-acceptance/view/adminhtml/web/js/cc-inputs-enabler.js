define([
    'jquery'
], function ($) {
    return function(methodCode, value) {
        ['cc-year', 'cc-month', 'cc-number', 'cc-cvv'].each(function (field) {
            var element = $('[data-container="' + methodCode + '-' + field + '"]');
            if (!element.length) {
                return;
            }
            element.prop('disabled', value);
        });
    }
});
