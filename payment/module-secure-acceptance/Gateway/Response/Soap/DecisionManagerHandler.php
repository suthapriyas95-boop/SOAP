<?php

namespace CyberSource\SecureAcceptance\Gateway\Response\Soap;

class DecisionManagerHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{

    const DM_REVIEW_REASON_CODE = 480;
    const KEY_REASON_CODE = 'reasonCode';

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    public function __construct(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader)
    {
        $this->subjectReader = $subjectReader;
    }

    /**
     * Handles DM fields
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $payment = $paymentDO->getPayment();

        if ($response[self::KEY_REASON_CODE] != self::DM_REVIEW_REASON_CODE) {
            return;
        }

        if (!$payment instanceof \Magento\Sales\Model\Order\Payment) {
            return;
        }

        $payment->setIsFraudDetected(true);
        $payment->setIsTransactionPending(true);
    }
}
