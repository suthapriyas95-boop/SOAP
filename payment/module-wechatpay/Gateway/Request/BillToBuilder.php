<?php
/**
 * Copyright © 2021 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\WeChatPay\Gateway\Request;

class BillToBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    /**
     * @var \CyberSource\WeChatPay\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @param \CyberSource\WeChatPay\Gateway\Helper\SubjectReader $subjectReader
     */
    public function __construct(
        \CyberSource\WeChatPay\Gateway\Helper\SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    /**
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $order = $this->subjectReader->readPayment($buildSubject)->getOrder();
        return [
            'billTo' => [
                'firstName' => $order->getBillingAddress()->getFirstname(),
                'lastName' => $order->getBillingAddress()->getLastname(),
                'company' =>  $order->getBillingAddress()->getCompany(),
                'email' =>  $order->getBillingAddress()->getEmail(),
                'street1' =>  $order->getBillingAddress()->getStreetLine1(),
                'street2' =>  $order->getBillingAddress()->getStreetLine2(),
                'city' =>  $order->getBillingAddress()->getCity(),
                'state' =>  $order->getBillingAddress()->getRegionCode(),
                'country' =>  $order->getBillingAddress()->getCountryId(),
                'phoneNumber' =>  $order->getBillingAddress()->getTelephone(),
                'postalCode' =>  $order->getBillingAddress()->getPostcode()
            ]
        ];
    }
}
