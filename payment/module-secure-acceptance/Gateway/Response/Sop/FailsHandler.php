<?php
/*
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\SecureAcceptance\Gateway\Response\Sop;


use CyberSource\SecureAcceptance\Gateway\Validator\ResponseCodeValidator;

class FailsHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{

    private $fieldsToRecord = [
        'message',
        'required_fields',
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
     * @inheritDoc
     */
    public function handle(array $handlingSubject, array $response)
    {

        $responseCode = $response[ResponseCodeValidator::RESULT_CODE] ?? null;

        if (in_array(
            $responseCode,
            [
                ResponseCodeValidator::APPROVED,
                ResponseCodeValidator::DM_REVIEW,
            ]
        )) {
            return;
        }

        $paymentDo = $this->subjectReader->readPayment($handlingSubject);

        /** @var \Magento\Sales\Model\Order $order */
        $order = $paymentDo->getPayment()->getOrder();

        $detailsToAdd = [__('An error occurred during Secure Acceptance transaction:')];

        foreach ($this->fieldsToRecord as $fieldKey) {
            if (!$fieldValue = $response[$fieldKey] ?? null) {
                continue;
            }
            $detailsToAdd[] = $fieldKey . ': ' . $fieldValue;
        }

        if (!method_exists($order, 'addCommentToStatusHistory')) {
            $order->addStatusHistoryComment(implode("\n", $detailsToAdd));
            return;
        }

        $order->addCommentToStatusHistory(implode("\n", $detailsToAdd));

    }
}
