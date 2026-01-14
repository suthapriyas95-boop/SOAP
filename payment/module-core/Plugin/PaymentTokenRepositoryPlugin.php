<?php

namespace CyberSource\Core\Plugin;

class PaymentTokenRepositoryPlugin
{
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    private $checkoutSession;

    /**
     * @var \Magento\Payment\Gateway\Config\Config
     */
    private $config;

    /**
     * @var string
     */
    private $configKey;

    /**
     * @var string
     */
    private $methodCode;

    /**
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     * @param \Magento\Framework\Session\SessionManagerInterface $checkoutSession
     * @param \Magento\Payment\Gateway\Config\Config $config
     * @param null|string $configKey
     * @param null|string $methodCode
     */
    public function __construct(
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        \Magento\Framework\Session\SessionManagerInterface $checkoutSession,
        \Magento\Payment\Gateway\Config\Config $config,
        $configKey = null,
        $methodCode = null
    ) {
        $this->serializer = $serializer;
        $this->checkoutSession = $checkoutSession;
        $this->config = $config;
        $this->configKey = $configKey;
        $this->methodCode = $methodCode;
    }

    /**
     * @param \Magento\Vault\Model\PaymentTokenRepository $subject
     * @param \Magento\Vault\Api\Data\PaymentTokenSearchResultsInterface $result
     *
     * @return mixed
     */
    public function afterGetList(\Magento\Vault\Model\PaymentTokenRepository $subject, $result)
    {
        if (!$this->methodCode) {
            return $result;
        }

        $validTokens = [];
        foreach ($result->getItems() as $token) {
            if ($this->isValidToken($token)) {
                $validTokens[] = $token;
            }
        }

        return $result->setItems($validTokens);
    }

    /**
     * @param \Magento\Vault\Api\Data\PaymentTokenInterface $token
     * @return bool
     */
    private function isValidToken($token)
    {
        if ($token->getPaymentMethodCode() != $this->methodCode) {
            return true;
        }

        try {
            $details = $this->serializer->unserialize($token->getTokenDetails());
            if ($tokenMerchantId = $details['merchantId'] ?? false) {
                $currentStoreId = $this->checkoutSession->getStore()
                    ? $this->checkoutSession->getStore()->getId()
                    : $this->checkoutSession->getQuote()->getStoreId();

                return $this->config->getValue($this->configKey, $currentStoreId) == $tokenMerchantId;
            }
        } catch (\Exception $e) {

        }

        return true;
    }
}
