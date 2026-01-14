<?php

namespace CyberSource\PayPal\Model\Ui;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterface;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;

/**
 * Class TokenUiComponentProvider
 */
class TokenUiComponentProvider implements TokenUiComponentProviderInterface
{
    /**
     * @var TokenUiComponentInterfaceFactory
     */
    private $componentFactory;

    /**
     * Config
     */
    private $config;

    /**
     * @param TokenUiComponentInterfaceFactory $componentFactory
     * @param \CyberSource\PayPal\Model\Config $config
     */
    public function __construct(
        TokenUiComponentInterfaceFactory $componentFactory,
        \CyberSource\PayPal\Model\Config $config
    ) {
        $this->componentFactory = $componentFactory;
        $this->config = $config;
    }

    /**
     * Get UI component for token
     * @param PaymentTokenInterface $paymentToken
     * @return TokenUiComponentInterface
     */
    public function getComponentForToken(PaymentTokenInterface $paymentToken)
    {
        $jsonDetails = json_decode($paymentToken->getTokenDetails() ?: '{}', true);
        $component = $this->componentFactory->create(
            [
                'config' => [
                    'code' => \CyberSource\PayPal\Model\Config::CODE_VAULT,
                    'title' => $this->config->getTitle(),
                    'maskedToken' => '****' . substr($paymentToken->getGatewayToken() ?? '', -4),
                    TokenUiComponentProviderInterface::COMPONENT_DETAILS => $jsonDetails,
                    TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => $paymentToken->getPublicHash(),
                ],
                'name' => 'CyberSource_PayPal/js/view/payment/method-renderer/vault'
            ]
        );

        return $component;
    }
}
