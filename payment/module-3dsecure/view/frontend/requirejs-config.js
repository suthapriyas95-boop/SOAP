var config = {
    config: {
        mixins: {
            'CyberSource_SecureAcceptance/js/view/payment/method-renderer/iframe': {
                'CyberSource_ThreeDSecure/js/view/payment/iframe-mixin': true
            },
            'CyberSource_SecureAcceptance/js/view/payment/method-renderer/microform': {
                'CyberSource_ThreeDSecure/js/view/payment/microform-mixin': true
            },
            'CyberSource_SecureAcceptance/js/view/payment/method-renderer/vault': {
                'CyberSource_ThreeDSecure/js/view/payment/vault-mixin': true
            },
            'Magento_Checkout/js/model/error-processor': {
                'CyberSource_ThreeDSecure/js/model/error-processor-mixin': true
            }
        }
    }
};
