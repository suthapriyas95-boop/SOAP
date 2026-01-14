<?php

namespace CyberSource\Atp\Model;

class DmeValidationResult
{
    /**
     * @var string
     */
    private $decision;

    /**
     * @var string
     */
    private $type;

    /**
     * @var array
     */
    private $eventData;

    /**
     * @return array
     */
    public function getEventData()
    {
        return $this->eventData;
    }

    /**
     * @param array $eventData
     * @return DmeValidationResult
     */
    public function setEventData($eventData)
    {
        $this->eventData = $eventData;
        return $this;
    }

    /**
     * @return string
     */
    public function getDecision()
    {
        return $this->decision;
    }

    /**
     * @param string $decision
     * @return DmeValidationResult
     */
    public function setDecision($decision)
    {
        $this->decision = $decision;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return DmeValidationResult
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAccepted()
    {
        return $this->decision == Source\Actions::ACTION_ACCEPT;
    }

    /**
     * @return bool
     */
    public function isChallenge()
    {
        return $this->decision == Source\Actions::ACTION_CHALLENGE;
    }

    /**
     * @return bool
     */
    public function isRejected()
    {
        return $this->decision == Source\Actions::ACTION_REJECT;
    }
}
