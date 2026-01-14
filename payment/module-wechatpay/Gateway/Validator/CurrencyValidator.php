<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\WeChatPay\Gateway\Validator;

class CurrencyValidator extends \Magento\Payment\Gateway\Validator\AbstractValidator
{
    /**
     * @var \CyberSource\WeChatPay\Gateway\Config\Config
     */
    private $config;

    /**
     * @param \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory
     * @param \CyberSource\WeChatPay\Gateway\Config\Config $config
     */
    public function __construct(
        \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory,
        \CyberSource\WeChatPay\Gateway\Config\Config $config
    ) {
        parent::__construct($resultFactory);
        $this->config = $config;
    }

    /**
     * @param array $validationSubject
     * @return \Magento\Payment\Gateway\Validator\ResultInterface
     */
    public function validate(array $validationSubject)
    {
        if (in_array($validationSubject['currency'], $this->getSupportedCurrencyList())) {
            return $this->createResult(true, []);
        }

        return $this->createResult(false, [__('The currency is not supported by WeChat Pay.')]);
    }

    /**
     * @return string[]
     */
    private function getSupportedCurrencyList()
    {
        return explode(',', $this->config->getValue('currency') ?? '');
    }
}
