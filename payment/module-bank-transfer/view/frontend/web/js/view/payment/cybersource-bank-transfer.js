/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'cybersource_bank_transfer_ideal',
                component: 'CyberSource_BankTransfer/js/view/payment/method-renderer/cybersource-bank-transfer-ideal'
            },
            {
                type: 'cybersource_bank_transfer_sofort',
                component: 'CyberSource_BankTransfer/js/view/payment/method-renderer/cybersource-bank-transfer',
                config: {
                    code: 'cybersource_bank_transfer_sofort',
                    template: 'CyberSource_BankTransfer/payment/cybersource-bank-transfer'
                }
            },
            {
                type: 'cybersource_bank_transfer_bancontact',
                component: 'CyberSource_BankTransfer/js/view/payment/method-renderer/cybersource-bank-transfer',
                config: {
                    code: 'cybersource_bank_transfer_bancontact',
                    template: 'CyberSource_BankTransfer/payment/cybersource-bank-transfer'
                }
            });
        /** Add view logic here if needed */
        return Component.extend({});
    }
);


