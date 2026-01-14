<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\WeChatPay\Test\Unit\Model;

use CyberSource\WeChatPay\Model\CurrentOrderResolver;
use PHPUnit\Framework\TestCase;

class CurrentOrderResolverTest extends TestCase
{
    /**
     * @var \Magento\Sales\Model\OrderRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderRepositoryMock;

    /**
     * @var \Magento\Customer\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerSessionMock;

    /**
     * @var \Magento\Checkout\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkoutSessionMock;

    /**
     * @var \Magento\Framework\Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registryMock;

    /**
     * @var CurrentOrderResolver
     */
    protected $resolver;

    /**
     * @var \Magento\Sales\Model\Order|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderMock;

    protected function setUp()
    {
        $this->orderRepositoryMock = $this->createMock(\Magento\Sales\Model\OrderRepository::class);
        $this->customerSessionMock = $this->createMock(\Magento\Customer\Model\Session::class);
        $this->checkoutSessionMock = $this->createMock(\Magento\Checkout\Model\Session::class);
        $this->registryMock = $this->createMock(\Magento\Framework\Registry::class);

        $this->orderMock = $this->createMock(
            \Magento\Sales\Model\Order::class
        );

        $this->resolver = new CurrentOrderResolver(
            $this->orderRepositoryMock,
            $this->customerSessionMock,
            $this->checkoutSessionMock,
            $this->registryMock
        );
    }

    public function testGetById()
    {
        $orderId = 123;

        $customerId = 23;

        $this->customerSessionMock->method('getCustomerId')->willReturn($customerId);
        $this->orderMock->method('getCustomerId')->willReturn($customerId);

        $this->orderRepositoryMock->method('get')->with($orderId)->willReturn($this->orderMock);

        static::assertEquals($this->orderMock, $this->resolver->get($orderId));
    }

    public function testGetByIdNotFound()
    {
        $orderId = 123;

        $customerId = 23;

        $this->customerSessionMock->method('getCustomerId')->willReturn($customerId);
        $this->orderMock->method('getCustomerId')->willReturn($customerId);

        $this->orderRepositoryMock->method('get')->with($orderId)->willThrowException(
            new \Magento\Framework\Exception\NoSuchEntityException(__('not found'))
        );

        static::assertEquals(false, $this->resolver->get($orderId));
    }

    public function testGetByIdNonOwner()
    {
        $orderId = 123;

        $customerId = 23;

        $this->customerSessionMock->method('getCustomerId')->willReturn(null);
        $this->orderMock->method('getCustomerId')->willReturn($customerId);

        $this->orderRepositoryMock->method('get')->with($orderId)->willReturn($this->orderMock);

        static::assertEquals(false, $this->resolver->get($orderId));
    }

    public function testGetFromRegistry()
    {
        $orderId = 3232;

        $customerId = 23;

        $this->customerSessionMock->method('getCustomerId')->willReturn($customerId);
        $this->orderMock->method('getCustomerId')->willReturn($customerId);

        $this->orderMock->method('getEntityId')->willReturn($orderId);

        $this->registryMock->method('registry')->with('current_order')->willReturn($this->orderMock);

        static::assertEquals($this->orderMock, $this->resolver->get(null));
    }

    public function testGetFromSession()
    {
        $orderId = 3232;

        $customerId = 23;

        $this->customerSessionMock->method('getCustomerId')->willReturn($customerId);
        $this->orderMock->method('getCustomerId')->willReturn($customerId);

        $this->orderMock->method('getEntityId')->willReturn($orderId);

        $this->checkoutSessionMock->method('getLastRealOrder')->willReturn($this->orderMock);

        static::assertEquals($this->orderMock, $this->resolver->get(null));
    }

    public function testGetFromSessionNoId()
    {
        $orderId = 3232;

        $customerId = 23;

        $this->customerSessionMock->method('getCustomerId')->willReturn($customerId);
        $this->orderMock->method('getCustomerId')->willReturn($customerId);

        $this->orderMock->method('getEntityId')->willReturn(null);

        $this->checkoutSessionMock->method('getLastRealOrder')->willReturn($this->orderMock);

        static::assertEquals(false, $this->resolver->get(null));
    }
}
