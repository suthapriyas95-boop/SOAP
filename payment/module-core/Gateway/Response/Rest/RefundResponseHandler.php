<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Gateway\Response\Rest;

use Magento\Payment\Gateway\Response\HandlerInterface;

class RefundResponseHandler implements HandlerInterface
{
    const REQUEST_ID = 'id';
    /**
     * @var \CyberSource\Core\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    public function __construct(\CyberSource\Core\Gateway\Helper\SubjectReader $subjectReader)
    {
        $this->subjectReader = $subjectReader;
    }

    /**
     * Handles refund transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     *
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        /** @var $payment \Magento\Sales\Model\Order\Payment */

        $paymentDo = $this->subjectReader->readPayment($handlingSubject);
        $payment = $paymentDo->getPayment();

        $payment->setTransactionId($response[self::REQUEST_ID]);
        $payment->setShouldCloseParentTransaction(!$payment->getCreditmemo()->getInvoice()->canRefund());
    }
}
