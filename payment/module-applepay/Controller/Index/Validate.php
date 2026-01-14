<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\ApplePay\Controller\Index;

use CyberSource\ApplePay\Gateway\Config\Config;
use CyberSource\Core\Model\LoggerInterface;
use Magento\Framework\App\Action\Context;
use Magento\Store\Model\StoreManagerInterface;

class Validate extends \Magento\Framework\App\Action\Action
{
    const APPLE_PAY_PROD_START_SESSION_URL = 'https://apple-pay-gateway-pr-pod2.apple.com/paymentservices/startSession';
	
	const APPLE_PAY_TEST_START_SESSION_URL = 'https://apple-pay-gateway-cert.apple.com/paymentservices/startSession';
	
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Validate constructor.
     * @param Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \CyberSource\ApplePay\Gateway\Config\Config $gatewayConfig,
		\CyberSource\Core\Model\LoggerInterface $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);

        $this->resultJsonFactory = $resultJsonFactory;
        $this->config = $gatewayConfig;
		$this->logger = $logger;
        $this->storeManager = $storeManager;

    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
		$sessionUrl = ($this->config->isTestMode())? self::APPLE_PAY_TEST_START_SESSION_URL : self::APPLE_PAY_PROD_START_SESSION_URL;
       
        $storeId = $this->storeManager->getStore()->getStoreId(); 
        $data = [
            'merchantIdentifier' => $this->config->getAppleMerchantId($storeId),
            'domainName' => $this->config->getDomain($storeId),
            'displayName' => $this->config->getDisplayName($storeId)
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $sessionUrl);
        curl_setopt($ch, CURLOPT_SSLCERT, $this->config->getPathCert($storeId));
        curl_setopt($ch, CURLOPT_SSLKEY, $this->config->getPathKey($storeId));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if (! $result = curl_exec($ch)) {
            $this->logger->critical("Apple Pay merchant validation failed: " . curl_error($ch));
        }

        curl_close($ch);

        return $this->resultJsonFactory->create()->setData(
            ['session' => json_decode($result, 1)]
        );
    }
}