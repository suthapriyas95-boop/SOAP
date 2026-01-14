<?php

namespace CyberSource\Atp\Observer;

use CyberSource\Atp\Model\Config;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

class UpdateRejectedObserver implements ObserverInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        if (! $this->config->isInternalResolution()) {
            return;
        }

        throw new LocalizedException(__($this->config->getRejectionMessage()));
    }
}
