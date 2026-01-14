<?php

namespace CyberSource\Core\Gateway\Request\Rest;

class CaptureDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    const CC_CAPTURE_SERVICE_TOTAL_COUNT = 99;

    /**
     * @var \CyberSource\Core\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var \CyberSource\Core\Model\LoggerInterface
     */
    protected $logger;

    /**
     * @param \CyberSource\Core\Gateway\Helper\SubjectReader $subjectReader
     */
    public function __construct(
        \CyberSource\Core\Gateway\Helper\SubjectReader $subjectReader,
        \CyberSource\Core\Model\LoggerInterface $logger
    ) {
        $this->subjectReader = $subjectReader;
        $this->logger = $logger;
    }

    /**
     * Builds Order Data
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $this->logger->info('capturedatabuilder');
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
            'processingInformation' => [
                'capture' => 'true',
                'captureOptions' => [
                    'captureSequenceNumber' => $isFinalCapture ? static::CC_CAPTURE_SERVICE_TOTAL_COUNT : $captureSequence,
                    'totalCaptureCount' => static::CC_CAPTURE_SERVICE_TOTAL_COUNT,
                ]
            ]
        ];
    }
}
