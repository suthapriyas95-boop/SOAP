<?php

namespace CyberSource\PayPal\Model;

class Credit extends Payment
{
    /**
     * Payment method code
     * @var string
     */
    protected $_code  = Config::CODE_CREDIT;

    /**
     * Checkout payment form
     * @var string
     */
    protected $_formBlockType = \CyberSource\PayPal\Block\Bml\Form::class;

    /**
     * Checkout redirect URL getter for onepage checkout
     *
     * @return string
     */
    public function getCheckoutRedirectUrl()
    {
        return $this->urlBuilder->getUrl('cybersourcepaypal/bml/start');
    }

    public function isAvailable(?\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return parent::isAvailable($quote) && $this->gatewayConfig->isPayPalCreditEnabled();
    }
}
