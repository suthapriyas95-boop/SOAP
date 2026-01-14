<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\WeChatPay\Model\Ui;

/**
 * Class ConfigProvider
 * @codeCoverageIgnore
 */
class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    const CODE = 'cybersourcewechatpay';

    /**
     * @var \CyberSource\WeChatPay\Gateway\Config\Config
     */
    private $config;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    private $assetRepository;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $url;

    /**
     * @var \CyberSource\Core\Model\Checkout\PaymentFailureRouteProviderInterface
     */
    private $paymentFailureRouteProvider;

    /**
     * @param \CyberSource\WeChatPay\Gateway\Config\Config $config
     * @param \Magento\Framework\View\Asset\Repository $assetRepository
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\UrlInterface $url
     * @param \CyberSource\Core\Model\Checkout\PaymentFailureRouteProviderInterface $paymentFailureRouteProvider
     */
    public function __construct(
        \CyberSource\WeChatPay\Gateway\Config\Config $config,
        \Magento\Framework\View\Asset\Repository $assetRepository,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\UrlInterface $url,
        \CyberSource\Core\Model\Checkout\PaymentFailureRouteProviderInterface $paymentFailureRouteProvider
    ) {
        $this->config = $config;
        $this->assetRepository = $assetRepository;
        $this->request = $request;
        $this->url = $url;
        $this->paymentFailureRouteProvider = $paymentFailureRouteProvider;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'isActive' => $this->config->isActive(),
                    'title' => $this->config->getTitle(),
                    'iconUrl' => $this->getIconUrl(),
                    'checkStatusFrequency' => $this->config->getCheckStatusFrequency(),
                    'maxStatusRequests' => $this->config->getMaxStatusRequests(),
                    'popupMessageDelay' => $this->config->getPopupMessageDelay(),
                    'failureRedirectUrl' => $this->url->getUrl($this->paymentFailureRouteProvider->getFailureRoutePath()),
                    'successUrl' => $this->url->getUrl('checkout/onepage/success'),
                    'failureUrl' => $this->url->getUrl('checkout/onepage/failure')
                ]
            ]
        ];
    }

    /**
     * @return string
     */
    private function getIconUrl()
    {
        return $this->assetRepository->getUrlWithParams(
            'CyberSource_WeChatPay::assets/wcp.png',
            ['_secure' => $this->request->isSecure()]
        );
    }
}
