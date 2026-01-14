<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\GooglePay\Model;


class AddressConverter
{

    /**
     * @var \Magento\Quote\Api\Data\AddressInterfaceFactory
     */
    private $addressInterfaceFactory;
    /**
     * @var \Magento\Directory\Model\Region
     */
    private $region;

    public function __construct(
        \Magento\Quote\Api\Data\AddressInterfaceFactory $addressInterfaceFactory,
        \Magento\Directory\Model\Region $region
    ) {
        $this->addressInterfaceFactory = $addressInterfaceFactory;
        $this->region = $region;
    }

    /**
     * Converts apple address data to magento AddressInterface object
     *
     * @param array $googleAddressData
     *
     * @return \Magento\Quote\Api\Data\AddressInterface
     */
    public function convertGoogleAddress($googleAddressData)
    {
        /** @var \Magento\Quote\Api\Data\AddressInterface $address */
        $address = $this->addressInterfaceFactory->create();

        $countryId = $googleAddressData['countryCode'];
        $region = $googleAddressData['administrativeArea'];

        if ($regionId = $this->getRegionId($region, $countryId)) {
            $address->setRegionId($regionId);
        } else {
            $address->setRegion($region);
        }

        list($fistName, $lastname) = explode(' ', $googleAddressData['name'] ?? ' ');

        $street = array_filter([
            $googleAddressData['address1'] ?? null,
            $googleAddressData['address2'] ?? null,
            $googleAddressData['address3'] ?? null,
        ]);

        $address
            ->setFirstname($fistName)
            ->setLastname($lastname)
            ->setPostcode($googleAddressData['postalCode'] ?? null)
            ->setStreet($street)
            ->setCity($googleAddressData['locality'] ?? null)
            ->setEmail($googleAddressData['emailAddress'] ?? null)
            ->setTelephone($googleAddressData['phoneNumber'] ?? null)
            ->setCountryId($countryId);

        return $address;
    }

    private function getRegionId($region, $countryId)
    {
        $regionModel = $this->region->loadByName($region, $countryId);

        // attempt to load region also by code (CA, NY etc)
        if (!$regionModel->getId()) {
            $regionModel = $this->region->loadByCode($region, $countryId);
        }

        return $regionModel->getId();
    }
}
