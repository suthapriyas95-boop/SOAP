<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\GooglePay\Block;

use CyberSource\GooglePay\Gateway\Config\Config;

class Button
    extends \Magento\Framework\View\Element\Template
    implements \Magento\Catalog\Block\ShortcutInterface
{

    const ALIAS_ELEMENT_INDEX = 'alias';

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \Magento\Payment\Model\MethodInterface
     */
    private $method;

    private $isMiniCart;

    /**
     * @var \Magento\Checkout\Model\ConfigProviderInterface
     */
    private $configProvider;

    /**
     * @var \Magento\Framework\Math\Random
     */
    private $random;

    /**
     * @var \Magento\Directory\Model\AllowedCountries
     */
    private $allowedCountries;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializerJson;

    /**
     * Button constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Payment\Model\MethodInterface $method
     * @param \Magento\Checkout\Model\ConfigProviderInterface $configProvider
     * @param \Magento\Framework\Math\Random $random
     * @param \Magento\Directory\Model\AllowedCountries $allowedCountries
     * @param \Magento\Framework\Serialize\Serializer\Json $serializerJson
     * @param \Magento\Framework\Registry $registry
     * @param string $shortcutTemplate
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Payment\Model\MethodInterface $method,
        \Magento\Checkout\Model\ConfigProviderInterface $configProvider,
        \Magento\Framework\Math\Random $random,
        \Magento\Directory\Model\AllowedCountries $allowedCountries,
        \Magento\Framework\Serialize\Serializer\Json $serializerJson,
        \Magento\Framework\Registry $registry,
        $shortcutTemplate,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->checkoutSession = $checkoutSession;
        $this->method = $method;
        $this->configProvider = $configProvider;
        $this->random = $random;

        $this->setTemplate($shortcutTemplate);
        $this->allowedCountries = $allowedCountries;
        $this->registry = $registry;
        $this->serializerJson = $serializerJson;
    }

    /**
     * @inheritDoc
     */
    public function getAlias()
    {
        return $this->getData(self::ALIAS_ELEMENT_INDEX);
    }

    /**
     * @inheritdoc
     */
    protected function _toHtml()
    {
        if (!$this->shouldRender()) {
            return '';
        }

        return parent::_toHtml();
    }

    protected function _beforeToHtml()
    {
        $this->setShortcutHtmlId(
            $this->random->getUniqueHash('googlepay_shortcut_')
        );

        return parent::_beforeToHtml();
    }

    protected function shouldRender()
    {
        return $this->method->isAvailable($this->checkoutSession->getQuote())
            && $this->method->getConfigData(Config::KEY_ACTIVE)
            && $this->isButtonEnabled();
    }

    private function isButtonEnabled()
    {
        return $this->isMiniCart && $this->method->getConfigData(Config::KEY_BUTTON_SHOW_MINICART)
            || !$this->isMiniCart && $this->method->getConfigData(Config::KEY_BUTTON_SHOW_PDP);
    }

    public function isOrPositionBefore()
    {
        return $this->getShowOrPosition() == \Magento\Catalog\Block\ShortcutButtons::POSITION_BEFORE;
    }

    /**
     * Check is "OR" label position after shortcut
     *
     * @return bool
     */
    public function isOrPositionAfter()
    {
        return $this->getShowOrPosition() == \Magento\Catalog\Block\ShortcutButtons::POSITION_AFTER;
    }


    /**
     * @param bool $isCatalog
     *
     * @return $this
     */
    public function setIsInCatalogProduct($isCatalog)
    {
        $this->isMiniCart = !$isCatalog;

        return $this;
    }

    public function getIsInCatalogProduct()
    {
        return !$this->isMiniCart;
    }

    public function getJsonConfig()
    {

        $config = array_merge(
            [
                'isCatalogProduct' => !$this->isMiniCart,
                'requiresShipping' => $this->getRequiresShipping(),
                'shortcutContainerClass' => '#' . $this->escapeHtml($this->getShortcutHtmlId()),
            ],
            $this->configProvider->getConfig()['payment'][\CyberSource\GooglePay\Model\Ui\ConfigProvider::CODE]
        );

        return $this->serializerJson->serialize($config);
    }

    private function getRequiresShipping()
    {
        if (!$this->getIsInCatalogProduct()) {
            return !$this->checkoutSession->getQuote()->isVirtual();
        }

        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->registry->registry('current_product');

        return $product && $product->getId() && !$product->isVirtual();

    }

}
