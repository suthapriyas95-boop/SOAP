<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\WeChatPay\Model;

class CurrentOrderResolver
{
    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    private $orderRepository;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Registry $registry
    ) {
        $this->orderRepository = $orderRepository;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->registry = $registry;
    }

    /**
     * @param null|mixed $orderId
     *
     * @return \Magento\Sales\Api\Data\OrderInterface|bool
     */
    public function get($orderId = null)
    {
        if ($orderId) {
            try {
                $order = $this->orderRepository->get($orderId);

                if ($this->customerSession->getCustomerId() !== $order->getCustomerId()) {
                    return false;
                }

                return $order;
            } catch (\Exception $e) {
                return false;
            }
        }

        $currentOrder = $this->registry->registry('current_order');
        $lastRealOrder = $this->checkoutSession->getLastRealOrder();

        $order = $currentOrder ?: $lastRealOrder;
        if (!$order->getEntityId()) {
            return false;
        }

        return $order;
    }
}
