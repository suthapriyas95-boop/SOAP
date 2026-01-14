define([
    'jquery',
    'CyberSource_SecureAcceptance/js/view/payment/method-renderer/iframe'
], function ($, Component) {
    return Component.extend({
        defaults: {
            active: false,
            template: 'CyberSource_SecureAcceptance/payment/hosted/redirect',
            code: 'chcybersource'
        }
    });
});
