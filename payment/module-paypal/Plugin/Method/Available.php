<?php

namespace CyberSource\PayPal\Plugin\Method;

use \Magento\Checkout\Model\Cart;
class Available
{
    const ALLOW_COUNTRIES = 'payment/cybersourcepaypal/specificcountry';
    const ALLOW_SPECIFIC = 'payment/cybersourcepaypal/allowspecific';

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * PaymentMethodAvailable constructor.
     * @param Cart $cart
     */

    
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * AdminFailed constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */

    public function __construct(
        Cart $cart,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig ){
        $this->cart = $cart;
        $this->scopeConfig = $scopeConfig;
    }
 
    public function afterGetAvailableMethods($subject, $result)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $allowspecific = $this->scopeConfig->getValue(self::ALLOW_SPECIFIC, $storeScope);
        $allowCountriesId = explode(",",$this->scopeConfig->getValue(self::ALLOW_COUNTRIES, $storeScope) ?? '');
        $shippingAddressCollection = $this->cart->getQuote()->getShippingAddress();
        $billingCountryId = $shippingAddressCollection->getCountryId();

        foreach ($result as $key=>$_result) {
            if ($allowspecific == '1' && !in_array($billingCountryId, $allowCountriesId) && $_result->getCode() == "cybersourcepaypal_credit") {
                $isAllowed =  false; 
                if (!$isAllowed) {
                    unset($result[$key]);
                }
            }
        }
        return $result;
    }
}