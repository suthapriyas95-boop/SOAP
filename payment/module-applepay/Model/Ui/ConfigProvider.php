<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\ApplePay\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use CyberSource\ApplePay\Gateway\Config\Config;

/**
 * Class ConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{
    const APPLEPAY_CODE = 'cybersource_applepay';

    /**
     * @var Config
     */
    private $config;

    /**
     * Constructor
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
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
                self::APPLEPAY_CODE => [
                    'active' => $this->config->isActive(),
                    'title' => $this->config->getTitle()
                ],
            ]
        ];
    }
}
