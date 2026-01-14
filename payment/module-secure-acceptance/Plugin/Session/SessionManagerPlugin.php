<?php
/*
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\SecureAcceptance\Plugin\Session;

use CyberSource\SecureAcceptance\Helper\RequestDataBuilder;

class SessionManagerPlugin
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
     * @var \Magento\Framework\Session\SessionStartChecker
     */
    private $sessionStartChecker;

    /**
     * SidResolverPlugin constructor.
     *
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \CyberSource\SecureAcceptance\Gateway\Config\SaConfigProviderInterfaceFactory $configProviderFactory,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \CyberSource\SecureAcceptance\Model\SignatureManagementInterface $signatureManagement,
        \Magento\Framework\Session\SessionStartChecker $sessionStartChecker
    ) {

        $this->request = $request;
        $this->configProvider = $configProviderFactory->create();
        $this->encryptor = $encryptor;
        $this->signatureManagement = $signatureManagement;
        $this->sessionStartChecker = $sessionStartChecker;
    }

    /**
     * @param \Magento\Framework\Session\SessionManagerInterface $subject
     */
    public function beforeStart($subject)
    {

        if (!$this->request->isPost()) {
            return;
        }

        if (!$this->sessionStartChecker->check()) {
            return;
        }

        if (session_status() !== PHP_SESSION_NONE || headers_sent()) {
            return;
        }

        if (!$encryptedSid = $this->getSaReqParam(RequestDataBuilder::KEY_SID)) {
            return;
        }

        $storeId = $this->getSaReqParam(RequestDataBuilder::KEY_STORE_ID);

        if (!$this->signatureManagement->validateSignature(
            $this->request->getParams(),
            $this->configProvider->getSecretKey($storeId)
        )) {
            return;
        }

        $subject->setSessionId($this->encryptor->decrypt($encryptedSid));

    }

    private function getSaReqParam($value)
    {
        return $this->request->getParam('req_' . $value, null);
    }

}
