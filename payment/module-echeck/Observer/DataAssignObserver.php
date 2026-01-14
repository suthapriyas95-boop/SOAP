<?php
/**
 * Copyright � 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Observer;

use Magento\Directory\Model\RegionFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

class DataAssignObserver extends AbstractDataAssignObserver
{
    /**
     * @var RegionFactory
     */
    private $regionFactory;

    public function __construct(
        RegionFactory $regionFactory
    ) {
        $this->regionFactory = $regionFactory;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);
        $paymentMethod = $this->readMethodArgument($observer);

        if ($paymentMethod->getCode() !== \CyberSource\ECheck\Model\Ui\ConfigProvider::CODE) {
            return;
        }

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_array($additionalData)) {
            return;
        }

        $additionalData = new DataObject($additionalData);
        $paymentInfo = $paymentMethod->getInfoInstance();

        if (!$paymentInfo instanceof InfoInterface) {
            throw new LocalizedException(__('Payment model does not provided.'));
        }

        $paymentInfo->setAdditionalInformation('check_bank_transit_number', $additionalData->getDataByKey('check_bank_transit_number'));
        $paymentInfo->setAdditionalInformation('check_account_number', $additionalData->getDataByKey('check_account_number'));

        if ($checkNumber = $driversLicenseNumber = $additionalData->getDataByKey('check_number')) {
            $paymentInfo->setAdditionalInformation('check_number', $checkNumber);
        }
        if ($driversLicenseNumber = $additionalData->getDataByKey('drivers_license_number')) {
            $paymentInfo->setAdditionalInformation('drivers_license_number', $driversLicenseNumber);
        }
        if ($region_id = $additionalData->getDataByKey('drivers_license_state')) {
            $region = $this->regionFactory->create();
            $region = $region->load($region_id);
            $paymentInfo->setAdditionalInformation('drivers_license_state', $region->getCode());
        }
    }
}
