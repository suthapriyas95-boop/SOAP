<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Gateway\Request;

use CyberSource\ECheck\Gateway\Config\Config;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

class CaptureRequest extends AbstractRequest implements BuilderInterface
{
    /**
     * Builds request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $buildSubject['payment'];

        /** @var OrderAdapterInterface $order */
        $order = $paymentDO->getOrder();
        $payment = $paymentDO->getPayment();

        if (!$payment instanceof OrderPaymentInterface) {
            throw new \LogicException('Order payment should be provided.');
        }

        $request = $this->buildAuthNodeRequest($this->config->getMerchantId(), $order->getOrderIncrementId());

        $ecDebitService = new \stdClass();
        $ecDebitService->run = "true";
        $request->ecDebitService = $ecDebitService;

        $request->billTo = $this->buildAddress($order->getBillingAddress(), \Magento\Quote\Model\Quote\Address::TYPE_BILLING, $payment);

        if ($order->getShippingAddress()) {
            $request->shipTo = $this->buildAddress($order->getShippingAddress());
        }

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $order->getCurrencyCode();
        $purchaseTotals->grandTotalAmount = $this->formatAmount($order->getGrandTotalAmount());
        $request->purchaseTotals = $purchaseTotals;

        $bankTransitNumber = $payment->getAdditionalInformation('check_bank_transit_number');
        $accountNumber = $payment->getAdditionalInformation('check_account_number');
        $checkNumber = $payment->getAdditionalInformation('check_number');

        $request->check = $this->buildAccountNode($bankTransitNumber, $accountNumber, $checkNumber);

        $request = $this->buildRequestItems($order->getItems(), $request);

        $request->deviceFingerprintID = $this->checkoutSession->getData('fingerprint_id');

        $request->customerID = (!is_null($this->customerSession->getCustomerId())) ? $this->customerSession->getCustomerId() : 'guest';

        $request->merchantDefinedData = $this->buildDecisionManagerFields($payment);

        return (array) $request;
    }
}
