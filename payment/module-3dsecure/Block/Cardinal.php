<?php
/**
 *
 */

namespace CyberSource\ThreeDSecure\Block;

class Cardinal extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \CyberSource\ThreeDSecure\Gateway\Config\Config
     */
    private $config;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \CyberSource\ThreeDSecure\Gateway\Config\Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
    }

    public function isSandbox()
    {
        return $this->config->isTestMode();
    }
}
