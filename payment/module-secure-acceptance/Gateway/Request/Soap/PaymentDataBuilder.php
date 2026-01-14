<?php

namespace CyberSource\SecureAcceptance\Gateway\Request\Soap;

use Magento\Payment\Helper\Formatter;

class PaymentDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    use Formatter;

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @param \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader
     */
    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    /**
     * Builds Order Data
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $request = [];

        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $order = $paymentDO->getOrder();

        $request['merchantReferenceCode'] = $order->getOrderIncrementId();

        try {
            $amount = $this->subjectReader->readAmount($buildSubject);
        } catch (\InvalidArgumentException $e) {
            // seems we are doing authorization reversal, getting a full authorized amount
            $amount = $paymentDO->getPayment()->getBaseAmountAuthorized();
        }

        $request['purchaseTotals'] = [
            'currency' => $paymentDO->getOrder()->getCurrencyCode(),
            'grandTotalAmount' => $this->formatPrice($amount),
        ];

        return $request;
    }
}
