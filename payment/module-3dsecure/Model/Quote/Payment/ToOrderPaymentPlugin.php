<?php
/**
 *
 */

namespace CyberSource\ThreeDSecure\Model\Quote\Payment;

class ToOrderPaymentPlugin
{

    /**
     * @var \Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory
     */
    private $orderPaymentExtensionFactory;

    public function __construct(
        \Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory $orderPaymentExtensionFactory
    ) {
        $this->orderPaymentExtensionFactory = $orderPaymentExtensionFactory;
    }


    /**
     * Plugin method that converts CcaResponse extension attribute from Quote Payment to Order Payment model
     *
     * @param \Magento\Quote\Model\Quote\Payment\ToOrderPayment $subject
     * @param \Magento\Sales\Api\Data\OrderPaymentInterface $result
     * @param \Magento\Quote\Model\Quote\Payment $quotePayment
     * @param array $data
     * @return \Magento\Sales\Api\Data\OrderPaymentInterface
     */
    public function afterConvert(
        \Magento\Quote\Model\Quote\Payment\ToOrderPayment $subject,
        \Magento\Sales\Api\Data\OrderPaymentInterface $result,
        \Magento\Quote\Model\Quote\Payment $quotePayment,
        $data = []
    ) {
        $this->assignSessionId($quotePayment, $result);
        $this->assignExtensionAttributes($quotePayment, $result);
        return $result;
    }

    private function assignSessionId(
        \Magento\Quote\Model\Quote\Payment $quotePayment,
        \Magento\Sales\Api\Data\OrderPaymentInterface $result
    ) {
        if ($sessionId = $quotePayment->getAdditionalInformation('payer_auth_enroll_session_id')) {
            $result->setAdditionalInformation('payer_auth_enroll_session_id', $sessionId);
        }
    }

    private function assignExtensionAttributes(
        \Magento\Quote\Model\Quote\Payment $quotePayment,
        \Magento\Sales\Api\Data\OrderPaymentInterface $result
    ) {
        if ($quotePayment->getExtensionAttributes() === null) {
            return $result;
        }

        if ($ccaResponse = $quotePayment->getExtensionAttributes()->getCcaResponse()) {
            $orderPaymentExtensionAttributes = $this->getExtensionAttributes($result);
            $orderPaymentExtensionAttributes->setCcaResponse($ccaResponse);
        }
    }

    private function getExtensionAttributes(\Magento\Sales\Api\Data\OrderPaymentInterface $payment)
    {
        $extensionAttributes = $payment->getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->orderPaymentExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }
        return $extensionAttributes;
    }
}
