<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\BankTransfer\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Store\Model\StoreManagerInterface;

class Pay extends \Magento\Framework\App\Action\Action
{
    /**
     * @var Session
     */
    protected $_session;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var array
     */
    protected $_gatewayAPI;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    private $formKeyValidator;

    /**
     * Pay constructor.
     * @param Context $context
     * @param Session $session
     * @param array $gatewayAPI
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        Session $session,
        array $gatewayAPI,
        StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
    ) {
        $this->_session = $session;
        $this->_storeManager = $storeManager;
        $this->_gatewayAPI = $gatewayAPI;
        $this->scopeConfig = $scopeConfig;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->formKeyValidator = $formKeyValidator;
        parent::__construct($context);
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $result->setData(['error' => __('Invalid formkey')]);
        }

        $quote = $this->_session->getQuote();
        $quote->reserveOrderId();
        $this->_session->replaceQuote($quote);

        $guestEmail = $this->_request->getParam('guestEmail');
        
        if (!empty($guestEmail) && $guestEmail != 'null') {
            $quote->getBillingAddress()->setEmail($guestEmail);
            
            $this->_session->setData('guestEmail', $guestEmail);
        }
        
        $bankCode = $this->_request->getParam('bank');
        
        $paymentMethod = (in_array($bankCode, ['sofort', 'bancontact'])) ? $bankCode : 'ideal';

        $data = $this->_gatewayAPI[$paymentMethod]->bankTransferSale(
            $quote,
            $this->_storeManager->getStore(),
            $bankCode,
            $this->_session->getData('fingerprint_id')
        );
        if (!empty($data['response'])) {
            $this->_session->setData('response', $data['response']);
            $quote->getPayment()->setAdditionalInformation('bank_payment_method', $paymentMethod)->save();
        }
        $result = $this->resultJsonFactory->create();
        $result = $result->setData($data);
        return $result;
    }
}
