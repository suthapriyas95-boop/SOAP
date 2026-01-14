<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\Core\Gateway\Request\Rest;


class OperationBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    /**
     * @var string
     */
    private $operationName;

    /**
     * @var \CyberSource\Core\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * OperationBuilder constructor.
     *
     * @param \CyberSource\Core\Gateway\Helper\SubjectReader $subjectReader
     * @param string $operationName
     */
    public function __construct(
        \CyberSource\Core\Gateway\Helper\SubjectReader $subjectReader,
        $operationName
    ) {
        $this->subjectReader = $subjectReader;
        $this->operationName = $operationName;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        $id = $payment->getParentTransactionId() ?: $payment->getRefundTransactionId();

        return [
            \CyberSource\Core\Gateway\Http\Client\Rest::KEY_URL_PARAMS => [
                $id,
                $this->operationName
            ]
        ];
    }
}
