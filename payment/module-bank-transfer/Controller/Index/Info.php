<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\BankTransfer\Controller\Index;

use CyberSource\BankTransfer\Service\IdealSoap;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\QuoteManagement;
use Magento\Checkout\Model\Cart;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

class Info extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Session
     */
    protected $_session;

    /**
     * @var QuoteManagement
     */
    protected $_quoteManagement;

    /**
     * @var Cart
     */
    protected $_cart;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var IdealSoap
     */
    protected $_gatewayAPI;

    /**
     * @var OrderPaymentRepositoryInterface
     */
    protected $_orderPaymentRepository;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     *
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;
    
    /**
     *
     * @var \CyberSource\BankTransfer\Model\IdealOption
     */
    private $idealOption;

    
    /**
     * Receipt constructor.
     * @param Context $context
     * @param Session $session
     * @param QuoteManagement $quoteManagement
     * @param Cart $cart
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\Session $customerSession
     * @param array $gatewayAPI
     * @param OrderPaymentRepositoryInterface $orderPaymentRepository
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \CyberSource\BankTransfer\Model\IdealOption $idealOption
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        Session $session,
        QuoteManagement $quoteManagement,
        Cart $cart,
        StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Session $customerSession,
        IdealSoap $gatewayAPI,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \CyberSource\BankTransfer\Model\IdealOption $idealOption
    ) {
        $this->_session = $session;
        $this->_quoteManagement = $quoteManagement;
        $this->_cart = $cart;
        $this->_storeManager = $storeManager;
        $this->_customerSession = $customerSession;
        $this->_gatewayAPI = $gatewayAPI;
        $this->_orderPaymentRepository = $orderPaymentRepository;
        $this->scopeConfig = $scopeConfig;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->idealOption = $idealOption;
        parent::__construct($context);
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $needUpdate = false;
        $data = [];
        foreach ($this->idealOption->getCollection() as $option) {
            if (time() - strtotime($option->getData('created_date') ?? '') > 24*3600) {
                $needUpdate = true;
                break;
            }
            $data[$option->getData('option_id')] = $option->getData('option_name');
        }

        if ($needUpdate || empty($data)) {
            foreach ($this->idealOption->getCollection() as $option) {
                $option->delete();
            }
            $quote = $this->_session->getQuote();

            $data = $this->_gatewayAPI->getListOfBanks($quote->getId());
            foreach ($data as $optionId => $optionName) {
                $this->idealOption->setData([
                    'created_date' => gmdate("Y-m-d\\TH:i:s\\Z"),
                    'option_id' => $optionId,
                    'option_name' => $optionName
                ]);
                $this->idealOption->save();
            }
        }
        $result = $this->resultJsonFactory->create();
        $result = $result->setData($data);
        return $result;
    }
}
