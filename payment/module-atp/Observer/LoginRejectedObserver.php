<?php

namespace CyberSource\Atp\Observer;

use CyberSource\Atp\Model\Config;
use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

class LoginRejectedObserver implements ObserverInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Session
     */
    private $session;

    /**
     * @param Config $config
     * @param Session $session
     */
    public function __construct(
        Config $config,
        Session $session
    ) {
        $this->config = $config;
        $this->session = $session;
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

        $this->session->logout();
        throw new LocalizedException(__($this->config->getRejectionMessage()));
    }
}
