<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Gateway\Request\Flex;

use PHPUnit\Framework\TestCase;

class BuilderStrategyTest extends TestCase
{

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Config\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    /**
     * @var \Magento\Payment\Gateway\Request\BuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $microformBuilderMock;

    /**
     * @var \Magento\Payment\Gateway\Request\BuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $standardBuilderMock;

    /**
     * @var BuilderStrategy
     */
    protected $builderStrategy;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentDOMock;

    /**
     * @var \Magento\Sales\Model\Order\Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMock;

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $subjectReaderMock;

    protected function setUp()
    {

        $this->configMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Config\Config::class);
        $this->microformBuilderMock = $this->createMock(\Magento\Payment\Gateway\Request\BuilderInterface::class);
        $this->standardBuilderMock = $this->createMock(\Magento\Payment\Gateway\Request\BuilderInterface::class);
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Sales\Model\Order\Payment::class);
        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);

        $this->builderStrategy = new BuilderStrategy(
            $this->configMock,
            $this->subjectReaderMock,
            $this->microformBuilderMock,
            $this->standardBuilderMock
        );

    }

    public function testBuildMicroform()
    {
        $subject = ['payment' => $this->paymentMock];

        $this->configMock->method('isMicroform')->willReturn(true);
        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($this->paymentDOMock);

        $buildResult = ['some' => 'result'];

        $this->standardBuilderMock->expects(static::never())->method('build');
        $this->microformBuilderMock->expects(static::once())->method('build')->with($subject)->willReturn($buildResult);

        static::assertEquals($buildResult, $this->builderStrategy->build($subject));
    }

    public function testBuildNotMicroform()
    {
        $subject = ['payment' => $this->paymentMock];

        $this->configMock->method('isMicroform')->willReturn(false);
        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($this->paymentDOMock);

        $buildResult = ['some' => 'result'];

        $this->microformBuilderMock->expects(static::never())->method('build');
        $this->standardBuilderMock->expects(static::once())->method('build')->with($subject)->willReturn($buildResult);

        static::assertEquals($buildResult, $this->builderStrategy->build($subject));
    }

    public function testBuildVault()
    {
        $subject = ['payment' => $this->paymentMock];

        $this->configMock->method('isMicroform')->willReturn(false);
        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($this->paymentDOMock);
        $this->paymentMock->method('getMethod')->willReturn('chcybersource_cc_vault');

        $buildResult = ['some' => 'result'];

        $this->microformBuilderMock->expects(static::never())->method('build');
        $this->standardBuilderMock->expects(static::once())->method('build')->with($subject)->willReturn($buildResult);

        static::assertEquals($buildResult, $this->builderStrategy->build($subject));
    }
}
