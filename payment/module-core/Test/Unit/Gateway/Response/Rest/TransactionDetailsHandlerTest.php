<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Gateway\Response\Rest;

use PHPUnit\Framework\TestCase;

class TransactionDetailsHandlerTest extends TestCase
{

    /**
     * @var \Magento\Payment\Gateway\Data\OrderAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderAdapterMock;

    /**
     * @var \Magento\Sales\Model\Order\Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDOMock;

    /** @var TransactionDetailsHandler */
    private $transactionDetailsHandler;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReaderMock;


    protected function setUp()
    {
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Sales\Model\Order\Payment::class);
        $this->orderAdapterMock = $this->createMock(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class);

        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentDOMock->expects(static::any())->method('getOrder')->willReturn($this->orderAdapterMock);
    }

    /**
     * @param $shouldCloseTransaction
     * @param $shouldCloseParentTransaction
     * @dataProvider dataProviderTestHandle
     */
    public function testHandle($shouldCloseTransaction, $shouldCloseParentTransaction)
    {
        $subject = ['payment' => $this->paymentDOMock];
        $response = ['id' => '123123123'];

        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($subject['payment']);

        $this->transactionDetailsHandler = new TransactionDetailsHandler(
            $this->subjectReaderMock,
            $shouldCloseTransaction,
            $shouldCloseParentTransaction
        );

        $this->paymentMock->expects(static::once())->method('setTransactionId')->with($response['id']);
        $this->paymentMock->expects(static::once())->method('setCcTransId')->with($response['id']);

        $this->paymentMock
            ->expects($shouldCloseParentTransaction !== null ? static::once() : static::never())
            ->method('setShouldCloseParentTransaction')
            ->with($shouldCloseParentTransaction)
        ;

        $this->paymentMock
            ->expects($shouldCloseTransaction !== null ? static::once() : static::never())
            ->method('setIsTransactionClosed')
            ->with($shouldCloseParentTransaction)
        ;

        $this->transactionDetailsHandler->handle($subject, $response);
    }

    public function dataProviderTestHandle()
    {
        return [
            [
                'shouldCloseTransaction' => null,
                'shouldCloseParentTransaction' => null,
            ],
            [
                'shouldCloseTransaction' => true,
                'shouldCloseParentTransaction' => true,
            ],
            [
                'shouldCloseTransaction' => false,
                'shouldCloseParentTransaction' => false,
            ],
        ];
    }
}
