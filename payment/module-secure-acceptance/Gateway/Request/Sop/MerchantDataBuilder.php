<?php
/*
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\SecureAcceptance\Gateway\Request\Sop;

class MerchantDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{


    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Config\SaConfigProviderInterface
     */
    private $configProvider;

    /**
     * @var \Magento\Framework\Locale\Resolver
     */
    private $localeResolver;

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Config\Config
     */
    private $config;

    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader,
        \CyberSource\SecureAcceptance\Gateway\Config\SaConfigProviderInterface $configProvider,
        \CyberSource\SecureAcceptance\Gateway\Config\Config $config,
        \Magento\Framework\Locale\Resolver $localeResolver
    ) {
        $this->subjectReader = $subjectReader;
        $this->configProvider = $configProvider;
        $this->localeResolver = $localeResolver;
        $this->config = $config;
    }

    /**
     * Builds Merchant Data
     *
     * @param array $buildSubject
     *
     * @return array
     */
    public function build(array $buildSubject)
    {
        $order = $this->subjectReader->readPayment($buildSubject)->getOrder();
        $storeId = $order->getStoreId();
        return [
            'access_key' => $this->configProvider->getAccessKey($storeId),
            'profile_id' => $this->configProvider->getProfileId($storeId),
            'partner_solution_id' => \CyberSource\Core\Helper\AbstractDataBuilder::PARTNER_SOLUTION_ID,
            'locale' => $this->getLocale($storeId),
        ];
    }

    private function getLocale($storeId = null)
    {
        return str_replace('_', '-', strtolower(
            $this->config->getLocale($storeId)
                ?: $this->localeResolver->getLocale() ?? ''
        ));
    }
}
