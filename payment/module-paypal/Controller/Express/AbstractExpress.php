<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace CyberSource\PayPal\Controller\Express;

use CyberSource\Core\Model\LoggerInterface;
use CyberSource\PayPal\Model\Config as CyberSourcePayPalConfig;

/**
 * Abstract Express Checkout Controller
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class AbstractExpress extends \Magento\Paypal\Controller\Express\AbstractExpress
{
    /**
     * @var \CyberSource\PayPal\Model\Express\Checkout
     */
    protected $_checkout;

    /**
     * @var CyberSourcePayPalConfig
     */
    public $gatewayConfig;

    /**
     * @var LoggerInterface
     */
    protected $cyberLogger;

    /**
     * @var \CyberSource\Core\Model\Checkout\PaymentFailureRouteProviderInterface
     */
    protected $paymentFailureRouteProvider;

    /**
     * AbstractExpress constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Paypal\Model\Express\Checkout\Factory $checkoutFactory
     * @param \Magento\Framework\Session\Generic $paypalSession
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param \Magento\Customer\Model\Url $customerUrl
     * @param CyberSourcePayPalConfig $gatewayConfig
     * @param LoggerInterface $cyberLogger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Paypal\Model\Express\Checkout\Factory $checkoutFactory,
        \Magento\Framework\Session\Generic $paypalSession,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Magento\Customer\Model\Url $customerUrl,
        \CyberSource\Core\Model\Checkout\PaymentFailureRouteProviderInterface $paymentFailureRouteProvider,
        CyberSourcePayPalConfig $gatewayConfig,
        LoggerInterface $cyberLogger
    ) {
        parent::__construct(
            $context,
            $customerSession,
            $checkoutSession,
            $orderFactory,
            $checkoutFactory,
            $paypalSession,
            $urlHelper,
            $customerUrl
        );
        $this->gatewayConfig = $gatewayConfig;
        $this->cyberLogger = $cyberLogger;
        $this->paymentFailureRouteProvider = $paymentFailureRouteProvider;
    }
}
