<?php
/**
 * Copyright © 2019 CyberSource. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace CyberSource\ThreeDSecure\Observer;

class DataAssignObserver extends \Magento\Payment\Observer\AbstractDataAssignObserver
{


    /**
     * @var \Magento\Quote\Api\Data\PaymentExtensionInterfaceFactory
     */
    private $extensionFactory;

    public function __construct(\Magento\Quote\Api\Data\PaymentExtensionInterfaceFactory $extensionFactory)
    {
        $this->extensionFactory = $extensionFactory;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        //TODO: add check if payment method is 3ds enabled

        $this->assignExtensionAttributes($observer);
        $this->assignSessionId($observer);
    }

    private function assignSessionId($observer)
    {
        $data = $this->readDataArgument($observer);

        if (!$additionalData = $data->getDataByPath(\Magento\Quote\Api\Data\PaymentInterface::KEY_ADDITIONAL_DATA)) {
            return;
        }

        if (empty($additionalData['sessionId'])) {
            return;
        };

        $payment = $this->readPaymentModelArgument($observer);
        $payment->setAdditionalInformation('payer_auth_enroll_session_id', $additionalData['sessionId']);
    }

    private function assignExtensionAttributes($observer)
    {
        $data = $this->readDataArgument($observer);

        if (!$additionalData = $data->getDataByPath(\Magento\Quote\Api\Data\PaymentInterface::KEY_ADDITIONAL_DATA)) {
            return;
        }

        if (!isset($additionalData['extension_attributes']) || !$additionalData['extension_attributes'] instanceof \Magento\Quote\Api\Data\PaymentExtension) {
            return;
        }

        /** @var \Magento\Quote\Api\Data\PaymentExtension $dataExtensionAttributes */
        $dataExtensionAttributes = $additionalData['extension_attributes'];

        if (!$ccaResponse = $dataExtensionAttributes->getCcaResponse()) {
            return;
        }

        $payment = $this->readPaymentModelArgument($observer);
        /** @var \Magento\Quote\Api\Data\PaymentExtensionInterface $paymentExtensionAttributes */
        $paymentExtensionAttributes = $payment->getExtensionAttributes() ?? $this->extensionFactory->create();

        $paymentExtensionAttributes->setCcaResponse($ccaResponse);

        $payment->setExtensionAttributes($paymentExtensionAttributes);
    }
}
