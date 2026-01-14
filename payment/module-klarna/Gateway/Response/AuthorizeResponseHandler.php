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

class AuthorizeResponseHandler extends AbstractResponseHandler implements HandlerInterface
{
    /**
     * @var RequestDataBuilder
     */
    private $requestDataBuilder;

    /**
     * @var SOAPClient
     */
    private $soapClient;

    /**
     * @var TransferFactory
     */
    private $transferFactory;

    /**
     * AuthorizeResponseHandler constructor.
     * @param RequestDataBuilder $requestDataBuilder
     * @param SOAPClient $SOAPClient
     * @param TransferFactory $transferFactory
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        RequestDataBuilder $requestDataBuilder,
        SOAPClient $SOAPClient,
        TransferFactory $transferFactory,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        SubjectReader $subjectReader
    ) {
        $this->requestDataBuilder = $requestDataBuilder;
        $this->soapClient = $SOAPClient;
        $this->transferFactory = $transferFactory;

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
        $payment = $this->handleAuthorizeResponse($payment, $response);

        $merchantUrl = $response['apAuthReply']->merchantURL ?? null;
        $payment->setAdditionalInformation('merchantUrl', $merchantUrl);

        $apStatus = $response['apAuthReply']->paymentStatus ?? '';

        $payment->setIsTransactionPending(strtolower($apStatus) == static::PAYMENT_STATUS_PENDING);
        $payment->setIsTransactionClosed(false);
    }
}
