<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Gateway\Request\Soap;

use PHPUnit\Framework\TestCase;

class CardDataBuilderTest extends TestCase
{


    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subjectReaderMock;

    /**
     * @var CardDataBuilder
     */
    protected $builderMock;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentDOMock;

    /**
     * @var \Magento\Payment\Model\InfoInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMock;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Payment\Model\InfoInterface::class);

        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);

        $this->builderMock = new CardDataBuilder($this->subjectReaderMock);
    }

    public function testBuild()
    {

        $subject = ['payment' => $this->paymentDOMock];

        $expMonth = '11';
        $expYear = '22';

        $expected = [
            'card' => [
                'expirationMonth' => $expMonth,
                'expirationYear' => $expYear,
            ]
        ];

        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($subject['payment']);

        $this->paymentMock
            ->method('getAdditionalInformation')
            ->with('expDate')
            ->willReturn(implode('-', [$expMonth, $expYear]));

        static::assertEquals($expected, $this->builderMock->build($subject));
    }
}
