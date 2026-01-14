<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace CyberSource\PayPal\Observer;

use Magento\Framework\Event\ObserverInterface;
use CyberSource\PayPal\Model\Config as PaypalConfig;
use Magento\Framework\Event\Observer as EventObserver;

/**
 * PayPal module observer
 */
class AddPaypalShortcutsObserver implements ObserverInterface
{
    /**
     * @var PaypalConfig
     */
    protected $paypalConfig;

    /**
     * Constructor
     *
     * @param PaypalConfig $paypalConfig
     */
    public function __construct(
        PaypalConfig $paypalConfig
    ) {
        $this->paypalConfig = $paypalConfig;
    }

    /**
     * Add PayPal shortcut buttons
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        /** @var \Magento\Catalog\Block\ShortcutButtons $shortcutButtons */
        $shortcutButtons = $observer->getEvent()->getContainer();
        $blocks = [
            \CyberSource\PayPal\Block\Express\InContext\Minicart\Button::class => PaypalConfig::CODE,
            \CyberSource\PayPal\Block\Express\Shortcut::class => PaypalConfig::CODE,
            \CyberSource\PayPal\Block\Bml\Shortcut::class => PaypalConfig::CODE,
        ];
        foreach ($blocks as $blockInstanceName => $paymentMethodCode) {
            if (!$this->paypalConfig->isActive()) {
                continue;
            }

            $shortcut = $shortcutButtons->getLayout()->createBlock($blockInstanceName);
            $shortcut->setIsInCatalogProduct(
                $observer->getEvent()->getIsCatalogProduct()
            )->setShowOrPosition(
                $observer->getEvent()->getOrPosition()
            );
            $shortcutButtons->addShortcut($shortcut);
        }
    }
}
