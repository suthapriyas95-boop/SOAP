<?php

namespace CyberSource\SecureAcceptance\Gateway\Response\Soap;

class MitDetailsHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{
    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var \Magento\Vault\Model\PaymentTokenRepository
     */
    private $paymentTokenRepository;

    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader,
        \Magento\Vault\Model\PaymentTokenRepository $paymentTokenRepository
    ) {
        $this->subjectReader = $subjectReader;
        $this->paymentTokenRepository = $paymentTokenRepository;
    }

    /**
     * Handler MIT-related data for admin token payments
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $payment = $paymentDO->getPayment();
        $paymentNetworkTransactionID = 0;

        if (!$payment instanceof \Magento\Sales\Model\Order\Payment) {
            return;
        }

        $extensionAttributes = $payment->getExtensionAttributes();

        /** @var \Magento\Vault\Model\PaymentToken $vaultPaymentToken */
        $vaultPaymentToken = $extensionAttributes ? $extensionAttributes->getVaultPaymentToken() : null;

        if (is_null($vaultPaymentToken) || $vaultPaymentToken->isEmpty()) {
            return;
        }

        if (!isset($response['ccAuthReply'])) {
            return;
        }

        if (property_exists($response['ccAuthReply'], 'paymentNetworkTransactionID') && $response['ccAuthReply']->paymentNetworkTransactionID) {
            $paymentNetworkTransactionID = $response['ccAuthReply']->paymentNetworkTransactionID;
        }

        $details = $this->getTokenDetails($vaultPaymentToken);

        if (isset($details['paymentNetworkTransactionID'])) {
            return;
        }

        $details['paymentNetworkTransactionID'] = $paymentNetworkTransactionID;

        $vaultPaymentToken->setTokenDetails(json_encode($details));

        $this->paymentTokenRepository->save($vaultPaymentToken);
    }

    private function getTokenDetails($token)
    {
        return json_decode($token->getTokenDetails() ?: '{}', true);
    }
}
