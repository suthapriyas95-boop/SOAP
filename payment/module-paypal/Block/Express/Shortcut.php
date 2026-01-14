<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace CyberSource\PayPal\Block\Express;

use CyberSource\PayPal\Model\Source\RedirectionType;
use Magento\Paypal\Helper\Shortcut\ValidatorInterface;
use CyberSource\PayPal\Model\Config as CyberSourcePayPalConfig;

/**
 * Paypal express checkout shortcut link
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Shortcut extends \Magento\Paypal\Block\Express\Shortcut
{
    /**
     * @var CyberSourcePayPalConfig
     */
    private $gatewayConfig;

    /**
     * Shortcut constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Paypal\Model\ConfigFactory $paypalConfigFactory
     * @param \Magento\Paypal\Model\Express\Checkout\Factory $checkoutFactory
     * @param \Magento\Framework\Math\Random $mathRandom
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param ValidatorInterface $shortcutValidator
     * @param CyberSourcePayPalConfig $gatewayConfig
     * @param string $paymentMethodCode
     * @param string $startAction
     * @param string $checkoutType
     * @param string $alias
     * @param string $shortcutTemplate
     * @param \Magento\Checkout\Model\Session|null $checkoutSession
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Paypal\Model\ConfigFactory $paypalConfigFactory,
        \Magento\Paypal\Model\Express\Checkout\Factory $checkoutFactory,
        \Magento\Framework\Math\Random $mathRandom,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        ValidatorInterface $shortcutValidator,
        CyberSourcePayPalConfig $gatewayConfig,
        $paymentMethodCode,
        $startAction,
        $checkoutType,
        $alias,
        $shortcutTemplate,
        ?\Magento\Checkout\Model\Session $checkoutSession = null,
        array $data = []
    ) {

        parent::__construct(
            $context,
            $paypalConfigFactory,
            $checkoutFactory,
            $mathRandom,
            $localeResolver,
            $shortcutValidator,
            $paymentMethodCode,
            $startAction,
            $checkoutType,
            $alias,
            $shortcutTemplate,
            $checkoutSession,
            $data
        );

        $this->gatewayConfig = $gatewayConfig;
    }

    /**
     * @return bool
     */
    protected function shouldRender()
    {
        $isRedirect = $this->getIsInCatalogProduct() || !$this->gatewayConfig->isInContext();
        return $isRedirect && $this->_shouldRender;
    }
}
