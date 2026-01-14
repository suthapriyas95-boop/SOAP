<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\GooglePay\Controller\Index;


class Place extends \Magento\Framework\App\Action\Action
{

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var \CyberSource\GooglePay\Model\AddressConverter
     */
    private $addressConverter;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    private $formKeyValidator;

    /**
     * @var \CyberSource\Core\Model\Checkout\PaymentFailureRouteProviderInterface
     */
    private $paymentFailureRouteProvider;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \CyberSource\GooglePay\Model\AddressConverter $addressConverter,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \CyberSource\Core\Model\Checkout\PaymentFailureRouteProviderInterface $paymentFailureRouteProvider
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->cartRepository = $cartRepository;
        $this->cartManagement = $cartManagement;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->addressConverter = $addressConverter;
        $this->formKeyValidator = $formKeyValidator;
        $this->paymentFailureRouteProvider = $paymentFailureRouteProvider;
    }


    /**
     * @inheritDoc
     */
    public function execute()
    {

        $result = $this->resultJsonFactory->create();

        try {

            if (!$this->formKeyValidator->validate($this->getRequest())) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Invalid formkey'));
            }

            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $this->checkoutSession->getQuote();

            if (!$quote->getId() || !$quote->getIsActive()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Quote not found. Please refresh the page and try again.'));
            }

            $quote->reserveOrderId();

            $quote = $this->ignoreAddressValidation($quote);
            $quote->setPaymentMethod(\CyberSource\GooglePay\Model\Ui\ConfigProvider::CODE);
            $quote->setInventoryProcessed(false);

            $quote->getPayment()->importData(
                [
                    'method' => \CyberSource\GooglePay\Model\Ui\ConfigProvider::CODE,
                    'paymentToken' => $this->_request->getParam('token'),
                ]
            );

            if ($billingAddressData = $this->getRequest()->getParam('billingAddress')) {
                $billingAddress = $this->addressConverter->convertGoogleAddress($billingAddressData);
                $billingAddress
                    ->setCustomerAddressId(null)
                    ->setEmail($quote->getBillingAddress()->getEmail())
                    ->setCustomerId($quote->getCustomerId());
                $quote->setBillingAddress($billingAddress);
            }

            if ($shippingAddressData = $this->getRequest()->getParam('shippingAddress')) {
                $shippingAddress = $this->addressConverter->convertGoogleAddress($shippingAddressData);
                $shippingAddress
                    ->setCustomerAddressId(null)
                    ->setEmail($quote->getBillingAddress()->getEmail())
                    ->setCustomerId($quote->getCustomerId());
                $quote->setShippingAddress($shippingAddress);
            }

            if (!$quote->getCustomerId()) {
                $guestEmail = $this->getRequest()->getParam('email');

                if (!$guestEmail) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('Email is required.'));
                }

                $billingAddress = $quote->getBillingAddress();
                $billingAddress->setEmail($guestEmail);

                $quote
                    ->setCustomerEmail($guestEmail)
                    ->setCustomerIsGuest(1)
                    ->setCustomerFirstname($billingAddress->getFirstname())
                    ->setCustomerLastname($billingAddress->getFirstname())
                    ;
            }

            $quote->collectTotals();
            $this->cartRepository->save($quote);

            $this->checkoutSession->clearHelperData();

            $order = $this->cartManagement->submit($quote);
            $this->checkoutSession->setLastSuccessQuoteId($quote->getId());
            $this->checkoutSession->setLastQuoteId($quote->getId());
            $this->checkoutSession->setLastOrderId($order->getId());
            $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
            $this->checkoutSession->setLastOrderStatus($order->getStatus());

            return $result->setData([
                'status' => 200,
                'message' => __('Your order has been successfully created!'),
                'redirect_url' => $this->_url->getUrl('checkout/onepage/success'),
            ]);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $result->setData([
                'status' => 500,
                'message' => $e->getMessage(),
                'redirect_url' => $this->_url->getUrl($this->paymentFailureRouteProvider->getFailureRoutePath())
            ]);
        } catch (\Exception $e) {
            $result->setData([
                'status' => 500,
                'message' => __('Unable to place order. Please try again.'),
                'redirect_url' => $this->_url->getUrl($this->paymentFailureRouteProvider->getFailureRoutePath())
            ]);
        }

        return $result;
    }

    /**
     * Make sure addresses will be saved without validation errors
     *
     * @param \Magento\Quote\Model\Quote $quote
     *
     * @return
     */
    private function ignoreAddressValidation($quote)
    {
        $quote->getBillingAddress()->setShouldIgnoreValidation(true);
        if (!$quote->getIsVirtual()) {
            $quote->getShippingAddress()->setShouldIgnoreValidation(true);
        }

        return $quote;
    }

}
