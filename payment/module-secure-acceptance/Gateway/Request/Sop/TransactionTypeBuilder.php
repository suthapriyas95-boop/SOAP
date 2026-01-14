<?php
/*
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\SecureAcceptance\Gateway\Request\Sop;


class TransactionTypeBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    public function __construct(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader)
    {
        $this->subjectReader = $subjectReader;
    }

    public function build(array $buildSubject)
    {

        $payment = $this->subjectReader->readPayment($buildSubject)->getPayment();

        $operations = ['authorization'];

        if (
            $payment->getMethodInstance()->getConfigPaymentAction()
            == \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE
        ) {
            $operations = ['sale'];
        }

        if ($payment->getAdditionalInformation(\Magento\Vault\Model\Ui\VaultConfigProvider::IS_ACTIVE_CODE)) {
            $operations[] = 'create_payment_token';
        }

        return ['transaction_type' => implode(',', $operations)];
    }
}
