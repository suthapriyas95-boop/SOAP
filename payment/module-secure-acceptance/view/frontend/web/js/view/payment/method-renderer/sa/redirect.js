define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/url',
    'Magento_Payment/js/view/payment/iframe',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/action/set-payment-information',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Vault/js/view/payment/vault-enabler'
], function ($, modal, urlBuilder, Component, additionalValidators, setPaymentInformationAction, fullScreenLoader, VaultEnabler) {

    return Component.extend({
        defaults: {
            active: false,
            template: 'CyberSource_SecureAcceptance/payment/sa/redirect',
            code: 'chcybersource'
        },
        redirectAfterPlaceOrder: false,
        initialize: function () {
            this._super();
            this.vaultEnabler = new VaultEnabler();
            this.vaultEnabler.setPaymentCode(this.getVaultCode());
        },
        isActive: function () {
            return true;
        },
        getData: function () {
            var data = {
                'method': this.getCode(),
                'additional_data': {}
            };

            if (this.getAppendCardData()) {
                data.additional_data.ccType = this.creditCardType();
            }

            this.vaultEnabler.visitAdditionalData(data);

            return data;
        },
        isVaultEnabled: function () {
            return this.vaultEnabler.isVaultEnabled();
        },
        getVaultCode: function () {
            return window.checkoutConfig.payment[this.getCode()].vaultCode;
        },
        getCode: function () {
            return this.code;
        },
        context: function () {
            return this;
        },
        setPlaceOrderHandler: function (handler) {
            this.placeOrderHandler = handler;
        },
        setValidateHandler: function (handler) {
            this.validateHandler = handler;
        },
        getAppendCardData: function() {
            return !!window.checkoutConfig.payment[this.getCode()].silent_post;
        },
        placeOrder: function () {
            if (!this.validateHandler() || !additionalValidators.validate()) {
                return;
            }
            var isEnabled = window.checkoutConfig.cybersource_recaptcha && window.checkoutConfig.cybersource_recaptcha.enabled.cybersource;
            var recaptcha_invisible = window.checkoutConfig.payment.chcybersource.recaptcha_invisible;
                if(isEnabled && recaptcha_invisible != "invisible"){
                     var options = {
                         type: 'popup',
                         responsive: true,
                         innerScroll: true,
                         buttons: [{
                             text: $.mage.__('OK'),
                             class: 'mymodal1',
                             click: function () {
                                $('body').trigger('processStart');
                                 var url = urlBuilder.build("checkout");
                                 window.location = url;
                                 this.closeModal();
                             }
                         }]
                     };
             
                     var popup = modal(options, $('#sa-recaptcha'));    
                     var rresponse = jQuery('#g-recaptcha-response').val();
                     if(rresponse.length == 0) {
                        $("#sa-recaptcha").modal("openModal");
                        $('.action-close').css('display', 'none');
                        this.isPlaceOrderActionAllowed(false);
                         return false;
                     }
                     $('#sa-recaptcha').on('modalclosed', function() { 
                        $('body').trigger('processStart');
                        var url = urlBuilder.build("checkout");
                         window.location = url;
                     });
                 }

            fullScreenLoader.startLoader();

            this.isPlaceOrderActionAllowed(false);

            this.getPlaceOrderDeferredObject()
                .then(this.placeOrderHandler)
                .then(this.initTimeoutHandler.bind(this))
                .always(
                    function () {
                        this.isPlaceOrderActionAllowed(true);
                    }.bind(this)
                )
            ;
        }
    });


});
