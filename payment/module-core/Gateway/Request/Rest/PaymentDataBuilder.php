<?php

namespace CyberSource\Core\Gateway\Request\Rest;

use Magento\Payment\Helper\Formatter;

class PaymentDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    use Formatter;

    /**
     * @var \CyberSource\Core\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @param \CyberSource\Core\Gateway\Helper\SubjectReader $subjectReader
     */
    public function __construct(
        \CyberSource\Core\Gateway\Helper\SubjectReader $subjectReader
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

        $request['clientReferenceInformation'] = ['code' => $order->getOrderIncrementId()];

        try {
            $amount = $this->subjectReader->readAmount($buildSubject);
        } catch (\InvalidArgumentException $e) {
            // seems we are doing authorization reversal, getting a full authorized amount
            $amount = $paymentDO->getPayment()->getBaseAmountAuthorized();
        }

        $request['orderInformation'] = [
            'amountDetails' => [
                'currency' => $paymentDO->getOrder()->getCurrencyCode(),
                'totalAmount' => $this->formatPrice($amount),
            ],
        ];

        return $request;
    }
}
