<?php

namespace CyberSource\Atp\Model;

use Magento\Framework\Event\ManagerInterface;

class DmeResultPropagationManager
{
    const EVENT_PREFIX = 'cybersource_atp_';

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @param ManagerInterface $manager
     */
    public function __construct(ManagerInterface $manager)
    {
        $this->eventManager = $manager;
    }

    /**
     * @param DmeValidationResult $result
     */
    public function propagate(DmeValidationResult $result)
    {
        $type = $result->getType();
        $decision = $result->getDecision() ?? '';
        $eventData = $result->getEventData();

        $eventData = array_merge($eventData, ['type' => $type, 'decision' => $decision]);

        // cybersource_atp_login
        $this->eventManager->dispatch(self::EVENT_PREFIX . $type, $eventData);

        // cybersource_atp_challenge
        $this->eventManager->dispatch(self::EVENT_PREFIX . strtolower($decision), $eventData);

        // cybersource_atp_login_challenge
        $this->eventManager->dispatch(self::EVENT_PREFIX . $type . '_' . strtolower($decision), $eventData);
    }
}
