<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace CyberSource\PayPal\Controller\Express;

use Magento\Checkout\Helper\Data;
use Magento\Checkout\Helper\ExpressRedirect;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Framework\Exception\LocalizedException;
use CyberSource\PayPal\Model\Express\Checkout;

/**
 * Class GetToken
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GetToken extends \Magento\Paypal\Controller\Express\GetToken
{
    /**
     * Config mode type
     *
     * @var string
     */
    protected $_configType = \Magento\Paypal\Model\Config::class;

    /**
     * Config method type
     *
     * @var string
     */
    protected $_configMethod = \CyberSource\PayPal\Model\Config::CODE;

    /**
     * Checkout mode type
     *
     * @var string
     */
    protected $_checkoutType = \CyberSource\PayPal\Model\Express\Checkout::class;

    /**
     * @return string|null
     * @throws LocalizedException
     */
    protected function getToken()
    {
        $this->_initCheckout();
        $quote = $this->_getQuote();
        $hasButton = $this->getRequest()->getParam(Checkout::PAYMENT_INFO_BUTTON) == 1;

        /** @var Data $checkoutHelper */
        $checkoutHelper = $this->_objectManager->get(Data::class);
        $quoteCheckoutMethod = $quote->getCheckoutMethod();
        $customerData = $this->_customerSession->getCustomerDataObject();

        if ($quote->getIsMultiShipping()) {
            $quote->setIsMultiShipping(false);
            $quote->removeAllAddresses();
        }

        if ($customerData->getId()) {
            $this->_checkout->setCustomerWithAddressChange(
                $customerData,
                $quote->getBillingAddress(),
                $quote->getShippingAddress()
            );
        } elseif ((!$quoteCheckoutMethod || $quoteCheckoutMethod !== Onepage::METHOD_REGISTER)
                && !$checkoutHelper->isAllowedGuestCheckout($quote, $quote->getStoreId())
        ) {
            $expressRedirect = $this->_objectManager->get(ExpressRedirect::class);

            $this->messageManager->addNoticeMessage(
                __('To check out, please sign in with your email address.')
            );

            $expressRedirect->redirectLogin($this);
            $this->_customerSession->setBeforeAuthUrl(
                $this->_url->getUrl('*/*/*', ['_current' => true])
            );

            return null;
        }

        // Bill Me Later
        $this->_checkout->setIsBml((bool)$this->getRequest()->getParam('bml'));

        return $this->_checkout->start(
            $this->_url->getUrl('*/*/return'),
            $this->_url->getUrl('*/*/cancel'),
            $hasButton
        );
    }
}
