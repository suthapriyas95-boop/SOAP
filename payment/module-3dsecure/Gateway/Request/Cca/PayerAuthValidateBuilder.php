<?php

namespace CyberSource\ThreeDSecure\Gateway\Request\Cca;

class PayerAuthValidateBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }      
                   
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        $processorTransactionId = $payment->getAdditionalInformation('payer_auth_enroll_transaction_id');
        return [
            'payerAuthValidateService' => [
                'run' => 'true',
                'authenticationTransactionID'=>$processorTransactionId,
            ]
        ];
    }
}
