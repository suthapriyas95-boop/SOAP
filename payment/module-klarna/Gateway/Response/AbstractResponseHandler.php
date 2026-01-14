<?php

namespace CyberSource\KlarnaFinancial\Gateway\Response;

use CyberSource\KlarnaFinancial\Gateway\Helper\SubjectReader;
use CyberSource\KlarnaFinancial\Gateway\Validator\ResponseCodeValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\ContextHelper;

abstract class AbstractResponseHandler
{
    const REQUEST_ID = "requestID";
    const REASON_CODE = "reasonCode";
    const DECISION = "decision";
    const MERCHANT_REFERENCE_CODE = "merchantReferenceCode";
    const REQUEST_TOKEN = "requestToken";
    const RECONCILIATION_ID = "reconciliationID";
    const CALL_ID = "callID";
    const CURRENCY = "currency";
    const AMOUNT = "amount";

    const PAYMENT_STATUS_PENDING = 'pending';
    const PAYMENT_STATUS_AUTHORIZED = 'authorized';
    const PAYMENT_STATUS_ABANDONED = 'abandoned';
    const PAYMENT_STATUS_FAILED = 'failed';

    const CAPTURE_TRANSACTION_ID =  'CaptureTransactionId';


    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * AbstractResponseHandler constructor.
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        SubjectReader $subjectReader,
        \Magento\Framework\Serialize\SerializerInterface $serializer
    ) {
        $this->subjectReader = $subjectReader;
        $this->serializer = $serializer;
    }

    /**
     * @param array $buildSubject
     * @return \Magento\Payment\Model\InfoInterface
     */
    protected function getValidPaymentInstance(array $buildSubject)
    {
        /** @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface $paymentDO */
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        /** @var \Magento\Payment\Model\InfoInterface $payment */
        $payment = $paymentDO->getPayment();

        ContextHelper::assertOrderPayment($payment);

        return $payment;
    }

    protected function handleAuthorizeResponse($payment, $response)
    {
        /** @var $payment \Magento\Sales\Model\Order\Payment */

        $payment->setTransactionId($response[self::REQUEST_ID]);
        $payment->setCcTransId($response[self::REQUEST_ID]);
        $payment->setAdditionalInformation('authorize', $this->serializer->serialize($response));

        return $payment;
    }
}
