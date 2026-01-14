<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\KlarnaFinancial\Controller\Index;

use CyberSource\KlarnaFinancial\Gateway\Validator\ResponseCodeValidator;
use CyberSource\KlarnaFinancial\Helper\RequestDataBuilder;
use CyberSource\KlarnaFinancial\Model\Ui\ConfigProvider;
use CyberSource\KlarnaFinancial\Gateway\Http\Client\SOAPClient;
use Magento\Checkout\Api\AgreementsValidatorInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Session\SessionManagerInterface;
use CyberSource\KlarnaFinancial\Gateway\Http\TransferFactory;

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
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private $resultPageFactory;

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
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    private $formKeyValidator;
    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @var \CyberSource\Core\Model\Checkout\PaymentFailureRouteProviderInterface
     */
    private $paymentFailureRouteProvider;

    /**
     * PlaceOrder constructor.
     *
     * @param Context $context
     * @param SessionManagerInterface $checkoutSession
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param RequestDataBuilder $requestDataBuilder
     * @param SOAPClient $soapClient
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param TransferFactory $transferFactory
     * @param AgreementsValidatorInterface $agreementsValidator
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \CyberSource\Core\Model\Checkout\PaymentFailureRouteProviderInterface $paymentFailureRouteProvider
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     */
    public function __construct(
        Context $context,
        SessionManagerInterface $checkoutSession,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        RequestDataBuilder $requestDataBuilder,
        SOAPClient $soapClient,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        TransferFactory $transferFactory,
        AgreementsValidatorInterface $agreementsValidator,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \CyberSource\Core\Model\Checkout\PaymentFailureRouteProviderInterface $paymentFailureRouteProvider,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        parent::__construct($context);

        $this->checkoutSession = $checkoutSession;
        $this->quoteManagement = $quoteManagement;
        $this->resultPageFactory = $resultPageFactory;
        $this->requestDataBuilder = $requestDataBuilder;
        $this->soapClient = $soapClient;
        $this->quoteRepository = $quoteRepository;
        $this->transferFactory = $transferFactory;
        $this->agreementsValidator = $agreementsValidator;
        $this->formKeyValidator = $formKeyValidator;
        $this->eventManager = $eventManager;
        $this->paymentFailureRouteProvider = $paymentFailureRouteProvider;
    }

    public function execute()
    {

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        if (!$this->formKeyValidator->validate($this->getRequest())) {
            $this->messageManager->addErrorMessage(__('Invalid formkey'));
            return $resultRedirect->setPath(
                $this->paymentFailureRouteProvider->getFailureRoutePath(),
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }

        $authorizationToken = $this->_request->getParam('authorizationToken');
        $guestEmail = $this->_request->getParam('guestEmail');

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->checkoutSession->getQuote();
        $quote->reserveOrderId();
        if ($guestEmail && $guestEmail !== 'null') {
            $quote->getBillingAddress()->setEmail($guestEmail);
        }

        $quote = $this->ignoreAddressValidation($quote);

        // setting email to guest quote
        if (!$quote->getCustomerId()) {
            $quote = $this->prepareGuestQuote($quote);
        }

        $quote->setPaymentMethod(ConfigProvider::CODE);
        $quote->setInventoryProcessed(false);

        // Set Sales Order Payment
        $quote->getPayment()->importData(['method' => ConfigProvider::CODE]);
        $quote->getPayment()->setAdditionalInformation("authorizationToken", $authorizationToken);

        $quote->collectTotals();
        $this->quoteRepository->save($quote);

        $this->checkoutSession->setLastSuccessQuoteId($quote->getId());
        $this->checkoutSession->setLastQuoteId($quote->getId());
        $this->checkoutSession->clearHelperData();


        try {
            $order = $this->quoteManagement->submit($quote);
            $this->eventManager->dispatch(
                'cybersource_quote_submit_success',
                [
                    'order' => $order,
                    'quote' => $quote
                ]
            );

            $this->checkoutSession->unsProcessorToken();
            $this->checkoutSession->setLastOrderId($order->getId());
            $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
            $this->checkoutSession->setLastOrderStatus($order->getStatus());

            $successValidator = $this->_objectManager->get('Magento\Checkout\Model\Session\SuccessValidator');

            if($redirectUrl = $order->getPayment()->getAdditionalInformation('merchantUrl')) {
                return $resultRedirect->setUrl($redirectUrl);
            }

            if (!$successValidator->isValid()) {
                return $resultRedirect->setPath(
                    $this->paymentFailureRouteProvider->getFailureRoutePath(),
                    ['_secure' => true]
                );
            }

            $this->messageManager->addSuccessMessage('Your order has been successfully created!');
            return $resultRedirect->setPath('checkout/onepage/success', ['_secure' => true]);
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, $e->getMessage());
        }

        return $resultRedirect->setPath(
            $this->paymentFailureRouteProvider->getFailureRoutePath(),
            ['_secure' => true]
        );
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
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Magento\Quote\Model\Quote
     */
    private function prepareGuestQuote(\Magento\Quote\Model\Quote $quote)
    {
        $quote->setCustomerId(null);
        $quote->setCustomerEmail($quote->getBillingAddress()->getEmail());
        $quote->setCustomerIsGuest(true);
        $quote->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);

        return $quote;
    }
}
