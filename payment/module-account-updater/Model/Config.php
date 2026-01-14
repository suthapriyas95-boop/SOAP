<?php

namespace CyberSource\AccountUpdater\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    const TEST_ENDPOINT_URL = 'https://ebctest.cybersource.com/ebctest/DownloadReport';
    const PROD_ENDPOINT_URL = 'https://ebc.cybersource.com/ebc/DownloadReport';

    const KEY_ACTIVE = 'payment/chcybersource/au_active';
    const KEY_TEST_MODE = 'payment/chcybersource/au_test_mode';
    const KEY_USERNAME = 'payment/chcybersource/au_username';
    const KEY_PASSWORD = 'payment/chcybersource/au_password';
    const KEY_MERCHANT_ID = 'payment/chcybersource/au_merchant_id';
    const KEY_CRON_EXPR = 'payment/chcybersource/au_cron_expr';
    const KEY_TEST_REPORT_PATH = 'payment/chcybersource/au_test_report_path';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return (bool) $this->getValue(self::KEY_ACTIVE);
    }

    /**
     * @return bool
     */
    public function isTestMode()
    {
        return (bool) $this->getValue(self::KEY_TEST_MODE);
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->getValue(self::KEY_USERNAME);
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->getValue(self::KEY_PASSWORD);
    }

    /**
     * @return string
     */
    public function getMerchantId()
    {
        return $this->getValue(self::KEY_MERCHANT_ID);
    }

    /**
     * @return string
     */
    public function getCronExpr()
    {
        return $this->getValue(self::KEY_CRON_EXPR);
    }

    /**
     * @return string
     */
    public function getTestReportPath()
    {
        return $this->getValue(self::KEY_TEST_REPORT_PATH);
    }

    /**
     * @return string
     */
    public function getEndpointUrl()
    {
        return $this->isTestMode() ? self::TEST_ENDPOINT_URL : self::PROD_ENDPOINT_URL;
    }

    /**
     * @param string $path
     * @return string
     */
    private function getValue($path)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
    }
}
