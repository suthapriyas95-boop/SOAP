<?php

namespace CyberSource\SecureAcceptance\Plugin\Helper;

class RequestDataBuilderPlugin
{

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $urlBuilder;

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Config\SaConfigProviderInterface
     */
    private $configProvider;

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Config\Config
     */
    private $config;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    private $checkoutSession;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $encryptor;

    /**
     * RequestDataBuilderPlugin constructor.
     *
     * @param \CyberSource\SecureAcceptance\Gateway\Config\Config $config
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Session\SessionManagerInterface $checkoutSession
     */
    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Config\Config $config,
        \CyberSource\SecureAcceptance\Gateway\Config\SaConfigProviderInterface $configProvider,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Session\SessionManagerInterface $checkoutSession,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    ) {
        $this->config = $config;
        $this->configProvider = $configProvider;
        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
        $this->checkoutSession = $checkoutSession;
        $this->encryptor = $encryptor;
    }

    /**
     *
     * Plugin method for \CyberSource\SecureAcceptance\Helper\RequestDataBuilder::buildSilentRequestData
     *
     * Appends override_custom_receipt_page field to the request and generates a new signature.
     * Erases device fingerprint and remote ip.
     *
     * @param \CyberSource\SecureAcceptance\Helper\RequestDataBuilder $subject
     * @param $result
     * @return mixed
     */
    public function afterBuildSilentRequestData(
        \CyberSource\SecureAcceptance\Helper\RequestDataBuilder $subject,
        $result
    ) {

        $result['override_custom_receipt_page'] = $this->getCustomReceiptPageUrl();

        unset($result['device_fingerprint_id']);
        unset($result['customer_ip_address']);
        unset($result['signed_field_names']);
        unset($result['signature']);
        $result[\CyberSource\SecureAcceptance\Helper\RequestDataBuilder::KEY_SCOPE] = \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE;
        $storeId = $result[\CyberSource\SecureAcceptance\Helper\RequestDataBuilder::KEY_STORE_ID] ?? null;
        $result['access_key'] = $this->configProvider->getAccessKey($storeId);
        $result['profile_id'] = $this->configProvider->getProfileId($storeId);
        $result['signed_field_names'] = $subject->getSignedFields($result);
        $result['signature'] = $subject->sign($result, $this->configProvider->getSecretKey($storeId));

        return $result;
    }

    /**
     *
     * Plugin method for \CyberSource\SecureAcceptance\Helper\RequestDataBuilder::buildSilentData
     *
     * Appends override_custom_receipt_page field to the request and generates a new signature.
     *
     * @param \CyberSource\SecureAcceptance\Helper\RequestDataBuilder $subject
     * @param $result
     * @return mixed
     */
    public function afterBuildRequestData(
        \CyberSource\SecureAcceptance\Helper\RequestDataBuilder $subject,
        $result
    ) {

        $result['override_custom_receipt_page'] = $this->getCustomReceiptPageUrl();

        unset($result['signed_field_names']);
        unset($result['signature']);
        $result[\CyberSource\SecureAcceptance\Helper\RequestDataBuilder::KEY_SCOPE] = \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE;
        $storeId = $result[\CyberSource\SecureAcceptance\Helper\RequestDataBuilder::KEY_STORE_ID] ?? null;
        $result['access_key'] = $this->configProvider->getAccessKey($storeId);
        $result['profile_id'] = $this->configProvider->getProfileId($storeId);
        $result['signed_field_names'] = $subject->getSignedFields($result);
        $result['signature'] = $subject->sign($result, $this->configProvider->getSecretKey($storeId));

        return $result;
    }

    private function getCustomReceiptPageUrl()
    {
        return $this->urlBuilder->getUrl('chcybersource/transparent/response',
            ['_secure' => $this->request->isSecure()]);
    }

    /**
     * @return \Magento\Quote\Model\Quote\Interceptor
     */
    private function getQuote()
    {
        return $this->checkoutSession->getQuote();
    }

}
