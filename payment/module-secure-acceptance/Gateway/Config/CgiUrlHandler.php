<?php

namespace CyberSource\SecureAcceptance\Gateway\Config;

class CgiUrlHandler implements \Magento\Payment\Gateway\Config\ValueHandlerInterface
{

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var false
     */
    private $isAdmin;

    /**
     * CgiUrlHandler constructor.
     * @param Config $config
     */
    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Config\Config $config,
        $isAdmin = false
    ) {
        $this->config = $config;
        $this->isAdmin = $isAdmin;
    }

    /**
     * @inheritdoc
     */
    public function handle(array $subject, $storeId = null)
    {
        $uri = '/pay';

        if ($this->config->getUseIFrame()) {
            $uri = '/embedded/pay';
        }

        if ($this->config->isSilent()) {
            $uri = '/silent/pay';
        }

        if($this->config->isMicroform()){
            $uri = '/token/create';
        }

        if (!$this->config->getIsLegacyMode() && !$this->isAdmin) {

            $uri = '/token/create';

            if ($this->config->getUseIFrame()) {
                $uri = '/embedded/token/create';
            }

            if ($this->config->isSilent()) {
                $uri = '/silent/embedded/token/create';
            }
        }

        return $this->getServiceUrl() . $uri;
    }

    protected function getServiceUrl()
    {
        return $this->config->getSopServiceUrl();
    }
}
