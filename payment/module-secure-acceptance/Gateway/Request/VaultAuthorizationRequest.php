<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Laminas\Http\Request;

class VaultAuthorizationRequest extends AbstractRequest implements BuilderInterface
{

    /**
     * @var bool
     */
    private $isAdmin;

    /**
     * VaultAuthorizationRequest constructor.
     * @param \CyberSource\SecureAcceptance\Gateway\Config\Config $config
     * @param \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader
     * @param \CyberSource\SecureAcceptance\Helper\RequestDataBuilder $requestDataBuilder
     * @param bool $isAdmin
     */
    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Config\Config $config,
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader,
        \CyberSource\SecureAcceptance\Helper\RequestDataBuilder $requestDataBuilder,
        \CyberSource\SecureAcceptance\Helper\Vault $vault,
        $isAdmin = false
    ) {
        $this->isAdmin = $isAdmin;
        parent::__construct($config, $subjectReader, $requestDataBuilder, $vault);
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {

        $this->vaultHelper->unsVaultEnabled();
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $this->getValidPaymentInstance($buildSubject);

        /** @var \Magento\Vault\Model\PaymentToken $vaultPaymentToken */
        $vaultPaymentToken = $payment->getExtensionAttributes()->getVaultPaymentToken();

        if ($vaultPaymentToken !== null && !$vaultPaymentToken->isEmpty()) {

            /**
             * When order is placed with multishipping we need to build requestData from order not from quote
             */
            $isMultiShipping = false;
            if ($payment->getOrder()->getQuote() !== null) {
                $isMultiShipping = $payment->getOrder()->getQuote()->getIsMultiShipping();
            }

            if ($isMultiShipping) {
                $requestData = $this->requestDataBuilder->buildSilentRequestData(
                    null,
                    $vaultPaymentToken->getGatewayToken(),
                    null,
                    $payment->getOrder()
                );
            } elseif ($this->isAdmin) {
                $requestData = $this->requestDataBuilder->buildSilentRequestData(
                    $paymentDO->getOrder()->getBillingAddress()->getEmail(),
                    $vaultPaymentToken->getGatewayToken(),
                    null,
                    $payment->getOrder(),
                    $paymentDO->getOrder()->getCurrencyCode(),
                    ['merchant_defined_data24' => 'token_payment']
                );
            } else {
                //vault token payment
                $requestData = $this->requestDataBuilder->buildSilentRequestData(
                    null,
                    $vaultPaymentToken->getGatewayToken(),
                    null,
                    null,
                    null,
                    ['merchant_defined_data24' => 'token_payment']
                );
            }
        }

        $postUrl = $this->config->getSopServiceUrl();
        if ($this->config->isTestMode()) {
            $postUrl = $this->config->getSopServiceUrlTest();
        }

        $requestData['method'] = Request::METHOD_POST;
        $requestData['uri'] = $postUrl . '/silent/pay';

        return $requestData;
    }
}
