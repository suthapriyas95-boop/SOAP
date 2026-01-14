<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\BankTransfer\Service;

use CyberSource\BankTransfer\Model\Config;
use CyberSource\BankTransfer\Helper\RequestDataBuilder;
use CyberSource\Core\Model\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;


class SofortSoap extends SoapAPI{


    /**
     * SoapAPI for Sofort
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param RequestDataBuilder $dataBuilder
     * @param Config $gatewayConfig
     * @param \SoapClient|null $client
     * @throws \Exception
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        RequestDataBuilder $dataBuilder,
        Config $gatewayConfig,
        ?\SoapClient $client = null
    ) {
        parent::__construct(
            $scopeConfig,
            $logger,
            $dataBuilder,
            $gatewayConfig,
            'sofort',
            $client
        );
    }
}