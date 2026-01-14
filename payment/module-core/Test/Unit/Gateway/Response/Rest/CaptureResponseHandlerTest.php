<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Gateway\Response\Rest;

use PHPUnit\Framework\TestCase;

class CaptureResponseHandlerTest extends TestCase
{

    /**
     * @var \CyberSource\Core\Gateway\Helper\SubjectReader|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subjectReaderMock;

    /**
     * @var CaptureResponseHandler
     */
    protected $handler;

    /**
     * @var \Magento\Sales\Model\Order\Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $payment;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->createMock(\CyberSource\Core\Gateway\Helper\SubjectReader::class);

        $this->payment = $this->createMock(\Magento\Sales\Model\Order\Payment::class);

        $this->handler = new CaptureResponseHandler($this->subjectReaderMock);

    }

    public function testHandle()
    {
        $subject = ['payment' => $this->getPaymentDataObjectMock()];
        $response = ['id' => '123123123123'];

        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($subject['payment']);

        $this->payment->method('setTransactionId')->with($response['id'])->willReturnSelf();
        $this->payment->method('setIsTransactionClosed')->with(false)->willReturnSelf();
        $this->payment->method('setIsTransactionPending')->with(false)->willReturnSelf();
        $this->payment->method('setIsFraudDetected')->with(false)->willReturnSelf();

        $this->handler->handle($subject, $response);

    }


    /**
     * Create mock for payment data object and order payment
     */
    private function getPaymentDataObjectMock()
    {
        $mock = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObject::class)
            ->setMethods(['getPayment'])
            ->disableOriginalConstructor()
            ->getMock();

        $mock->expects(static::once())
            ->method('getPayment')
            ->willReturn($this->payment);

        return $mock;
    }


}
