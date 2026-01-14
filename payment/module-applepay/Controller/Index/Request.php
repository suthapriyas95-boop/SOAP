<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\ApplePay\Controller\Index;

use Magento\Checkout\Model\Session;
use Magento\Store\Model\Information;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Store\Model\StoreManagerInterface;
use CyberSource\ApplePay\Gateway\Config\Config;
use Magento\Framework\Controller\Result\JsonFactory;

class Request extends Action
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var Information
     */
    private $storeInfo;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Config
     */
    private $config;

    /**
     * Request constructor.
     * @param Config $config
     * @param Context $context
     * @param Session $session
     * @param Information $storeInfo
     * @param JsonFactory $resultJsonFactory
     * @param StoreManagerInterface $storeManagerInterface
     */
    public function __construct(
        Config $config,
        Context $context,
        Session $session,
        Information $storeInfo,
        JsonFactory $resultJsonFactory,
        StoreManagerInterface $storeManagerInterface
    ) {
        $this->config = $config;
        $this->session = $session;
        $this->storeInfo = $storeInfo;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->storeManager = $storeManagerInterface;

        parent::__construct($context);
    }
    
    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $quote = $this->session->getQuote();

        /** @var \Magento\Store\Model\Store $currentStore */
        $currentStore = $this->storeManager->getStore();
        $storeInfo = $this->storeInfo->getStoreInformationObject($currentStore);

        $data = ['request' => [
            'countryCode' => $storeInfo->getCountryId() ? $storeInfo->getCountryId() : 'US',
            'currencyCode' => $quote->getCurrency()->getQuoteCurrencyCode(),
            'supportedNetworks' => $this->getSupportedNetworks(),
            'requiredBillingContactFields' => ["email", "name", "phone", "postalAddress"],
            'merchantCapabilities' => ['supports3DS'],
            'total' => [
                'label' => $storeInfo->getName() ? $storeInfo->getName() : 'Default Store Name',
                'amount' => sprintf('%.2F', $quote->getGrandTotal()),
            ]
        ]];

        return $this->resultJsonFactory->create()->setData($data);
    }

    /**
     * Converts magento allowed cc types to apple format
     *
     * @return array
     */
    private function getSupportedNetworks()
    {
        $ccTypesMap = [
            'VI' => 'visa',
            'MC' => 'masterCard',
            'AE' => 'amex',
            'DI' => 'discover',
            'JCB' => 'jcb'
        ];

        $result = [];

        foreach ((array) $this->config->getCcTypes() as $type) {
            if (isset($ccTypesMap[$type])) {
                $result[] = $ccTypesMap[$type];
            }
        }

        return $result;
    }
}
