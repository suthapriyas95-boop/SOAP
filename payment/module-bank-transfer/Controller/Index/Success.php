<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\BankTransfer\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Model\QuoteManagement;
use Magento\Checkout\Model\Cart;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use CyberSource\Core\Model\LoggerInterface;


class Success extends \Magento\Framework\App\Action\Action
{
    
    /**
     * @var QuoteManagement
     */
    protected $_quoteManagement;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var OrderPaymentRepositoryInterface
     */
    protected $orderPaymentRepository;
    
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;
    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;
    
    private $order;

    /**
     * Success constructor.
     * @param Context $context
     * @param QuoteManagement $quoteManagement
     * @param Cart $cart
     * @param LoggerInterface $logger
     * @param OrderPaymentRepositoryInterface $orderPaymentRepository
     * @param \Magento\Customer\Model\Session $customerSession
     * @param SessionManagerInterface $checkoutSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        QuoteManagement $quoteManagement,
        Cart $cart,
        LoggerInterface $logger,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        \Magento\Customer\Model\Session $customerSession,
        SessionManagerInterface $checkoutSession,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Sales\Api\Data\OrderInterface $order
    ) {
        $this->_quoteManagement = $quoteManagement;
        $this->cart = $cart;
        $this->logger = $logger;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->order = $order;

        parent::__construct($context);
        $this->eventManager = $eventManager;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        $quote = $this->checkoutSession->getQuote();
        $responses = $this->checkoutSession->getData('response');
        $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        try {
            // Set CustomerData to quote
            if (!$this->customerSession->isLoggedIn()) {
                $quote->setCustomerIsGuest(1);
                $quote->setCheckoutMethod('guest');
                $quote->setCustomerId(null);
                $quote->setCustomerEmail($this->checkoutSession->getData('guestEmail'));
                $quote->setCustomerGroupId(\Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID);
                $this->checkoutSession->setData('guestEmail', null);
            }
            $quote->setPaymentMethod('cybersource_bank_transfer');
            $quote->setInventoryProcessed(false);
            $quote->save();
            $quote->getPayment()->setAdditionalInformation('cyber_response', (array)$responses);
            $quote->getPayment()->setTransactionId($responses->apSaleReply->processorTransactionID);
            
            $quote->getPayment()->setMethod('cybersource_bank_transfer');

            // Collect Totals & Save Quote
            $quote->collectTotals()->save();

            $this->checkoutSession->setIsNotAdminCapture(true);
            // Create Order From Quote
            $quote->setReservedOrderId($responses->merchantReferenceCode);
            $order = $this->_quoteManagement->submit($quote);
            $this->eventManager->dispatch(
                'cybersource_quote_submit_success',
                [
                    'order' => $order,
                    'quote' => $quote
                ]
            );
            $orderId = $quote->getReservedOrderId();
            $orderCollection = $this->order->loadByIncrementId($orderId);
            $payment = $orderCollection->getPayment();
            $payment->setLastTransId($responses->requestID);
            $payment->setAdditionalInformation('request_id', $responses->requestID);
            $payment->setAdditionalInformation('request_token', $responses->requestToken);
            $this->orderPaymentRepository->save($payment);
            $this->checkoutSession->setLastSuccessQuoteId($quote->getId());
            $this->checkoutSession->setLastQuoteId($quote->getId());
            $this->checkoutSession->setLastOrderId($orderCollection->getId());
            $this->checkoutSession->setLastOrderStatus($orderCollection->getStatus());
            $this->checkoutSession->setLastRealOrderId($orderCollection->getRealOrderId());
            $this->cart->truncate()->save();
            $resultRedirect->setUrl($this->_url->getUrl('checkout/onepage/success'));
            $this->messageManager->addSuccessMessage(__('Your order has been successfully created!'));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new LocalizedException(__($e->getMessage()));
        }
       
        return $resultRedirect;
    }
}
