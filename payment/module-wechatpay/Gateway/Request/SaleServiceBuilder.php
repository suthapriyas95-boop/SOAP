<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\WeChatPay\Gateway\Request;

class SaleServiceBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \CyberSource\WeChatPay\Gateway\Config\Config
     */
    private $config;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \CyberSource\WeChatPay\Gateway\Config\Config $config
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \CyberSource\WeChatPay\Gateway\Config\Config $config
    ) {
        $this->storeManager = $storeManager;
        $this->config = $config;
    }

    /**
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $successUrl = $this->config->getWeChatSuccessUrl() ?? '';
        $successUrl = strpos($successUrl, 'http') !== 0
            ? $this->storeManager->getStore()->getUrl($successUrl)
            : $successUrl;

        return [
            'successURL' => $successUrl,
            'transactionTimeout' => $this->config->getQrExpirationTime()
        ];
    }
}
