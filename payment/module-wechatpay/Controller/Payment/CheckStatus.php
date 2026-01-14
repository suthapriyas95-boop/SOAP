<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\WeChatPay\Controller\Payment;

use Magento\Sales\Model\Order;

class CheckStatus extends \Magento\Framework\App\Action\Action
{
    const COMMAND_CODE = 'status';

    /**
     * @var \Magento\Payment\Gateway\Command\CommandManagerInterface
     */
    private $commandManager;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    private $formKeyValidator;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var \CyberSource\Core\Service\OrderToQuoteInterface
     */
    private $orderToQuote;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var \CyberSource\WeChatPay\Model\CurrentOrderResolver
     */
    private $currentOrderResolver;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $session;

    /**
     * @var \CyberSource\Core\Model\LoggerInterface
     */
    private $logger;

    /**
     * @var \CyberSource\WeChatPay\Model\StatusCheckMessageMapper
     */
    private $statusCheckMessageMapper;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Payment\Gateway\Command\CommandManagerInterface $commandManager
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \CyberSource\Core\Service\OrderToQuoteInterface $orderToQuote
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \CyberSource\WeChatPay\Model\CurrentOrderResolver $currentOrderResolver
     * @param \CyberSource\Core\Model\LoggerInterface $logger
     * @param \CyberSource\WeChatPay\Model\StatusCheckMessageMapper $statusCheckMessageMapper
     * @param \Magento\Checkout\Model\Session $session
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Payment\Gateway\Command\CommandManagerInterface $commandManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \CyberSource\Core\Service\OrderToQuoteInterface $orderToQuote,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \CyberSource\WeChatPay\Model\CurrentOrderResolver $currentOrderResolver,
        \CyberSource\Core\Model\LoggerInterface $logger,
        \CyberSource\WeChatPay\Model\StatusCheckMessageMapper $statusCheckMessageMapper,
        \Magento\Checkout\Model\Session $session
    ) {
        parent::__construct($context);
        $this->commandManager = $commandManager;
        $this->formKeyValidator = $formKeyValidator;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->orderToQuote = $orderToQuote;
        $this->cartRepository = $cartRepository;
        $this->currentOrderResolver = $currentOrderResolver;
        $this->logger = $logger;
        $this->session = $session;
        $this->statusCheckMessageMapper = $statusCheckMessageMapper;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            if (!$this->getRequest()->isPost()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Wrong method.'));
            }

            if (!$this->formKeyValidator->validate($this->getRequest())) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Invalid formkey.'));
            }

            $orderId = $this->getRequest()->getParam('order_id');
            if (!$order = $this->currentOrderResolver->get($orderId)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Order does not exist.'));
            }

            $oldState = $order->getState();

            $this->commandManager->executeByCode(
                self::COMMAND_CODE,
                $order->getPayment()
            );

            $order = $this->currentOrderResolver->get($order->getId());

            $wcpStatus = $order->getPayment()->getAdditionalInformation('wcpStatus');

            if (
                !$orderId
                && (
                    ($oldState === Order::STATE_PAYMENT_REVIEW && $order->getState() === Order::STATE_CANCELED)
                    || $this->getRequest()->getParam('final')
                    || $this->getRequest()->getParam('cancel')
                )
            ) {
                // payment has failed on checkout, we must add items back to the quote
                $quote = $this->session->getQuote();
                $quote = $this->orderToQuote->convertOrderToQuote($order->getId(), $quote);
                $this->cartRepository->save($quote);
            }

            $message = $this->getMessage($wcpStatus);

            $result->setData(
                [
                    'success' => true,
                    'state' => $order->getState(),
                    'status' => $order->getStatus(),
                    'is_settled' => $order->getState() == Order::STATE_PROCESSING,
                    'is_failed' => $this->isFailed($order),
                    'status_msg' => $message,
                ]
            );
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            $result->setData(
                [
                    'success' => false,
                    'error_msg' => $e->getMessage()
                ]
            );
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            $result->setData(
                [
                    'success' => false,
                    'error_msg' => __('Unable to complete status request.')
                ]
            );
        }

        return $result;
    }

    /**
     * @param string $wcpStatus
     *
     * @return \Magento\Framework\Phrase|string
     */
    private function getMessage($wcpStatus)
    {
        $message = $this->statusCheckMessageMapper->getMessage(
            $wcpStatus
        );

        if ($wcpStatus !== \CyberSource\WeChatPay\Gateway\Config\Config::PAYMENT_STATUS_PENDING) {
            return $message;
        }

        if ($this->getRequest()->getParam('cancel')) {
            return __('The Payment was cancelled. You will now be redirected to your shopping cart.');
        }

        if ($this->getRequest()->getParam('final')) {
            return __('Your payment could not be processed and you have not been charged. You will now be redirected to your shopping cart.');
        }

        return $message;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     *
     * @return bool
     */
    private function isFailed(\Magento\Sales\Api\Data\OrderInterface $order): bool
    {
        return $order->getState() == Order::STATE_CANCELED
            || $this->getRequest()->getParam('cancel')
            || $this->getRequest()->getParam('final');
    }
}
