<?php

namespace CyberSource\SecureAcceptance\Gateway\Config;

class CcTypesHandler implements \Magento\Payment\Gateway\Config\ValueHandlerInterface
{

    /**
     * @var Config
     */
    protected $config;

    /**
     * CcTypesHandler constructor.
     * @param Config $config
     */
    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Config\Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function handle(array $subject, $storeId = null)
    {
        return $this->config->getCcTypes();
    }
}
