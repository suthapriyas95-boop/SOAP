<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Gateway\Request;

use CyberSource\SecureAcceptance\Gateway\Config\Config;
use CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader;
use CyberSource\SecureAcceptance\Helper\RequestDataBuilder;
use CyberSource\SecureAcceptance\Helper\Vault;
use Magento\Payment\Gateway\Helper\ContextHelper;

abstract class AbstractRequest
{

    const TRANSPARENT_RESPONSE_KEY = 'cybersource_transparent_response';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var RequestDataBuilder
     */
    protected $requestDataBuilder;

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var Vault
     */
    protected $vaultHelper;

    public function __construct(
        Config $config,
        SubjectReader $subjectReader,
        RequestDataBuilder $requestDataBuilder,
        Vault $vaultHelper
    ) {
        $this->config = $config;
        $this->subjectReader = $subjectReader;
        $this->requestDataBuilder = $requestDataBuilder;
        $this->vaultHelper = $vaultHelper;
    }

    protected function getValidPaymentInstance(array $buildSubject)
    {
        /** @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface $paymentDO */
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();

        ContextHelper::assertOrderPayment($payment);

        return $payment;
    }
}
