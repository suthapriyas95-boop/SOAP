<?php
/**
 *
 */

namespace CyberSource\ThreeDSecure\Webapi\Error;

class ProcessorPlugin
{

    public function beforeMaskException(
        \Magento\Framework\Webapi\ErrorProcessor $subject,
        \Exception $e
    ) {
        $previousException = $e->getPrevious();
        if ($previousException && $previousException instanceof \CyberSource\ThreeDSecure\Gateway\PaEnrolledException) {
            return [$previousException];
        }
        return [$e];
    }
}
