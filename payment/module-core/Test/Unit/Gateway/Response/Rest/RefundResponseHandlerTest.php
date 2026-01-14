<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Gateway\Response\Rest;

use PHPUnit\Framework\TestCase;

class RefundResponseHandlerTest extends TestCase
{

    /**
     * @var \CyberSource\Core\Gateway\Helper\SubjectReader|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subjectReaderMock;

    /**
     * @var \Magento\Sales\Model\Order\Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $payment;

    /**
     * @var RefundResponseHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->createMock(\CyberSource\Core\Gateway\Helper\SubjectReader::class);

        $this->payment = $this->createPartialMock(
            \Magento\Sales\Model\Order\Payment::class,
            [
                'setTransactionId',
                'setIsTransactionClosed',
                'setIsTransactionPending',
                'setShouldCloseParentTransaction',
                'getCreditmemo',
                'getInvoice',
                'canRefund',
            ]
        );
        $this->handler = new RefundResponseHandler($this->subjectReaderMock);

    }

    public function testHandle()
    {
        $subject = ['payment' => $this->getPaymentDataObjectMock()];
        $response = ['id' => '123123123123'];

        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($subject['payment']);

        $canRefund = true;

        $this->payment->method('getCreditmemo')->willReturnSelf();
        $this->payment->method('getInvoice')->willReturnSelf();
        $this->payment->method('canRefund')->willReturn($canRefund);

        $this->payment->method('setTransactionId')->with($response['id'])->willReturnSelf();
        $this->payment->method('setShouldCloseParentTransaction')->with(!$canRefund)->willReturnSelf();

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
