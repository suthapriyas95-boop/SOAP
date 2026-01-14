<?php
/**
 *
 */

namespace CyberSource\ThreeDSecure\Model\Ui;

class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{


    /**
     * @var \CyberSource\ThreeDSecure\Gateway\Config\Config
     */
    private $threeDsConfig;

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Config\Config
     */
    private $saConfig;

    public function __construct(
        \CyberSource\ThreeDSecure\Gateway\Config\Config $threeDsConfig,
        \CyberSource\SecureAcceptance\Gateway\Config\Config $saConfig,
        \CyberSource\SecureAcceptance\Helper\RequestDataBuilder $requestDataBuilder
    ) {
        $this->threeDsConfig = $threeDsConfig;
        $this->saConfig = $saConfig;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {

        if (!$this->threeDsConfig->isEnabled()) {
            return [];
        }

        return [
            'payment' => [
                \CyberSource\SecureAcceptance\Model\Ui\ConfigProvider::CODE => [
                    '3ds_enabled' => $this->isEnabledForSa(),
                    '3ds_testmode' => (bool)$this->threeDsConfig->isTestMode(),
                    '3ds_cards' => $this->threeDsConfig->getEnabledCards(),
                ],
                \CyberSource\SecureAcceptance\Model\Ui\ConfigProvider::CC_VAULT_CODE => [
                    '3ds_enabled' => true,
                    '3ds_testmode' => (bool)$this->threeDsConfig->isTestMode(),
                    '3ds_cards' => $this->threeDsConfig->getEnabledCards(),
                ],
            ]
        ];
    }

    private function isEnabledForSa()
    {
        return $this->saConfig->isSilent() || $this->saConfig->isMicroform();
    }
}
