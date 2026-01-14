<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\KlarnaFinancial\Gateway\Request;


class StatusRequestBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{

    /**
     * @var \CyberSource\KlarnaFinancial\Gateway\Helper\SubjectReader
     */
    private $subjectReader;
    /**
     * @var \CyberSource\KlarnaFinancial\Gateway\Config\Config
     */
    private $config;

    public function __construct(
        \CyberSource\KlarnaFinancial\Gateway\Helper\SubjectReader $subjectReader,
        \CyberSource\KlarnaFinancial\Gateway\Config\Config $config
    ) {
        $this->subjectReader = $subjectReader;
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject)
    {

        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $order = $paymentDO->getOrder();
        $request = [
            'merchantReferenceCode' => $order->getOrderIncrementId(),
            'apPaymentType' => \CyberSource\KlarnaFinancial\Helper\RequestDataBuilder::PAYMENT_TYPE,
            'apCheckStatusService' => [
                'run' => 'true',
                'checkStatusRequestID' => $paymentDO->getPayment()->getLastTransId(),
            ]
        ];

        return $request;
    }
}
