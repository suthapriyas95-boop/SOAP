<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\WeChatPay\Gateway\Response;

use CyberSource\WeChatPay\Gateway\Config\Config;

class StatusResponseHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{
    /**
     * @var \CyberSource\Core\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @param \CyberSource\Core\Gateway\Helper\SubjectReader $subjectReader
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        \CyberSource\Core\Gateway\Helper\SubjectReader $subjectReader,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        $this->subjectReader = $subjectReader;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @inheritDoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        $apStatus = $response['apCheckStatusReply']->paymentStatus ?? '';

        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        $order = $this->orderRepository->get($paymentDO->getOrder()->getId());
        $payment = $order->getPayment();

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment->setTransactionId($payment->getLastTransId());

        $normalizedStatus = strtolower($apStatus ?? '');

        $payment->setAdditionalInformation('wcpStatus', $normalizedStatus);

        switch ($normalizedStatus) {
            case Config::PAYMENT_STATUS_SETTLED:
                $payment->setIsTransactionApproved(true)->update(false);
                $this->orderRepository->save($order);
                break;

            case Config::PAYMENT_STATUS_FAILED:
            case Config::PAYMENT_STATUS_ABANDONED:
                $payment->setIsTransactionDenied(true)->update(false);
                $this->orderRepository->save($order);

                break;

            case Config::PAYMENT_STATUS_REFUNDED:
            case Config::PAYMENT_STATUS_PENDING:
            default:
                return;
        }
    }
}
