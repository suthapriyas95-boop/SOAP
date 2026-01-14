<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Gateway\Request\Rest;

use CyberSource\Core\Gateway\Request\Rest\OperationBuilder;
use PHPUnit\Framework\TestCase;

class OperationBuilderTest extends TestCase
{

    /**
     * @var \CyberSource\Core\Gateway\Helper\SubjectReader|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subjectReaderMock;

    /**
     * @var \CyberSource\Core\Gateway\Request\Rest\OperationBuilder
     */
    protected $builder;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentDOMock;

    /**
     * @var \Magento\Payment\Model\InfoInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMock;

    /**
     * @var \Magento\Payment\Gateway\Data\OrderAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderMock;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->createMock(\CyberSource\Core\Gateway\Helper\SubjectReader::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createPartialMock(
            \Magento\Payment\Model\InfoInterface::class,
            [
                'getOrder',
                'encrypt',
                'decrypt',
                'setAdditionalInformation',
                'hasAdditionalInformation',
                'unsAdditionalInformation',
                'getAdditionalInformation',
                'getMethodInstance',
                'getParentTransactionId',
                'getRefundTransactionId',
            ]
        );
        $this->orderMock = $this->createMock(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class);

        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentDOMock->expects(static::any())->method('getOrder')->willReturn($this->orderMock);

    }

    public function testBuild()
    {
        $operation = 'someoperation';

        $subject = ['payment' => $this->paymentDOMock];
        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($subject['payment']);
        $transactionId = '1231231231232';

        $this->paymentMock->method('getParentTransactionId')->willReturn($transactionId);

        $this->builder = new OperationBuilder(
            $this->subjectReaderMock,
            $operation
        );

        static::assertEquals(
            [
                'url_params' => [
                    $transactionId,
                    $operation
                ]
            ],
            $this->builder->build($subject)
        );
    }
}
