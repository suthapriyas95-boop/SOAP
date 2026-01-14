<?php
/*
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\SecureAcceptance\Gateway\Request\Sop;


class ShippingItemBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    use \Magento\Payment\Helper\Formatter;

    const KEY_SHIPPING_AND_HANDLING = 'shipping_and_handling';

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject)
    {

        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();

        $shippingAmount = $payment->getData('base_shipping_amount');

        if (!$shippingAmount) {
            return [];
        }

        $shippingData = [
                'code' => self::KEY_SHIPPING_AND_HANDLING,
                'quantity' => 1,
                'unit_price' => $this->formatPrice($shippingAmount),
            ];

        if (!$payment instanceof \Magento\Sales\Model\Order\Payment) {
            return [$shippingData];
        }

        $order = $paymentDO->getPayment()->getOrder();
        $shippingData['tax_amount'] = $this->formatPrice($order->getBaseShippingTaxAmount());

        return [$shippingData];
    }


}
