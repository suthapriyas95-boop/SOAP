<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\WeChatPay\Test\Unit\Controller\Payment;

use CyberSource\WeChatPay\Controller\Payment\GetQrData;
use PHPUnit\Framework\TestCase;

class GetQrDataTest extends TestCase
{
    /**
     * @var \Magento\Framework\App\Action\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \CyberSource\WeChatPay\Gateway\Config\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    /**
     * @var \Magento\Framework\View\Result\LayoutFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $layoutFactoryMock;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $validatorMock;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $jsonFactoryMock;

    /**
     * @var \CyberSource\WeChatPay\Model\CurrentOrderResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $currentOrderResolverMock;

    /**
     * @var \CyberSource\Core\Model\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $loggerInterfaceMock;

    /**
     * @var GetQrData
     */
    protected $controller;

    /**
     * @var \Magento\Framework\App\Request\Http|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var \Magento\Framework\Controller\ResultFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultFactoryMock;

    /**
     * @var \Magento\Framework\Controller\Result\Json|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultJsonMock;

    /**
     * @var \Magento\Sales\Model\Order|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderMock;

    /**
     * @var \Magento\Sales\Api\Data\OrderPaymentInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMock;

    protected function setUp()
    {
        $this->contextMock = $this->createMock(\Magento\Framework\App\Action\Context::class);
        $this->configMock = $this->createMock(\CyberSource\WeChatPay\Gateway\Config\Config::class);
        $this->layoutFactoryMock = $this->createMock(\Magento\Framework\View\Result\LayoutFactory::class);
        $this->validatorMock = $this->createMock(\Magento\Framework\Data\Form\FormKey\Validator::class);
        $this->jsonFactoryMock = $this->createMock(\Magento\Framework\Controller\Result\JsonFactory::class);
        $this->currentOrderResolverMock = $this->createMock(\CyberSource\WeChatPay\Model\CurrentOrderResolver::class);
        $this->loggerInterfaceMock = $this->createMock(\CyberSource\Core\Model\LoggerInterface::class);

        $this->requestMock = $this->createMock(\Magento\Framework\App\Request\Http::class);
        $this->resultFactoryMock = $this->createMock(\Magento\Framework\Controller\ResultFactory::class);

        $this->resultJsonMock = $this->createMock(\Magento\Framework\Controller\Result\Json::class);
        $this->jsonFactoryMock->method('create')->willReturn($this->resultJsonMock);

        $this->orderMock = $this->createMock(\Magento\Sales\Model\Order::class);

        $this->paymentMock = $this->createMock(\Magento\Sales\Api\Data\OrderPaymentInterface::class);
        $this->orderMock->method('getPayment')->willReturn($this->paymentMock);

        $this->contextMock
            ->expects(static::any())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->contextMock
            ->expects(static::any())
            ->method('getResultFactory')
            ->willReturn($this->resultFactoryMock);

        $this->controller = new GetQrData(
            $this->contextMock,
            $this->configMock,
            $this->layoutFactoryMock,
            $this->validatorMock,
            $this->jsonFactoryMock,
            $this->currentOrderResolverMock,
            $this->loggerInterfaceMock
        );
    }

    public function testExecute()
    {
        $this->requestMock->method('isPost')->willReturn(true);

        $this->validatorMock->method('validate')->with($this->requestMock)->willReturn(true);

        $orderId = 222;

        $this->requestMock->method('getParam')->with('order_id')->willReturn($orderId);
        $this->currentOrderResolverMock->method('get')->with($orderId)->willReturn($this->orderMock);

        $this->orderMock->method('getId')->willReturn($orderId);

        $this->orderMock->method('getState')->willReturn('payment_review');

        $this->paymentMock->method('getAdditionalInformation')->willReturn([
            'qrCodeUrl' => 'http://example.org/',
        ]);

        $this->configMock->method('getMaxStatusRequests')->willReturn(5);

        $this->resultJsonMock
            ->method('setData')
            ->with([
                'success' => true,
                'qr_url' => 'http://example.org/',
                'qr_notice' => __('Please scan the QR code using the WeChat Mobile App and follow the instructions in the App. After successfully completing the payment on the WeChat App click \'Confirm\' to proceed. Click \'Cancel\' to go back and select an alternate payment method or to edit your shopping cart.')
            ])
            ->willReturnSelf();

        static::assertEquals($this->resultJsonMock, $this->controller->execute());
    }

    public function testExecuteNonPost()
    {
        $this->requestMock->method('isPost')->willReturn(false);

        $this->validatorMock->method('validate')->with($this->requestMock)->willReturn(true);

        $orderId = 222;

        $this->requestMock->method('getParam')->with('order_id')->willReturn($orderId);
        $this->currentOrderResolverMock->method('get')->with($orderId)->willReturn($this->orderMock);

        $this->orderMock->method('getId')->willReturn($orderId);

        $this->orderMock->method('getState')->willReturn('payment_review');

        $this->paymentMock->method('getAdditionalInformation')->willReturn([
            'qrCodeUrl' => 'http://example.org/',
        ]);

        $this->configMock->method('getMaxStatusRequests')->willReturn(5);

        $this->resultJsonMock
            ->method('setData')
            ->with([
                'success' => false,
                'error_msg' => __('Unable to retrieve QR code url.'),
            ])
            ->willReturnSelf();

        static::assertEquals($this->resultJsonMock, $this->controller->execute());
    }

    public function testExecuteInvalidFormkey()
    {
        $this->requestMock->method('isPost')->willReturn(true);

        $this->validatorMock->method('validate')->with($this->requestMock)->willReturn(false);

        $orderId = 222;

        $this->requestMock->method('getParam')->with('order_id')->willReturn($orderId);
        $this->currentOrderResolverMock->method('get')->with($orderId)->willReturn($this->orderMock);

        $this->orderMock->method('getId')->willReturn($orderId);

        $this->orderMock->method('getState')->willReturn('payment_review');

        $this->paymentMock->method('getAdditionalInformation')->willReturn([
            'qrCodeUrl' => 'http://example.org/',
        ]);

        $this->configMock->method('getMaxStatusRequests')->willReturn(5);

        $this->resultJsonMock
            ->method('setData')
            ->with([
                'success' => false,
                'error_msg' => __('Unable to retrieve QR code url.'),
            ])
            ->willReturnSelf();

        static::assertEquals($this->resultJsonMock, $this->controller->execute());
    }

    public function testExecuteInvalidOrder()
    {
        $this->requestMock->method('isPost')->willReturn(true);

        $this->validatorMock->method('validate')->with($this->requestMock)->willReturn(true);

        $orderId = 222;

        $this->requestMock->method('getParam')->with('order_id')->willReturn($orderId);
        $this->currentOrderResolverMock->method('get')->with($orderId)->willReturn(null);

        $this->orderMock->method('getId')->willReturn($orderId);

        $this->orderMock->method('getState')->willReturn('payment_review');

        $this->paymentMock->method('getAdditionalInformation')->willReturn([
            'qrCodeUrl' => 'http://example.org/',
        ]);

        $this->configMock->method('getMaxStatusRequests')->willReturn(5);

        $this->resultJsonMock
            ->method('setData')
            ->with([
                'success' => false,
                'error_msg' => __('Unable to retrieve QR code url.'),
            ])
            ->willReturnSelf();

        static::assertEquals($this->resultJsonMock, $this->controller->execute());
    }
}
