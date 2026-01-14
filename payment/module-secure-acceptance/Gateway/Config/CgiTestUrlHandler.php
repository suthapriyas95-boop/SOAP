<?php

namespace CyberSource\SecureAcceptance\Gateway\Config;

class CgiTestUrlHandler extends CgiUrlHandler
{

    protected function getServiceUrl()
    {
        return $this->config->getSopServiceUrlTest();
    }
}
