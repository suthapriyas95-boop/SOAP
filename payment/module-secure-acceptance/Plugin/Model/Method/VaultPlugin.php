<?php

namespace CyberSource\SecureAcceptance\Plugin\Model\Method;

use Magento\Backend\Model\Session\Quote;
use Magento\Vault\Model\Method\Vault;
use CyberSource\SecureAcceptance\Model\PaymentTokenManagement;
use CyberSource\SecureAcceptance\Model\Ui\ConfigProvider;

class VaultPlugin
{

    /**
     * @var Quote
     */
    private $quote;

    /**
     * @var PaymentTokenManagement
     */
    private $tokenManagement;

    /**
     * VaultPlugin constructor.
     * @param PaymentTokenManagement $tokenManagement
     */
    public function __construct(
        Quote $quote,
        PaymentTokenManagement $tokenManagement
    ) {
        $this->quote = $quote;
        $this->tokenManagement = $tokenManagement;
    }

    /**
     * @param Vault $subject
     * @param $result
     * @return boolean
     */
    public function afterIsAvailable(
        Vault $subject,
        $result
    ) {
        if (!$result) {
            return $result;
        }
        if ($subject->getCode() != ConfigProvider::CC_VAULT_CODE) {
            return $result;
        }

        if (!$customerId = $this->quote->getCustomerId()) {
            return false; // no vault for a blank customer
        }

        $tokens = $this->tokenManagement->getAvailableTokens($customerId, ConfigProvider::CODE);
        if (empty($tokens)) {
            return false;
        }

        return $result;
    }

    public function afterGetFormBlockType(Vault $subject, $result)
    {

        if ($subject->getCode() != ConfigProvider::CC_VAULT_CODE) {
            return $result;
        }

        return \CyberSource\SecureAcceptance\Block\Vault\Form::class;
    }
}
