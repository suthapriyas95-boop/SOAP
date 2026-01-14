<?php

namespace CyberSource\SecureAcceptance\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;

class VaultCaptureRequest extends AbstractRequest implements BuilderInterface
{
    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $this->vaultHelper->unsVaultEnabled();

        $payment = $this->getValidPaymentInstance($buildSubject);
        $request = $this->requestDataBuilder->buildCaptureRequest($payment, $buildSubject['amount']);

        return (array) $request;
    }
}
