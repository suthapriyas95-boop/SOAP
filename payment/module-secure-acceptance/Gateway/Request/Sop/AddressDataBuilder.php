<?php
/*
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Gateway\Request\Sop;

use Magento\Payment\Helper\Formatter;

class AddressDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    use Formatter;

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

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
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $order = $paymentDO->getOrder();

        $request = $this->addKeyPrefix($this->buildAddress($order->getBillingAddress()), 'bill_to_');

        if ($order->getShippingAddress()) {
            $request = array_merge(
                $request,
                $this->addKeyPrefix($this->buildAddress($order->getShippingAddress()), 'ship_to_')
            );
        }

        return $request;
    }

    private function addKeyPrefix($inputDataArray, $prefix = '')
    {
        $out = [];

        foreach ($inputDataArray as $key => $item) {
            $out[$prefix . $key] = $item;
        }

        return $out;
    }

    private function buildAddress($address)
    {
        $addressFields =
        [
            'forename' => $address->getFirstname(),
            'surname' => $address->getLastname(),
            'company_name' => $address->getCompany() ? substr($address->getCompany(), 0, 40) : "NA",
            'email' => $address->getEmail(),
            'address_city' => $address->getCity(),
            'address_state' => $address->getRegionCode(),
            'address_country' => $address->getCountryId(),
            'address_postal_code' => $address->getPostcode(),
            'phone' => $address->getTelephone(),
        ];

        if($address instanceof \Magento\Payment\Gateway\Data\AddressAdapterInterface)
		{
            $addressFields['address_line1'] = $address->getStreetLine1();
            $addressFields['address_line2'] = $address->getStreetLine2();
        }
        else{
            $addressFields['address_line1'] = $address->getStreetLine(1);
            $addressFields['address_line2'] = $address->getStreetLine(2);
        }
        return $addressFields;
    }
}