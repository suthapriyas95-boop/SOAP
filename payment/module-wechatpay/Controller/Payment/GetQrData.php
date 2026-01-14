<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\WeChatPay\Controller\Payment;

use Magento\Framework\App\Action\Context;

class GetQrData extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \CyberSource\WeChatPay\Gateway\Config\Config
     */
    private $config;

    /**
     * @var \Magento\Framework\View\Result\LayoutFactory
     */
    private $resultLayoutFactory;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    private $formKeyValidator;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $jsonResultFactory;

    /**
     * @var \CyberSource\WeChatPay\Model\CurrentOrderResolver
     */
    private $currentOrderResolver;

    /**
     * @var \CyberSource\Core\Model\LoggerInterface
     */
    private $logger;

    /**
     * @param Context $context
     * @param \CyberSource\WeChatPay\Gateway\Config\Config $config
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory
     * @param \CyberSource\WeChatPay\Model\CurrentOrderResolver $currentOrderResolver
     * @param \CyberSource\Core\Model\LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        \CyberSource\WeChatPay\Gateway\Config\Config $config,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        \CyberSource\WeChatPay\Model\CurrentOrderResolver $currentOrderResolver,
        \CyberSource\Core\Model\LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->resultLayoutFactory = $resultLayoutFactory;
        $this->formKeyValidator = $formKeyValidator;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->currentOrderResolver = $currentOrderResolver;
        $this->logger = $logger;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonResultFactory->create();

        try {
            if (! $this->getRequest()->isPost()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Wrong method.'));
            }

            if (! $this->formKeyValidator->validate($this->getRequest())) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Invalid formkey.'));
            }

            $orderId = $this->getRequest()->getParam('order_id');
            if (! $order = $this->currentOrderResolver->get($orderId)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Order does not exist.'));
            }

            $result->setData(
                [
                    'success' => true,
                    'qr_url' => $this->getQrCodeUrl($order),
                    'qr_notice' => $this->getQrNotice()
                ]
            );
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            $result->setData(['success' => false, 'error_msg' => __('Unable to retrieve QR code url.')]);
        }


        return $result;
    }

    /**
     * @return string
     */
    private function getQrNotice()
    {
        $confirmButtonNotice = __("After successfully completing the payment on the WeChat App click 'Confirm' to proceed.");
        $notice = __("Please scan the QR code using the WeChat Mobile App and follow the instructions in the App.");
        $notice .= $this->config->getMaxStatusRequests() > 0 ? " {$confirmButtonNotice} " : ' ';
        $notice .= __("Click 'Cancel' to go back and select an alternate payment method or to edit your shopping cart.");

        return $notice;
    }

    /**
     * @return string
     */
    private function getQrCodeUrl($order)
    {
        if ($order->getState() != \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW) {
            return '';
        }

        return $order->getPayment()->getAdditionalInformation()['qrCodeUrl'] ?? '';
    }
}
