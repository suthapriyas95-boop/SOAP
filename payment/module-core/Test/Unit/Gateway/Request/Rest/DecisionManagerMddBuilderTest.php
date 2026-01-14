<?php declare(strict_types=1);
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Test\Unit\Gateway\Request\Rest;

use PHPUnit\Framework\TestCase;

class DecisionManagerMddBuilderTest extends TestCase
{
    /** @var \CyberSource\Core\Gateway\Request\Rest\DecisionManagerMddBuilder */
    private $decisionManagerMddBuilder;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReaderMock;

    /** @var \Magento\Customer\Model\Session | \PHPUnit_Framework_MockObject_MockObject */
    private $customerSessionMock;

    /** @var \Magento\Checkout\Model\Session | \PHPUnit_Framework_MockObject_MockObject */
    private $checkoutSessionMock;

    /** @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory | \PHPUnit_Framework_MockObject_MockObject */
    private $orderCollectionFactoryMock;

    /** @var \Magento\GiftMessage\Helper\Message | \PHPUnit_Framework_MockObject_MockObject */
    private $giftMessageHelperMock;

    /** @var \Magento\Backend\Model\Auth | \PHPUnit_Framework_MockObject_MockObject */
    private $authMock;
    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface | \PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentDOMock;
    /**
     * @var \Magento\Payment\Gateway\Data\OrderAdapterInterface | \PHPUnit\Framework\MockObject\MockObject
     */
    private $orderMock;
    /**
     * @var \Magento\Quote\Model\Quote | \PHPUnit\Framework\MockObject\MockObject
     */
    private $quoteMock;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory | \PHPUnit\Framework\MockObject\MockObject
     */
    private $orderCollectionMock;
    /**
     * @var \Magento\Customer\Api\Data\CustomerInterface | \PHPUnit\Framework\MockObject\MockObject
     */
    private $customerDataMock;
    /**
     * @var \Magento\Framework\DataObject | \PHPUnit\Framework\MockObject\MockObject
     */
    private $dataObjectMock;
    /**
     * @var \Magento\Quote\Model\Quote\Address | \PHPUnit\Framework\MockObject\MockObject
     */
    private $addressMock;
    /**
     * @var \Magento\GiftMessage\Model\Message | \PHPUnit\Framework\MockObject\MockObject
     */
    private $giftMessageMock;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cartRepositoryMock;

    /**
     * @var int
     */
    private $quoteId;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);
        $this->customerSessionMock = $this->createMock(\Magento\Customer\Model\Session::class);
        $this->checkoutSessionMock = $this->createPartialMock(\Magento\Checkout\Model\Session::class, [
            'getQuote',
            'getFingerprintId',
            'getQuoteId',
        ]);

        $this->cartRepositoryMock = $this->createMock(\Magento\Quote\Api\CartRepositoryInterface::class);
        $this->orderCollectionFactoryMock = $this->createMock(\Magento\Sales\Model\ResourceModel\Order\CollectionFactory::class);
        $this->giftMessageHelperMock = $this->createMock(\Magento\GiftMessage\Helper\Message::class);
        $this->giftMessageMock = $this->createMock(\Magento\GiftMessage\Model\Message::class);
        $this->authMock = $this->createMock(\Magento\Backend\Model\Auth::class);
        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->orderMock = $this->createMock(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class);
        $this->quoteMock = $this->createPartialMock(\Magento\Quote\Model\Quote::class, [
            'getCustomerEmail',
            'getCouponCode',
            'getBaseSubtotal',
            'getBaseSubtotalWithDiscount',
            'getGiftMessageId',
            'getIsVirtual',
            'getShippingAddress',
        ]);
        $this->orderCollectionMock = $this->createMock(\Magento\Sales\Model\ResourceModel\Order\Collection::class);
        $this->customerDataMock = $this->createMock(\Magento\Customer\Api\Data\CustomerInterface::class);
        $this->dataObjectMock = $this->createPartialMock(\Magento\Framework\DataObject::class, [
            'getCreatedAt',
        ]);
        $this->addressMock = $this->createPartialMock(\Magento\Quote\Model\Quote\Address::class, [
            'getShippingMethod',
            'getShippingDescription',
        ]);

        $this->checkoutSessionMock->expects(static::never())->method('getQuote');
        $this->checkoutSessionMock->method('getQuoteId')->willReturn($this->quoteId);
        $this->cartRepositoryMock->method('get')->with($this->quoteId)->willReturn($this->quoteMock);
        $this->quoteId = 123;

        $this->decisionManagerMddBuilder = new \CyberSource\Core\Gateway\Request\Rest\DecisionManagerMddBuilder(
            $this->subjectReaderMock,
            $this->customerSessionMock,
            $this->checkoutSessionMock,
            $this->orderCollectionFactoryMock,
            $this->giftMessageHelperMock,
            $this->cartRepositoryMock,
            $this->authMock
        );
    }

    public function testBuild()
    {
        $buildSubject = ['payment' => $this->paymentDOMock];
        $isLoggedIn = true;
        $customerEmail = 'customer@email.com';
        $customerId = 1;
        $collectionSize = 3;
        $createdAt = '2010-10-10';
        $createdAt2 = '2011-10-10';
        $couponCode = 'code';
        $baseSubtotal = 100;
        $baseSubtotalWithDiscount = 70;
        $giftMessageId = 1;
        $giftMessage = 'gift_message';
        $shippingMethod = 'shipping_method';
        $shippingDescription = 'shipping_description';
        $fingerPrintId = 11;
        $remoteIp = '127.0.0.1';

        $result = [
            'merchantDefinedInformation' => [
                [
                    'key' => '1',
                    'value' => (int)$isLoggedIn,
                ],
                [
                    'key' => '2',
                    'value' => $createdAt,
                ],
                [
                    'key' => '3',
                    'value' => $collectionSize,
                ],
                [
                    'key' => '4',
                    'value' => $createdAt2,
                ],
                [
                    'key' => '5',
                    'value' => round((time() - strtotime($createdAt ?? '')) / (3600 * 24)),
                ],
                [
                    'key' => '6',
                    'value' => 1,
                ],
                [
                    'key' => '20',
                    'value' => $couponCode,
                ],
                [
                    'key' => '21',
                    'value' => $baseSubtotal - $baseSubtotalWithDiscount,
                ],
                [
                    'key' => '22',
                    'value' => $giftMessage,
                ],
                [
                    'key' => '23',
                    'value' => 'web',
                ],
                [
                    'key' => '31',
                    'value' => $shippingMethod,
                ],
                [
                    'key' => '32',
                    'value' => $shippingDescription,
                ],
            ],
            'deviceInformation' => [
                'ipAddress' => $remoteIp,
            ],
        ];

        $this->subjectReaderMock->method('readPayment')->with($buildSubject)->willReturn($this->paymentDOMock);

        $this->paymentDOMock->method('getOrder')->willReturn($this->orderMock);

        $this->checkoutSessionMock->method('getQuote')->willReturn($this->quoteMock);
        $this->checkoutSessionMock->method('getFingerprintId')->willReturn($fingerPrintId);

        $this->customerSessionMock->method('isLoggedIn')->willReturn($isLoggedIn);
        $this->customerSessionMock->method('getCustomerId')->willReturn($customerId);
        $this->customerSessionMock->method('getCustomerData')->willReturn($this->customerDataMock);

        $this->quoteMock->method('getCustomerEmail')->willReturn($customerEmail);
        $this->quoteMock->method('getCouponCode')->willReturn($couponCode);
        $this->quoteMock->method('getBaseSubtotal')->willReturn($baseSubtotal);
        $this->quoteMock->method('getBaseSubtotalWithDiscount')->willReturn($baseSubtotalWithDiscount);
        $this->quoteMock->method('getGiftMessageId')->willReturn($giftMessageId);
        $this->quoteMock->method('getIsVirtual')->willReturn(false);
        $this->quoteMock->method('getShippingAddress')->willReturn($this->addressMock);

        $this->orderCollectionFactoryMock->method('create')->willReturn($this->orderCollectionMock);

        $this->orderCollectionMock->method('addFieldToFilter')->with('customer_id', $customerId)->willReturnSelf();
        $this->orderCollectionMock->method('setOrder')->with('created_at', 'desc')->willReturnSelf();
        $this->orderCollectionMock->method('getSize')->willReturn($collectionSize);
        $this->orderCollectionMock->method('getFirstItem')->willReturn($this->dataObjectMock);

        $this->customerDataMock->method('getCreatedAt')->willReturn($createdAt);

        $this->dataObjectMock->method('getCreatedAt')->willReturn($createdAt2);

        $this->authMock->method('isLoggedIn')->willReturn(false);

        $this->addressMock->method('getShippingMethod')->willReturn($shippingMethod);
        $this->addressMock->method('getShippingDescription')->willReturn($shippingDescription);

        $this->orderMock->method('getCustomerId')->willReturn($customerId);
        $this->orderMock->method('getRemoteIp')->willReturn($remoteIp);

        $this->giftMessageHelperMock->method('getGiftMessage')->with($giftMessageId)->willReturn($this->giftMessageMock);

        $this->giftMessageMock->method('getMessage')->willReturn($giftMessage);

        $this->assertEquals($result, $this->decisionManagerMddBuilder->build($buildSubject));
    }
}
