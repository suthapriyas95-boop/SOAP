/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Ui/js/form/element/select'
], function (Select) {
    'use strict';

    return Select.extend({
        defaults: {
            options: [
                {"value": "", "label": " "},
                {"value": "US", "label": "United States", "is_region_required": true},
                {"value": "CA", "label": "Canada", "is_region_required": true}
            ]
        }
    });
});
