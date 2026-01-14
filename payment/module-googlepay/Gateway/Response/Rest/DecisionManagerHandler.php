<?php

namespace CyberSource\GooglePay\Gateway\Response\Rest;

class DecisionManagerHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{

    const RESPONSE_CODE_CREATED = 201;
    const  DM_REVIEW_STATUS = 'AUTHORIZED_PENDING_REVIEW';

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var bool
     */
    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader  
    ) {
        $this->subjectReader = $subjectReader;
    }

    /**
     * Handles transaction details
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $payment = $paymentDO->getPayment();

        $status = $response['status'];
        $httpCode = $response['http_code'];
        
        if ($httpCode == self:: RESPONSE_CODE_CREATED && $status != self::DM_REVIEW_STATUS) {
            return;
        }

        if (!$payment instanceof \Magento\Sales\Model\Order\Payment) {
            return;
        }

        $payment->setIsFraudDetected(true);
        $payment->setIsTransactionPending(true);
    }
}
