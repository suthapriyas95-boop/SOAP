<?php
/**
 *
 */

namespace CyberSource\SecureAcceptance\Gateway\Response\Soap;

class TokenHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{
    const KEY_PAYMENT_TOKEN = 'token';

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Config\Config
     */
    private $config;

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var \CyberSource\SecureAcceptance\Model\PaymentTokenManagement
     */
    private $paymentTokenManagement;

    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader,
        \CyberSource\SecureAcceptance\Model\PaymentTokenManagement $paymentTokenManagement,
        \CyberSource\SecureAcceptance\Gateway\Config\Config $config
    ) {
        $this->subjectReader = $subjectReader;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->config = $config;
    }

    /**
     * Handles vault token data for microform instance
     */
    public function handle(array $handlingSubject, array $response)
    {

        if (!$this->config->isMicroform()) {
            return;
        }

        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $payment = $paymentDO->getPayment();

        $token = $this->paymentTokenManagement->getTokenFromPayment($payment);

        if (!$token) {
            return;
        }

        if (!$payment->getAdditionalInformation(\Magento\Vault\Model\Ui\VaultConfigProvider::IS_ACTIVE_CODE)) {
            return;
        }

        $tokenData = $this->buildTokenToSave($payment, $response);

        if ($tokenData !== null) {
            $payment->setAdditionalInformation(
                \CyberSource\SecureAcceptance\Gateway\Response\AbstractResponseHandler::TOKEN_DATA,
                $tokenData
            );
        }

    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param array $response
     *
     * @return array
     */
    private function buildTokenToSave($payment, $response)
    {

        $cardType = $response['card']->cardType ?? '';
        $cardNumber = $payment->getAdditionalInformation('maskedPan') ?? '';
        $ccLastFour = substr($cardNumber, -4);
        $cardExpiry = $payment->getAdditionalInformation(\CyberSource\SecureAcceptance\Observer\DataAssignObserver::KEY_EXP_DATE);

        $result = [
            'payment_token' => $this->paymentTokenManagement->getTokenFromPayment($payment),
            'card_type' => $cardType,
            'cc_last4' => $ccLastFour,
            'card_expiry_date' => $cardExpiry,
            'instrument_id' => $this->paymentTokenManagement->getInstrumentIdFromPayment($payment),
        ];

        if (preg_match('/^([0-9]{6}).+/', $cardNumber, $matches)) {
            $result['card_bin'] = $matches[1];
        }

        return $result;
    }
}
