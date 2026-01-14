define([
    'jquery',
    'CyberSource_SecureAcceptance/js/view/payment/method-renderer/sa/redirect',
    'Magento_Checkout/js/model/full-screen-loader',
    'CyberSource_SecureAcceptance/js/action/cancel'
], function ($, Component, fullScreenLoader, cancelAction) {
    return Component.extend({
        defaults: {
            active: false,
            template: 'CyberSource_SecureAcceptance/payment/sa/iframe',
            code: 'chcybersource'
        },
        initTimeoutHandler: function () {
            // we don't need to initialize a timer for iframe option
        },
        iframeLoadHandler: function () {
            fullScreenLoader.stopLoader(true);
            fullScreenLoader.stopLoader(true);
            fullScreenLoader.stopLoader(true);
            fullScreenLoader.stopLoader(true);
        },
        iframeReturnHandler: function(){
            fullScreenLoader.startLoader();
        },
        iframeCloseHandler: function () {
            fullScreenLoader.stopLoader(true);
            this.isPlaceOrderActionAllowed(true);
        },
        iframeCloseBtnHandler: function () {
            fullScreenLoader.startLoader(true);
            cancelAction();
        }
    });
});
