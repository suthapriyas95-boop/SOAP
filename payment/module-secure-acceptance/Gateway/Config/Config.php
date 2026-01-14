<?php
/**
 * Copyright © 2018 CollinsHarper. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Gateway\Config;

use CyberSource\Core\Model\AbstractGatewayConfig;
use CyberSource\SecureAcceptance\Model\Ui\ConfigProvider;
use Magento\Backend\Model\Auth;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Config
 * @codeCoverageIgnore
 */
class Config extends AbstractGatewayConfig
{
    const KEY_PROFILE_ID = "profile_id";
    const KEY_SECRET_KEY = "secret_key";
    const KEY_ACCESS_KEY = "access_key";
    const KEY_SOP_PROFILE_ID = "sop_profile_id";
    const KEY_SOP_SECRET_KEY = "sop_secret_key";
    const KEY_SOP_ACCESS_KEY = "sop_access_key";
    const KEY_AUTH_ACTIVE = "auth_active";
    const KEY_AUTH_PROFILE_ID = "auth_profile_id";
    const KEY_AUTH_SECRET_KEY = "auth_secret_key";
    const KEY_AUTH_ACCESS_KEY = "auth_access_key";
    const KEY_SOP_AUTH_ACTIVE = "sop_auth_active";
    const KEY_SOP_AUTH_PROFILE_ID = "sop_auth_profile_id";
    const KEY_SOP_AUTH_SECRET_KEY = "sop_auth_secret_key";
    const KEY_SOP_AUTH_ACCESS_KEY = "sop_auth_access_key";
    const KEY_SOP_SERVICE_URL = "service_url";
    const KEY_SOP_SERVICE_URL_TEST = "service_url_test";
    const KEY_ACTIVE = "active";
    const KEY_USE_IFRAME = "use_iframe";
    const KEY_USE_IFRAME_SANDBOX = "use_iframe_sandbox";
    const KEY_TITLE = "title";
    const KEY_DEBUG = "debug";
    const KEY_IGNORE_AVS = "ignore_avs";
    const KEY_IGNORE_CVN = "ignore_cvn";
    const KEY_ALLOWSPECIFIC = "allowspecific";
    const KEY_DEVELOPER_ID = "developer_id";
    const KEY_VAULT_ENABLE = 'active';
    const KEY_VAULT_ADMIN_ENABLE = 'active_admin';
    const KEY_VAULT_ADMIN_ENABLE_CVV = 'enable_admin_cvv';
    const KEY_FLOW_MODE = 'sa_flow_mode';
    const KEY_TOKEN_SKIP_DM = 'token_skip_decision_manager';
    const KEY_TOKEN_SKIP_AUTO_AUTH = 'token_skip_auto_auth';
    const KEY_TOKEN_PASS_EXPIRATION_DATE = 'token_pass_expiration_date';
    const KEY_CSRF_TOKEN_EXPIRATION_LIFE_TIME = 'csrf_token_expiration_lifetime';
    const KEY_LOCALE = 'locale';
    const SA_FLOW = 0;
    const SOAP_FLOW = 1;
    const KEY_ENFORCE_SCA_SOAP_ON_SAVE = 'enforce_sca_soap_on_save';
    const KEY_ENFORCE_SCA_SA_WITHOUT_SAVE ='enforce_sca_sa_without_save';
    const KEY_ENFORCE_SCA_SA_ON_SAVE ='enforce_sca_sa_on_save';
    const KEY_RECAPTHA_INVISIBLE = 'recaptcha_type';
    const KEY_RECAPTHA_LANGUAGE = 'recaptcha_language';
    /**
     * @var \Magento\Framework\App\State
     */
    private $appState;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\State $appState
     * @param string|null $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\State $appState,
        $methodCode = null,
        $pathPattern = self::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
        $this->appState = $appState;
    }

    public function getProfileId($storeId = null)
    {
        return $this->getValue(self::KEY_PROFILE_ID, $storeId);
    }

    public function getSecretKey($storeId = null)
    {
        return $this->getValue(self::KEY_SECRET_KEY, $storeId);
    }

    public function getAccessKey($storeId = null)
    {
        return $this->getValue(self::KEY_ACCESS_KEY, $storeId);
    }

    public function getSopProfileId($storeId = null)
    {
        return $this->getValue(self::KEY_SOP_PROFILE_ID, $storeId);
    }

    public function getSopSecretKey($storeId = null)
    {
        return $this->getValue(self::KEY_SOP_SECRET_KEY, $storeId);
    }

    public function getSopAccessKey($storeId = null)
    {
        return $this->getValue(self::KEY_SOP_ACCESS_KEY, $storeId);
    }

    public function getSopServiceUrl()
    {
        return $this->getValue(self::KEY_SOP_SERVICE_URL);
    }

    public function getSopServiceUrlTest()
    {
        return $this->getValue(self::KEY_SOP_SERVICE_URL_TEST);
    }

    public function isActive()
    {
        return $this->getValue(self::KEY_ACTIVE);
    }

    public function getUseIFrame()
    {
        return (bool) $this->getValue(self::KEY_USE_IFRAME);
    }

    /**
     * Return option value for WM iframe's sandbox attribute enabled
     *
     * @return bool
     */
    public function getUseIFrameSandbox()
    {
        return (bool) $this->getValue(self::KEY_USE_IFRAME_SANDBOX);
    }

    public function getTitle()
    {
        return $this->getValue(self::KEY_TITLE);
    }

    public function getTestMode($storeId = null)
    {
        if(!$storeId){
            $storeId = $this->getCurrentStoreId();
        }
        return $this->getUseTestWsdl($storeId);
    }

    public function getDebug()
    {
        return $this->getValue(self::KEY_DEBUG);
    }

    public function getIgnoreAvs()
    {
        return (bool)$this->getValue(self::KEY_IGNORE_AVS);
    }

    public function getIgnoreCvn()
    {
        return (bool)$this->getValue(self::KEY_IGNORE_CVN);
    }

    public function getAllowSpecific()
    {
        return $this->getValue(self::KEY_ALLOWSPECIFIC);
    }

    public function getDeveloperId()
    {
        return $this->getValue(self::KEY_DEVELOPER_ID);
    }

    public function isVaultEnabled()
    {
        return $this->isSilent() && $this->isVaultEnabledConfiguredOption();
    }

    /**
     * Returns the *configured* value of vault enabled flag, despite the SOP or other method is enabled
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isVaultEnabledConfiguredOption($storeId = null)
    {
        $this->setMethodCode(ConfigProvider::CC_VAULT_CODE);
        $isVaultEnable = (bool) $this->getValue(self::KEY_VAULT_ENABLE, $storeId);
        $this->setMethodCode(ConfigProvider::CODE);

        return $isVaultEnable;
    }

    /**
     * @param int|null $storeId
     *
     * @return mixed
     */
    public function isVaultEnabledAdmin($storeId = null)
    {
        $this->setMethodCode(ConfigProvider::CC_VAULT_CODE);
        $value = $this->getValue(self::KEY_VAULT_ADMIN_ENABLE, $storeId);
        $this->setMethodCode(ConfigProvider::CODE);

        return $value;
    }

    public function getVaultTitle()
    {
        $this->setMethodCode(ConfigProvider::CC_VAULT_CODE);
        $title = $this->getValue(self::KEY_TITLE);
        $this->setMethodCode(ConfigProvider::CODE);
        return $title;
    }

    public function getIsLegacyMode($storeId = null)
    {
        $flowType = $this->getValue(self::KEY_FLOW_MODE, $storeId);

        if ($flowType == self::SA_FLOW || $this->isAdmin()) {
            return true;
        }
        return false;
    }

    public function isMicroform($storeId = null)
    {
        return $this->getSaType($storeId) == \CyberSource\Core\Model\Source\SecureAcceptance\Type::SA_FLEX_MICROFORM;
    }

    public function getAuthActive($storeId = null)
    {
        return $this->getValue(self::KEY_AUTH_ACTIVE, $storeId);
    }

    public function getAuthProfileId($storeId = null)
    {
        return $this->getAuthActive($storeId)
            ? $this->getValue(self::KEY_AUTH_PROFILE_ID)
            : $this->getProfileId($storeId);
    }

    public function getAuthSecretKey($storeId = null)
    {
        return $this->getAuthActive($storeId)
            ? $this->getValue(self::KEY_AUTH_SECRET_KEY, $storeId)
            : $this->getSecretKey($storeId);
    }

    public function getAuthAccessKey($storeId = null)
    {
        return $this->getAuthActive($storeId)
            ? $this->getValue(self::KEY_AUTH_ACCESS_KEY)
            : $this->getAccessKey($storeId);
    }

    public function getSopAuthActive($storeId = null)
    {
        return $this->getValue(self::KEY_SOP_AUTH_ACTIVE, $storeId);
    }

    public function getSopAuthProfileId($storeId = null)
    {
        return $this->getSopAuthActive($storeId)
            ? $this->getValue(self::KEY_SOP_AUTH_PROFILE_ID)
            : $this->getSopProfileId($storeId);
    }

    public function getSopAuthSecretKey($storeId = null)
    {
        return $this->getSopAuthActive($storeId)
            ? $this->getValue(self::KEY_SOP_AUTH_SECRET_KEY, $storeId)
            : $this->getSopSecretKey($storeId);
    }

    public function getSopAuthAccessKey($storeId = null)
    {
        return $this->getSopAuthActive($storeId)
            ? $this->getValue(self::KEY_SOP_AUTH_ACCESS_KEY)
            : $this->getSopAccessKey($storeId);
    }

    /**
     * @return bool
     */
    public function getTokenPassExpirationDate($storeId = null)
    {
        return (bool)$this->getValue(self::KEY_TOKEN_PASS_EXPIRATION_DATE, $storeId);
    }

    public function getCsrfTokenExpirationLifeTime()
    {
        return $this->getValue(self::KEY_CSRF_TOKEN_EXPIRATION_LIFE_TIME);
    }

    public function getLocale($storeId = null)
    {
        return $this->getValue(self::KEY_LOCALE, $storeId);
    }

    public function isAdmin()
    {
        try {
            return $this->appState->getAreaCode() === \Magento\Framework\App\Area::AREA_ADMINHTML;
        } catch (\Exception $e) {
            // On the \CyberSource\SecureAcceptance\Plugin\Store\Api\StoreResolverInterfacePlugin::beforeGetCurrentStoreId
            // method call in the frontend area the code is not set yet, so we just return false
        }
        return false;
    }

    /**
     * @param  $storeId
     * @return boolean
     */
    public function isScaEnforcedOnCardSaveSoap($storeId = null)
    {
        return $this->getValue(self::KEY_ENFORCE_SCA_SOAP_ON_SAVE, $storeId) == 1;
    }

    /**
     * @param  $storeId
     * @return boolean
     */
    public function isScaEnforcedOnCardSaveSa($storeId = null)
    {
        return $this->getValue(self::KEY_ENFORCE_SCA_SA_ON_SAVE, $storeId) == 1;
    }

    /**
     * @param  $storeId
     * @return boolean
     */
    public function isScaEnforcedWithoutCardSaveSa($storeId = null)
    {
        return $this->getValue(self::KEY_ENFORCE_SCA_SA_WITHOUT_SAVE, $storeId) == 1;
    }

    public function getRecapthaInvisible($storeId = null)
    {
        $storeId = $storeId ?? $this->getCurrentStoreId();
        return $this->getValue(self::KEY_RECAPTHA_INVISIBLE, $storeId);
    }

    public function getRecapthaLang($storeId = null)
    {
        $storeId = $storeId ?? $this->getCurrentStoreId();
        return $this->getValue(self::KEY_RECAPTHA_LANGUAGE, $storeId);
    }

}
