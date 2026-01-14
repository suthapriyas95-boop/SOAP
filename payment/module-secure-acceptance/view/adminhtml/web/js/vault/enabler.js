/*
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

define(
    [],
    function () {
        return function (element, gatewayCode) {
            return element.find(
                '[data-container="' + gatewayCode + '-vault-enabled"]'
            ).prop("checked");
        }
    }
);
