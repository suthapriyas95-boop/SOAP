<?php

namespace CyberSource\Core\Plugin;

use CyberSource\Core\Model\Logger;
use CyberSource\Core\Model\LoggerInterface;

class LoggerCensorPlugin
{
    /**
     * @var Logger\Censor
     */
    private $censor;

    /**
     * @param Logger\Censor $censor
     */
    public function __construct(Logger\Censor $censor)
    {
        $this->censor = $censor;
    }

    /**
     * @param LoggerInterface $subject
     * @param $data
     * @param array $context
     * @return array
     */
    public function beforeDebug(LoggerInterface $subject, $data, array $context = [])
    {
        return [$this->censor->censor($data), $context];
    }

    /**
     * @param LoggerInterface $subject
     * @param $message
     * @param array $context
     * @return array
     */
    public function beforeEmergency(LoggerInterface $subject, $message, array $context = [])
    {
        return [$this->censor->censor($message), $context];
    }

    /**
     * @param LoggerInterface $subject
     * @param $message
     * @param array $context
     * @return array
     */
    public function beforeAlert(LoggerInterface $subject, $message, array $context = [])
    {
        return [$this->censor->censor($message), $context];
    }

    /**
     * @param LoggerInterface $subject
     * @param $message
     * @param array $context
     * @return array
     */
    public function beforeCritical(LoggerInterface $subject, $message, array $context = [])
    {
        return [$this->censor->censor($message), $context];
    }

    /**
     * @param LoggerInterface $subject
     * @param $message
     * @param array $context
     * @return array
     */
    public function beforeError(LoggerInterface $subject, $message, array $context = [])
    {
        return [$this->censor->censor($message), $context];
    }

    /**
     * @param LoggerInterface $subject
     * @param $message
     * @param array $context
     * @return array
     */
    public function beforeWarning(LoggerInterface $subject, $message, array $context = [])
    {
        return [$this->censor->censor($message), $context];
    }

    /**
     * @param LoggerInterface $subject
     * @param $message
     * @param array $context
     * @return array
     */
    public function beforeNotice(LoggerInterface $subject, $message, array $context = [])
    {
        return [$this->censor->censor($message), $context];
    }

    /**
     * @param LoggerInterface $subject
     * @param $message
     * @param array $context
     * @return array
     */
    public function beforeInfo(LoggerInterface $subject, $message, array $context = [])
    {
        return [$this->censor->censor($message), $context];
    }

    /**
     * @param LoggerInterface $subject
     * @param $level
     * @param $message
     * @param array $context
     * @return array
     */
    public function beforeLog(LoggerInterface $subject, $level, $message, array $context = [])
    {
        return [$level, $this->censor->censor($message), $context];
    }
}
