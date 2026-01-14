define([], function () {
    'use strict';

    var types = [
        {
            cybsCardType: '001',
            type: 'VI'
        },
        {
            cybsCardType: '002',
            type: 'MC'
        },
        {
            cybsCardType: '003',
            type: 'AE'
        },
        {
            cybsCardType: '004',
            type: 'DI'
        },
        {
            cybsCardType: '005',
            type: 'DN'
        },
        {
            cybsCardType: '007',
            type: 'JCB'
        },
        {
            cybsCardType: '042',
            type: 'MI'
        }
    ];

    return {
        getMagentoType: function (cybsCardType) {
            var i, value;

            if (!cybsCardType) {
                return null;
            }

            for (i = 0; i < types.length; i++) {
                value = types[i];

                if (value.cybsCardType === cybsCardType) {
                    return value.type;
                }
            }
        }
    };

});
