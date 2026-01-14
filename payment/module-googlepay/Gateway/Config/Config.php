<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\GooglePay\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Config
 * @package CyberSource\GooglePay\Gateway\Config
 * @codeCoverageIgnore
 */
class Config extends \CyberSource\Core\Model\AbstractGatewayConfig
{

    const KEY_GOOGLE_MERCHANT_ID = 'google_merchant_id';
    const KEY_DISPLAY_NAME = 'display_name';
    const KEY_CC_TYPES = 'cctypes';
    const KEY_BUTTON_SHOW_PDP = 'button_pdp_is_visible';
    const KEY_BUTTON_SHOW_MINICART = 'button_minicart_is_visible';

    /**
     * @var null|string
     */
    private $methodCode;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var string
     */
    private $pathPattern;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        $methodCode,
        $pathPattern,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->methodCode = $methodCode;
        $this->scopeConfig = $scopeConfig;
        $this->pathPattern = $pathPattern;
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
        $this->_storeManager = $storeManager;	
    }

    /**
     * Returns config value with fallback to core
     *
     * @param string $field
     * @param null $storeId
     *
     * @return mixed
     */
    public function getValue($field, $storeId = null)
    {
        $this->_storeManager->setCurrentStore($storeId);
        $this->setMethodCode($this->methodCode);
        $value = parent::getValue($field, $storeId);
        if ($value === null) {
            $this->setMethodCode(\CyberSource\Core\Model\Config::CODE);
            $value = parent::getValue($field, $storeId);
            $this->setMethodCode($this->methodCode);
        }

        return $value;
    }

    /**
	* return module specific credentials
	
	* @param string $field
	* @param $storeId
	* @return string $value
	
	*/
	public function getGPayConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            sprintf($this->pathPattern, $this->methodCode, $field),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getGoogleMerchantId($storeId = null)
    {
        return $this->getValue(static::KEY_GOOGLE_MERCHANT_ID, $storeId);
    }

    public function getMerchantDisplayName($storeId = null)
    {
        return $this->getValue(static::KEY_DISPLAY_NAME, $storeId);
    }

    public function getCcTypes($storeId = null)
    {
        $configuredValue = $this->getGPayConfigValue(static::KEY_CC_TYPES, $storeId);
        if (empty($configuredValue)) {
            // Extremely weird case that no card types allowed, but the module is enabled.
            // We must pass some card to avoid the error.
            return ['VI'];
        }

        return explode(',', $configuredValue ?? '');
    }

    public function buttonShowInCart($storeId = null)
    {
        return $this->getValue(static::KEY_BUTTON_SHOW_MINICART, $storeId);
    }

    public function buttonShowPdp($storeId = null)
    {
        return $this->getValue(static::KEY_BUTTON_SHOW_PDP, $storeId);
    }

    public function isTestMode($storeId = null)
    {
        $currentMethod = $this->methodCode;
        $this->setMethodCode('chcybersource');
        $testMode = parent::isTestMode($storeId);
        $this->setMethodCode($currentMethod);
        return $testMode;
    }
}
