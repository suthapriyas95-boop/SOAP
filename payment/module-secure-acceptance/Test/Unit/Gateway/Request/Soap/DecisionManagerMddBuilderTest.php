<?php declare(strict_types=1);

namespace CyberSource\SecureAcceptance\Gateway\Request\Soap;

use PHPUnit\Framework\TestCase;

class DecisionManagerMddBuilderTest extends TestCase
{
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cartRepositoryMock;

    /**
     * @var \Magento\Framework\DB\Select|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dbSelectMock;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Grid\Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderGridCollectionMock;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Grid\CollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderGridCollectionFactoryMock;

    /** @var DecisionManagerMddBuilder */
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


    protected function setUp()
    {
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);
        $this->customerSessionMock = $this->createMock(\Magento\Customer\Model\Session::class);
        $this->checkoutSessionMock = $this->createPartialMock(\Magento\Checkout\Model\Session::class, [
            'getQuote',
            'getQuoteId',
            'getFingerprintId'
        ]);
        $this->orderCollectionFactoryMock = $this->createMock(\Magento\Sales\Model\ResourceModel\Order\CollectionFactory::class);
        $this->orderGridCollectionFactoryMock = $this->createMock(\Magento\Sales\Model\ResourceModel\Order\Grid\CollectionFactory::class);
        $this->giftMessageHelperMock = $this->createMock(\Magento\GiftMessage\Helper\Message::class);
        $this->giftMessageMock = $this->createMock(\Magento\GiftMessage\Model\Message::class);
        $this->cartRepositoryMock = $this->createMock(\Magento\Quote\Api\CartRepositoryInterface::class);
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
            'getShippingAddress'
        ]);
        $this->orderCollectionMock = $this->createMock(\Magento\Sales\Model\ResourceModel\Order\Collection::class);
        $this->orderGridCollectionMock = $this->createMock(\Magento\Sales\Model\ResourceModel\Order\Grid\Collection::class);
        $this->customerDataMock = $this->createMock(\Magento\Customer\Api\Data\CustomerInterface::class);
        $this->dataObjectMock = $this->createPartialMock(\Magento\Framework\DataObject::class, [
            'getCreatedAt',
        ]);
        $this->addressMock = $this->createPartialMock(\Magento\Quote\Model\Quote\Address::class, [
            'getShippingMethod',
            'getShippingDescription',
            'getEmail',
        ]);
        $this->dbSelectMock = $this->createMock(\Magento\Framework\DB\Select::class);

        $this->decisionManagerMddBuilder = new DecisionManagerMddBuilder(
            $this->subjectReaderMock,
            $this->customerSessionMock,
            $this->checkoutSessionMock,
            $this->orderCollectionFactoryMock,
            $this->orderGridCollectionFactoryMock,
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

        $quoteId = 123;

        $result = [
            'merchantDefinedData' => [
                'field1' => (int)$isLoggedIn,
                'field2' => $createdAt,
                'field3' => $collectionSize,
                'field4' => $createdAt2,
                'field5' => round((time() - strtotime($createdAt ?? '')) / (3600 * 24)),
                'field6' => 1,
                'field20' => $couponCode,
                'field21' => $baseSubtotal - $baseSubtotalWithDiscount,
                'field22' => $giftMessage,
                'field23' => 'web',
                'field31' => $shippingMethod,
                'field32' => $shippingDescription
            ],
            'deviceFingerprintID' => $fingerPrintId,
            'billTo' => [
                'customerID' => $customerId,
                'ipAddress' => $remoteIp,
            ]

        ];

        $this->subjectReaderMock->method('readPayment')->with($buildSubject)->willReturn($this->paymentDOMock);

        $this->paymentDOMock->method('getOrder')->willReturn($this->orderMock);

        $this->checkoutSessionMock->method('getQuoteId')->willReturn($quoteId);
        $this->cartRepositoryMock->method('get')->with($quoteId)->willReturn($this->quoteMock);

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
        $this->orderGridCollectionFactoryMock->method('create')->willReturn($this->orderGridCollectionMock);

        $this->orderCollectionMock->method('addFieldToFilter')->with('customer_id', $customerId)->willReturnSelf();
        $this->orderCollectionMock->method('setOrder')->with('created_at', 'desc')->willReturnSelf();
        $this->orderCollectionMock->method('getSize')->willReturn($collectionSize);
        $this->orderCollectionMock->method('getFirstItem')->willReturn($this->dataObjectMock);
        $this->orderGridCollectionMock->method('addFieldToFilter')->with('customer_email', $customerEmail)->willReturnSelf();;
        $this->orderGridCollectionMock->method('getSelect')->willReturn($this->dbSelectMock);
        $this->orderGridCollectionMock->method('getSize')->willReturn(1);

        $this->customerDataMock->method('getCreatedAt')->willReturn($createdAt);

        $this->dataObjectMock->method('getCreatedAt')->willReturn($createdAt2);

        $this->authMock->method('isLoggedIn')->willReturn(false);

        $this->addressMock->method('getShippingMethod')->willReturn($shippingMethod);
        $this->addressMock->method('getShippingDescription')->willReturn($shippingDescription);
        $this->addressMock->method('getEmail')->willReturn($customerEmail);

        $this->orderMock->method('getCustomerId')->willReturn($customerId);
        $this->orderMock->method('getRemoteIp')->willReturn($remoteIp);
        $this->orderMock->method('getBillingAddress')->willReturn($this->addressMock);

        $this->giftMessageHelperMock->method('getGiftMessage')->with($giftMessageId)->willReturn($this->giftMessageMock);

        $this->giftMessageMock->method('getMessage')->willReturn($giftMessage);

        $this->assertEquals($result, $this->decisionManagerMddBuilder->build($buildSubject));
    }
}
