<?php

namespace CyberSource\Core\Model;

interface LoggerInterface extends \Psr\Log\LoggerInterface
{
    /**
     * @param array|string $data
     * @param array $context
     */
    public function debug($data, array $context = []):void;
}
