<?php

namespace CyberSource\Atp\Model;

use CyberSource\Core\Model\AbstractGatewayConfig;

class Config extends AbstractGatewayConfig
{
    const LOG_FILE = 'var/log/atp.log';

    const KEY_ACTIVE = 'atp_active';
    const KEY_REJECTION_MESSAGE = 'atp_rejection_message';
    const KEY_ACTION_ON_ERROR = 'atp_action_on_error';
    const KEY_INTERNAL_RESOLUTION = 'atp_internal_resolution';

    /**
     * @return bool
     */
    public function isActive()
    {
        return (bool) $this->getValue(self::KEY_ACTIVE);
    }

    /**
     * @return bool
     */
    public function isInternalResolution()
    {
        return (bool) $this->getValue(self::KEY_INTERNAL_RESOLUTION);
    }

    /**
     * @return string
     */
    public function getRejectionMessage()
    {
        return $this->getValue(self::KEY_REJECTION_MESSAGE);
    }

    /**
     * @return string
     */
    public function getActionOnError()
    {
        return $this->getValue(self::KEY_ACTION_ON_ERROR);
    }
}
