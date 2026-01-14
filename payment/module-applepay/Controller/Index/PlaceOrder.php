<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\ApplePay\Controller\Index;

use CyberSource\ApplePay\Helper\RequestDataBuilder;
use CyberSource\ApplePay\Model\Ui\ConfigProvider;
use CyberSource\ApplePay\Gateway\Http\Client\SOAPClient;
use CyberSource\ApplePay\Gateway\Http\TransferFactory;
use Magento\Checkout\Api\AgreementsValidatorInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Api\Data\AddressInterfaceFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Magento\Directory\Model\Region;

class PlaceOrder extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \Magento\Quote\Model\QuoteManagement $quoteManagement
     */
    private $quoteManagement;

    /**
     * @var JsonFactory $resultJsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var RequestDataBuilder
     */
    private $requestDataBuilder;

    /**
     * @var SOAPClient
     */
    private $soapClient;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var TransferFactory
     */
    private $transferFactory;

    /**
     * @var AgreementsValidatorInterface
     */
    private $agreementsValidator;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var AddressInterfaceFactory
     */
    private $addressDataFactory;

    /**
     * @var Region
     */
    private $region;
    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @var \CyberSource\Core\Model\Checkout\PaymentFailureRouteProviderInterface
     */
    private $paymentFailureRouteProvider;

    /**
     * @var Magento\Customer\Model\Customer
     */
    private $customer;

    /**
     * PlaceOrder constructor.
     *
     * @param Context $context
     * @param SessionManagerInterface $checkoutSession
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param JsonFactory $resultJsonFactory
     * @param RequestDataBuilder $requestDataBuilder
     * @param SOAPClient $soapClient
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param TransferFactory $transferFactory
     * @param AgreementsValidatorInterface $agreementsValidator
     * @param OrderRepository $orderRepository
     * @param AddressInterfaceFactory $addressDataFactory
     * @param Region $region
     * @param \CyberSource\Core\Model\Checkout\PaymentFailureRouteProviderInterface $paymentFailureRouteProvider
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Customer\Model\Customer $customer
     */
    public function __construct(
        Context $context,
        SessionManagerInterface $checkoutSession,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        RequestDataBuilder $requestDataBuilder,
        SOAPClient $soapClient,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        TransferFactory $transferFactory,
        AgreementsValidatorInterface $agreementsValidator,
        OrderRepository $orderRepository,
        AddressInterfaceFactory $addressDataFactory,
        Region $region,
        \CyberSource\Core\Model\Checkout\PaymentFailureRouteProviderInterface $paymentFailureRouteProvider,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Customer\Model\Customer $customer

    ) {
        parent::__construct($context);

        $this->checkoutSession = $checkoutSession;
        $this->quoteManagement = $quoteManagement;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->requestDataBuilder = $requestDataBuilder;
        $this->soapClient = $soapClient;
        $this->quoteRepository = $quoteRepository;
        $this->transferFactory = $transferFactory;
        $this->agreementsValidator = $agreementsValidator;
        $this->orderRepository = $orderRepository;
        $this->addressDataFactory = $addressDataFactory;
        $this->region = $region;
        $this->eventManager = $eventManager;
        $this->paymentFailureRouteProvider = $paymentFailureRouteProvider;
        $this->customer = $customer;

    }

    public function execute()
    {
        $paymentData = $this->_request->getParam('payment');
        $guestEmail = $this->_request->getParam('guestEmail');

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        if (empty($paymentData)) {
            $this->messageManager->addErrorMessage('Sorry, we were unable to process your request. Please, try again');
            return $resultRedirect->setPath(
                $this->paymentFailureRouteProvider->getFailureRoutePath(),
                ['_secure' => true]
            );
        }

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->checkoutSession->getQuote();
        $quote->reserveOrderId();

        // setting email to guest quote
        if (! $quote->getCustomerId()) {
            $quote
                ->setCustomerEmail($guestEmail)
                ->setCustomerIsGuest(1)
                ->getBillingAddress()
                    ->setEmail($guestEmail);
        }

        // if default billing specified and it's valid then we set it explicitly
        $appleBillingAddress = isset($paymentData['billingContact']) ? $paymentData['billingContact'] : [];
        if ($appleBillingAddress && $this->validateAppleAddress($appleBillingAddress)) {
            $appleBillingAddress = $this->convertAppleAddress($appleBillingAddress);
            $appleBillingAddress
                ->setCustomerAddressId(null)
                ->setEmail($quote->getBillingAddress()->getEmail())
                ->setCustomerId($quote->getCustomerId());

            $quote->setBillingAddress($appleBillingAddress);
        }

        $quote = $this->ignoreAddressValidation($quote);

        $encryptedData = $paymentData['token']['paymentData'];
        $applePaymentMethod = $paymentData['token']['paymentMethod'];

        $quote->setPaymentMethod(ConfigProvider::APPLEPAY_CODE);
        $quote->setInventoryProcessed(false);

        // Set Sales Order Payment
        $quote->getPayment()->importData(['method' => ConfigProvider::APPLEPAY_CODE]);
        $quote->getPayment()->setAdditionalInformation("encryptedData", $encryptedData);
        $quote->getPayment()->setAdditionalInformation("applePaymentMethod", $applePaymentMethod);

        $quote->collectTotals();
        
        $billingAddress = $quote->getBillingAddress();
        $customerId = $quote->getCustomerId();
        if (empty($billingAddress->getFirstname()) && $customerId != null) {
            $customer = $this->customer->load($customerId);
            $billing = $this->buildAddress($customer->getDefaultBillingAddress());
            $this->setBillingAddressValues($quote, $billing);
        }
        $this->quoteRepository->save($quote);

        $this->checkoutSession->setLastSuccessQuoteId($quote->getId());
        $this->checkoutSession->setLastQuoteId($quote->getId());
        $this->checkoutSession->clearHelperData();

        $result = $this->resultJsonFactory->create();

        try {
            $order = $this->quoteManagement->submit($quote);
            $this->eventManager->dispatch(
                'cybersource_quote_submit_success',
                [
                    'order' => $order,
                    'quote' => $quote
                ]
            );

            $this->checkoutSession->setLastOrderId($order->getId());
            $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
            $this->checkoutSession->setLastOrderStatus($order->getStatus());

            $successValidator = $this->_objectManager->get('Magento\Checkout\Model\Session\SuccessValidator');

            if (!$successValidator->isValid()) {
                $resultData = [
                    'status' => 500,
                    'message' => 'Unable to place order. Please try again.',
                    'redirect_url' => $this->_url->getUrl($this->paymentFailureRouteProvider->getFailureRoutePath())
                ];
            } else {

                $resultData = [
                    'status' => 200,
                    'message' => 'Your order has been successfully created!',
                    'redirect_url' => $this->_url->getUrl('checkout/onepage/success')
                ];
            }


            $result->setData($resultData);
            return $result;

        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        }

        $resultData = [
            'status' => 500,
            'message' => 'Unable to place order. Please try again.',
            'redirect_url' => $this->_url->getUrl($this->paymentFailureRouteProvider->getFailureRoutePath())
        ];

        $result->setData($resultData);
        return $result;
    }

    /**
     * Make sure addresses will be saved without validation errors
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

    /**
     * Validates apple address
     *
     * @param array $appleAddressData
     * @return bool
     */
    private function validateAppleAddress($appleAddressData)
    {
        $mandatoryFields = [
            'givenName',
            'familyName',
            'countryCode',
            'administrativeArea',
            'locality',
            'addressLines',
            'postalCode'
        ];

        foreach ($mandatoryFields as $mandatoryField) {
            if (empty($appleAddressData[$mandatoryField])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Converts apple address data to magento AddressInterface object
     *
     * @param array $appleAddressData
     * @return \Magento\Quote\Api\Data\AddressInterface
     */
    private function convertAppleAddress($appleAddressData)
    {
        /** @var \Magento\Quote\Api\Data\AddressInterface $address */
        $address = $this->addressDataFactory->create();

        $countryId = $appleAddressData['countryCode'];
        $region = $appleAddressData['administrativeArea'];

        $regionModel = $this->region->loadByCode($region, $countryId);

        if ($regionId = $regionModel->getId()) {
            $address->setRegionId($regionId);
        } else {
            $address->setRegion($region);
        }

        $address
            ->setFirstname($appleAddressData['givenName'])
            ->setLastname($appleAddressData['familyName'])
            ->setPostcode($appleAddressData['postalCode'])
            ->setStreet($appleAddressData['addressLines'])
            ->setCity($appleAddressData['locality'])
            ->setCountryId($countryId);

        return $address;
    }
    private function buildAddress($address)
    {
        $addressFields =
        [
            'firstName' => $address->getFirstname(),
            'lastName' => $address->getLastname(),
            'company' => $address->getCompany(),
            'email' => $address->getEmail(),
            'city' => $address->getCity(),
            'state' => $address->getRegionCode(),
            'country' => $address->getCountryId(),
            'postalCode' => $address->getPostcode(),
            'phoneNumber' => $address->getTelephone(),
        ];

        if($address instanceof \Magento\Payment\Gateway\Data\AddressAdapterInterface)
		{
            $addressFields['street1'] = $address->getStreetLine1();
            $addressFields['street2'] = $address->getStreetLine2();
        }
        else{
            $addressFields['street1'] = $address->getStreetLine(1);
            $addressFields['street2'] = $address->getStreetLine(2);
        }
        return $addressFields;
    }
    
    function setBillingAddressValues($quote, $billing)
    {
        $billingAddress = $quote->getBillingAddress();
        $billingAddress->setFirstname($billing['firstName'])
                        ->setLastname($billing['lastName'])
                        ->setStreet($billing['street1'])
                        ->setCity($billing['city'])
                        ->setRegion($billing['state'])
                        ->setPostcode($billing['postalCode'])
                        ->setCountryId($billing['country'])
                        ->setTelephone($billing['phoneNumber']);
    }
}
