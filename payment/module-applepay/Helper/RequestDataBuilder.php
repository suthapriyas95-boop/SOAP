<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\ApplePay\Helper;

use CyberSource\Core\Helper\AbstractDataBuilder;
use CyberSource\Core\Model\LoggerInterface;
use Magento\Checkout\Helper\Data as CheckoutHelper;
use CyberSource\ApplePay\Gateway\Config\Config;
use Magento\Quote\Model\Quote\Address;

class RequestDataBuilder extends AbstractDataBuilder
{
    const PAYMENT_SOLUTION = '001';
    const PAYMENT_DESCRIPTOR = 'RklEPUNPTU1PTi5BUFBMRS5JTkFQUC5QQVlNRU5U';

    private $applePaymentMethodCard = [
        'amex' => "003",
        'discover' => "004",
        'mastercard' => "002",
        'visa' => "001",
        'jcb' => "001",
    ];

    /**
     * @var Config
     */
    private $gatewayConfig;

    /**
     * @var \CyberSource\Core\Gateway\Request\Soap\ItemsBuilder
     */
    private $orderItemsBuilder;

    /**
     * @var \CyberSource\Core\Gateway\Request\Soap\AddressDataBuilder
     */
    private $addressDataBuilder;
	
	/**
	 * @var \Magento\Framework\Serialize\Serializer\Json
	 */
	private $serializer;

    /**
     * RequestDataBuilder constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param CheckoutHelper $checkoutHelper
     * @param Config $gatewayConfig
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Grid\CollectionFactory $orderGridCollectionFactory
     * @param \Magento\Backend\Model\Auth $auth
     * @param \Magento\GiftMessage\Model\Message $giftMessage

     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        CheckoutHelper $checkoutHelper,
        Config $gatewayConfig,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Grid\CollectionFactory $orderGridCollectionFactory,
        \CyberSource\Core\Gateway\Request\Soap\ItemsBuilder $orderItemsBuilder,
        \CyberSource\Core\Gateway\Request\Soap\AddressDataBuilder $addressDataBuilder,
        \Magento\Backend\Model\Auth $auth,
        \Magento\GiftMessage\Model\Message $giftMessage,
		\Magento\Framework\Serialize\Serializer\Json $serializer

    ) {
        parent::__construct(
            $context,
            $customerSession,
            $checkoutSession,
            $checkoutHelper,
            $orderCollectionFactory,
            $orderGridCollectionFactory,
            $auth,
            $giftMessage
        );

        $this->gatewayConfig = $gatewayConfig;
        $this->orderItemsBuilder = $orderItemsBuilder;
        $this->addressDataBuilder = $addressDataBuilder;
		$this->serializer = $serializer;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return mixed|\stdClass
     */
    public function buildAuthorizationRequestData(\Magento\Payment\Model\InfoInterface $payment, $subject)
    {
        $quote = $this->checkoutSession->getQuote();

        $request = new \stdClass();
        $request->merchantID = $this->gatewayConfig->getMerchantId();
        $request->partnerSolutionID = self::PARTNER_SOLUTION_ID;
        $request->developerId = $this->gatewayConfig->getDeveloperId();
        $request->merchantReferenceCode = $quote->getReservedOrderId();

        $ccAuthService = new \stdClass();
        $ccAuthService->run = "true";
        $request->ccAuthService = $ccAuthService;

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $quote->getQuoteCurrencyCode();
        $purchaseTotals->grandTotalAmount = $this->formatAmount($quote->getGrandTotal());
        $request->purchaseTotals = $purchaseTotals;

        $request = $this->buildRequestItems($request, $subject);
        $request = $this->buildAddresses($request, $subject);

        $request->customerID = $quote->getCustomerId() ? $quote->getCustomerId() : 'guest';

        $request->paymentSolution = self::PAYMENT_SOLUTION;

        $encryptedData = $payment->getAdditionalInformation("encryptedData");
        $encryptedPayment = new \stdClass();
        $encryptedPayment->descriptor = self::PAYMENT_DESCRIPTOR;
        $encryptedPayment->data = base64_encode($this->serializer->serialize($encryptedData));
        $encryptedPayment->encoding = 'Base64';
        $request->encryptedPayment  = $encryptedPayment;

        $applePaymentMethod = $payment->getAdditionalInformation("applePaymentMethod");

        $card = new \stdClass();
        $card->cardType = $this->applePaymentMethodCard[strtolower($applePaymentMethod['network'] ?? '')];

        $request->card = $card;

        return $request;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param mixed $amount
     * @return \stdClass
     */
    public function buildCaptureRequestData(\Magento\Payment\Model\InfoInterface $payment, $amount, $subject)
    {
        $merchantReferenceCode = $payment->getAdditionalInformation('merchantReferenceCode');

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $currency = $payment->getAdditionalInformation('currency');

        $order = $payment->getOrder();

        $request = new \stdClass();
        $request->merchantID = $this->gatewayConfig->getMerchantId();
        $request->partnerSolutionID = self::PARTNER_SOLUTION_ID;
        $request->developerId = $this->gatewayConfig->getDeveloperId();
        $request->merchantReferenceCode = $merchantReferenceCode;

        $ccCaptureService = new \stdClass();
        $ccCaptureService->run = "true";
        $ccCaptureService->authRequestID = $payment->getAdditionalInformation("requestID");

        $this->buildCaptureSequence($payment, $ccCaptureService, $amount);

        $request->ccCaptureService = $ccCaptureService;

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $currency;
        $purchaseTotals->grandTotalAmount = $this->formatAmount($amount);
        $request->purchaseTotals = $purchaseTotals;

        $request->paymentSolution = self::PAYMENT_SOLUTION;
        $request->orderRequestToken = $payment->getAdditionalInformation("requestToken");

        $request->customerID = $order->getCustomerId() ? $order->getCustomerId() : 'guest';

        $request = $this->buildRequestItems($request, $subject);

        $request = $this->buildAddresses($request, $subject);

        return $request;
    }

    /**
     * @return \stdClass
     */
    public function buildSettlementRequestData()
    {
        $request = new \stdClass();

        $ccCaptureService = new \stdClass();
        $ccCaptureService->run = "true";
        $request->ccCaptureService = $ccCaptureService;

        return $request;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return \stdClass
     */
    public function buildVoidRequestData(\Magento\Payment\Model\InfoInterface $payment)
    {
        $merchantReferenceCode = $payment->getAdditionalInformation('merchantReferenceCode');

        $request = new \stdClass();
        $request->merchantID = $this->gatewayConfig->getMerchantId();
        $request->partnerSolutionID = self::PARTNER_SOLUTION_ID;
        $request->developerId = $this->gatewayConfig->getDeveloperId();
        $request->merchantReferenceCode = $merchantReferenceCode;

        $request->paymentSolution = self::PAYMENT_SOLUTION;

        $voidService = new \stdClass();
        $voidService->run = "true";
        $voidService->voidRequestID = $payment->getAdditionalInformation("requestID");
        $request->voidService = $voidService;

        return $request;
    }


    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return \stdClass
     */
    public function buildAuthorizeReversalRequestData(\Magento\Payment\Model\InfoInterface $payment)
    {
        $request = new \stdClass();
        $request->merchantID = $this->gatewayConfig->getMerchantId();
        $request->merchantReferenceCode = $payment->getOrder()->getIncrementId();

        $ccAuthReversalService = new \stdClass();
        $ccAuthReversalService->run = "true";
        $ccAuthReversalService->authRequestID = $payment->getAdditionalInformation("requestID");
        $request->ccAuthReversalService = $ccAuthReversalService;

        $request->paymentSolution = self::PAYMENT_SOLUTION;
        $request->partnerSolutionID = self::PARTNER_SOLUTION_ID;

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $payment->getOrder()->getOrderCurrencyCode();
        $purchaseTotals->grandTotalAmount = $this->formatAmount($payment->getOrder()->getGrandTotal());
        $request->purchaseTotals = $purchaseTotals;

        return $request;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param $amount
     * @return \stdClass
     */
    public function buildRefundRequestData(\Magento\Payment\Model\InfoInterface $payment, $amount, $subject)
    {
        $merchantReferenceCode = $payment->getAdditionalInformation('merchantReferenceCode');
        $storeId = $payment->getOrder()->getStoreId();
        $currency = $payment->getAdditionalInformation('currency');

        $request = new \stdClass();
        $request->merchantID = $this->gatewayConfig->getMerchantId($storeId);
        $request->partnerSolutionID = self::PARTNER_SOLUTION_ID;
        $request->developerId = $this->gatewayConfig->getDeveloperId($storeId);
        $request->merchantReferenceCode = $merchantReferenceCode;

        $ccCreditService = new \stdClass();
        $ccCreditService->run = "true";
        $ccCreditService->captureRequestID = $payment->getCcTransId();
        $request->ccCreditService = $ccCreditService;

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $currency;
        $purchaseTotals->grandTotalAmount = $this->formatAmount($amount);
        $request->purchaseTotals = $purchaseTotals;

        $request->paymentSolution = self::PAYMENT_SOLUTION;

        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        $request = $this->buildRequestItems($request, $subject);

        $request = $this->buildAddresses($request, $subject);

        return $request;
    }

    /**
     * @param array $items
     * @param \stdClass $request
     * @return mixed
     */
    private function buildRequestItems($request, $subject)
    {

        $items = $this->orderItemsBuilder->build($subject);

        $items = $items['item'] ?? [];

        if (!empty($items)) {
            $request->item = $items;
        }

        return $request;
    }

    private function buildAddresses($request, $subject)
    {
        $addresses = $this->addressDataBuilder->build($subject);

        if ($billTo = $addresses['billTo'] ?? null) {
            $request->billTo = (object)$billTo;
        }
        if ($shipTo = $addresses['shipTo'] ?? null) {
            $request->shipTo = (object)$shipTo;
        }

        return $request;
    }
}
