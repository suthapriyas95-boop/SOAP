<?php
/**
 * Copyright © 2018 CollinsHarper. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\KlarnaFinancial\Gateway\Config;

use CyberSource\Core\Model\AbstractGatewayConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Config
 * @codeCoverageIgnore
 */
class Config extends AbstractGatewayConfig
{
    const CODE = 'cybersourceklarna';

    const KEY_ACTIVE = "active";
    const KEY_USE_DEFAULT_MID = "use_default_mid";
    const KEY_TITLE = "title";
    const KEY_PAYMENT_ACTION = "payment_action";
    const P12_ACCESSKEY = 'p12_accesskey';
    const P12_CERTIFICATE = 'klarna_p12_certificate';

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

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        $methodCode,
        $pathPattern
       
    ) {
        $this->methodCode = $methodCode;
        $this->scopeConfig = $scopeConfig;
        $this->pathPattern = $pathPattern;
        parent::__construct($scopeConfig, self::CODE, $pathPattern);
    }

    public function isActive()
    {
        return $this->getValue(self::KEY_ACTIVE);
    }

    public function getTitle()
    {
        return $this->getValue(self::KEY_TITLE);
    }
    public function isDefaultMid()
    {
        return $this->getValue(self::KEY_USE_DEFAULT_MID);
    }

    public function isTest($storeId = null)
    {
        $currentMethod = $this->methodCode;
        $this->setMethodCode('chcybersource');
        $testMode = parent::isTestMode($storeId);
        $this->setMethodCode($currentMethod);
        return $testMode;
    }

    public function getPaymentAction()
    {
        return $this->getValue(self::KEY_PAYMENT_ACTION);
    }

    public function getValue($field, $storeId = null)
    {
        $value = $this->scopeConfig->getValue(
            sprintf($this->pathPattern, $this->methodCode, $field),
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($value === null) {
            $this->setMethodCode(\CyberSource\Core\Model\Config::CODE);
            $value = parent::getValue($field, $storeId);
        }

        return $value;
    }

    public function isAuthMode()
    {
        return $this->getValue(self::KEY_PAYMENT_ACTION) ==
            \CyberSource\KlarnaFinancial\Model\Source\PaymentAction::ACTION_AUTHORIZE;
    }

    public function isDeveloperMode($storeId = null)
    {
        return ($this->isTest($storeId) == 1) ? "true" : "false";
    }
	
	/*
	
	* get module merchant id
	
	* @param $storeId
	* @return merchantId|NULL
	*/
	public function getMerchantId($storeId = null)
    {
		return $this->getModuleValue(self::KEY_MERCHANT_ID, $storeId);
    }
     /*
	* get module p12 password
	* @param $storeId
	* @return merchantId|NULL
	*/
	public function getP12AccessKey($storeId = null)
    {
		return $this->getModuleValue(self::P12_ACCESSKEY, $storeId);
    }
	

     /*
	* get module p12 certificate
	* @param $storeId
	* @return merchantId|NULL
	*/
	public function getP12Certificate($storeId = null)
    {
		return $this->getModuleValue(self::P12_CERTIFICATE, $storeId);
    }
	
	
	
	/*
	
	* get module transaction key
	
	* @param $storeId
	* @return transactionKey|NULL
	*/
	public function getTransactionKey($storeId = null)
    {
		return $this->getModuleValue(self::KEY_TRANSACTION_KEY, $storeId);
    }
	
	/**
	* return module specific credentials
	
	* @param string $field
	* @param $storeId
	* @return string $value
	
	*/
	public function getModuleValue($field, $storeId = null)
    {
        $value = null;
        $isDefaultMid = $this->isDefaultMid();
        if(!$isDefaultMid){
            $value = $this->scopeConfig->getValue(
            sprintf($this->pathPattern, $this->methodCode, $field),
            ScopeInterface::SCOPE_STORE,
            $storeId
            );
        }
    
        return $value;
    }
	
}
