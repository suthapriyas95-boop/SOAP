<?php

namespace CyberSource\Core\Gateway\Request\Rest;

class DecisionManagerMddBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var \Magento\GiftMessage\Helper\Message
     */
    protected $giftMessageHelper;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var \Magento\Backend\Model\Auth
     */
    private $auth;

    /**
     * DecisionManagerMddBuilder constructor.
     *
     * @param \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\GiftMessage\Helper\Message $giftMessageHelper
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \Magento\Backend\Model\Auth $auth
     */
    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\GiftMessage\Helper\Message $giftMessageHelper,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Backend\Model\Auth $auth
    ) {
        $this->subjectReader = $subjectReader;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->giftMessageHelper = $giftMessageHelper;
        $this->cartRepository = $cartRepository;
        $this->auth = $auth;
    }

    /**
     * Builds DecisionManager MDD fields
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {

        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $quote = $this->getQuote();
        $order = $paymentDO->getOrder();

        $request = [];
        $result = [];

        $result['1'] = (int)$this->customerSession->isLoggedIn();// Registered or Guest Account

        if ($this->customerSession->isLoggedIn()) {

            $orders = $this->getOrders();

            $result['2'] = $this->getAccountCreationDate(); // Account Creation Date

            $result['3'] = $orders->getSize(); // Purchase History Count

            if ($orders->getSize() > 0) {
                $result['4'] = $orders->getFirstItem()->getCreatedAt(); // Last Order Date
            }

            $result['5'] = $this->getAccountAge();// Member Account Age (Days)
        }

        $result['6'] = $this->isRepeatCustomer($order->getBillingAddress()->getEmail()); // Repeat Customer

        $result['20'] = $quote->getCouponCode(); //Coupon Code

        $result['21'] = $quote->getBaseSubtotal() - $quote->getBaseSubtotalWithDiscount(); // Discount

        $result['22'] = $this->getGiftMessage(); // Gift Message

        $result['23'] = ($this->auth->isLoggedIn()) ? 'call center' : 'web'; //order source

        if (!$quote->getIsVirtual()) {
            if ($shippingAddress = $quote->getShippingAddress()) {
                $result['31'] = $quote->getShippingAddress()->getShippingMethod();
                $result['32'] = $quote->getShippingAddress()->getShippingDescription();
            }
        }

        /**
         *  Remove invalid values before send it to cybersource, otherwise it will trigger a 403 without any error
         */
        foreach ($result as $key => $value) {
            if ($value !== null && !empty($value) && $value !== "" && $value !== null) {
                $request['merchantDefinedInformation'][] = ['key' => (string)$key, 'value' => (string)$value];
            }
        }

        $request['deviceInformation']['ipAddress'] =  $paymentDO->getOrder()->getRemoteIp();

        return $request;
    }

    private function getQuote()
    {
        // $this->checkoutSession->getQuote() method has a side effect of setting customerId from a session
        // to the quote that breaks a compatibility with persistent shopping cart feature
        return $this->cartRepository->get($this->checkoutSession->getQuoteId());
    }

    private function getOrders()
    {
        $field = 'customer_email';
        $value = $this->getQuote()->getCustomerEmail();
        if ($this->customerSession->isLoggedIn()) {
            $field = 'customer_id';
            $value = $this->customerSession->getCustomerId();
        }
        return $this->orderCollectionFactory->create()
            ->addFieldToFilter($field, $value)
            ->setOrder('created_at', 'desc');
    }


    private function isRepeatCustomer($customerEmail)
    {
        $orders = $this->orderCollectionFactory->create()
            ->addFieldToFilter('customer_email', $customerEmail)
        ;

        $orders->getSelect()->limit(1);

        return (int)($orders->getSize() > 0);
    }

    private function getAccountCreationDate()
    {
        return $this->customerSession->getCustomerData()->getCreatedAt();
    }

    private function getAccountAge()
    {
        return round((time() - strtotime($this->customerSession->getCustomerData()->getCreatedAt() ?? '')) / (3600 * 24));
    }

    private function getGiftMessage()
    {
        $message = $this->giftMessageHelper->getGiftMessage($this->getQuote()->getGiftMessageId());
        return $message->getMessage() ? $message->getMessage() : '';
    }
}
