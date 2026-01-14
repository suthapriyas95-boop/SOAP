<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\PayPal\Model;

use CyberSource\Core\Model\LoggerInterface;
use CyberSource\PayPal\Model\Express\Checkout;
use CyberSource\PayPal\Service\CyberSourcePayPalSoapAPI;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use CyberSource\PayPal\Helper\RequestDataBuilder;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Model\Order;

class Payment extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CODE = 'cybersourcepaypal';

    protected $_code = self::CODE;
    protected $_canAuthorize = true;
    protected $_isOffline = false;
    protected $_isGateway                   = true;
    protected $_canUseInternal              = false;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = true;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = true;
    protected $_canVoid                     = true;

    /**
     * @var CyberSourcePayPalSoapAPI|null
     */
    private $gatewayAPI = null;

    /**
     * @var RequestDataBuilder
     */
    private $helper;

    /**
     * @var LoggerInterface
     */
    protected $cyberLogger;

    /**
     * @var Config
     */
    protected $gatewayConfig;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface
     */
    protected $transactionBuilder;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \CyberSource\PayPal\Gateway\Request\VaultSaleRequestBuilder
     */
    private $vaultSaleRequestBuilder;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface
     */
    private $paymentDataObjectFactory;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;


    /**
     * Payment constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param CyberSourcePayPalSoapAPI $cyberSourceAPI
     * @param Session $checkoutSession
     * @param Config $gatewayConfig
     * @param RequestDataBuilder $dataBuilder
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param Order\Payment\Transaction\BuilderInterface $transactionBuilder
     * @param LoggerInterface $cyberLogger
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        CyberSourcePayPalSoapAPI $cyberSourceAPI,
        Session $checkoutSession,
        Config $gatewayConfig,
        RequestDataBuilder $dataBuilder,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        LoggerInterface $cyberLogger,
        \Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface $paymentDataObjectFactory,
        \CyberSource\PayPal\Gateway\Request\VaultSaleRequestBuilder $vaultSaleRequestBuilder,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );

        $this->gatewayAPI = $cyberSourceAPI;
        $this->gatewayConfig = $gatewayConfig;
        $this->checkoutSession = $checkoutSession;
        $this->helper = $dataBuilder;
        $this->transactionBuilder = $transactionBuilder;
        $this->urlBuilder = $urlBuilder;
        $this->cyberLogger = $cyberLogger;
        $this->vaultSaleRequestBuilder = $vaultSaleRequestBuilder;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->serializer = $serializer;
    }

    /**
     * Retrieve payment method title
     *
     * @return string
     * @deprecated 100.2.0
     */
    public function getTitle()
    {
        return $this->gatewayConfig->getTitle();
    }

    /**
     * Assign data to info model instance
     *
     * @param array|\Magento\Framework\DataObject $data
     * @return \Magento\Payment\Model\Info
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function assignData(\Magento\Framework\DataObject $data)
    {
        parent::assignData($data);
        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_array($additionalData)) {
            return $this;
        }
        foreach ($additionalData as $key => $value) {
            // Skip extension attributes
            if ($key === \Magento\Framework\Api\ExtensibleDataInterface::EXTENSION_ATTRIBUTES_KEY) {
                continue;
            }
            $this->getInfoInstance()->setAdditionalInformation($key, $value);
        }
        return $this;
    }

    /**
     * Authorize payment service method
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Exception
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        parent::authorize($payment, $amount);

        $response = $this->runPaypalAuthorization();

        $payment->setAdditionalInformation(Checkout::PAYMENT_INFO_TRANSPORT_RESPONSE_CODE, $response->reasonCode);
        $payment->setAdditionalInformation(Checkout::PAYMENT_INFO_TRANSPORT_AUTH_TXN_ID, $response->requestID);
        $payment->setAdditionalInformation(Checkout::PAYMENT_INFO_TRANSPORT_TXN_ID, $response->requestID);

        $this->handlePaymentStatus($payment, $response->decision);
        $this->updateOrderInformation($payment, $response->requestID, $response->reasonCode);

        return $this;
    }

    /**
     * Capture payment service method
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Exception
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $response = null;

        if ($billingAgreementId = $payment->getAdditionalInformation(Checkout::PAYMENT_INFO_TRANSPORT_BA_ID)) {
            // paypal BA sale
            $paymentDO = $this->paymentDataObjectFactory->create($payment);
            $saleRequest = $this->vaultSaleRequestBuilder->build(['payment' => $paymentDO, 'amount' => $amount]);
            $response = $this->gatewayAPI->vaultSaleService($saleRequest);
        } else {
            if ($payment->getParentTransactionId()) { // doing prior auth capture
                // running authorization if not yet performed
                if (! $requestId = $payment->getAdditionalInformation(Checkout::PAYMENT_INFO_TRANSPORT_AUTH_TXN_ID)) {
                    $authResponse = $this->runPaypalAuthorization();
                    $requestId = $authResponse->requestID;
                }

                // capturing authorized amount
                $captureRequest = $this->helper->buildCaptureService($payment, $amount, $requestId);
                $this->gatewayAPI->setPayment($payment);
                $response = $this->gatewayAPI->captureService($captureRequest);
            } else { // doing sale request
                $response = $this->runPaypalSale($payment);
            }
        }

        $payment->setAdditionalInformation(Checkout::PAYMENT_INFO_TRANSPORT_RESPONSE_CODE, $response->reasonCode);
        $payment->setAdditionalInformation(Checkout::PAYMENT_INFO_TRANSPORT_TXN_ID, $response->requestID);

        $this->handlePaymentStatus($payment, $response->decision);
        $this->updateOrderInformation($payment, $response->requestID, $response->reasonCode);

        if ($payment->getIsFraudDetected()) {
            $formattedPrice = $payment->getOrder()->getBaseCurrency()->formatTxt($amount);
            $message = __('The ordering amount of %1 is pending approval on the payment gateway.', $formattedPrice);

            $transaction = $this->transactionBuilder->setPayment($payment)
                ->setOrder($payment->getOrder())
                ->setTransactionId($payment->getTransactionId())
                ->build(Order\Payment\Transaction::TYPE_ORDER);
            $payment->addTransactionCommentsToOrder($transaction, $message);
        }

        return $this;
    }

    private function updateOrderInformation($payment, $txnId, $responseCode)
    {
        $payment->setMethod(Payment::CODE);
        $payment->setLastTransId($txnId);
        $payment->setTransactionId($txnId);
        $payment->setCcTransId($txnId);

        $payment->setIsTransactionClosed(0);
        $payment->setIsTransactionPending(0);
        $payment->setIsFraudDetected(0);

        if ($responseCode == 480) {
            $payment->setIsTransactionPending(1);
            $payment->setIsFraudDetected(1);
        }
    }

    /**
     * Void Captured Payment. Try to perform cancel if void fail
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @throws LocalizedException
     * @return $this
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        try {
            $request = $this->helper->buildAuthorizeReversal($payment);

            $this->gatewayAPI->setPayment($payment);
            $response = $this->gatewayAPI->authorizeReversalService($request);

            if ($response !== null) {
                $payment->setAdditionalInformation('reversal', $this->serializer->serialize($response));
            }
        } catch (\Exception $e) {

            $this->cyberLogger->error($e->getMessage());
            throw new LocalizedException(__('Sorry but your transaction was unsuccessful.'));
        }

        return $this;
    }

    /**
     * Cancel a payment and reverse authorization at CyberSource
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @throws LocalizedException
     * @return $this
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        return $this->void($payment);
    }

    /**
     * Perform a refund
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws LocalizedException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        try {
            $transactionId = $payment->getCreditmemo()->getInvoice()->getTransactionId();
            $request = $this->helper->buildRefundService($payment, $amount, $transactionId);

            $this->gatewayAPI->setPayment($payment);

            $response = $this->gatewayAPI->refundService($request);

            if ($response) {
                $payment->setLastTransId($response->requestID);
                $payment->setTransactionId($response->requestID);
            }

        } catch (\Exception $e) {

            $this->cyberLogger->error($e->getMessage());
            throw new LocalizedException(__('Your refund could not be processed at this time.'), $e);
        }

        return $this;
    }

    /**
     * Payment action getter compatible with payment model
     *
     * @see \Magento\Sales\Model\Payment::place()
     * @return string
     */
    public function getConfigPaymentAction()
    {
        $infoInstance = $this->getData('info_instance');
        if ($infoInstance instanceof \Magento\Payment\Model\InfoInterface
            && $infoInstance->getAdditionalInformation(Checkout::PAYMENT_INFO_TRANSPORT_BA_ID)
        ) {
            return self::ACTION_AUTHORIZE_CAPTURE;
        }

        return $this->gatewayConfig->getPaymentAction();
    }

    /**
     * Checkout redirect URL getter for onepage checkout (hardcode)
     *
     * @see \Magento\Checkout\Controller\Onepage::savePaymentAction()
     * @see \Magento\Quote\Model\Quote\Payment::getCheckoutRedirectUrl()
     * @return string
     */
    public function getCheckoutRedirectUrl()
    {
        return $this->urlBuilder->getUrl('cybersourcepaypal/express/start');
    }

    /**
     * Check whether payment method can be used
     * @param \Magento\Quote\Api\Data\CartInterface|Quote|null $quote
     * @return bool
     */
    public function isAvailable(?\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return parent::isAvailable($quote) && $this->gatewayConfig->isActive();
    }

    /**
     * @return \stdClass|null
     * @throws \Exception
     */
    private function runPaypalAuthorization()
    {
        if (!$getDetailsResponse = $this->checkoutSession->getGetDetailsResponse()) {
            throw new LocalizedException(_('PayPal GetDetailsResponse is empty'));
        }

        $orderSetupRequest = $this->helper->buildOrderSetupService($getDetailsResponse, $this->getQuote());
        $orderSetupResponse = $this->gatewayAPI->orderSetupService($orderSetupRequest);

        $authRequest = $this->helper->buildAuthorizationService($this->getQuote(), $orderSetupResponse->requestID);
        return $this->gatewayAPI->authorizationService($authRequest);
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return \stdClass
     * @throws LocalizedException
     * @throws \Exception
     */
    private function runPaypalSale($payment)
    {
        if (!$getDetailsResponse = $this->checkoutSession->getGetDetailsResponse()) {
            throw new LocalizedException(_('PayPal GetDetailsResponse is empty'));
        }

        $orderSetupRequest = $this->helper->buildOrderSetupService($getDetailsResponse, $this->getQuote());
        $orderSetupResponse = $this->gatewayAPI->orderSetupService($orderSetupRequest);

        $payment->setAdditionalInformation(Checkout::PAYMENT_INFO_TRANSPORT_ORDER_SETUP_TXN_ID, $orderSetupResponse->requestID);

        $saleRequest = $this->helper->buildSaleService($this->getQuote(), $orderSetupResponse->requestID);
        return $this->gatewayAPI->saleService($saleRequest);
    }

    private function handlePaymentStatus($payment, $decision)
    {
        switch ($decision) {
            case 'DECLINE':
            case 'ERROR':
            case 'REJECT':
                $payment->setAdditionalInformation(Info::PAYMENT_STATUS, Order::STATE_CANCELED);
                break;

            case 'REVIEW':
                $payment->setAdditionalInformation(Info::PAYMENT_STATUS, Order::STATE_PAYMENT_REVIEW);
                $payment->setAdditionalInformation(Info::IS_FRAUD, true);
                break;
        }

        $payment->setAdditionalInformation(Info::PAYMENT_STATUS, Order::STATE_PROCESSING);
    }

    /**
     * @return \Magento\Quote\Model\Quote
     */
    private function getQuote()
    {
        return $this->checkoutSession->getQuote();
    }
}
