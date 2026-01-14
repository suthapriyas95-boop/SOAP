<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Gateway\Response\Flex;

use PHPUnit\Framework\TestCase;

class PublicKeyHandlerTest extends TestCase
{

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subjectReaderMock;

    /**
     * @var \CyberSource\SecureAcceptance\Model\Jwt\JwtProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $jwtProcessorMock;

    /**
     * @var PublicKeyHandler
     */
    protected $handler;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentDOMock;

    /**
     * @var \Magento\Sales\Model\Order\Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMock;


    protected function setUp()
    {
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);
        $this->jwtProcessorMock = $this->createMock(\CyberSource\SecureAcceptance\Model\Jwt\JwtProcessorInterface::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Sales\Model\Order\Payment::class);
        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);

        $this->handler = new PublicKeyHandler($this->subjectReaderMock, $this->jwtProcessorMock);

    }

    public function testHandle()
    {
        $response = ['keyId' => 'someJWTkey'];
        $publicKey = 'somepubkey';
        $subject = ['payment' => $this->paymentDOMock];

        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($this->paymentDOMock);

        $this->jwtProcessorMock->method('getPublicKey')->with($response['keyId'])->willReturn($publicKey);

        $this->paymentMock->method('setAdditionalInformation')->with('microformPublicKey', $publicKey);

        $this->handler->handle($subject, $response);
    }

    public function testHandleWithException()
    {
        $response = ['keyId' => null];
        $publicKey = 'somepubkey';
        $subject = ['payment' => $this->paymentDOMock];
        $this->expectException(\Magento\Payment\Gateway\Command\CommandException::class);

        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($this->paymentDOMock);

        $this->jwtProcessorMock->method('getPublicKey')->with($response['keyId'])->willReturn(null);

        $this->paymentMock->method('setAdditionalInformation')->with('microformPublicKey', $publicKey);

        $this->handler->handle($subject, $response);
    }
}
