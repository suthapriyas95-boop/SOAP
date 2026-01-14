<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\SecureAcceptance\Gateway\Response\Soap;


class SubscriptionCreateHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{

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
     * @inheritDoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDo = $this->subjectReader->readPayment($handlingSubject);

        $paySubscriptionCreateReply = $response['paySubscriptionCreateReply'] ?? null;

        if (!$paySubscriptionCreateReply) {
            return;
        }

        if ($token = $paySubscriptionCreateReply->subscriptionID ?? null) {
            $this->paymentTokenManagement->storeTokenIntoPayment($paymentDo->getPayment(), $token);
        }

        if ($instrumentId = $paySubscriptionCreateReply->instrumentIdentifierID ?? null) {
            $this->paymentTokenManagement->storeInstrumentIdIntoPayment($paymentDo->getPayment(), $instrumentId);
        }
    }
}
