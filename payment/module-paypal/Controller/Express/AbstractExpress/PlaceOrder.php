<?php

namespace CyberSource\PayPal\Controller\Express\AbstractExpress;

/**
 * Class PlaceOrder
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PlaceOrder extends \CyberSource\PayPal\Controller\Express\AbstractExpress
{
    /**
     * Submit the order
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        try {
            $this->_initCheckout();

            $this->_checkout->place($this->_initToken());

            // prepare session to success or cancellation page
            $this->_getCheckoutSession()->clearHelperData();

            // last successful quote
            $quoteId = $this->_getQuote()->getId();
            $this->_getCheckoutSession()->setLastQuoteId($quoteId)->setLastSuccessQuoteId($quoteId);

            // an order may be created
            $order = $this->_checkout->getOrder();
            if ($order) {
                $this->_getCheckoutSession()->setLastOrderId($order->getId())
                    ->setLastRealOrderId($order->getIncrementId())
                    ->setLastOrderStatus($order->getStatus());
            }

            $this->_initToken(false);
            $this->_redirect('checkout/onepage/success');
            return;
        } catch (\Exception $e) {
            $this->cyberLogger->critical("Unable to place PayPal Order: " . $e->getMessage(), ['stacktrace' => $e->getTraceAsString()]);
            $this->_redirectToCartAndShowError(__('We can\'t place the order.'));
        }
    }

    /**
     * Redirect customer back to PayPal with the same token
     *
     * @return void
     */
    protected function _redirectSameToken()
    {
        $token = $this->_initToken();
        $this->getResponse()->setRedirect(
            $this->gatewayConfig->getExpressCheckoutStartUrl($token)
        );
    }

    /**
     * Redirect customer to shopping cart and show error message
     *
     * @param string $errorMessage
     * @return void
     */
    protected function _redirectToCartAndShowError($errorMessage)
    {
        $this->messageManager->addErrorMessage($errorMessage);
        $this->_redirect($this->paymentFailureRouteProvider->getFailureRoutePath());
    }
}
