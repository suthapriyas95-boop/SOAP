<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\WeChatPay\Test\Unit\Gateway\Response;

use CyberSource\WeChatPay\Gateway\Response\TransactionDetailsHandler;
use PHPUnit\Framework\TestCase;

class TransactionDetailsHandlerTest extends TestCase
{
    /**
     * @var \CyberSource\Core\Gateway\Helper\SubjectReader|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subjectReaderMock;

    /**
     * @var string
     */
    protected $replySectionName;

    /**
     * @var TransactionDetailsHandler
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
        $this->replySectionName = 'someReply';

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Sales\Model\Order\Payment::class);
        $this->orderAdapterMock = $this->createMock(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class);

        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentDOMock->expects(static::any())->method('getOrder')->willReturn($this->orderAdapterMock);

        $this->handler = new TransactionDetailsHandler($this->subjectReaderMock, $this->replySectionName);
    }

    public function testHandle()
    {
        $subject = ['payment' => $this->paymentDOMock];
        $response = [
            'requestID' => '12312312312',
            'someReply' => (object)[
                'reconciliationID' => '3232323',
                'merchantURL' => 'http://example.org',
            ]
        ];

        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($this->paymentDOMock);

        $this->paymentMock->expects(static::once())->method('setTransactionId')->with($response['requestID']);

        $this->paymentMock->expects(static::exactly(2))->method('setAdditionalInformation')->withConsecutive(
            ['reconciliationID', $response['someReply']->reconciliationID],
            ['qrCodeUrl', $response['someReply']->merchantURL]
        );

        $this->handler->handle($subject, $response);
    }
}
