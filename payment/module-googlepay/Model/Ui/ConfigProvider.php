<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\GooglePay\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Locale\ResolverInterface;

/**
 * Class ConfigProvider
 * @codeCoverageIgnore
 */
class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'cybersource_googlepay';
    const GOOGLEPAY_GATEWAY_CODE = 'cybersource';

    const ENV_TEST = 'TEST';
    const ENV_PRODUCTION = 'PRODUCTION';

    /**
     * @var \CyberSource\GooglePay\Gateway\Config\Config
     */
    private $config;

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $url;

    /**
     * @var \CyberSource\GooglePay\Model\CardTypeMap
     */
    private $cardTypeMap;
    /**
     * @var \Magento\Store\Model\Information
     */
    private $storeInformation;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Directory\Model\AllowedCountries
     */
    private $allowedCountryModel;

    /**
     * Constructor
     *
     * @param \CyberSource\GooglePay\Gateway\Config\Config $config
     * @param ResolverInterface $localeResolver
     * @param \CyberSource\GooglePay\Model\CardTypeMap $cardTypeMap
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Store\Model\Information $storeInformation
     * @param \Magento\Framework\UrlInterface $url
     */
    public function __construct(
        \CyberSource\GooglePay\Gateway\Config\Config $config,
        ResolverInterface $localeResolver,
        \CyberSource\GooglePay\Model\CardTypeMap $cardTypeMap,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Store\Model\Information $storeInformation,
        \Magento\Directory\Model\AllowedCountries $allowedCountryModel,
        \Magento\Framework\UrlInterface $url
    ) {
        $this->config = $config;
        $this->localeResolver = $localeResolver;
        $this->url = $url;
        $this->cardTypeMap = $cardTypeMap;
        $this->storeInformation = $storeInformation;
        $this->storeManager = $storeManager;
        $this->allowedCountryModel = $allowedCountryModel;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $storeInfo = $this->storeInformation->getStoreInformationObject($this->storeManager->getStore());

        return [
            'payment' => [
                self::CODE => [
                    'isActive' => 1,
                    'title' => $this->config->getTitle(),
                    'environment' => $this->config->isTestMode() ? static::ENV_TEST : static::ENV_PRODUCTION,
                    'gatewayId' => static::GOOGLEPAY_GATEWAY_CODE,
                    'gatewayMerchantId' => $this->config->getMerchantId(),
                    'merchantName' => $this->config->getMerchantDisplayName() ?? $storeInfo->getName() ?? __('Default Store Name'),
                    'countryCode' => $storeInfo->getCountryId() ?? 'US',
                    'merchantId' => $this->config->getGoogleMerchantId(),
                    'cardTypes' => $this->getAllowedCardTypes(),
                    'allowedCountries' => $this->getAllowedCountries(),
                ]
            ]
        ];
    }

    private function getAllowedCountries()
    {
        return $this->config->getAllowspecific() === '1'
            ? explode(',', $this->config->getSpecificcountry() ?? '')
            : array_values($this->allowedCountryModel->getAllowedCountries());
    }

    /**
     * @return string[]
     */
    private function getAllowedCardTypes()
    {
        return array_map([$this->cardTypeMap, 'toGooglePayType'], $this->config->getCcTypes());
    }
}
