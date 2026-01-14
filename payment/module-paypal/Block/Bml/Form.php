<?php

namespace CyberSource\PayPal\Block\Bml;

use CyberSource\PayPal\Block\Express;
use CyberSource\PayPal\Model\Config;

class Form extends Express\Form
{
    /**
     * Payment method code
     * @var string
     */
    protected $_methodCode = Config::CODE_CREDIT;

    /**
     * Set template and redirect message
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_config = $this->_paypalConfigFactory->create()->setMethod($this->getMethodCode());
        /** @var $mark \Magento\Framework\View\Element\Template */
        $mark = $this->_getMarkTemplate();
        $mark->setPaymentAcceptanceMarkHref(
            'https://www.securecheckout.billmelater.com/paycapture-content/'
            . 'fetch?hash=AU826TU8&content=/bmlweb/ppwpsiw.html'
        )->setPaymentAcceptanceMarkSrc(
            'https://www.paypalobjects.com/webstatic/en_US/i/buttons/ppc-acceptance-medium.png'
        )->setPaymentWhatIs(__('See terms'));

        $this->_initializeRedirectTemplateWithMark($mark);
    }
}
