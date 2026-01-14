<?php
/*
 * Copyright © 2021 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\SecureAcceptance\Gateway\Config;


class SaConfigProvider implements SaConfigProviderInterface
{

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SaConfigProviderNonPa
     */
    private $configProviderNonPa;

    public function __construct(
        Config $config,
        \Magento\Framework\App\RequestInterface $request,
        \CyberSource\SecureAcceptance\Gateway\Config\SaConfigProviderNonPa $configProviderNonPa
    ) {
        $this->config = $config;
        $this->configProviderNonPa = $configProviderNonPa;
    }

    public function getProfileId($storeId = null)
    {
        if ($this->config->isSilent($storeId)) {
            return $this->config->getSopAuthActive($storeId)
                ? $this->config->getValue(\CyberSource\SecureAcceptance\Gateway\Config\Config::KEY_SOP_AUTH_PROFILE_ID, $storeId)
                : $this->config->getSopProfileId($storeId);
        }

        return $this->config->getAuthActive($storeId)
            ? $this->config->getValue(\CyberSource\SecureAcceptance\Gateway\Config\Config::KEY_AUTH_PROFILE_ID, $storeId)
            : $this->config->getProfileId($storeId);
    }

    public function getAccessKey($storeId = null)
    {
        if ($this->config->isSilent($storeId)) {
            return $this->config->getSopAuthActive($storeId)
                ? $this->config->getValue(\CyberSource\SecureAcceptance\Gateway\Config\Config::KEY_SOP_AUTH_ACCESS_KEY, $storeId)
                : $this->config->getSopAccessKey($storeId);
        }

        return $this->config->getAuthActive($storeId)
            ? $this->config->getValue(\CyberSource\SecureAcceptance\Gateway\Config\Config::KEY_AUTH_ACCESS_KEY, $storeId)
            : $this->config->getAccessKey($storeId);
    }

    public function getSecretKey($storeId = null)
    {
        if ($this->config->isSilent($storeId)) {
            return $this->config->getSopAuthActive($storeId)
                ? $this->config->getValue(\CyberSource\SecureAcceptance\Gateway\Config\Config::KEY_SOP_AUTH_SECRET_KEY, $storeId)
                : $this->config->getSopSecretKey($storeId);
        }

        return $this->config->getAuthActive($storeId)
            ? $this->config->getValue(\CyberSource\SecureAcceptance\Gateway\Config\Config::KEY_AUTH_SECRET_KEY, $storeId)
            : $this->config->getSecretKey($storeId);
    }
}
