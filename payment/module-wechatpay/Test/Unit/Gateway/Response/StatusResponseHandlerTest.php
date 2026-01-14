<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\WeChatPay\Test\Unit\Gateway\Response;

use CyberSource\WeChatPay\Gateway\Response\StatusResponseHandler;
use PHPUnit\Framework\TestCase;

class StatusResponseHandlerTest extends TestCase
{
    /**
     * @var \CyberSource\Core\Gateway\Helper\SubjectReader|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subjectReaderMock;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderRepositoryMock;

    /**
     * @var StatusResponseHandler
     */
    protected $handler;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentDOMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMock;

    /**
     * @var \Magento\Payment\Gateway\Data\OrderAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderAdapterMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderPaymentMock;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->createMock(\CyberSource\Core\Gateway\Helper\SubjectReader::class);
        $this->orderRepositoryMock = $this->createMock(\Magento\Sales\Api\OrderRepositoryInterface::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createPartialMock(\Magento\Sales\Model\Order\Payment::class,[
            'setIsTransactionPending',
            'setIsTransactionClosed'
        ]);
        $this->orderAdapterMock = $this->createMock(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class);

        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentDOMock->expects(static::any())->method('getOrder')->willReturn($this->orderAdapterMock);

        $this->orderPaymentMock = $this->createPartialMock(\Magento\Sales\Model\Order\Payment::class, [
            'setIsTransactionApproved',
            'setIsTransactionDenied',
            'getLastTransId',
            'setTransactionId',
            'update',
        ]);

        $this->handler = new StatusResponseHandler(
            $this->subjectReaderMock,
            $this->orderRepositoryMock
        );
    }

    public function testHandleApproved()
    {
        $paymentStatus = 'settled';

        $subject = ['payment' => $this->paymentDOMock];
        $response = [
            'apCheckStatusReply' => (object)[
                'paymentStatus' => $paymentStatus
            ]
        ];
        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($this->paymentDOMock);

        $orderId = 12323;

        $this->orderAdapterMock->method('getId')->willReturn($orderId);
        $orderMock = $this->createMock(\Magento\Sales\Api\Data\OrderInterface::class);
        $this->orderRepositoryMock->method('get')->with($orderId)->willReturn($orderMock);

        $orderMock->method('getPayment')->willReturn($this->orderPaymentMock);

        $lastTransactionId = '123232323';
        $this->orderPaymentMock->method('getLastTransId')->willReturn($lastTransactionId);
        $this->orderPaymentMock->method('setTransactionId')->with($lastTransactionId);

        $this->orderPaymentMock->expects(static::once())->method('setIsTransactionApproved')->with(true)->willReturnSelf();
        $this->orderPaymentMock->expects(static::once())->method('update')->with(false)->willReturnSelf();

        $this->orderRepositoryMock->expects(static::once())->method('save')->with($orderMock);

        $this->handler->handle($subject, $response);
    }

    /**
     * @param $paymentStatus
     * @dataProvider dataProviderTestHandleDenied
     */
    public function testHandleDenied($paymentStatus)
    {
        $subject = ['payment' => $this->paymentDOMock];
        $response = [
            'apCheckStatusReply' => (object)[
                'paymentStatus' => $paymentStatus
            ]
        ];
        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($this->paymentDOMock);

        $orderId = 12323;

        $this->orderAdapterMock->method('getId')->willReturn($orderId);
        $orderMock = $this->createMock(\Magento\Sales\Api\Data\OrderInterface::class);
        $this->orderRepositoryMock->method('get')->with($orderId)->willReturn($orderMock);

        $orderMock->method('getPayment')->willReturn($this->orderPaymentMock);

        $lastTransactionId = '123232323';
        $this->orderPaymentMock->method('getLastTransId')->willReturn($lastTransactionId);
        $this->orderPaymentMock->method('setTransactionId')->with($lastTransactionId);

        $this->orderPaymentMock->expects(static::once())->method('setIsTransactionDenied')->with(true)->willReturnSelf();
        $this->orderPaymentMock->expects(static::once())->method('update')->with(false)->willReturnSelf();

        $this->orderRepositoryMock->expects(static::once())->method('save')->with($orderMock);

        $this->handler->handle($subject, $response);
    }

    public function dataProviderTestHandleDenied()
    {
        return [
            ['failed'],
            ['abandoned'],
        ];
    }

    /**
     * @param $paymentStatus
     * @dataProvider dataProviderTestHandleSkip
     */
    public function testHandleSkip($paymentStatus)
    {
        $subject = ['payment' => $this->paymentDOMock];
        $response = [
            'apCheckStatusReply' => (object)[
                'paymentStatus' => $paymentStatus
            ]
        ];
        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($this->paymentDOMock);

        $orderId = 12323;

        $this->orderAdapterMock->method('getId')->willReturn($orderId);
        $orderMock = $this->createMock(\Magento\Sales\Api\Data\OrderInterface::class);
        $this->orderRepositoryMock->method('get')->with($orderId)->willReturn($orderMock);

        $orderMock->method('getPayment')->willReturn($this->orderPaymentMock);

        $lastTransactionId = '123232323';
        $this->orderPaymentMock->method('getLastTransId')->willReturn($lastTransactionId);
        $this->orderPaymentMock->method('setTransactionId')->with($lastTransactionId);

        $this->orderPaymentMock->expects(static::never())->method('setIsTransactionDenied')->with(true)->willReturnSelf();
        $this->orderPaymentMock->expects(static::never())->method('setIsTransactionApproved')->with(true)->willReturnSelf();
        $this->orderPaymentMock->expects(static::never())->method('update')->with(false)->willReturnSelf();

        $this->orderRepositoryMock->expects(static::never())->method('save')->with($orderMock);

        $this->handler->handle($subject, $response);
    }

    public function dataProviderTestHandleSkip()
    {
        return [
            ['pending'],
            ['refunded'],
            ['some_other_status'],
        ];
    }
}
