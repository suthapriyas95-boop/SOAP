<?php
/**
 * Copyright © 2018 CollinsHarper. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\KlarnaFinancial\Gateway\Response;

use CyberSource\KlarnaFinancial\Gateway\Helper\SubjectReader;
use CyberSource\KlarnaFinancial\Gateway\Http\Client\SOAPClient;
use CyberSource\KlarnaFinancial\Gateway\Http\TransferFactory;
use CyberSource\KlarnaFinancial\Helper\RequestDataBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order;

class CaptureResponseHandler extends AbstractResponseHandler implements HandlerInterface
{
    /**
     * @var RequestDataBuilder
     */
    private $requestDataBuilder;

    /**
     * AuthorizeResponseHandler constructor.
     * @param RequestDataBuilder $requestDataBuilder
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        RequestDataBuilder $requestDataBuilder,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        SubjectReader $subjectReader
    ) {
        $this->requestDataBuilder = $requestDataBuilder;

        parent::__construct($subjectReader, $serializer);
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     * @throws LocalizedException
     */
    public function handle(array $handlingSubject, array $response)
    {
        /** @var $payment \Magento\Sales\Model\Order\Payment */
        $payment = $this->getValidPaymentInstance($handlingSubject);

        $payment->setTransactionId($response[self::REQUEST_ID]);
        $payment->setAdditionalInformation(self::CAPTURE_TRANSACTION_ID, $response[self::REQUEST_ID]);
        $payment->setIsTransactionClosed(1);
        $payment->setIsTransactionPending(false);
        $payment->setIsFraudDetected(false);
    }
}
