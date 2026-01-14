<?php
/**
 *
 */

namespace CyberSource\SecureAcceptance\Gateway\Response\Sop;

use Magento\Vault\Model\Ui\VaultConfigProvider;

class TokenHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{

    const KEY_PAYMENT_TOKEN = 'payment_token';
    const KEY_PAYMENT_TOKEN_INSTRUMENT_IDENTIFIER_ID = 'payment_token_instrument_identifier_id';

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
        \CyberSource\SecureAcceptance\Model\PaymentTokenManagement $paymentTokenManagement
    ) {
        $this->subjectReader = $subjectReader;
        $this->paymentTokenManagement = $paymentTokenManagement;
    }

    /**
     * Stores payment token into payment object
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {

        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $payment = $paymentDO->getPayment();

        $token = $response[self::KEY_PAYMENT_TOKEN] ?? null;

        if (!$token) {
            return;
        }

        $this->paymentTokenManagement->storeTokenIntoPayment($payment, $token);

        if (!$payment->getAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE)) {
            return;
        }

        $tokenData = $this->buildTokenToSave($response);

        if ($tokenData !== null) {
            $payment->setAdditionalInformation(\CyberSource\SecureAcceptance\Gateway\Response\AbstractResponseHandler::TOKEN_DATA, $tokenData);
        }
    }

    /**
     * @param $response
     * @return array
     */
    private function buildTokenToSave($response)
    {
        $cardType = isset($response['req_card_type']) ? $response['req_card_type'] : '';
        $cardNumber = $response['req_card_number']  ?? '';
        if(empty($cardNumber)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Card Number is empty in the Gateway response'));
        }
        $ccLastFour = substr($cardNumber, -4);
        $cardExpiry = isset($response['req_card_expiry_date']) ? $response['req_card_expiry_date'] : '';

        $result = [
            'payment_token' => $response[static::KEY_PAYMENT_TOKEN],
            'card_type' => $cardType,
            'cc_last4' => $ccLastFour,
            'card_expiry_date' => $cardExpiry,
            'instrument_id' => $response[self::KEY_PAYMENT_TOKEN_INSTRUMENT_IDENTIFIER_ID] ?? null,
        ];

        if (preg_match('/^([0-9]{6}).+/', $cardNumber, $matches)) {
            $result['card_bin'] = $matches[1];
        }

        return $result;
    }
}
