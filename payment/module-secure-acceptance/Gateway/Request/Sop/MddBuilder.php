<?php
/*
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\SecureAcceptance\Gateway\Request\Sop;


class MddBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    const KEY_MERCHANT_DEFINED_DATA = 'merchant_defined_data';

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Request\Soap\DecisionManagerMddBuilder
     */
    private $mddBuilder;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $cartRepository;

    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \CyberSource\SecureAcceptance\Gateway\Request\Soap\DecisionManagerMddBuilder $mddBuilder
    ) {
        $this->subjectReader = $subjectReader;
        $this->cartRepository = $cartRepository;
        $this->mddBuilder = $mddBuilder;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject)
    {

        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        try {
            $quoteId = $paymentDO->getPayment()->getOrder()->getQuoteId();
            $buildSubject['quote'] = $this->cartRepository->get($quoteId);
            $soapResult = $this->mddBuilder->build($buildSubject);

            $result = [];
            $result = array_merge($result, $this->formatMdd($soapResult['merchantDefinedData'] ?? null));
            $result = array_merge($result, $this->formatCustomerData($soapResult['billTo'] ?? null));
            $result = array_merge($result, $this->formatDeviceFingerprint($soapResult['deviceFingerprintID'] ?? null));

            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }

    private function formatMdd($param)
    {
        if (!$param) {
            return [];
        }

        $result = [];

        $prefix = 'merchant_defined_data';
        foreach ($param['mddField'] as $key => $value) {
            $result[$prefix . $value['id']] = $value['_'];
        }

        return $result;
    }

    private function formatCustomerData($param)
    {
        if (!$param['ipAddress'] ?? null) {
            return [];
        }

        return ['customer_ip_address' => $param['ipAddress']];
    }

    private function formatDeviceFingerprint($param)
    {

        if (!$param) {
            return [];
        }

        return ['device_fingerprint_id' => $param, 'device_fingerprint_raw' => "true"];
    }
}
