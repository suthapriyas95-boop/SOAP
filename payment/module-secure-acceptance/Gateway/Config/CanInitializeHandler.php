<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace CyberSource\SecureAcceptance\Gateway\Config;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use Magento\Sales\Model\Order\Payment;

class CanInitializeHandler implements ValueHandlerInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Config\Config $config,
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader
    ) {
        $this->config = $config;
        $this->subjectReader = $subjectReader;
    }

    public function handle(array $subject, $storeId = null)
    {
        if ($this->config->isAdmin()) {
            return false;
        }

        $paymentDo = $this->subjectReader->readPayment($subject);

        if ($paymentDo->getPayment()->getMethodInstance()->getCode() == \CyberSource\SecureAcceptance\Model\Ui\ConfigProvider::CC_VAULT_CODE){
            return false;
        }

        return (bool)$this->config->getIsLegacyMode($storeId) && !$this->config->isMicroform();
    }
}
