<?php

namespace CyberSource\SecureAcceptance\Gateway\Request\Soap;

use Magento\Payment\Helper\Formatter;

class ParentTransactionIdBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var string
     */
    private $parentTransactionName;

    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader,
        string $parentTransactionName
    ) {
        $this->subjectReader = $subjectReader;
        $this->parentTransactionName = $parentTransactionName;
    }

    /**
     * Builds Order Data
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        //used getRefundTransactionId for refund
        $id = $payment->getParentTransactionId() ? $payment->getParentTransactionId() : $payment->getRefundTransactionId();
        return [$this->parentTransactionName => $id];
    }
}
