<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\WeChatPay\Test\Unit\Plugin;

use CyberSource\WeChatPay\Plugin\SalesOrderInvoicePlugin;
use PHPUnit\Framework\TestCase;

class SalesOrderInvoicePluginTest extends TestCase
{
    /**
     * @var \Magento\Sales\Model\Order\Invoice|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $invoiceMock;

    /**
     * @var \Magento\Sales\Model\Order|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderMock;

    /**
     * @var \Magento\Sales\Api\Data\OrderPaymentInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMock;

    /**
     * @var SalesOrderInvoicePlugin
     */
    protected $plugin;

    protected function setUp()
    {
        $this->invoiceMock = $this->createMock(\Magento\Sales\Model\Order\Invoice::class);
        $this->orderMock = $this->createMock(\Magento\Sales\Model\Order::class);
        $this->paymentMock = $this->createMock(\Magento\Sales\Api\Data\OrderPaymentInterface::class);

        $this->plugin = new \CyberSource\WeChatPay\Plugin\SalesOrderInvoicePlugin();
    }

    public function testAfterCanCapture()
    {
        $code = 'cybersourcewechatpay';

        $this->paymentMock->method('getMethod')->willReturn($code);

        $result = true;

        $this->invoiceMock->method('getOrder')->willReturn($this->orderMock);
        $this->orderMock->method('getPayment')->willReturn($this->paymentMock);

        static::assertEquals(false, $this->plugin->afterCanCapture(
            $this->invoiceMock,
            $result
        ));
    }

    /**
     * @param $resultValue
     * @dataProvider dataProviderTestAfterCanCaptureOtherMethod
     */
    public function testAfterCanCaptureOtherMethod($resultValue)
    {
        $code = 'somemethodcode';

        $this->paymentMock->method('getMethod')->willReturn($code);

        $result = $resultValue;

        $this->invoiceMock->method('getOrder')->willReturn($this->orderMock);
        $this->orderMock->method('getPayment')->willReturn($this->paymentMock);

        static::assertEquals($resultValue, $this->plugin->afterCanCapture(
            $this->invoiceMock,
            $result
        ));
    }

    public function dataProviderTestAfterCanCaptureOtherMethod()
    {
        return [
            [true],
            [false],
        ];
    }

}
