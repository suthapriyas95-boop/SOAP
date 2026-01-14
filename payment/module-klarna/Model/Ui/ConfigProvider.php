<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\KlarnaFinancial\Model\Ui;

use CyberSource\KlarnaFinancial\Service\CyberSourceSoap;
use Magento\Checkout\Model\ConfigProviderInterface;
use CyberSource\KlarnaFinancial\Gateway\Config\Config;
use Magento\Framework\Locale\ResolverInterface;

/**
 * Class ConfigProvider
 * @codeCoverageIgnore
 */
class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'cybersourceklarna';

    /**
     * @var ResolverInterface
     */
    private $localeResolver;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var CyberSourceSoap
     */
    private $cyberSourceSoap;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $url;

    /**
     * ConfigProvider constructor.
     * @param Config $config
     * @param ResolverInterface $localeResolver
     * @param CyberSourceSoap $cyberSourceSoap
     * @param \Magento\Framework\UrlInterface $url
     */
    public function __construct(
        Config $config,
        ResolverInterface $localeResolver,
        CyberSourceSoap $cyberSourceSoap,
        \Magento\Framework\UrlInterface $url
    ) {
        $this->config = $config;
        $this->localeResolver = $localeResolver;
        $this->cyberSourceSoap = $cyberSourceSoap;
        $this->url = $url;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $storeId = $this->cyberSourceSoap->getCurrentStoreId();
        return [
            'payment' => [
                self::CODE => [
                    'title' => $this->config->getTitle(),
                    'isDeveloperMode' => $this->config->isDeveloperMode($storeId),
                    'placeOrderUrl' => $this->url->getUrl('cybersourceklarna/index/placeorder'),
                ]
            ]
        ];
    }
}
