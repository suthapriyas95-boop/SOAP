<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\BankTransfer\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use CyberSource\BankTransfer\Model\Config;

/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{
    const BANK_TRANSFER_CODE = 'cybersource_bank_transfer';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $url;

    /**
     * Constructor
     *
     * @param Config $config
     */
    public function __construct(Config $config, \Magento\Framework\UrlInterface $url)
    {
        $this->config = $config;
        $this->url = $url;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                'cybersource_bank_transfer_ideal' => [
                    'active' => $this->config->isMethodActive('ideal'),
                    'title' => $this->config->getMethodTitle('ideal'),
                    'placeOrderUrl' => $this->url->getUrl('cybersourcebt/index/pay'),
                ],
                'cybersource_bank_transfer_sofort' => [
                    'active' => $this->config->isMethodActive('sofort'),
                    'title' => $this->config->getMethodTitle('sofort'),
                    'placeOrderUrl' => $this->url->getUrl('cybersourcebt/index/pay'),
                    'bankCode' => 'sofort',
                ],
                'cybersource_bank_transfer_bancontact' => [
                    'active' => $this->config->isMethodActive('bancontact'),
                    'title' => $this->config->getMethodTitle('bancontact'),
                    'placeOrderUrl' => $this->url->getUrl('cybersourcebt/index/pay'),
                    'bankCode' => 'bancontact',
                ],
            ]
        ];
    }
}
