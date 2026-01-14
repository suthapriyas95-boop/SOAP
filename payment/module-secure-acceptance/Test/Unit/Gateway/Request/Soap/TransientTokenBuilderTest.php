<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Gateway\Request\Soap;

use PHPUnit\Framework\TestCase;

class TransientTokenBuilderTest extends TestCase
{
    /**
     * @var TransientTokenBuilder
     */
    protected $transientTokenBuilder;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDOMock;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReaderMock;

    /** @var \CyberSource\SecureAcceptance\Model\PaymentTokenManagement | \PHPUnit_Framework_MockObject_MockObject */
    private $paymentTokenManagementMock;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);
        $this->paymentTokenManagementMock = $this->createMock(\CyberSource\SecureAcceptance\Model\PaymentTokenManagement::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Payment\Model\InfoInterface::class);
        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);

        $this->transientTokenBuilder = new \CyberSource\SecureAcceptance\Gateway\Request\Soap\TransientTokenBuilder(
            $this->subjectReaderMock
        );
    }

    public function testBuild($expected = [])
    {

        $subject = ['payment' => $this->paymentDOMock];

        $this->subjectReaderMock->method('readPayment')->willReturn($subject['payment']);

        $token = '123123123123';

        $this->paymentMock->method('getAdditionalInformation')->with(
            'transientToken'
        )->willReturn($token);

        $this->assertEquals(
            [
                'tokenSource' => [
                    'transientToken' => $token,
                ],
            ],
            $this->transientTokenBuilder->build($subject)
        );
    }

    public function testBuildEmpty()
    {

        $subject = ['payment' => $this->paymentDOMock];

        $this->subjectReaderMock->method('readPayment')->willReturn($subject['payment']);

        $this->paymentMock->method('getAdditionalInformation')->with(
            'transientToken'
        )->willReturn(null);

        $this->assertEquals(
            [],
            $this->transientTokenBuilder->build($subject)
        );
    }
}
