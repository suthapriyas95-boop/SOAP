<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\WeChatPay\Test\Unit\Controller\Payment;

use CyberSource\WeChatPay\Controller\Payment\CheckStatus;
use PHPUnit\Framework\TestCase;

class CheckStatusTest extends TestCase
{
    /**
     * @var \CyberSource\Core\Service\OrderToQuoteInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderToQuoteMock;

    /**
     * @var \Magento\Payment\Gateway\Command\CommandManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $commandManagerMock;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $validatorMock;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $jsonFactoryMock;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cartRepositoryMock;

    /**
     * @var \Magento\Checkout\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sessionMock;

    /**
     * @var \CyberSource\WeChatPay\Model\CurrentOrderResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $currentOrderResolverMock;

    /**
     * @var \CyberSource\Core\Model\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $loggerMock;

    /**
     * @var CheckStatus
     */
    protected $controller;

    /**
     * @var \Magento\Framework\Controller\Result\Json|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultJsonMock;

    /**
     * @var \Magento\Framework\App\Request\Http|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var \Magento\Sales\Model\Order|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderMock;

    /**
     * @var \Magento\Sales\Api\Data\OrderPaymentInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMock;

    /**
     * @var \Magento\Framework\App\Action\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    protected function setUp()
    {
        $this->contextMock = $this->createMock(\Magento\Framework\App\Action\Context::class);
        $this->commandManagerMock = $this->createMock(\Magento\Payment\Gateway\Command\CommandManagerInterface::class);
        $this->validatorMock = $this->createMock(\Magento\Framework\Data\Form\FormKey\Validator::class);
        $this->jsonFactoryMock = $this->createMock(\Magento\Framework\Controller\Result\JsonFactory::class);
        $this->orderToQuoteMock = $this->createMock(\CyberSource\Core\Service\OrderToQuoteInterface::class);
        $this->cartRepositoryMock = $this->createMock(\Magento\Quote\Api\CartRepositoryInterface::class);
        $this->sessionMock = $this->createMock(\Magento\Checkout\Model\Session::class);
        $this->currentOrderResolverMock = $this->createMock(\CyberSource\WeChatPay\Model\CurrentOrderResolver::class);
        $this->loggerMock = $this->createMock(\CyberSource\Core\Model\LoggerInterface::class);

        $this->resultJsonMock = $this->createMock(\Magento\Framework\Controller\Result\Json::class);
        $this->jsonFactoryMock->method('create')->willReturn($this->resultJsonMock);

        $this->requestMock = $this->createMock(\Magento\Framework\App\Request\Http::class);
        $this->contextMock->expects(static::any())->method('getRequest')->willReturn($this->requestMock);

        $this->orderMock = $this->createMock(\Magento\Sales\Model\Order::class);
        $this->paymentMock = $this->createMock(\Magento\Sales\Model\Order\Payment::class);
        $this->orderMock->method('getPayment')->willReturn($this->paymentMock);

        $this->controller = new CheckStatus(
            $this->contextMock,
            $this->commandManagerMock,
            $this->validatorMock,
            $this->jsonFactoryMock,
            $this->orderToQuoteMock,
            $this->cartRepositoryMock,
            $this->currentOrderResolverMock,
            $this->loggerMock,
            $this->sessionMock
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
        $this->orderMock->method('getStatus')->willReturn('payment_review');

        $this->commandManagerMock->expects(static::once())->method('executeByCode')->with('status', $this->paymentMock);

        $this->resultJsonMock->method('setData')->with([
            'success' => true,
            'state' => 'payment_review',
            'status' => 'payment_review',
            'is_settled' => false,
            'is_failed' => false,
        ])->willReturnSelf();

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
        $this->orderMock->method('getStatus')->willReturn('payment_review');

        $this->commandManagerMock->expects(static::never())->method('executeByCode')->with('status', $this->paymentMock);

        $this->resultJsonMock->method('setData')->with([
            'success' => false,
            'error_msg' => __('Invalid formkey.')
        ])->willReturnSelf();

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
        $this->orderMock->method('getStatus')->willReturn('payment_review');

        $this->commandManagerMock->expects(static::never())->method('executeByCode')->with('status', $this->paymentMock);

        $this->resultJsonMock->method('setData')->with([
            'success' => false,
            'error_msg' => __('Order does not exist.')
        ])->willReturnSelf();

        static::assertEquals($this->resultJsonMock, $this->controller->execute());
    }

    public function testExecuteWithLocalizedException()
    {
        $this->requestMock->method('isPost')->willReturn(false);
        $this->validatorMock->method('validate')->with($this->requestMock)->willReturn(true);

        $orderId = 222;

        $this->requestMock->method('getParam')->with('order_id')->willReturn($orderId);
        $this->currentOrderResolverMock->method('get')->with($orderId)->willReturn($this->orderMock);

        $this->orderMock->method('getId')->willReturn($orderId);

        $this->orderMock->method('getState')->willReturn('payment_review');
        $this->orderMock->method('getStatus')->willReturn('payment_review');

        $this->resultJsonMock->method('setData')->with([
            'success' => false,
            'error_msg' => __('Wrong method.')
        ])->willReturnSelf();

        static::assertEquals($this->resultJsonMock, $this->controller->execute());
    }

    public function testExecuteWithException()
    {
        $this->requestMock->method('isPost')->willReturn(true);
        $this->validatorMock->method('validate')->with($this->requestMock)->willReturn(true);

        $orderId = 222;

        $this->requestMock->method('getParam')->with('order_id')->willReturn($orderId);
        $this->currentOrderResolverMock->method('get')->with($orderId)->willReturn($this->orderMock);

        $exception = new \Exception('test');

        $this->commandManagerMock->expects(static::once())
            ->method('executeByCode')
            ->with('status', $this->paymentMock)
            ->willThrowException($exception);

        $this->orderMock->method('getId')->willReturn($orderId);

        $this->orderMock->method('getState')->willReturn('payment_review');
        $this->orderMock->method('getStatus')->willReturn('payment_review');

        $this->resultJsonMock->method('setData')->with([
            'success' => false,
            'error_msg' => __('Unable to complete status request.')
        ])->willReturnSelf();

        static::assertEquals($this->resultJsonMock, $this->controller->execute());
    }
}
