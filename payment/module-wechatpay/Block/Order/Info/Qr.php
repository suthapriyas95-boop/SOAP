<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\WeChatPay\Block\Order\Info;

class Qr extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \CyberSource\WeChatPay\Gateway\Config\Config
     */
    private $config;

    /**
     * @var \CyberSource\WeChatPay\Model\CurrentOrderResolver
     */
    private $currentOrderResolver;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \CyberSource\WeChatPay\Gateway\Config\Config $config
     * @param \CyberSource\WeChatPay\Model\CurrentOrderResolver $currentOrderResolver
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \CyberSource\WeChatPay\Gateway\Config\Config $config,
        \CyberSource\WeChatPay\Model\CurrentOrderResolver $currentOrderResolver,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->currentOrderResolver = $currentOrderResolver;
        $this->setTemplate('order/info/buttons/qr.phtml');
    }

    /**
     * @return string
     */
    public function isApplicable()
    {
        return $this->getOrder()
            && $this->getOrder()->getState() == \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW
            && $this->getOrder()->getPayment()->getMethod() == \CyberSource\WeChatPay\Model\Ui\ConfigProvider::CODE;
    }

    /**
     * @return int
     */
    public function getPopupMessageDelay()
    {
        return $this->config->getPopupMessageDelay();
    }

    /**
     * @return int
     */
    public function getCheckStatusFrequency()
    {
        return $this->config->getCheckStatusFrequency();
    }

    /**
     * @return int
     */
    public function getMaxStatusRequests()
    {
        return $this->config->getMaxStatusRequests();
    }

    /**
     * @return bool|\Magento\Sales\Api\Data\OrderInterface
     */
    public function getOrder()
    {
        return $this->currentOrderResolver->get();
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getLabel()
    {
        return __('Pay with WeChat QR Code');
    }
}
