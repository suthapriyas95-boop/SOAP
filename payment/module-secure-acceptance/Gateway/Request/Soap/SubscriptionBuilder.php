<?php

namespace CyberSource\SecureAcceptance\Gateway\Request\Soap;

class SubscriptionBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{


    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var \CyberSource\SecureAcceptance\Model\PaymentTokenManagement
     */
    private $paymentTokenManagement;

    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader,
        \CyberSource\SecureAcceptance\Model\PaymentTokenManagement $paymentTokenManagement
    ) {
        $this->subjectReader = $subjectReader;
        $this->paymentTokenManagement = $paymentTokenManagement;
    }

    /**
     * Builds Subscription data request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {

        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();

        $token = $this->paymentTokenManagement->getTokenFromPayment($payment);

        if (!$token) {
            throw new \InvalidArgumentException('Subscription Id must be provided');
        }

        return [
            'recurringSubscriptionInfo' => [
                'subscriptionID' => $token,
            ],
        ];
    }
}
