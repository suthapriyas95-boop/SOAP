<?php

namespace CyberSource\PayPal\Plugin\Vault\Model\Method;

class VaultPlugin
{

    /**
     * Plugin method.
     *
     * Overrides payment action value for PayPal Vault payments to authorize_capture because it's the only one
     * method supported for billing agreements.
     *
     * @param \Magento\Vault\Model\Method\Vault $subject
     * @param $result
     * @return string
     */
    public function afterGetConfigPaymentAction(\Magento\Vault\Model\Method\Vault $subject, $result)
    {
        if ($subject->getProviderCode() !== \CyberSource\PayPal\Model\Config::CODE) {
            return $result;
        }

        return \CyberSource\PayPal\Model\Payment::ACTION_AUTHORIZE_CAPTURE;
    }
}
