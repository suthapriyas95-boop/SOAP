<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\WeChatPay\Test\Unit\Gateway\Request;

use CyberSource\WeChatPay\Gateway\Request\SaleServiceBuilder;
use PHPUnit\Framework\TestCase;

class SaleServiceBuilderTest extends TestCase
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManagerMock;

    /**
     * @var SaleServiceBuilder
     */
    protected $builder;

    /**
     * @var \CyberSource\WeChatPay\Gateway\Config\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    protected function setUp()
    {
        $this->storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $this->configMock = $this->createMock(\CyberSource\WeChatPay\Gateway\Config\Config::class);

        $this->builder = new SaleServiceBuilder(
            $this->storeManagerMock,
            $this->configMock
        );
    }

    public function testBuild()
    {
        $result = [
            'successURL' => 'http://example.org',
            'transactionTimeout' => 3600,
        ];

        $this->configMock->method('getWeChatSuccessUrl')->willReturn($result['successURL']);
        $this->configMock->method('getQrExpirationTime')->willReturn($result['transactionTimeout']);

        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);

        $this->storeManagerMock->method('getStore')->willReturn($storeMock);

        static::assertEquals($result, $this->builder->build([]));
    }
}
