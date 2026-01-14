<?php

namespace CyberSource\PayPal\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use \Magento\Payment\Gateway\Helper\SubjectReader;

class VaultResponseHandler implements HandlerInterface
{
    const REASON_CODE = "reasonCode";
    const REQUEST_ID = "requestID";
    const DECISION = "decision";

    const CODE_DM_REVIEW = 480;

    /**
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);
        $payment = $paymentDO->getPayment();

        /** @var $payment \Magento\Sales\Model\Order\Payment */
        $payment->setTransactionId($response[self::REQUEST_ID]);
        $payment->setLastTransId($response[self::REQUEST_ID]);
        $payment->setCcTransId($response[self::REQUEST_ID]);

        $payment->setIsTransactionClosed(0);
        $payment->setIsTransactionPending(0);
        $payment->setIsFraudDetected(0);

        if ($response[self::REASON_CODE] == self::CODE_DM_REVIEW) {
            $payment->setIsTransactionPending(1);
            $payment->setIsFraudDetected(1);
        }
    }
}
