<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Gateway\Request\Soap;

class TransactionIdBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    /**
     * @var \CyberSource\Core\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var string
     */
    private $transactionIdFieldName;

    /**
     * @param \CyberSource\Core\Gateway\Helper\SubjectReader $subjectReader
     * @param string $transactionIdFieldName
     */
    public function __construct(
        \CyberSource\Core\Gateway\Helper\SubjectReader $subjectReader,
        string $transactionIdFieldName
    ) {
        $this->subjectReader = $subjectReader;
        $this->transactionIdFieldName = $transactionIdFieldName;
    }

    /**
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $payment = $this->subjectReader->readPayment($buildSubject)->getPayment();
        return [$this->transactionIdFieldName => $payment->getLastTransId()];
    }
}
