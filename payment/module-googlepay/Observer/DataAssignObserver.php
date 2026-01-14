<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\GooglePay\Observer;


class DataAssignObserver  extends \Magento\Payment\Observer\AbstractDataAssignObserver
{
    const KEY_PAYMENT_TOKEN = 'paymentToken';

    /**
     * @inheritDoc
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->assignGooglePayData($observer);
    }

    private function assignGooglePayData(\Magento\Framework\Event\Observer $observer)
    {

        $paymentMethod = $this->readMethodArgument($observer);

        if ($paymentMethod->getCode() !== \CyberSource\GooglePay\Model\Ui\ConfigProvider::CODE) {
            return;
        }

        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(\Magento\Quote\Api\Data\PaymentInterface::KEY_ADDITIONAL_DATA);
        $additionalData = new \Magento\Framework\DataObject($additionalData);

        if (!$paymentToken = $additionalData->getDataByKey(static::KEY_PAYMENT_TOKEN)) {
            return;
        }

        $payment = $this->readPaymentModelArgument($observer);

        $payment->setAdditionalInformation(static::KEY_PAYMENT_TOKEN, base64_encode($paymentToken));

    }
}
