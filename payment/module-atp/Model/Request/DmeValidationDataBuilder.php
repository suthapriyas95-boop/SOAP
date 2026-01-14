<?php

namespace CyberSource\Atp\Model\Request;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;

class DmeValidationDataBuilder
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \CyberSource\Atp\Model\Config
     */
    protected $config;

    /**
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \CyberSource\Atp\Model\Config $config
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \CyberSource\Atp\Model\Config $config
    ) {
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param string $type
     * @param CustomerInterface $customerData
     * @return \stdClass
     */
    public function build($type, $customerData)
    {
        $request = new \stdClass();

        $request->customerID = $customerData->getId() ?: 'new';
        $request->customerFirstName = $customerData->getFirstname();
        $request->customerLastName = $customerData->getLastname();
        $request->merchantID = $this->config->getMerchantId();
        $request->merchantReferenceCode = $this->getReferenceCode($type, $customerData);

        foreach ((array)$customerData->getAddresses() as $address) {
            if ($address->isDefaultBilling()) {
                $request->billTo = $this->buildAddressObject($address, true);
            }

            if ($address->isDefaultShipping()) {
                $request->shipTo = $this->buildAddressObject($address);
            }
        }

        $request->billTo = $request->billTo ?? new \stdClass();
        $request->billTo->email = $customerData->getEmail();

        $request->dmeService = $this->buildDmeServiceObject($type);
        $request->deviceFingerprintID = $this->getFingerprintId();

        return $request;
    }

    /**
     * @param AddressInterface $addressData
     * @param bool $isBillingAddress
     * @return \stdClass
     */
    protected function buildAddressObject($addressData, $isBillingAddress = false)
    {
        $street1 = isset($addressData->getStreet()[0]) ? $addressData->getStreet()[0] : null;
        $street2 = isset($addressData->getStreet[1]) ? $addressData->getStreet()[1] : null;

        $cyberSourceAddress = new \stdClass();
        $cyberSourceAddress->firstName = $addressData->getFirstname();
        $cyberSourceAddress->lastName = $addressData->getLastname();
        $cyberSourceAddress->street1 = $street1;
        $cyberSourceAddress->street2 = $street2;
        $cyberSourceAddress->city = $addressData->getCity();
        $cyberSourceAddress->state = $addressData->getRegion()->getRegion();
        $cyberSourceAddress->postalCode = $addressData->getPostcode();
        $cyberSourceAddress->country = $addressData->getCountryId();
        $cyberSourceAddress->phoneNumber = $addressData->getTelephone();

        if ($isBillingAddress) {
            $cyberSourceAddress->company = $addressData->getCompany();
        }

        return $cyberSourceAddress;
    }

    /**
     * @param $type
     * @return \stdClass
     */
    protected function buildDmeServiceObject($type)
    {
        $dmeService = new \stdClass();
        $dmeService->run = 'true';
        $dmeService->eventType = $type;

        return $dmeService;
    }

    /**
     * @param string $type
     * @param CustomerInterface $customerData
     * @return string
     */
    protected function getReferenceCode($type, $customerData)
    {
        $id = $customerData->getId() ?: 'new';
        return uniqid($type . '_' . $id . '_');
    }

    /**
     * @return string|null
     */
    protected function getFingerprintId()
    {
        return $this->checkoutSession->getData('fingerprint_id');
    }
}
