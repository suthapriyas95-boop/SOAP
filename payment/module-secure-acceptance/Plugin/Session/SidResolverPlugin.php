<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Plugin\Session;

/**
 * Class SidResolverPlugin
 */
class SidResolverPlugin
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Config\SaConfigProviderInterface
     */
    private $configProvider;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $encryptor;

    /**
     * @var \CyberSource\SecureAcceptance\Model\SignatureManagementInterface
     */
    private $signatureManagement;

    /**
     * SidResolverPlugin constructor.
     *
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \CyberSource\SecureAcceptance\Gateway\Config\SaConfigProviderInterfaceFactory $configProviderFactory,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \CyberSource\SecureAcceptance\Model\SignatureManagementInterface $signatureManagement
    ) {
        $this->request = $request;
        $this->configProvider = $configProviderFactory->create();
        $this->encryptor = $encryptor;
        $this->signatureManagement = $signatureManagement;
    }

    public function afterGetSid(
        \Magento\Framework\Session\SidResolverInterface $subject,
        $result,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager
    ) {
        if ($result !== null) {
            return $result;
        }

        if (!$this->request->isPost()) {
            return $result;
        }

        if (!$encryptedSid = $this->request->getParam(
            'req_' . \CyberSource\SecureAcceptance\Helper\RequestDataBuilder::KEY_SID
        )) {
            return $result;
        }

        $storeId = $this->getSaReqParam(\CyberSource\SecureAcceptance\Helper\RequestDataBuilder::KEY_STORE_ID);

        if (!$this->signatureManagement->validateSignature($this->request->getParams(), $this->configProvider->getSecretKey($storeId))) {
            return $result;
        }

        return $this->encryptor->decrypt($encryptedSid);
    }

    private function getSaReqParam($value)
    {
        return $this->request->getParam('req_' . $value, null);
    }

}
