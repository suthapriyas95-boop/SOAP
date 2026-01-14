<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\WeChatPay\Gateway\Request;

class TestResponseCodeBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    /**
     * @var \CyberSource\WeChatPay\Gateway\Config\Config
     */
    private $config;

    /**
     * @param \CyberSource\WeChatPay\Gateway\Config\Config $config
     */
    public function __construct(\CyberSource\WeChatPay\Gateway\Config\Config $config)
    {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject)
    {
        if (!$this->config->isTestMode()) {
            return [];
        }

        if (!$configValue = $this->config->getTestStatusResponseCode()) {
            return [];
        }

        return ['reconciliationID' => $configValue];
    }
}
