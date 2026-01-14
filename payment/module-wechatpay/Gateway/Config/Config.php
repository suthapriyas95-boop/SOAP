<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\WeChatPay\Gateway\Config;

use CyberSource\Core\Model\AbstractGatewayConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
/**
 * Class Config
 * @codeCoverageIgnore
 */
class Config extends AbstractGatewayConfig
{
    const CODE = 'cybersourcewechatpay';
    const KEY_USE_DEFAULT_MID = "use_default_mid";
    const PAYMENT_STATUS_PENDING = 'pending';
    const PAYMENT_STATUS_SETTLED = 'settled';
    const PAYMENT_STATUS_FAILED = 'failed';
    const PAYMENT_STATUS_ABANDONED = 'abandoned';
    const PAYMENT_STATUS_REFUNDED = 'refunded';
    const KEY_WECHAT_SUCCESS_URL = 'wechat_success_url';
    const KEY_QR_EXPIRATION_TIME = 'qr_expiration_time';
    const CHECK_STATUS_FREQUENCY = 'check_status_frequency';
    const KEY_MAX_STATUS_REQUESTS = 'max_status_requests';
    const KEY_POPUP_MESSAGE_DELAY = 'popup_message_delay';
    const KEY_TEST_CHECK_STATUS_RESPONSE_CODE = 'test_response_code';
    const P12_ACCESSKEY = 'p12_accesskey';
    const P12_CERTIFICATE = 'wechatpay_p12_certificate';
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

    /**
     * @return string
     */
    public function getWeChatSuccessUrl()
    {
        return $this->getValue(self::KEY_WECHAT_SUCCESS_URL);
    }

    public function isDefaultMid()
    {
        return $this->getValue(self::KEY_USE_DEFAULT_MID);
    }

    /**
     * @return int
     */
    public function getQrExpirationTime()
    {
        return (int) $this->getValue(self::KEY_QR_EXPIRATION_TIME);
    }

    /**
     * @return int
     */
    public function getPopupMessageDelay()
    {
        return (int) $this->getValue(self::KEY_POPUP_MESSAGE_DELAY);
    }

    /**
     * @return int
     */
    public function getCheckStatusFrequency()
    {
        return (int) $this->getValue(self::CHECK_STATUS_FREQUENCY);
    }

    /**
     * @return int
     */
    public function getMaxStatusRequests()
    {
        return (int) $this->getValue(self::KEY_MAX_STATUS_REQUESTS);
    }

    /**
     * @return string
     */
    public function getTestStatusResponseCode()
    {
        return $this->getValue(self::KEY_TEST_CHECK_STATUS_RESPONSE_CODE);
    }

    /**
     * @param string $field
     * @param null $storeId
     *
     * @return mixed
     */
    public function getValue($field, $storeId = null)
    {
        $this->setMethodCode(\CyberSource\WeChatPay\Model\Ui\ConfigProvider::CODE);
        $value = parent::getValue($field, $storeId);
        if ($value === null) {
            $this->setMethodCode(\CyberSource\Core\Model\Config::CODE);
            $value = parent::getValue($field, $storeId);
        }
        return $value;
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
	
    public function isTestMode($storeId = null)
    {
        $currentMethod = $this->methodCode;
        $this->setMethodCode('chcybersource');
        $testMode = parent::isTestMode($storeId);
        $this->setMethodCode($currentMethod);
        return $testMode;
    }
}
