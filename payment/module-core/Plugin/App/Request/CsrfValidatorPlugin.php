<?php

namespace CyberSource\Core\Plugin\App\Request;

class CsrfValidatorPlugin
{


    /**
     * This plugin method makes some CyberSource module controllers bypass CSRF check completely.
     * Unfortunately, we are not able to implement \Magento\Framework\App\CsrfAwareActionInterface
     * because we must support both 2.2 and 2.3 branches of Magento but that interface requires usage of nullable
     * return types which are not unsupported by PHP 7.0.
     *
     * @param $subject
     * @param $proceed
     * @param $request
     * @param $action
     * @return bool
     */
    public function aroundValidate($subject, $proceed, $request, $action)
    {
        if ($action instanceof \CyberSource\Core\Action\CsrfIgnoringAction) {
            return true;
        }

        return $proceed($request, $action);
    }
}
