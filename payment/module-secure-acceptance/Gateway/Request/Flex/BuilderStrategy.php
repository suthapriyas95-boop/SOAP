<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\SecureAcceptance\Gateway\Request\Flex;


class BuilderStrategy implements \Magento\Payment\Gateway\Request\BuilderInterface
{

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Config\Config
     */
    private $config;

    /**
     * @var \Magento\Payment\Gateway\Request\BuilderInterface
     */
    private $microformBuilder;

    /**
     * @var \Magento\Payment\Gateway\Request\BuilderInterface
     */
    private $standardBuilder;

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Config\Config $config,
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader,
        \Magento\Payment\Gateway\Request\BuilderInterface $microformBuilder,
        \Magento\Payment\Gateway\Request\BuilderInterface $standardBuilder
    ) {
        $this->config = $config;
        $this->subjectReader = $subjectReader;
        $this->microformBuilder = $microformBuilder;
        $this->standardBuilder = $standardBuilder;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject)
    {

        $payment = $this->subjectReader->readPayment($buildSubject)->getPayment();

        // always use the standard builder for vault
        if ($payment->getMethod() == \CyberSource\SecureAcceptance\Model\Ui\ConfigProvider::CC_VAULT_CODE) {
            return $this->standardBuilder->build($buildSubject);
        }

        if ($this->config->isMicroform()) {
            return $this->microformBuilder->build($buildSubject);
        }

        return $this->standardBuilder->build($buildSubject);
    }
}
