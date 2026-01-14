define([
    'mage/utils/wrapper',
    'jquery',
    'mageUtils'
], function (wrapper, $, utils) {
    'use strict';

    var types = [
        {
            title: 'Discover',
            type: 'DI',
            pattern: '^6(?:011\\d{12}|5\\d{14}|4[4-9]\\d{13}|22(?:1(?:2[6-9]|[3-9]\\d)|[2-8]\\d{2}|9(?:[01]\\d|2[0-5]))\\d{10})$',
            gaps: [4, 8, 12],
            lengths: [16, 17, 18, 19],
            code: {
                name: 'CID',
                size: 3
            }
        }
    ];

    var getCardTypes = function (cardNumber) {
        var i, value, result = [];
        if (utils.isEmpty(cardNumber)) {
            return result;
        }

        if (cardNumber === '') {
            return $.extend(true, {}, types);
        }

        for (i = 0; i < types.length; i++) {
            value = types[i];

            if (new RegExp(value.pattern).test(cardNumber)) {
                result.push($.extend(true, {}, value));
            }
        }

        return result;
    };

    return function (cardType) {
        cardType.getCardTypes = wrapper.wrap(cardType.getCardTypes, function (_super, cardNumber) {

            var originalTypes = _super();

            if (originalTypes.length === 0) {
                return getCardTypes(cardNumber);
            }

            return originalTypes;
        });

        return cardType;
    };
});
