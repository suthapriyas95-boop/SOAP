<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\GooglePay\Controller\Index;

use CyberSource\ApplePay\Gateway\Config\Config;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Quote\Api\ShippingMethodManagementInterface;
use Magento\Store\Model\Information;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{

    /**
     * @var \Magento\Framework\App\Action\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \CyberSource\ApplePay\Gateway\Config\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    /**
     * @var \Magento\Checkout\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sessionMock;

    /**
     * @var \Magento\Store\Model\Information|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeInfoMock;

    /**
     * @var \Magento\Quote\Api\ShippingMethodManagementInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingMethodManagementMock;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $pricingHelperMock;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultJsonFactoryMock;

    /**
     * @var Request
     */
    protected $controller;

    /**
     * @var \Magento\Framework\Controller\Result\Json|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultJsonMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteMock;

    /**
     * @var \Magento\Quote\Model\Cart\Currency|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $currencyMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $addressMock;

    protected function setUp()
    {

        $this->contextMock = $this->createMock(\Magento\Framework\App\Action\Context::class);
        $this->configMock = $this->createMock(\CyberSource\ApplePay\Gateway\Config\Config::class);
        $this->sessionMock = $this->createMock(\Magento\Checkout\Model\Session::class);
        $this->storeInfoMock = $this->createMock(\Magento\Store\Model\Information::class);
        $this->shippingMethodManagementMock = $this->createMock(\Magento\Quote\Api\ShippingMethodManagementInterface::class);
        $this->pricingHelperMock = $this->createMock(\Magento\Framework\Pricing\Helper\Data::class);
        $this->resultJsonFactoryMock = $this->createMock(\Magento\Framework\Controller\Result\JsonFactory::class);

        $this->resultJsonMock = $this->createMock(\Magento\Framework\Controller\Result\Json::class);
        $this->resultJsonFactoryMock->method('create')->willReturn($this->resultJsonMock);

        $this->addressMock = $this->createMock(\Magento\Quote\Model\Quote\Address::class);

        $this->quoteMock = $this->createPartialMock(
            \Magento\Quote\Model\Quote::class,
            [
                'getPayment',
                'reserveOrderId',
                'collectTotals',
                'isVirtual',
                'getCustomerId',
                'setCustomerEmail',
                'setCustomerIsGuest',
                'setCustomerFirstname',
                'setCustomerLastname',
                'getBillingAddress',
                'getShippingAddress',
                'setBillingAddress',
                'setShippingAddress',
                'getCurrency',
                'getBaseGrandTotal',
                'getId',
            ]
        );
        $this->sessionMock->method('getQuote')->willReturn($this->quoteMock);

        $this->currencyMock = $this->createMock(\Magento\Quote\Model\Cart\Currency::class);
        $this->quoteMock->method('getCurrency')->willReturn($this->currencyMock);

        $this->pricingHelperMock->method('currency')->willReturnArgument(0);

        $this->controller = new Request(
            $this->contextMock,
            $this->configMock,
            $this->sessionMock,
            $this->storeInfoMock,
            $this->shippingMethodManagementMock,
            $this->pricingHelperMock,
            $this->resultJsonFactoryMock
        );
    }

    public function testExecute()
    {

        $expected = [
            'success' => true,
            'request' => [
                'total' => [
                    'currencyCode' => 'USD',
                    'totalPrice' => '10.99',
                    'totalPriceStatus' => 'FINAL',
                ],
                'rates' => [
                    [
                        'id' => 'test_test',
                        'label' => '10.00: Test',
                        'description' => 'Test Test',
                    ],
                ],
                'defaultSelectedOptionId' => 'test_test'
            ],
        ];

        $quoteId = 1;
        $this->currencyMock->method('getBaseCurrencyCode')->willReturn($expected['request']['total']['currencyCode']);
        $this->quoteMock->method('getBaseGrandTotal')->willReturn($expected['request']['total']['totalPrice']);
        $this->quoteMock->method('isVirtual')->willReturn(false);
        $this->quoteMock->method('getShippingAddress')->willReturn($this->addressMock);
        $this->quoteMock->method('getId')->willReturn($quoteId);
        $this->addressMock->method('getCountryId')->willReturn('US');
        $this->addressMock->method('getShippingMethod')->willReturn('test_test');

        $rateMock = $this->createMock(\Magento\Quote\Api\Data\ShippingMethodInterface::class);
        $rateMock->method('getMethodCode')->willReturn('test');
        $rateMock->method('getCarrierCode')->willReturn('test');
        $rateMock->method('getBaseAmount')->willReturn('10.00');
        $rateMock->method('getMethodTitle')->willReturn('Test');
        $rateMock->method('getCarrierTitle')->willReturn('Test');

        $this->shippingMethodManagementMock->method('getList')->with($quoteId)->willReturn([$rateMock]);

        $this->resultJsonMock->expects(static::once())->method('setData')->with($expected)->willReturnSelf();

        static::assertEquals($this->resultJsonMock, $this->controller->execute());
    }

    public function testExecuteWithLocalizedException()
    {

        $expected = [
            'success' => false,
            'message' => 'Something wrong',
        ];

        $quoteId = 1;
        $this->currencyMock->method('getBaseCurrencyCode')->willReturn('USD');
        $this->quoteMock->method('getBaseGrandTotal')->willReturn('10.00');
        $this->quoteMock->method('isVirtual')->willReturn(false);
        $this->quoteMock->method('getShippingAddress')->willReturn($this->addressMock);
        $this->quoteMock->method('getId')->willReturn($quoteId);
        $this->addressMock->method('getCountryId')->willReturn('US');
        $this->addressMock->method('getShippingMethod')->willReturn('test_test');

        $rateMock = $this->createMock(\Magento\Quote\Api\Data\ShippingMethodInterface::class);
        $rateMock->method('getMethodCode')->willReturn('test');
        $rateMock->method('getCarrierCode')->willReturn('test');
        $rateMock->method('getBaseAmount')->willReturn('10.00');
        $rateMock->method('getMethodTitle')->willReturn('Test');
        $rateMock->method('getCarrierTitle')->willReturn('Test');

        $exception = new \Magento\Framework\Exception\LocalizedException(__($expected['message']));

        $this->shippingMethodManagementMock->method('getList')->with($quoteId)->willThrowException($exception);

        $this->resultJsonMock->expects(static::once())->method('setData')->with($expected)->willReturnSelf();

        static::assertEquals($this->resultJsonMock, $this->controller->execute());
    }

    public function testExecuteWithException()
    {

        $expected = [
            'success' => false,
            'message' => 'Unable to get cart details.',
        ];

        $quoteId = 1;
        $this->currencyMock->method('getBaseCurrencyCode')->willReturn('USD');
        $this->quoteMock->method('getBaseGrandTotal')->willReturn('10.00');
        $this->quoteMock->method('isVirtual')->willReturn(false);
        $this->quoteMock->method('getShippingAddress')->willReturn($this->addressMock);
        $this->quoteMock->method('getId')->willReturn($quoteId);
        $this->addressMock->method('getCountryId')->willReturn('US');
        $this->addressMock->method('getShippingMethod')->willReturn('test_test');

        $rateMock = $this->createMock(\Magento\Quote\Api\Data\ShippingMethodInterface::class);
        $rateMock->method('getMethodCode')->willReturn('test');
        $rateMock->method('getCarrierCode')->willReturn('test');
        $rateMock->method('getBaseAmount')->willReturn('10.00');
        $rateMock->method('getMethodTitle')->willReturn('Test');
        $rateMock->method('getCarrierTitle')->willReturn('Test');

        $exception = new \Exception('unknown');

        $this->shippingMethodManagementMock->method('getList')->with($quoteId)->willThrowException($exception);

        $this->resultJsonMock->expects(static::once())->method('setData')->with($expected)->willReturnSelf();

        static::assertEquals($this->resultJsonMock, $this->controller->execute());
    }




}
