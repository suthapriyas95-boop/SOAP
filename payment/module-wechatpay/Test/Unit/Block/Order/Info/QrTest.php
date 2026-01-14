<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\WeChatPay\Test\Unit\Block\Order\Info;

use CyberSource\WeChatPay\Block\Order\Info\Qr;
use PHPUnit\Framework\TestCase;

class QrTest extends TestCase
{
    /**
     * @var \Magento\Framework\View\Element\Template\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \Magento\Checkout\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sessionMock;

    /**
     * @var \Magento\Framework\Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registryMock;

    /**
     * @var Qr
     */
    protected $block;

    /**
     * @var \Magento\Sales\Model\Order|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderMock;

    /**
     * @var \Magento\Sales\Api\Data\OrderPaymentInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMock;

    /**
     * @var \CyberSource\WeChatPay\Model\CurrentOrderResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resolverMock;

    /**
     * @var \CyberSource\WeChatPay\Gateway\Config\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    protected function setUp()
    {
        $this->contextMock = $this->createMock(\Magento\Framework\View\Element\Template\Context::class);
        $this->resolverMock = $this->createMock(\CyberSource\WeChatPay\Model\CurrentOrderResolver::class);
        $this->configMock = $this->createMock(\CyberSource\WeChatPay\Gateway\Config\Config::class);

        $this->orderMock = $this->createMock(\Magento\Sales\Model\Order::class);
        $this->paymentMock = $this->createMock(\Magento\Sales\Api\Data\OrderPaymentInterface::class);
        $this->orderMock->method('getPayment')->willReturn($this->paymentMock);

        $this->orderMock->method('getPayment')->willReturn($this->paymentMock);

        $this->resolverMock->method('get')->willReturn($this->orderMock);

        $this->block = new \CyberSource\WeChatPay\Block\Order\Info\Qr(
            $this->contextMock,
            $this->configMock,
            $this->resolverMock,
            []
        );
    }

    /**
     * @param $state
     * @param $methodCode
     * @param $isApplicable
     * @dataProvider dataProviderTestIsApplicable
     */
    public function testIsApplicable($state, $methodCode, $isApplicable)
    {
        $this->orderMock->method('getState')->willReturn($state);
        $this->paymentMock->method('getMethod')->willReturn($methodCode);

        static::assertEquals($isApplicable, $this->block->isApplicable());
    }

    public function dataProviderTestIsApplicable()
    {
        return [
            ['state' => 'complete', 'methodCode' => 'checkmo', 'isApplicable' => false],
            ['state' => 'payment_review', 'methodCode' => 'checkmo', 'isApplicable' => false],
            ['state' => 'payment_review', 'methodCode' => 'cybersourcewechatpay', 'isApplicable' => true],
        ];
    }

    public function testGetLabel()
    {
        static::assertNotEmpty($this->block->getLabel());
    }
}
