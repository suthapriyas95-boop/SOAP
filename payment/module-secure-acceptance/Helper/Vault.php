<?php

namespace CyberSource\SecureAcceptance\Helper;

use Magento\Framework\Session\SessionManagerInterface;

class Vault
{

    /**
     * @var SessionManagerInterface
     */
    private $checkoutSession;
    
    /**
     * VaultHelper constructor.
     * @param SessionManagerInterface $checkoutSession
     */
    public function __construct(SessionManagerInterface $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param $vaultIsEnabled
     * @return void
     */
    public function setVaultEnabled($vaultIsEnabled)
    {
        $this->checkoutSession->unsVaultIsEnabled();
        $quote = $this->checkoutSession->getQuote();
        $quote->getPayment()->setAdditionalInformation(\Magento\Vault\Model\Ui\VaultConfigProvider::IS_ACTIVE_CODE, (bool)$vaultIsEnabled);
        $quote->save();

        if ($vaultIsEnabled) {
            $this->checkoutSession->setVaultIsEnabled($vaultIsEnabled);
        }
    }

    /**
     * @return bool
     */
    public function getVaultEnabled()
    {
        return $this->checkoutSession->getVaultIsEnabled();
    }

    /**
     * @return void
     */
    public function unsVaultEnabled()
    {
        $this->checkoutSession->unsVaultIsEnabled();
    }
}
