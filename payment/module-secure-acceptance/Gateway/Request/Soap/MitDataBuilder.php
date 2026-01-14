<?php

namespace CyberSource\SecureAcceptance\Gateway\Request\Soap;

class MitDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    public function __construct(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader)
    {
        $this->subjectReader = $subjectReader;
    }

    /**
     * Builds MIT fields for admin token payments
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $request = [];

        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();

        /** @var \Magento\Vault\Model\PaymentToken $vaultPaymentToken */
        $vaultPaymentToken = $payment->getExtensionAttributes()->getVaultPaymentToken();

        if (is_null($vaultPaymentToken) || $vaultPaymentToken->isEmpty()) {
            return [];
        }

        $request['subsequentAuthStoredCredential'] = 'true';

        return $request;
    }

    private function getTokenDetails($token)
    {
        return json_decode($token->getTokenDetails() ?: '{}', true);
    }
}
