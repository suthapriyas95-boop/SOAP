<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\BankTransfer\Model;

use CyberSource\Core\Model\AbstractGatewayConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Config
 */
class Config extends AbstractGatewayConfig
{

    /**
     * @var string
     */
    protected $_paymentMethod='ideal';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    const KEY_TITLE = 'bank_transfer_title';
    
    const KEY_ACTIVE = 'bank_transfer_active';
	
	const KEY_USE_DEFAULT_MID = "use_default_mid";
    const P12_ACCESSKEY = 'p12_accesskey';
    const P12_CERTIFICATE = 'p12_certificate';
    
    protected $method;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        $methodCode,
        $pathPattern
    ) {
        $this->_scopeConfig = $scopeConfig;
        parent::__construct($scopeConfig, 'cybersource_bank_transfer', $pathPattern);
    }

    public function isActive()
    {
        return $this->getValue(self::KEY_ACTIVE);
    }
    
    public function isMethodActive($method)
    {
        return $this->getValue($method.'_active');
    }

    public function getMethodTitle($method)
    {
        return $this->getValue($method.'_title');
    }

    public function getMethodAvailableCurrencies($method)
    {
        return explode(',', $this->getValue($method.'_currency') ?? '');
    }
    
    public function getCode()
    {
        return Payment::CODE;
    }
	
	
    /**
     * get module Credentials
     * @param $storeId
     * @return $value
     */
    public function getModuleValue($field, $storeId = null)
    {
         
        $value = $this->_scopeConfig->getValue(
            "payment/cybersource_bank_transfer/".$this->_paymentMethod."_".$field,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return $value;
    }
 
    
    /**
	 * get module merchant id
	 * @param $storeId
	 * @return merchantId|NULL
	 */
	public function getMerchantId($storeId = null)
    {
		$value = null;
		$isDefaultMid = $this->isDefaultMid($storeId);
		if(!$isDefaultMid){
			$value = $this->getModuleValue(self::KEY_MERCHANT_ID, $storeId);
		}
		return $value;
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
	
	/**
	 * get module transaction key
	 * @param $storeId
	 * @return transactionKey|NULL
	 */
	public function getTransactionKey($storeId = null)
    {
		$value = null;
		$isDefaultMid = $this->isDefaultMid($storeId);
		if(!$isDefaultMid){ 
			$value = $this->getModuleValue(self::KEY_TRANSACTION_KEY, $storeId);
		}
		return $value;
    }

    public function setBankTransferPaymentMethod($paymentMethod)
    {
        $this->_paymentMethod = $paymentMethod;
    }
	
	public function isDefaultMid($storeId)
	{
       return $this->getModuleValue(self::KEY_USE_DEFAULT_MID,$storeId);
    }

} 
