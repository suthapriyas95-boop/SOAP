<?php

namespace CyberSource\Atp\Plugin;

use Magento\Framework\Registry;
use CyberSource\Atp\Model\Config;
use CyberSource\Atp\Service\CyberSourceSoapAPI;
use CyberSource\Atp\Model\DmeValidationResultFactory;
use CyberSource\Atp\Model\DmeResultPropagationManager;

abstract class AbstractAtpPlugin
{
    const KEY_REGISTRY_ATP_IN_PROGRESS = 'atp_action_in_progress';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var CyberSourceSoapAPI
     */
    protected $cyberSourceSoapApi;

    /**
     * @var DmeResultPropagationManager
     */
    protected $eventPropagationManager;

    /**
     * @var DmeValidationResultFactory
     */
    protected $dmeResultFactory;

    /**
     * @param Config $config
     * @param Registry $registry
     * @param CyberSourceSoapAPI $cyberSourceSoapClient
     * @param DmeResultPropagationManager $eventPropagationManager
     * @param DmeValidationResultFactory $dmeResultFactory
     */
    public function __construct(
        Config $config,
        Registry $registry,
        CyberSourceSoapAPI $cyberSourceSoapClient,
        DmeResultPropagationManager $eventPropagationManager,
        DmeValidationResultFactory $dmeResultFactory
    ) {
        $this->config = $config;
        $this->registry = $registry;
        $this->cyberSourceSoapApi = $cyberSourceSoapClient;
        $this->eventPropagationManager = $eventPropagationManager;
        $this->dmeResultFactory = $dmeResultFactory;
    }

    /**
     * @return bool
     */
    protected function canProcessAtpEvent()
    {
        if (! $this->config->isActive()) {
            return false;
        }

        if ($this->registry->registry(self::KEY_REGISTRY_ATP_IN_PROGRESS)) {
            return false;
        }

        return true;
    }

    /**
     * @return void
     */
    protected function preventFurtherAtpProcessing()
    {
        $this->registry->register(self::KEY_REGISTRY_ATP_IN_PROGRESS, true, true);
    }
}
