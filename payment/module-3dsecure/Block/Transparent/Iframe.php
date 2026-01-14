<?php
/**
 *
 */

namespace CyberSource\ThreeDSecure\Block\Transparent;

class Iframe extends \Magento\Payment\Block\Transparent\Iframe
{

    /**
     * @var \CyberSource\ThreeDSecure\Gateway\Config\Config
     */
    private $threeDsConfig;

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Config\Config
     */
    private $config;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \CyberSource\ThreeDSecure\Gateway\Config\Config $threeDsConfig,
        \CyberSource\SecureAcceptance\Gateway\Config\Config $config,
        array $data = []
    ) {
        parent::__construct($context, $registry, $data);
        $this->threeDsConfig = $threeDsConfig;
        $this->config = $config;
    }

    public function getParams()
    {
        $params = parent::getParams();

        if ($this->config->isSilent()) {
            return $params;
        }

        return array_merge($params, [
            '3ds_active' => true
        ]);
    }
}
