define([
    'jquery',
    'Magento_Payment/js/view/payment/iframe',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/action/set-payment-information',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Vault/js/view/payment/vault-enabler'
], function ($, Component, additionalValidators, setPaymentInformationAction, fullScreenLoader, VaultEnabler) {

    return Component.extend({
        defaults: {
            active: false,
            template: 'CyberSource_SecureAcceptance/payment/iframe',
            code: 'chcybersource'
        },
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

            if (this.getPassExpDate()) {
                data = $.extend(data, {
                    'additional_data': {
                        'expDate': this.creditCardExpMonth() + '-' + this.creditCardExpYear()
                    }
                });
            }

            if (this.getUseCvn()) {
                data.additional_data.cvv = this.creditCardVerificationNumber();
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
        getUseCvn: function () {
            return !window.checkoutConfig.payment[this.getCode()].ignore_cvn;
        },
        getPassExpDate: function () {
            return !!window.checkoutConfig.payment[this.getCode()].pass_expiration_date;
        },
        getHiddenFormTpl: function () {
            return  '<form target="<%= data.target %>" action="<%= data.action %>" method="POST" ' +
                'hidden enctype="application/x-www-form-urlencoded" class="no-display">' +
                '<% _.each(data.inputs, function(val, key){ %>' +
                '<input value="<%- val %>" name="<%- key %>" type="hidden">' +
                '<% }); %>' +
                '</form>';
        },
        placeOrder: function () {
            if (this.validateHandler() && additionalValidators.validate()) {

                fullScreenLoader.startLoader();

                this.isPlaceOrderActionAllowed(false);

                $.when(
                    setPaymentInformationAction(this.messageContainer, this.getData())
                )
                    .done(this.done.bind(this))
                    .fail(this.fail.bind(this))
                    .always(
                        function () {
                            this.isPlaceOrderActionAllowed(true);
                        }.bind(this)
                    )
                ;
            }
        }
    });


});
