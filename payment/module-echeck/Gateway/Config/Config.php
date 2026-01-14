<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Gateway\Config;

use CyberSource\Core\Model\AbstractGatewayConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Config
 */
class Config extends AbstractGatewayConfig
{
    const KEY_TITLE = 'title';
    const KEY_ACTIVE = 'active';
    const KEY_PAYMENT_ACTION = 'payment_action';
    const KEY_MERCHANT_PASSWORD = 'merchant_password';
    const KEY_MERCHANT_USERNAME = 'merchant_username';
    const KEY_IS_DRIVERS_LICENSE_NUMBER_REQUIRED = 'drivers_license_number';
    const KEY_IS_CHECK_NUMBER_REQUIRED = 'check_number';
    const KEY_AGREEMENT_REQUIRED = 'agreement_required';
    const KEY_SEC_CODE = 'sec_code';
    const KEY_STORE_PHONE = 'general/store_information/phone';

    protected $method;

    protected $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        $methodCode,
        $pathPattern
    ) {
        $this->method = $methodCode;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
    }

    public function isActive()
    {
        return $this->getValue(self::KEY_ACTIVE);
    }

    public function getTitle()
    {
        return $this->getValue(self::KEY_TITLE);
    }

    public function getPaymentAction()
    {
        return 'authorize';
    }

    public function setMethod($methodCode)
    {
        $this->method = $methodCode;
    }

    public function getConfigPaymentAction()
    {
        return 'authorize';
    }

    public function getMerchantId($storeId = null)
    {
        $this->setMethodCode('chcybersource');
        $merchantId = parent::getMerchantId();
        $this->setMethodCode('cybersourceecheck');
        return $merchantId;
    }

    public function getMerchantPassword()
    {
        return $this->getValue(self::KEY_MERCHANT_PASSWORD);
    }

    public function getMerchantUsername()
    {
        return $this->getValue(self::KEY_MERCHANT_USERNAME);
    }

    public function getTestEventType()
    {
        return $this->getValue('test_event_type');
    }

    public function getAcceptEventType()
    {
        return explode(',', $this->getValue('accept_event_type') ?? '');
    }

    public function getRejectEventType()
    {
        return explode(',', $this->getValue('reject_event_type') ?? '');
    }

    public function getPendingEventType()
    {
        return explode(',', $this->getValue('pending_event_type') ?? '');
    }

    public function getServerUrl()
    {
        $url = $this->getValue('service_url');
        if ($this->isTestMode()) {
            $url = $this->getValue('test_service_url');
        }
        return $url;
    }

    public function getAgreementRequired()
    {
        return $this->getValue(self::KEY_AGREEMENT_REQUIRED);
    }

    public function getStorePhone()
    {
        return $this->scopeConfig->getValue(
            self::KEY_STORE_PHONE,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function isDriversLicenseNumberRequired()
    {
        return $this->getValue(self::KEY_IS_DRIVERS_LICENSE_NUMBER_REQUIRED);
    }

    public function isCheckNumberRequired()
    {
        return $this->getValue(self::KEY_IS_CHECK_NUMBER_REQUIRED);
    }

    public function getSecCode()
    {
        return $this->getValue(self::KEY_SEC_CODE);
    }

    public function isTestMode($storeId = null)
    {
        $currentMethod = $this->method;
        $this->setMethodCode('chcybersource');
        $testMode = parent::isTestMode($storeId);
        $this->setMethodCode($currentMethod);
        return $testMode;
    }
}
