<?php

namespace CyberSource\SecureAcceptance\Gateway\Response\Soap;

class AdditionalInfoHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{
    //todo resolve missing fields

    const RESPONSE_KEYS = [
        '' => [
            'merchantReferenceCode' => 'req_reference_number',
            'requestID' => 'transaction_id',
//            'decision' => 'decision',
            'reasonCode' => 'reason_code',
            'requestToken' => 'request_token',
        ],
        'ccAuthReply' => [
            'amount' => 'auth_amount',
            'authorizationCode' => 'auth_code',
            'avsCode' => 'auth_avs_code',
            'avsCodeRaw' => 'auth_avs_code_raw',
            'authorizedDateTime' => 'auth_time',
            'processorResponse' => 'auth_response',
            'reconciliationID' => 'auth_trans_ref_no',
        ],
        'decisionReply' => [
            'casePriority' => 'decision_case_priority',
//            'decision_early_rcode',
//            'decision_early_reason_code',
//            'decision_early_return_code',
//            'decision_rcode',
//            'decision_reason_code',
//            'decision_return_code',
//            'decision_rflag',
//            'decision_rmsg',

        ],
        'afsReply' => [
//            'score_identity_info',?
//            'score_rcode',
//            'score_return_code',
//            'score_rflag',
//            'score_rmsg',
            'reasonCode' => 'score_reason_code',
            'afsResult' => 'score_score_result',
            'hostSeverity' => 'score_host_severity',
            'consumerLocalTime' => 'score_time_local',
            'afsFactorCode' => 'score_factors??',
            'addressInfoCode' => 'score_address_info',
            'internetInfoCode' => 'score_internet_info',
            'phoneInfoCode' => 'score_phone_info',
            'suspiciousInfoCode' => 'score_suspicious_info',
            'velocityInfoCode' => 'score_velocity_info',
            'scoreModelUsed' => 'score_model_used',
//            'cardBin' => '',
            'binCountry' => 'score_bin_country',
            'cardAccountType' => 'score_card_account_type',
            'cardScheme' => 'score_card_scheme',
            'cardIssuer' => 'score_card_issuer',
        ]
    ];

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    /**
     * Handles additional information
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $payment = $paymentDO->getPayment();

        if (!$payment instanceof \Magento\Sales\Model\Order\Payment) {
            return;
        }

        $this->saveAdditionalInfo($response, $payment);
    }

    /**
     * @param $object
     * @param $payment \Magento\Sales\Model\Order\Payment
     * @param string $objectName
     */
    private function saveAdditionalInfo($object, $payment, $objectName = '')
    {
        foreach ($object as $responseKey => $value) {
            $key = $objectName ? $objectName . '_' . $responseKey : $responseKey;
            if (is_object($value) || is_array($value)) {
                $this->saveAdditionalInfo($value, $payment, $key);
                continue;
            }
            $payment->setAdditionalInformation($key, $value);

            //duplicate value for compatibility with the previous version
            if ($oldKey = self::RESPONSE_KEYS[$objectName][$responseKey] ?? '') {
                $payment->setAdditionalInformation($oldKey, $value);
            }
        }
    }
}
