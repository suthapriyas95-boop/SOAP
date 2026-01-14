var config = {
    map: {
        '*': {
            transparent: 'Magento_Payment/js/transparent',
            'Magento_Payment/transparent': 'Magento_Payment/js/transparent'
        }
    },
    config: {
        mixins: {
            'mage/validation': {
                'CyberSource_SecureAcceptance/js/lib/mage/validation-mixin': true
            },
            'Magento_Payment/js/model/credit-card-validation/credit-card-number-validator/credit-card-type': {
                'CyberSource_SecureAcceptance/js/model/credit-card-validation/credit-card-number-validator/credit-card-type-mixin': true
            }
        }
    }

};
