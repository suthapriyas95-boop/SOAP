<?php

namespace CyberSource\SecureAcceptance\Gateway\Exception;

class InvalidRequestException extends \Exception
{
    public $fieldErrors;
    public $links;
}
