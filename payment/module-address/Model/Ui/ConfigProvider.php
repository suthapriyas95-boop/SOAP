<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Address\Model\Ui;


class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritDoc
     */
    public function getConfig()
    {
        return [
            'addressVerification' => (bool)$this->scopeConfig->getValue(
                'payment/chcybersource/address_check_enabled',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            )
        ];
    }
}
