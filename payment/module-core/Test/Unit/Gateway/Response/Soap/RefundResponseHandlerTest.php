<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Test\Unit\Gateway\Response\Soap;

use CyberSource\Core\Gateway\Response\Soap\RefundResponseHandler;
use PHPUnit\Framework\TestCase;

class RefundResponseHandlerTest extends TestCase
{
    /**
     * @var \CyberSource\Core\Gateway\Helper\SubjectReader|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subjectReaderMock;

    /**
     * @var RefundResponseHandler
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

    /**
     * @var \Magento\Payment\Gateway\Data\OrderAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderAdapterMock;

    protected function setUp()
    {
        $this->subjectReaderMock  = $this->createMock(\CyberSource\Core\Gateway\Helper\SubjectReader::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createPartialMock(\Magento\Sales\Model\Order\Payment::class, [
            'getCreditmemo',
            'getInvoice',
            'canRefund',
            'setShouldCloseParentTransaction',
            'setTransactionId',
        ]);
        $this->orderAdapterMock = $this->createMock(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class);

        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentDOMock->expects(static::any())->method('getOrder')->willReturn($this->orderAdapterMock);

        $this->handler = new RefundResponseHandler($this->subjectReaderMock);
    }

    /**
     * @param $shouldClose
     * @dataProvider dataProviderTestHandle
     */
    public function testHandle($shouldClose)
    {
        $subject = [
            'payment' => $this->paymentDOMock,
        ];

        $response = [
            'requestID' => '12312312313',
        ];

        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($this->paymentDOMock);

        $this->paymentMock->method('getCreditmemo')->willReturnSelf();
        $this->paymentMock->method('getInvoice')->willReturnSelf();
        $this->paymentMock->method('canRefund')->willReturn(!$shouldClose);

        $this->paymentMock->expects(static::atLeastOnce())->method('setShouldCloseParentTransaction')->with($shouldClose);
        $this->paymentMock->expects(static::atLeastOnce())->method('setTransactionId')->with($response['requestID']);

        $this->handler->handle($subject, $response);
    }

    public function dataProviderTestHandle()
    {
        return [
            [true],
            [false],
        ];
    }
}
