<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\GooglePay\Controller\Index;

use PHPUnit\Framework\TestCase;

class PlaceTest extends TestCase
{

    /**
     * @var \Magento\Framework\App\Action\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \Magento\Checkout\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sessionMock;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cartRepositoryInterfaceMock;

    /**
     * @var \Magento\Quote\Api\CartManagementInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cartManagementInterfaceMock;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $jsonFactoryMock;

    /**
     * @var \CyberSource\GooglePay\Model\AddressConverter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $addressConverterMock;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $formKeyValidatorMock;

    /**
     * @var \Magento\Framework\Controller\Result\Json|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultJsonMock;

    /**
     * @var Place
     */
    protected $controller;
    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;
    /**
     * @var \CyberSource\Core\Model\Checkout\PaymentFailureRouteProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentFailureRouteProvider;
    /**
     * @var \Magento\Framework\UrlInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlMock;
    /**
     * @var \Magento\Quote\Model\Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteMock;
    /**
     * @var \Magento\Quote\Api\Data\AddressInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $addressMock;
    /**
     * @var \Magento\Quote\Model\Quote\Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMock;
    /**
     * @var \Magento\Sales\Model\Order|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderMock;


    protected function setUp()
    {

        $this->contextMock = $this->createMock(\Magento\Framework\App\Action\Context::class);
        $this->sessionMock = $this->createMock(\Magento\Checkout\Model\Session::class);
        $this->cartRepositoryInterfaceMock = $this->createMock(\Magento\Quote\Api\CartRepositoryInterface::class);
        $this->cartManagementInterfaceMock = $this->createMock(\Magento\Quote\Model\QuoteManagement::class);
        $this->jsonFactoryMock = $this->createMock(\Magento\Framework\Controller\Result\JsonFactory::class);
        $this->addressConverterMock = $this->createMock(\CyberSource\GooglePay\Model\AddressConverter::class);
        $this->formKeyValidatorMock = $this->createMock(\Magento\Framework\Data\Form\FormKey\Validator::class);
        $this->paymentFailureRouteProvider = $this->createMock(\CyberSource\Core\Model\Checkout\PaymentFailureRouteProviderInterface::class);

        $this->paymentFailureRouteProvider->method('getFailureRoutePath')->willReturn('checkout/cart');

        $this->resultJsonMock = $this->createMock(\Magento\Framework\Controller\Result\Json::class);
        $this->jsonFactoryMock->method('create')->willReturn($this->resultJsonMock);

        $this->requestMock = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $this->contextMock->method('getRequest')->willReturn($this->requestMock);

        $this->urlMock = $this->createMock(\Magento\Framework\UrlInterface::class);
        $this->urlMock->method('getUrl')->willReturnArgument(0);
        $this->contextMock->method('getUrl')->willReturn($this->urlMock);

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
                'getId',
                'getIsActive',
            ]
        );
        $this->sessionMock->method('getQuote')->willReturn($this->quoteMock);

        $this->addressMock = $this->createPartialMock(
            \Magento\Quote\Model\Quote\Address::class,
            [
                'setShouldIgnoreValidation',
            ]
        );

        $this->paymentMock = $this->createMock(\Magento\Quote\Model\Quote\Payment::class);
        $this->quoteMock->method('getPayment')->willReturn($this->paymentMock);

        $this->orderMock = $this->createMock(\Magento\Sales\Model\Order::class);

        $this->controller = new Place(
            $this->contextMock,
            $this->sessionMock,
            $this->cartRepositoryInterfaceMock,
            $this->cartManagementInterfaceMock,
            $this->jsonFactoryMock,
            $this->addressConverterMock,
            $this->formKeyValidatorMock,
            $this->paymentFailureRouteProvider
        );
    }

    public function testExecute()
    {

        $this->formKeyValidatorMock->method('validate')->with($this->requestMock)->willReturn(true);

        $this->quoteMock->method('isVirtual')->willReturn(false);
        $this->quoteMock->method('getBillingAddress')->willReturn($this->addressMock);
        $this->quoteMock->method('getShippingAddress')->willReturn($this->addressMock);
        $this->quoteMock->method('getCustomerId')->willReturn(1);
        $this->quoteMock->method('getId')->willReturn(1);
        $this->quoteMock->method('getIsActive')->willReturn(true);

        $addressData = ['some' => 'data'];
        $email = 'test@example.org';
        $token = 'sometoken';

        $this->requestMock->method('getParam')->willReturnMap([
            ['billingAddress', null, $addressData],
            ['shippingAddress', null, $addressData],
            ['token', null, $token],
            ['email', null, $email],
        ]);

        $this->paymentMock->method('importData')->with(
            [
                'method' => 'cybersource_googlepay',
                'paymentToken' => $token,
            ]
        );

        $this->addressConverterMock->method('convertGoogleAddress')->with($addressData)->willReturn($this->addressMock);
        $this->quoteMock->expects(static::once())->method('setBillingAddress')->with($this->addressMock);
        $this->quoteMock->expects(static::once())->method('setShippingAddress')->with($this->addressMock);

        $this->quoteMock->expects(static::once())->method('reserveOrderId');
        $this->quoteMock->expects(static::once())->method('collectTotals');
        $this->cartRepositoryInterfaceMock->expects(static::once())->method('save')->with($this->quoteMock);

        $this->cartManagementInterfaceMock->method('submit')->with($this->quoteMock)->willReturn($this->orderMock);

        $this->resultJsonMock->method('setData')->with([
            'status' => 200,
            'message' => __('Your order has been successfully created!'),
            'redirect_url' => 'checkout/onepage/success',
        ])->willReturnSelf();

        static::assertEquals($this->resultJsonMock, $this->controller->execute());
    }

    public function testExecuteGuest()
    {

        $this->formKeyValidatorMock->method('validate')->with($this->requestMock)->willReturn(true);

        $this->quoteMock->method('isVirtual')->willReturn(false);
        $this->quoteMock->method('getBillingAddress')->willReturn($this->addressMock);
        $this->quoteMock->method('getShippingAddress')->willReturn($this->addressMock);
        $this->quoteMock->method('getCustomerId')->willReturn(null);
        $this->quoteMock->method('getId')->willReturn(1);
        $this->quoteMock->method('getIsActive')->willReturn(true);

        $this->quoteMock->expects(static::once())->method('setCustomerEmail')->willReturnSelf();
        $this->quoteMock->expects(static::once())->method('setCustomerIsGuest')->willReturnSelf();
        $this->quoteMock->expects(static::once())->method('setCustomerFirstname')->willReturnSelf();
        $this->quoteMock->expects(static::once())->method('setCustomerLastname')->willReturnSelf();

        $addressData = ['some' => 'data'];
        $email = 'test@example.org';
        $token = 'sometoken';

        $this->requestMock->method('getParam')->willReturnMap([
            ['billingAddress', null, $addressData],
            ['shippingAddress', null, $addressData],
            ['token', null, $token],
            ['email', null, $email],
        ]);

        $this->paymentMock->method('importData')->with(
            [
                'method' => 'cybersource_googlepay',
                'paymentToken' => $token,
            ]
        );

        $this->addressConverterMock->method('convertGoogleAddress')->with($addressData)->willReturn($this->addressMock);
        $this->quoteMock->expects(static::once())->method('setBillingAddress')->with($this->addressMock);
        $this->quoteMock->expects(static::once())->method('setShippingAddress')->with($this->addressMock);

        $this->quoteMock->expects(static::once())->method('reserveOrderId');
        $this->quoteMock->expects(static::once())->method('collectTotals');
        $this->cartRepositoryInterfaceMock->expects(static::once())->method('save')->with($this->quoteMock);

        $this->cartManagementInterfaceMock->method('submit')->with($this->quoteMock)->willReturn($this->orderMock);

        $this->resultJsonMock->method('setData')->with([
            'status' => 200,
            'message' => __('Your order has been successfully created!'),
            'redirect_url' => 'checkout/onepage/success',
        ])->willReturnSelf();

        static::assertEquals($this->resultJsonMock, $this->controller->execute());
    }


    public function testExecuteWithLocalizedException()
    {

        $this->formKeyValidatorMock->method('validate')->with($this->requestMock)->willReturn(true);

        $this->quoteMock->method('isVirtual')->willReturn(false);
        $this->quoteMock->method('getBillingAddress')->willReturn($this->addressMock);
        $this->quoteMock->method('getShippingAddress')->willReturn($this->addressMock);
        $this->quoteMock->method('getCustomerId')->willReturn(1);
        $this->quoteMock->method('getId')->willReturn(1);
        $this->quoteMock->method('getIsActive')->willReturn(true);

        $addressData = ['some' => 'data'];
        $email = 'test@example.org';
        $token = 'sometoken';

        $this->requestMock->method('getParam')->willReturnMap([
            ['billingAddress', null, $addressData],
            ['shippingAddress', null, $addressData],
            ['token', null, $token],
            ['email', null, $email],
        ]);

        $this->paymentMock->method('importData')->with(
            [
                'method' => 'cybersource_googlepay',
                'paymentToken' => $token,
            ]
        );

        $this->addressConverterMock->method('convertGoogleAddress')->with($addressData)->willReturn($this->addressMock);
        $this->quoteMock->expects(static::once())->method('setBillingAddress')->with($this->addressMock);
        $this->quoteMock->expects(static::once())->method('setShippingAddress')->with($this->addressMock);

        $this->quoteMock->expects(static::once())->method('reserveOrderId');
        $this->quoteMock->expects(static::once())->method('collectTotals');
        $this->cartRepositoryInterfaceMock->expects(static::once())->method('save')->with($this->quoteMock);

        $exception = new \Magento\Framework\Exception\LocalizedException(__('Something wrong'));

        $this->cartManagementInterfaceMock->method('submit')->with($this->quoteMock)->willThrowException($exception);

        $this->resultJsonMock->method('setData')->with([
            'status' => 500,
            'message' => __('Something wrong'),
            'redirect_url' => 'checkout/cart',
        ])->willReturnSelf();

        static::assertEquals($this->resultJsonMock, $this->controller->execute());
    }

    public function testExecuteWithException()
    {

        $this->formKeyValidatorMock->method('validate')->with($this->requestMock)->willReturn(true);

        $this->quoteMock->method('isVirtual')->willReturn(false);
        $this->quoteMock->method('getBillingAddress')->willReturn($this->addressMock);
        $this->quoteMock->method('getShippingAddress')->willReturn($this->addressMock);
        $this->quoteMock->method('getCustomerId')->willReturn(1);
        $this->quoteMock->method('getId')->willReturn(1);
        $this->quoteMock->method('getIsActive')->willReturn(true);

        $addressData = ['some' => 'data'];
        $email = 'test@example.org';
        $token = 'sometoken';

        $this->requestMock->method('getParam')->willReturnMap([
            ['billingAddress', null, $addressData],
            ['shippingAddress', null, $addressData],
            ['token', null, $token],
            ['email', null, $email],
        ]);

        $this->paymentMock->method('importData')->with(
            [
                'method' => 'cybersource_googlepay',
                'paymentToken' => $token,
            ]
        );

        $this->addressConverterMock->method('convertGoogleAddress')->with($addressData)->willReturn($this->addressMock);
        $this->quoteMock->expects(static::once())->method('setBillingAddress')->with($this->addressMock);
        $this->quoteMock->expects(static::once())->method('setShippingAddress')->with($this->addressMock);

        $this->quoteMock->expects(static::once())->method('reserveOrderId');
        $this->quoteMock->expects(static::once())->method('collectTotals');
        $this->cartRepositoryInterfaceMock->expects(static::once())->method('save')->with($this->quoteMock);

        $exception = new \Exception(__('Something wrong'));

        $this->cartManagementInterfaceMock->method('submit')->with($this->quoteMock)->willThrowException($exception);

        $this->resultJsonMock->method('setData')->with([
            'status' => 500,
            'message' => __('Unable to place order. Please try again.'),
            'redirect_url' => 'checkout/cart',
        ])->willReturnSelf();

        static::assertEquals($this->resultJsonMock, $this->controller->execute());
    }

    public function testExecuteInvalidQuote()
    {
        $this->formKeyValidatorMock->method('validate')->with($this->requestMock)->willReturn(true);

        $this->quoteMock->method('isVirtual')->willReturn(false);
        $this->quoteMock->method('getBillingAddress')->willReturn($this->addressMock);
        $this->quoteMock->method('getShippingAddress')->willReturn($this->addressMock);
        $this->quoteMock->method('getCustomerId')->willReturn(1);
        $this->quoteMock->method('getId')->willReturn(1);
        $this->quoteMock->method('getIsActive')->willReturn(null);

        $addressData = ['some' => 'data'];
        $email = 'test@example.org';
        $token = 'sometoken';

        $this->requestMock->method('getParam')->willReturnMap([
            ['billingAddress', null, $addressData],
            ['shippingAddress', null, $addressData],
            ['token', null, $token],
            ['email', null, $email],
        ]);

        $this->paymentMock->method('importData')->with(
            [
                'method' => 'cybersource_googlepay',
                'paymentToken' => $token,
            ]
        );

        $this->addressConverterMock->method('convertGoogleAddress')->with($addressData)->willReturn($this->addressMock);
        $this->quoteMock->expects(static::never())->method('setBillingAddress')->with($this->addressMock);
        $this->quoteMock->expects(static::never())->method('setShippingAddress')->with($this->addressMock);

        $this->quoteMock->expects(static::never())->method('reserveOrderId');
        $this->quoteMock->expects(static::never())->method('collectTotals');
        $this->cartRepositoryInterfaceMock->expects(static::never())->method('save')->with($this->quoteMock);

        $this->resultJsonMock->method('setData')->with([
            'status' => 500,
            'message' => __('Quote not found. Please refresh the page and try again.'),
            'redirect_url' => 'checkout/cart',
        ])->willReturnSelf();

        static::assertEquals($this->resultJsonMock, $this->controller->execute());

    }

}
