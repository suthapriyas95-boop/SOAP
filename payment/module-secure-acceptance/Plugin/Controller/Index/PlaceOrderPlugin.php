<?php
/*
 * Copyright © 2021 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Plugin\Controller\Index;

class PlaceOrderPlugin
{

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var \CyberSource\Core\Service\OrderToQuoteInterface
     */
    private $orderToQuote;

    /**
     * @var \CyberSource\SecureAcceptance\Model\Backpost\DetectorInterface
     */
    private $backpostDetector;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface|\Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \CyberSource\Core\Service\OrderToQuoteInterface $orderToQuote,
        \CyberSource\SecureAcceptance\Model\Backpost\DetectorInterface $backpostDetector,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Session\SessionManagerInterface $checkoutSession
    ) {
        $this->cartRepository = $cartRepository;
        $this->orderToQuote = $orderToQuote;
        $this->backpostDetector = $backpostDetector;
        $this->messageManager = $messageManager;
        $this->checkoutSession = $checkoutSession;
    }

    public function afterUpdateFailedState(
        \CyberSource\SecureAcceptance\Controller\Index\PlaceOrder $subject,
        $result,
        $order
    ) {
        try {
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $this->checkoutSession->getQuote();

            if ($quote->hasItems() || $this->backpostDetector->isBackpost()) {
                return;
            }

            $quote = $this->orderToQuote->convertOrderToQuote($order->getId(), $quote);
            $this->cartRepository->save($quote);
            $this->checkoutSession->setQuoteId($quote->getId());
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
    }
}
