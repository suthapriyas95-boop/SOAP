<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Test\Unit\Gateway\Request\Soap;

use CyberSource\Core\Gateway\Request\Soap\MerchantDataBuilder;
use PHPUnit\Framework\TestCase;

class MerchantDataBuilderTest extends TestCase
{
    /**
     * @var \Magento\Payment\Gateway\Data\OrderAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderAdapterMock;

    /**
     * @var \Magento\Payment\Model\InfoInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDOMock;

    /** @var MerchantDataBuilder */
    private $merchantDataBuilder;

    /** @var \CyberSource\Core\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReaderMock;

    /** @var \CyberSource\Core\Model\AbstractGatewayConfig | \PHPUnit_Framework_MockObject_MockObject */
    private $gatewayConfigMock;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->createMock(\CyberSource\Core\Gateway\Helper\SubjectReader::class);
        $this->gatewayConfigMock = $this->createMock(\CyberSource\Core\Model\AbstractGatewayConfig::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Payment\Model\InfoInterface::class);
        $this->orderAdapterMock = $this->createMock(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class);

        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentDOMock->expects(static::any())->method('getOrder')->willReturn($this->orderAdapterMock);

        $this->merchantDataBuilder = new MerchantDataBuilder(
            $this->subjectReaderMock,
            $this->gatewayConfigMock
        );
    }

    public function testBuild()
    {
        $subject = ['payment' => $this->paymentDOMock];
        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($subject['payment']);

        $expected = [
            'partnerSolutionID' => '03AASPFT',
            'storeId' => 1,
            'merchantID' => 'cybertest',
            'developerId' => '123'
        ];

        $this->orderAdapterMock->method('getStoreId')->willReturn($expected['storeId']);

        $this->gatewayConfigMock->method('getMerchantId')->willReturn($expected['merchantID']);
        $this->gatewayConfigMock->method('getDeveloperId')->willReturn($expected['developerId']);

        $this->assertEquals($expected, $this->merchantDataBuilder->build($subject));
    }
}
