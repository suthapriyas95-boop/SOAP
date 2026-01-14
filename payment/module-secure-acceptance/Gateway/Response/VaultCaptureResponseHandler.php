<?php

namespace CyberSource\SecureAcceptance\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;

class VaultCaptureResponseHandler extends AbstractResponseHandler implements HandlerInterface
{
    const REASON_CODE = "reasonCode";
    const REQUEST_ID = "requestID";

    /**
     * Handles fraud messages
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        $payment = $this->getValidPaymentInstance($handlingSubject);

        /** @var $payment \Magento\Sales\Model\Order\Payment */
        $payment->setTransactionId($response[self::REQUEST_ID]);

        $payment->setIsTransactionClosed(false);
        $payment->setIsTransactionPending(false);
    }
}
