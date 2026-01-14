<?php
/**
 * Copyright © 2021 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\WeChatPay\Gateway\Request;

class ParentTransactionIdBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    /**
     * @var \CyberSource\WeChatPay\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var string
     */
    private $parentTransactionIdFieldName;

    /**
     * @param \CyberSource\WeChatPay\Gateway\Helper\SubjectReader $subjectReader
     * @param string $parentTransactionIdFieldName
     */
    public function __construct(
        \CyberSource\WeChatPay\Gateway\Helper\SubjectReader $subjectReader,
        string $parentTransactionIdFieldName
    ) {
        $this->subjectReader = $subjectReader;
        $this->parentTransactionIdFieldName = $parentTransactionIdFieldName;
    }

    /**
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $payment = $this->subjectReader->readPayment($buildSubject)->getPayment();
        $parentTransactionId = $payment->getParentTransactionId() ?: $payment->getRefundTransactionId();
        return [$this->parentTransactionIdFieldName => $parentTransactionId];
    }
}
