<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Test\Unit\Gateway\Request\Soap;

use CyberSource\Core\Gateway\Request\Soap\BillToBuilder;
use PHPUnit\Framework\TestCase;

class BillToBuilderTest extends TestCase
{
    /**
     * @var \CyberSource\Core\Gateway\Helper\SubjectReader|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subjectReaderMock;

    /**
     * @var BillToBuilder
     */
    protected $builder;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentDOMock;

    /**
     * @var \Magento\Payment\Model\InfoInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMock;

    /**
     * @var \Magento\Payment\Gateway\Data\OrderAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderMock;

    /**
     * @var \Magento\Payment\Gateway\Data\AddressAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $addressMock;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->createMock(\CyberSource\Core\Gateway\Helper\SubjectReader::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Payment\Model\InfoInterface::class);
        $this->orderMock = $this->createMock(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class);
        $this->addressMock = $this->createMock(\Magento\Payment\Gateway\Data\AddressAdapterInterface::class);

        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentDOMock->expects(static::any())->method('getOrder')->willReturn($this->orderMock);
        $this->orderMock->expects(static::any())->method('getBillingAddress')->willReturn($this->addressMock);

        $this->builder = new BillToBuilder($this->subjectReaderMock);
    }

    public function testBuild()
    {
        $result = [
            'billTo' => [
                'firstName' => 'Test',
                'lastName' => 'User',
                'company' =>  'Company',
                'email' =>  'email@xyz.com',
                'street1' =>  'Street 1',
                'street2' =>  'Street 2',
                'city' =>  'City',
                'state' =>  'State',
                'country' =>  'Country',
                'phoneNumber' =>  '1234567890',
                'postalCode' =>  '10001'
            ]
        ];
        $subject = ['payment' => $this->paymentDOMock];

        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($this->paymentDOMock);

        $this->addressMock->method('getFirstname')->willReturn($result['billTo']['firstName']);
        $this->addressMock->method('getLastname')->willReturn($result['billTo']['lastName']);
        $this->addressMock->method('getCompany')->willReturn($result['billTo']['company']);
        $this->addressMock->method('getEmail')->willReturn($result['billTo']['email']);
        $this->addressMock->method('getStreetLine1')->willReturn($result['billTo']['street1']);
        $this->addressMock->method('getStreetLine2')->willReturn($result['billTo']['street2']);
        $this->addressMock->method('getCity')->willReturn($result['billTo']['city']);
        $this->addressMock->method('getRegionCode')->willReturn($result['billTo']['state']);
        $this->addressMock->method('getCountryId')->willReturn($result['billTo']['country']);
        $this->addressMock->method('getTelephone')->willReturn($result['billTo']['phoneNumber']);
        $this->addressMock->method('getPostcode')->willReturn($result['billTo']['postalCode']);

        static::assertEquals($result, $this->builder->build($subject));
    }
}
