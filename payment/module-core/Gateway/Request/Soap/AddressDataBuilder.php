<?php
/*
 * Copyright © 2021 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Gateway\Request\Soap;

use Magento\Payment\Helper\Formatter;

class AddressDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    use Formatter;

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    private $remoteAddress;

    /**
     * @param \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader
     */
    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    /**
     * Builds Address Data
     *
     * @param array $buildSubject
     *
     * @return array
     */
    public function build(array $buildSubject)
    {
        $request = [];

        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $order = $paymentDO->getOrder();

        $request['billTo'] = $this->buildAddress($order->getBillingAddress());

        if ($order->getShippingAddress()) {
            $request['shipTo'] = $this->buildAddress($order->getShippingAddress());
        }

        return $request;
    }

    private function buildAddress($address)
    {
        $addressFields =
        [
            'firstName' => $address->getFirstname(),
            'lastName' => $address->getLastname(),
            'company' => $address->getCompany() ? substr($address->getCompany(), 0, 40) : "NA", 
            'email' => $address->getEmail(),
            'city' => $address->getCity(),
            'state' => $address->getRegionCode(),
            'country' => $address->getCountryId(),
            'postalCode' => $address->getPostcode(),
            'phoneNumber' => $address->getTelephone(),
        ];

        if($address instanceof \Magento\Payment\Gateway\Data\AddressAdapterInterface)
		{
            $addressFields['street1'] = $address->getStreetLine1();
            $addressFields['street2'] = $address->getStreetLine2();
        }
        else{
            $addressFields['street1'] = $address->getStreetLine(1);
            $addressFields['street2'] = $address->getStreetLine(2);
        }
        return $addressFields;
    }
}