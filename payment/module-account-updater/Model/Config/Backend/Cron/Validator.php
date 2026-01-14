<?php

namespace CyberSource\AccountUpdater\Model\Config\Backend\Cron;

class Validator
{
    /**
     * @param string $expr
     * @return bool
     */
    public function validate($expr)
    {
        // @TODO: add more strict validation of cron expr
        return count(explode(' ', $expr ?? '')) === 5;
    }
}
