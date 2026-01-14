<?php

namespace CyberSource\SecureAcceptance\Helper;

class MethodForm
{
    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Config\Config
     */
    private $config;

    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Config\Config $config
    ) {
        $this->config = $config;
    }

    public function getCCVaultTemplateName()
    {
        return 'Magento_Vault::form/vault.phtml';
    }

    public function getCCTemplateName()
    {
        if ($this->config->isMicroform()) {
            return 'CyberSource_SecureAcceptance::payment/microform.phtml';
        }

        if ($this->config->isSilent()) {
            return 'CyberSource_SecureAcceptance::payment/sop.phtml';
        }

        if ($this->config->getUseIFrame()) {
            return 'CyberSource_SecureAcceptance::payment/wm-iframe.phtml';
        }

        return 'CyberSource_SecureAcceptance::payment/wm.phtml';
    }
}
