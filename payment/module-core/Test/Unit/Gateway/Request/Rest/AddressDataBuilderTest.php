<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Gateway\Request\Rest;

use CyberSource\Core\Gateway\Request\Rest\AddressDataBuilder;

class AddressDataBuilderTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subjectReaderMock;

    /**
     * @var \CyberSource\Core\Gateway\Request\Rest\AddressDataBuilder
     */
    protected $addressDataBuilder;

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
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Payment\Model\InfoInterface::class);
        $this->orderMock = $this->createMock(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class);
        $this->addressMock = $this->createMock(\Magento\Payment\Gateway\Data\AddressAdapterInterface::class);

        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentDOMock->expects(static::any())->method('getOrder')->willReturn($this->orderMock);
        $this->orderMock->expects(static::any())->method('getBillingAddress')->willReturn($this->addressMock);

        $this->addressDataBuilder = new AddressDataBuilder(
            $this->subjectReaderMock
        );

    }

    public function testBuild()
    {

        $subject = ['payment' => $this->paymentDOMock];

        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($subject['payment']);

        $address = [
            'firstName' => 'test',
            'middleName' => 't',
            'lastName' => 'example',
            'company' => 'example org',
            'email' => 'test@example.org',
            'address1' => 'test line 1',
            'address2' => 'line 2',
            'locality' => 'TestVille',
            'administrativeArea' => 'Testorado',
            'country' => 'TE',
            'phoneNumber' => '123123',
            'postalCode' => '12312312',
        ];

        $this->addressMock->method('getFirstname')->willReturn($address['firstName']);
        $this->addressMock->method('getMiddlename')->willReturn($address['middleName']);
        $this->addressMock->method('getLastname')->willReturn($address['lastName']);
        $this->addressMock->method('getCompany')->willReturn($address['company']);
        $this->addressMock->method('getEmail')->willReturn($address['email']);
        $this->addressMock->method('getStreetLine1')->willReturn($address['address1']);
        $this->addressMock->method('getStreetLine2')->willReturn($address['address2']);
        $this->addressMock->method('getCity')->willReturn($address['locality']);
        $this->addressMock->method('getRegionCode')->willReturn($address['administrativeArea']);
        $this->addressMock->method('getCountryId')->willReturn($address['country']);
        $this->addressMock->method('getTelephone')->willReturn($address['phoneNumber']);
        $this->addressMock->method('getPostcode')->willReturn($address['postalCode']);

        $this->orderMock->method('getBillingAddress')->willReturn($this->addressMock);
        $this->orderMock->method('getShippingAddress')->willReturn($this->addressMock);

        static::assertEquals(
            [
                'orderInformation' =>
                    [
                        'billTo'=> $address,
                        'shipTo' => $address
                    ],
            ],
            $this->addressDataBuilder->build($subject)
        );
    }
}
