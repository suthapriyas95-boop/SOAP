<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\KlarnaFinancial\Gateway\Response;


class StatusResponseHandler implements \Magento\Payment\Gateway\Response\HandlerInterface
{
    /**
     * @var \CyberSource\KlarnaFinancial\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \Magento\Sales\Model\Order\StatusResolver
     */
    private $statusResolver;

    /**
     * @var \Magento\Sales\Model\Order\Payment\State\CommandInterface
     */
    private $stateCommand;

    public function __construct(
        \CyberSource\KlarnaFinancial\Gateway\Helper\SubjectReader $subjectReader,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Order\Payment\State\CommandInterface $stateCommand,
        \Magento\Sales\Model\Order\StatusResolver $statusResolver
    ) {
        $this->subjectReader = $subjectReader;
        $this->orderRepository = $orderRepository;
        $this->statusResolver = $statusResolver;
        $this->stateCommand = $stateCommand;
    }

    /**
     * @inheritDoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);

        $order = $this->orderRepository->get($paymentDO->getOrder()->getId());
        $payment = $paymentDO->getPayment();

        $apStatus = $response['apCheckStatusReply']->paymentStatus ?? '';

        switch ($apStatus) {
            case AbstractResponseHandler::PAYMENT_STATUS_AUTHORIZED:
                $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                $order->setStatus($this->statusResolver->getOrderStatusByState($order, $order->getState()));
                $message = $this->stateCommand->execute($payment, $payment->getBaseAmountAuthorized(), $order);
                $payment->addTransactionCommentsToOrder(null, $message);
                break;
            case AbstractResponseHandler::PAYMENT_STATUS_ABANDONED:
            case AbstractResponseHandler::PAYMENT_STATUS_FAILED:
                $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED);
                $order->setStatus($this->statusResolver->getOrderStatusByState($order, $order->getState()));
                $payment->addTransactionCommentsToOrder(null, __('Transaction rejected by Klarna gateway.'));
            break;
            case AbstractResponseHandler::PAYMENT_STATUS_PENDING:
            default:
                return;
        }

        $this->orderRepository->save($order);

    }
}
