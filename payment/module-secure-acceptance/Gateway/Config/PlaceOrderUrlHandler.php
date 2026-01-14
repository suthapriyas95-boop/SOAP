<?php

namespace CyberSource\SecureAcceptance\Gateway\Config;

class PlaceOrderUrlHandler implements \Magento\Payment\Gateway\Config\ValueHandlerInterface
{

    /**
     * @var bool
     */
    private $isAdmin;

    /**
     * @var Config
     */
    protected $config;

    public function __construct(
        
        \CyberSource\SecureAcceptance\Gateway\Config\Config $config,
        bool $isAdmin = false
    ) {
        $this->isAdmin = $isAdmin;
        $this->config  = $config;
    }

    /**
     * Retrieve method configured value
     *
     * @param array $subject
     * @param int|null $storeId
     *
     * @return mixed
     */
    public function handle(array $subject, $storeId = null)
    {

        if ($this->isAdmin) {
            if($this->config->isMicroform()){
                return 'chcybersource/microform/tokenRequest';
            }
			else{	
                return 'chcybersource/transparent/requestSilentData';
            }
        }

        return 'cybersource/index/loadSilentData';
    }
}
