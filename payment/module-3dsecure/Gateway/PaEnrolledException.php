<?php
/**
 *
 */

namespace CyberSource\ThreeDSecure\Gateway;

class PaEnrolledException extends \Magento\Framework\Webapi\Exception
{

    public function __construct(
        \Magento\Framework\Phrase $phrase,
        $reasonCode,
        int $httpCode = self::HTTP_BAD_REQUEST,
        array $details = [],
        string $name = '',
        ?array $errors = null,
        ?string $stackTrace = null
    ) {
        parent::__construct($phrase, $reasonCode, $httpCode, $details, $name, $errors, $stackTrace);
    }
}
