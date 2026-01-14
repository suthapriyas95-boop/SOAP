<?php

namespace CyberSource\SecureAcceptance\Plugin\Model\Quote\Payment;


class ToOrderPaymentPlugin
{

    /**
     * Plugin method that converts CcaResponse extension attribute from Quote Payment to Order Payment model
     *
     * @param \Magento\Quote\Model\Quote\Payment\ToOrderPayment $subject
     * @param \Magento\Sales\Api\Data\OrderPaymentInterface $result
     * @param \Magento\Quote\Model\Quote\Payment $quotePayment
     * @param array $data
     *
     * @return \Magento\Sales\Api\Data\OrderPaymentInterface
     */
    public function afterConvert(
        \Magento\Quote\Model\Quote\Payment\ToOrderPayment $subject,
        \Magento\Sales\Api\Data\OrderPaymentInterface $result,
        \Magento\Quote\Model\Quote\Payment $quotePayment,
        $data = []
    ) {

        $quotePayment->unsAdditionalInformation('cvv');
        return $result;
    }


}
