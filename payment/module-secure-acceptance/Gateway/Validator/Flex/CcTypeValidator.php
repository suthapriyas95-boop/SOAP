<?php
/*
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\SecureAcceptance\Gateway\Validator\Flex;


class CcTypeValidator extends \Magento\Payment\Gateway\Validator\AbstractValidator
{

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Config\Config
     */
    private $config;

    /**
     * @var bool
     */
    private $isAdminHtml;

    public function __construct(
        \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory,
        \CyberSource\SecureAcceptance\Gateway\Config\Config $config,
        $isAdminHtml = false
    ) {
        parent::__construct($resultFactory);
        $this->config = $config;
        $this->isAdminHtml = $isAdminHtml;
    }


    /**
     * @inheritDoc
     */
    public function validate(array $validationSubject)
    {
        if (!$this->config->isMicroform() || $this->isAdminHtml) {
            return $this->createResult(
                true
            );
        }

        $payment = $validationSubject['payment'] ?? null;

        if (!$payment) {
            return $this->createResult(
                false,
                ['Payment must be provided.']
            );
        }

        if ($payment->getMethod() != \CyberSource\SecureAcceptance\Model\Ui\ConfigProvider::CODE) {
            return $this->createResult(
                true
            );
        }

        $ccType = $payment->getAdditionalInformation('cardType');

        if (!$ccType) {
            return $this->createResult(
                true
            );
        }

        $allowedCcTypes = $this->config->getCcTypes();

        $isValid = in_array($ccType, explode(',', $allowedCcTypes ?? ''));

        return $this->createResult($isValid, ['Invalid card type.']);
    }
}
