<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\BankTransfer\Model;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Config
 */
class Payment extends \Magento\Payment\Model\Method\AbstractMethod
{
    
    const CODE = 'cybersource_bank_transfer';
    protected $_code = self::CODE;
    protected $_isOffline = false;
    protected $_isGateway                   = true;
    protected $_canCapture                  = true;
    protected $_canInvoice                  = true;
    protected $_canRefund                   = true;
    protected $_canUseInternal              = false;
    protected $_supportedCurrencyCodes = ['USD'];
    protected $_gatewayAPI = null;
    protected $_checkoutSession;

    /**
     * Payment constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param array $gatewayAPI
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        array $gatewayAPI,
        Session $checkoutSession,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );

        $this->_gatewayAPI = $gatewayAPI;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * Capture payment abstract method
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        return $this;
    }
    
    /**
     * Refund payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @api
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $paymentMethod = $payment->getAdditionalInformation('bank_payment_method');

        $result = $this->_gatewayAPI[$paymentMethod]->bankTransferRefund(
            $payment->getOrder(),
            $payment->getAdditionalInformation('request_id')

        );
        if (empty($result) || $result->reasonCode != 100) {
            throw new LocalizedException(__('Payment gateway refunding error.'));
        }
        return $this;
    }
}
