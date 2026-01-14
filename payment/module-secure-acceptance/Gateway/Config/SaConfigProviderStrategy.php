<?php
/*
 * Copyright © 2021 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\SecureAcceptance\Gateway\Config;


class SaConfigProviderStrategy implements SaConfigProviderInterface
{

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SaConfigProviderInterface
     */
    private $paConfigProvider;

    /**
     * @var SaConfigProviderInterface
     */
    private $paNonPaConfigProvider;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    public function __construct(
        Config $config,
        SaConfigProviderInterface $paConfigProvider,
        SaConfigProviderInterface $paNonPaConfigProvider,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->config = $config;
        $this->paConfigProvider = $paConfigProvider;
        $this->paNonPaConfigProvider = $paNonPaConfigProvider;
        $this->request = $request;
    }

    public function getProfileId($storeId = null)
    {
        return $this->config->getIsLegacyMode($storeId)
            ? $this->paConfigProvider->getProfileId($storeId)
            : $this->paNonPaConfigProvider->getProfileId($storeId);
    }

    public function getAccessKey($storeId = null)
    {
        return $this->config->getIsLegacyMode($storeId)
            ? $this->paConfigProvider->getAccessKey($storeId)
            : $this->paNonPaConfigProvider->getAccessKey($storeId);

    }

    public function getSecretKey($storeId = null)
    {
        if ($this->request->getParam('req_' . \CyberSource\SecureAcceptance\Helper\RequestDataBuilder::KEY_SCOPE) == \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE) {
            // \CyberSource\SecureAcceptance\Plugin\Store\Api\StoreResolverInterfacePlugin is loaded before an actual scope is resolved.
            // We must check the SA req param.
            return $this->paNonPaConfigProvider->getSecretKey($storeId);
        }

        return $this->config->getIsLegacyMode($storeId)
            ? $this->paConfigProvider->getSecretKey($storeId)
            : $this->paNonPaConfigProvider->getSecretKey($storeId);

    }
}
