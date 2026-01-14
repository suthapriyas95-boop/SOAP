<?php

namespace CyberSource\SecureAcceptance\Gateway\Request\Soap;

use Magento\Payment\Helper\Formatter;

class CaptureSequenceBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{

    const CC_CAPTURE_SERVICE_TOTAL_COUNT = 99;
    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var \CyberSource\Core\Helper\RequestDataBuilder
     */
    private $requestDataBuilder;

    /**
     * @param \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader
     */
    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
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
        $amount = $this->subjectReader->readAmount($buildSubject);
        $payment = $paymentDO->getPayment();
        $order = $payment->getOrder();

        $captureSequence = $order->getInvoiceCollection()->getSize() + 1;
        $isPartialCapture = $order->getBaseGrandTotal() > $amount;
        $isFinalCapture = $order->getBaseTotalDue() <= $amount;

        if (!$isPartialCapture) {
            return [];
        }
        return [
            'totalCount' => static::CC_CAPTURE_SERVICE_TOTAL_COUNT,
            'sequence' => $isFinalCapture ? static::CC_CAPTURE_SERVICE_TOTAL_COUNT : $captureSequence
        ];
    }
}
