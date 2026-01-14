<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Test\Unit\Gateway\Request\Soap;

use CyberSource\Core\Gateway\Request\Soap\OrderMrnBuilder;
use PHPUnit\Framework\TestCase;

class OrderMrnBuilderTest extends TestCase
{
    /**
     * @var \CyberSource\Core\Gateway\Helper\SubjectReader|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subjectReaderMock;

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
    protected $orderAdapterMock;

    /**
     * @var OrderMrnBuilder
     */
    protected $builder;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->createMock(\CyberSource\Core\Gateway\Helper\SubjectReader::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Payment\Model\InfoInterface::class);
        $this->orderAdapterMock = $this->createMock(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class);

        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentDOMock->expects(static::any())->method('getOrder')->willReturn($this->orderAdapterMock);

        $this->builder = new OrderMrnBuilder(
            $this->subjectReaderMock
        );
    }

    public function testBuild()
    {
        $increment = '023232323';

        $subject = ['payment' => $this->paymentDOMock];
        $this->orderAdapterMock->method('getOrderIncrementId')->willReturn($increment);

        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($this->paymentDOMock);

        static::assertEquals(
            ['merchantReferenceCode' => $increment], $this->builder->build($subject)
        );
    }
}
