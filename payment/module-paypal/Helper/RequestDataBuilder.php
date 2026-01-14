<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\PayPal\Helper;

use CyberSource\Core\Helper\AbstractDataBuilder;
use CyberSource\Core\Model\Config as CoreConfig;
use CyberSource\PayPal\Model\Config;
use CyberSource\PayPal\Model\Express\Checkout;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote;
use Magento\Vault\Model\Ui\VaultConfigProvider;

class RequestDataBuilder extends AbstractDataBuilder
{
    const AP_PAYMENT_TYPE = 'PPL';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var \Magento\Quote\Api\Data\ShippingAssignmentInterface
     */
    private $shippingAssignment;

    /**
     * @var \Magento\Quote\Model\Shipping
     */
    private $quoteShipping;

    /**
     * @var \Magento\Quote\Model\Quote\Address\Total
     */
    private $total;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;
	
	protected $quote;

    /**
     * RequestDataBuilder constructor.
     *
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param Session $checkoutSession
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param Quote\Address\Total $total
     * @param \Magento\Quote\Model\Shipping $quoteShipping
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Helper\Data $data
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Grid\CollectionFactory $orderGridCollectionFactory
     * @param \Magento\Backend\Model\Auth $auth
     * @param \Magento\GiftMessage\Model\Message $giftMessage
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        Config $config,
        Session $checkoutSession,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total,
        \Magento\Quote\Model\Shipping $quoteShipping,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Helper\Data $data,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Grid\CollectionFactory $orderGridCollectionFactory,
        \Magento\Backend\Model\Auth $auth,
        \Magento\GiftMessage\Model\Message $giftMessage
    ) {
        parent::__construct(
            $context,
            $customerSession,
            $checkoutSession,
            $data,
            $orderCollectionFactory,
            $orderGridCollectionFactory,
            $auth,
            $giftMessage
        );

        $this->storeManager = $storeManager;
        $this->shippingAssignment = $shippingAssignment;
        $this->total = $total;
        $this->quoteShipping = $quoteShipping;
        $this->quoteRepository = $quoteRepository;
        $quote = $checkoutSession->getQuote();
        $this->config = $config;
        $this->config->setStoreId($quote->getStoreId());
        $this->quote = $quote;

        $this->setUpCredentials($config->getPayPalMerchantId(), $config->getTransactionKey());
    }

    /**
     * @param Quote $quote
     * @param string $returnUrl
     * @param string $cancelUrl
     * @param bool $isPayPalCredit
     * @param bool $excludeShipping
     * @return \stdClass
     */
    public function buildSessionService(Quote $quote, $returnUrl, $cancelUrl, $isPayPalCredit = false, $excludeShipping = false)
    {
        $request = $this->buildBaseRequest($quote->getStoreId());

        $request->merchantReferenceCode = $quote->getReservedOrderId();
        $request->partnerSolutionID = self::PARTNER_SOLUTION_ID;
        $apSessionsService = new \stdClass();
        $apSessionsService->run = "true";
        $apSessionsService->successURL = $returnUrl;
        $apSessionsService->cancelURL = $cancelUrl;

        if ($isPayPalCredit) {
            $apSessionsService->paymentOptionID = 'Credit';
        }

        $request->apSessionsService =  $apSessionsService;

        $request = $this->buildRequestItems($quote->getAllVisibleItems(), $request);

        if ($customerId = $this->customerSession->getCustomerId()) {
            $request->customerID = $customerId;
        }

        $request->purchaseTotals = $this->buildPurchaseTotals($quote, $request);
        $request = $this->balancePurchaseTotalsAndLineItems($request);

        $isBillingAgreement = $quote->getPayment()->getAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE);

        $request->ap = new \stdClass();
        $request->ap->billingAgreementIndicator = $isBillingAgreement ? 'true' : 'false';

        if ($excludeShipping) {
            return $request;
        }

        $shipTo = new \stdClass();
        $shippingAddress = $quote->getShippingAddress();

        $shipTo->firstName = $shippingAddress->getFirstname();
        $shipTo->lastName = $shippingAddress->getLastname();
        $shipTo->street1 = $shippingAddress->getStreetLine(1);
        if ($shippingAddress->getStreetLine(2)) {
            $shipTo->street2 = $shippingAddress->getStreetLine(2);
        }

        $shipTo->city = $shippingAddress->getCity();
        $shipTo->state = $shippingAddress->getRegion();
        $shipTo->postalCode = $shippingAddress->getPostcode();
        $shipTo->country = $shippingAddress->getCountryId();
        $shipTo->phoneNumber = $shippingAddress->getTelephone();

        $request->shipTo = $shipTo;

        

        return $request;
    }

    /**
     * @param array $setServiceResponse
     * @return \stdClass
     */
    public function buildCheckStatusService($setServiceResponse, $quote)
    {
        $request = $this->buildBaseRequest($quote->getStoreId());

        $request->merchantReferenceCode = $setServiceResponse['merchantReferenceCode'];
        $request->partnerSolutionID = self::PARTNER_SOLUTION_ID;
        if ($customerId = $this->customerSession->getCustomerId()) {
            $request->customerID = $customerId;
        };

        $apCheckStatusService = new \stdClass();
        $apCheckStatusService->run = "true";
        $apCheckStatusService->sessionsRequestID = $setServiceResponse['requestID'];

        $request->apCheckStatusService = $apCheckStatusService;

        return $request;
    }

    /**
     * @param $getDetailsResponse
     * @param Quote $quote
     * @return mixed|\stdClass
     */
    public function buildOrderSetupService($getDetailsResponse, $quote)
    {
        $request = $this->buildBaseRequest($quote->getStoreId());

        $request->merchantReferenceCode = $getDetailsResponse['merchantReferenceCode'];
        $request->partnerSolutionID = self::PARTNER_SOLUTION_ID;
        if ($customerId = $this->customerSession->getCustomerId()) {
            $request->customerID = $customerId;
        };

        $request->billTo = $this->buildAddress($quote->getBillingAddress(), $getDetailsResponse);
        $request->shipTo = $this->buildAddress($quote->getShippingAddress(), $getDetailsResponse);

        $ap = new \stdClass();
        $ap->payerID = $getDetailsResponse['paypalPayerId'];
        $request->ap = $ap;

        $request = $this->buildRequestItems($quote->getAllVisibleItems(), $request);

        $request->purchaseTotals = $this->buildPurchaseTotals($quote, $request);
        $request = $this->balancePurchaseTotalsAndLineItems($request);
        
        $apOrderService = new \stdClass();
        $apOrderService->run = "true";
        $apOrderService->sessionsRequestID = $getDetailsResponse['paypalEcSetRequestID'];

        $request->apOrderService = $apOrderService;

        return $request;
    }

    /**
     * @param Quote $quote
     * @param string $orderSetupRequestId
     * @return \stdClass
     */
    public function buildAuthorizationService($quote, $orderSetupRequestId)
    {
        $request = $this->buildBaseRequest($quote->getStoreId());

        $request->merchantReferenceCode = $quote->getReservedOrderId();
        $request->partnerSolutionID = self::PARTNER_SOLUTION_ID;
        if ($customerId = $this->customerSession->getCustomerId()) {
            $request->customerID = $customerId;
        };

        
        $request = $this->buildRequestItems($quote->getAllVisibleItems(), $request);

        $request->purchaseTotals = $this->buildPurchaseTotals($quote, $request);
        $request = $this->balancePurchaseTotalsAndLineItems($request);

        $apAuthService = new \stdClass();
        $apAuthService->run = "true";
        $apAuthService->orderRequestID = $orderSetupRequestId;

        $request->billTo = $this->buildAddress($quote->getBillingAddress());
        $request->shipTo = $this->buildAddress($quote->getShippingAddress());

        $request->merchantDefinedData = $this->buildDecisionManagerFields($quote);

        $request->apAuthService = $apAuthService;

        return $request;
    }

    /**
     * @param $quote
     * @param $orderSetupRequestId
     * @return \stdClass
     */
    public function buildSaleService($quote, $orderSetupRequestId)
    {
        $request = $this->buildAuthorizationService($quote, $orderSetupRequestId);

        $request->apSaleService = $request->apAuthService;
        unset($request->apAuthService);

        return $request;
    }


    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @param string $requestId
     * @return \stdClass
     */
    public function buildCaptureService(\Magento\Payment\Model\InfoInterface $payment, $amount, $requestId)
    {
        $request = $this->buildBaseRequest($payment->getOrder()->getStoreId());

        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        $request->merchantReferenceCode = $order->getIncrementId();
        $request->partnerSolutionID = self::PARTNER_SOLUTION_ID;
        $request->customerID = $payment->getOrder()->getCustomerId();

        $apCaptureService = new \stdClass();
        $apCaptureService->run = "true";
        $apCaptureService->authRequestID = $requestId;
        $this->buildCaptureSequence($payment, $apCaptureService, $amount);

        $request->apCaptureService = $apCaptureService;

        $invoice = $payment->getInvoice();
        if (!$invoice) {
            $invoice = $payment->getCreatedInvoice();
        }

        $invoicedItems = [];
        if ($invoice) {
            /** @var \Magento\Sales\Model\Order\Invoice\Item $invoiceItem */
            foreach ($invoice->getAllItems() as $invoiceItem) {
                if ($invoiceItem->getQty() >= 1) {
                    $invoicedItems[] = $invoiceItem;
                }
            }
        }

        $request = $this->buildRequestItems($invoicedItems, $request, $invoice->getShippingAmount());   

        $request->purchaseTotals = $this->buildPurchaseTotals($invoice, $request);
        $request = $this->balancePurchaseTotalsAndLineItems($request);

        $request->shipTo = $this->buildAddress($payment->getOrder()->getShippingAddress());
        $request->billTo = $this->buildAddress($payment->getOrder()->getBillingAddress());

        $request->billTo->ipAddress = $payment->getOrder()->getRemoteIp();

        return $request;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @param string $requestID
     * @return \stdClass
     */
    public function buildRefundService(\Magento\Payment\Model\InfoInterface $payment, $amount, $requestID)
    {
        $request = $this->buildBaseRequest($payment->getOrder()->getStoreId());

        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        $request->merchantReferenceCode = $order->getIncrementId();
        $request->partnerSolutionID = self::PARTNER_SOLUTION_ID;
        $request->customerID = $payment->getOrder()->getCustomerId();

        $apRefundService = new \stdClass();
        $apRefundService->run = "true";
        $apRefundService->refundRequestID = $requestID;

        $request->apRefundService = $apRefundService;

        $request->shipTo = $this->buildAddress($payment->getOrder()->getShippingAddress());
        $request->billTo = $this->buildAddress($payment->getOrder()->getBillingAddress());
        $request->billTo->ipAddress = $payment->getOrder()->getRemoteIp();

        /** @var \Magento\Sales\Model\Order\Creditmemo $creditMemo */
        $creditMemo = $payment->getCreditmemo();

        $request = $this->buildRequestItems($creditMemo->getAllItems(), $request, $creditMemo->getShippingAmount());

        $request->purchaseTotals = $this->buildPurchaseTotals($creditMemo, $request);
        $request = $this->balancePurchaseTotalsAndLineItems($request);


        return $request;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return \stdClass
     */
    public function buildAuthorizeReversal(\Magento\Payment\Model\InfoInterface $payment)
    {
        $request = $this->buildBaseRequest($payment->getOrder()->getStoreId());

        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        $requestId = $payment->getCcTransId();

        $request->merchantReferenceCode = $order->getIncrementId();
        $request->partnerSolutionID = self::PARTNER_SOLUTION_ID;
        $request->customerID = $payment->getOrder()->getCustomerId();

        $apAuthReversalService = new \stdClass();
        $apAuthReversalService->run = "true";
        $apAuthReversalService->authRequestID = $requestId;

        $request->apAuthReversalService = $apAuthReversalService;

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $payment->getOrder()->getOrderCurrencyCode();
        $purchaseTotals->grandTotalAmount = $this->formatAmount($payment->getOrder()->getGrandTotal());
        $request->purchaseTotals = $purchaseTotals;

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $request->shipTo = $this->buildAddress($payment->getOrder()->getShippingAddress());
        $request->billTo = $this->buildAddress($payment->getOrder()->getBillingAddress());
        $request->billTo->ipAddress = $payment->getOrder()->getRemoteIp();

        return $request;
    }

    /**
     * @param string $sessionRequestId
     * @param Quote $quote
     * @return \stdClass
     */
    public function buildBillingAgreementService($sessionRequestId, $quote)
    {
        $request = $this->buildBaseRequest($quote->getStoreId());

        $request->merchantReferenceCode = $quote->getReservedOrderId();
        $request->partnerSolutionID = self::PARTNER_SOLUTION_ID;
        $apBillingAgreementService = new \stdClass();
        $apBillingAgreementService->run = "true";
        $apBillingAgreementService->sessionsRequestID = $sessionRequestId;

        $request->apBillingAgreementService = $apBillingAgreementService;

        return $request;
    }

    /**
     * @param string $billingAgreementId
     * @param float $amount
     * @param string $merchantReferenceCode
     * @param string $currency
     * @return \stdClass
     */
    public function buildVaultSaleService($billingAgreementId, $amount, $merchantReferenceCode, $currency = 'USD')
    {
        $request = $this->buildBaseRequest();

        $request->merchantReferenceCode = $merchantReferenceCode;
        $request->partnerSolutionID = self::PARTNER_SOLUTION_ID;

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $currency;
        $purchaseTotals->grandTotalAmount = $this->formatAmount($amount);
        $request->purchaseTotals = $purchaseTotals;

        $apSaleService = new \stdClass();
        $apSaleService->run = "true";
        $request->apSaleService = $apSaleService;

        $ap = new \stdClass();
        $ap->billingAgreementID = $billingAgreementId;
        $request->ap = $ap;
        $shipTo = new \stdClass();
        $shippingAddress = $this->quote->getShippingAddress();

        $shipTo->firstName = $shippingAddress->getFirstname();
        $shipTo->lastName = $shippingAddress->getLastname();
        $shipTo->street1 = $shippingAddress->getStreetLine(1);
        if ($shippingAddress->getStreetLine(2)) {
            $shipTo->street2 = $shippingAddress->getStreetLine(2);
        }

        $shipTo->city = $shippingAddress->getCity();
        $shipTo->state = $shippingAddress->getRegion();
        $shipTo->postalCode = $shippingAddress->getPostcode();
        $shipTo->country = $shippingAddress->getCountryId();
        $shipTo->phoneNumber = $shippingAddress->getTelephone();

        $request->shipTo = $shipTo;

        return $request;
    }

    /**
     * @param string $billingAgreementId
     * @return \stdClass
     */
    public function buildCancelBillingAgreementService($billingAgreementId)
    {
        $request = $this->buildBaseRequest();

        $request->merchantReferenceCode = $billingAgreementId;
        $request->partnerSolutionID = self::PARTNER_SOLUTION_ID;
        $apCancelService = new \stdClass();
        $apCancelService->run = "true";
        $request->apCancelService = $apCancelService;

        $ap = new \stdClass();
        $ap->billingAgreementID = $billingAgreementId;
        $request->ap = $ap;

        return $request;
    }

    /**
     * @param Quote\Address $quoteAddress
     * @param array $getDetailsResponse
     * @return \stdClass
     */
    private function buildAddress($quoteAddress, $getDetailsResponse = null)
    {
        /** @var \Magento\Quote\Model\Quote\Address $quoteAddress */
        $address = new \stdClass();
        $address->city =  $quoteAddress->getData('city');
        $address->country = $quoteAddress->getData('country_id');
        $address->postalCode = $quoteAddress->getData('postcode');
        $address->state = $quoteAddress->getRegionCode();
        $address->street1 = $quoteAddress->getStreetLine(1);
        if (strlen(trim($quoteAddress->getStreetLine(2)))) {
            $address->street2 = $quoteAddress->getStreetLine(2);
        }
        if (!is_null($getDetailsResponse)) {
            $quoteAddress->setEmail($getDetailsResponse['paypalCustomerEmail']);
        }
        $address->email = $quoteAddress->getEmail();
        $address->company = $quoteAddress->getCompany();
        $address->phoneNumber = $quoteAddress->getTelephone();
        $address->firstName = $quoteAddress->getFirstname();
        $address->lastName = $quoteAddress->getLastname();

        if ($quoteAddress->getAddressType() == Quote\Address::TYPE_BILLING) {
            $address->ipAddress = $this->_remoteAddress->getRemoteAddress();
            $address->phoneNumber = $quoteAddress->getTelephone();
        }

        return $address;
    }

    /**
     * @param Quote\Item[] $items
     * @param \stdClass $request
     * @return \stdClass
     */
    private function buildRequestItems(array $items, \stdClass $request, $shippingCharge = null)
    {
        $index = 0;
        foreach ($items as $i => $item) {

            /** @var \Magento\Sales\Model\Order\Item $item */
            $qty = (!empty($item->getQty()) ? $item->getQty() : $item->getQtyOrdered());

            if (!empty($qty) && $qty == 0) {
                continue;
            }
            else if(empty($qty)){
                $qty = 1;
            }
            
            $requestItem = new \stdClass();
            $requestItem->id = $i;
            $requestItem->productName = $item->getName();
            $requestItem->productSKU = $item->getSku();
            $requestItem->quantity = (int) $qty;
            $requestItem->productCode = 'default';
            $requestItem->unitPrice = $this->formatAmount($item->getPrice());
            $requestItem->taxAmount = $this->formatAmount($item->getTaxAmount() + $item->getDiscountTaxCompensationAmount() + $item->getWeeeTaxAppliedRowAmount());
            $requestItem->totalAmount = $this->formatAmount($requestItem->unitPrice * $qty + $requestItem->taxAmount);
            $request->item[] = $requestItem;
            $index = $i;
        }

        $quoteShippingAddress = $this->checkoutSession->getQuote()->getShippingAddress();
        $shippingCostItem = new \stdClass();
        $shippingCostItem->id = $index + 1;
        $shippingCostItem->productCode = 'shipping_and_handling';
        $shippingCharge = $shippingCharge ?? $quoteShippingAddress->getBaseShippingAmount();
        $shippingCostItem->unitPrice = $this->formatAmount($shippingCharge);
        $shippingCostItem->taxAmount = $this->formatAmount($quoteShippingAddress->getBaseShippingTaxAmount());
        $request->item[] = $shippingCostItem;

        if (property_exists($request, 'item') && is_array($request->item)) {
            foreach ($request->item as $key => $item) {
                if ($item->unitPrice == 0 && $item->productCode != 'shipping_and_handling') {
                    unset($request->item[$key]);
                }
            }

            $request->item = array_values($request->item);
        }

        return $request;
    }

    /**
     * Builds Purchase Totals for Request.
     *
     * @param \Magento\Quote\Model\Quote | \Magento\Sales\Model\Order | \Magento\Sales\Model\Order\Invoice | \Magento\Sales\Model\Order\Creditmemo $object 
     * Takes Quote, Order, Invoice or CreditMemo object as an argument
     * 
     * @return \stdClass Returns the purchase totals
     */
    private function buildPurchaseTotals($object, $request){
        $purchaseTotals = new \stdClass();
        $discount = $object->getDiscountAmount() ?? ($object->getBaseSubtotal() - $object->getBaseSubtotalWithDiscount());
        $purchaseTotals->currency = $object->getBaseCurrencyCode();
        $purchaseTotals->grandTotalAmount = $this->formatAmount($object->getBaseGrandTotal());
        $purchaseTotals->taxAmount = $this->aggregateTaxAmountFromItems($request);
        $purchaseTotals->shippingAmount = $this->formatAmount($object->getShippingAddress()->getBaseShippingAmount());
        $purchaseTotals->subTotalAmount = $this->formatAmount($object->getBaseSubtotal());
        $purchaseTotals->shippingDiscountAmount = $this->formatAmount(abs($discount));
        return $purchaseTotals;
    }


    /**
     * @return \stdClass
     */
    private function buildBaseRequest($storeId = null)
    {
        $request = new \stdClass();

        if (is_null($storeId)) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        $request->apPaymentType = self::AP_PAYMENT_TYPE;
        $request->merchantID = $this->config->getValue(Config::KEY_MERCHANT_ID, $storeId);
        $request->deviceFingerprintID = $this->checkoutSession->getData('fingerprint_id');
        if ($developerId = $this->config->getDeveloperId()) {
            $request->developerId = $developerId;
        }

        if ($storeId) {
            $request->storeId = $storeId;
        }

        return $request;
    }

    /**
     * @param Quote\Item[] $items
     * @return string
     */
    private function aggregateTaxAmountFromItems($request)
    {
        $totalTaxAmount = 0;
        $items = $request->item;
        forEach($items as $item){
            $totalTaxAmount+=$item->taxAmount;
        }

        return $this->formatAmount($totalTaxAmount);
    }

    /**
     * Balances the Purchase totals and line items
     *
     * @param \stdClass $request
     * @return \stdClass
     */
    public function balancePurchaseTotalsAndLineItems($request){
        $originalRequest = $request;
        $isError = false;

        $items = $request->item;
        $purchaseTotals = $request->purchaseTotals;
        $discount = abs((float)$purchaseTotals->shippingDiscountAmount) ?? 0;
        $grandTotal = (float)$purchaseTotals->grandTotalAmount ?? 0;

        $lineItemsTotal = 0;
        $productCount = 0;
        
        forEach($items as $item){
            if($item->productCode != 'shipping_and_handling'){
                $lineItemsTotal += $item->totalAmount;
                $productCount++;
            }else{
                $lineItemsTotal += ($item->unitPrice + $item->taxAmount);
            }
            
        }
        $lineItemsTotal -= $discount;
        
        if($grandTotal == $lineItemsTotal){
            return $originalRequest;
        }

        
        $totalAmountDiff = $this->formatAmount($grandTotal - $lineItemsTotal);

        $addAllImbalanceToLineItem = false;
        if(($discount == 0)||($discount <= abs($totalAmountDiff))){
            $addAllImbalanceToLineItem = true;
        }

        $multipleSign = 1;
        if($totalAmountDiff < 0){
            $multipleSign = -1;
        }

        $multipleOfPointZeroOne = abs($totalAmountDiff)/0.01;

        $balanceDiscountAmount = 0;
        $balanceEachLineItemAmount = 0;
        $imbalanceToAddTillZero = 0;
        if($addAllImbalanceToLineItem){
            $balanceEachLineItemAmount = floor($multipleOfPointZeroOne/$productCount);
            $imbalanceToAddTillZero = $multipleOfPointZeroOne - $balanceEachLineItemAmount*$productCount;
        }
        else{
            $balanceEachLineItemAmount = ceil($multipleOfPointZeroOne/$productCount);
            $balanceDiscountAmount = $balanceEachLineItemAmount * $productCount - $multipleOfPointZeroOne;
        }

        //shipping line item
        $iterationCount = $productCount + 1;

        if($balanceEachLineItemAmount != 0 || $imbalanceToAddTillZero !=  0){
            $totalTax = 0;
            $subTotal = 0; 
            for($i=0;$i<$iterationCount;$i++){
                if($items[$i]->productCode != 'shipping_and_handling'){
                    $qty = $items[$i]->quantity;
                    $unitPrice = $items[$i]->unitPrice;
                    $tax = $items[$i]->taxAmount;
                    $rowTotal = $items[$i]->totalAmount;

                    $balancePerUnitPrice = floor($balanceEachLineItemAmount/$qty);
                    $balanceTaxAmount = $balanceEachLineItemAmount - $balancePerUnitPrice*$qty;
                    
                    if($imbalanceToAddTillZero > 0){
                        $balanceTaxAmount++;
                        $imbalanceToAddTillZero--;
                    }

                    $unitPrice += $balancePerUnitPrice*0.01*$multipleSign; 
                    $tax += $balanceTaxAmount*0.01*$multipleSign;
                    $rowTotal = $unitPrice*$qty + $tax;

                    $items[$i]->unitPrice = $this->formatAmount($unitPrice);
                    $items[$i]->taxAmount = $this->formatAmount($tax);
                    $items[$i]->totalAmount = $this->formatAmount($rowTotal);
                    $totalTax += $tax;
                    $subTotal += $unitPrice*$qty;

                    if($unitPrice < 0 || $tax < 0 || $rowTotal < 0 || $totalTax < 0 || $subTotal < 0) {
                        $isError = true;
                        break;
                    }
                }
                else
                {
                    $totalTax += $items[$i]->taxAmount ?? 0;
                }
            }
            $purchaseTotals->taxAmount = $this->formatAmount($totalTax);
            $purchaseTotals->subTotalAmount = $subTotal;
        }
        $request->item = $items;

        $purchaseTotalsShippingDiscountAmount = $this->formatAmount($discount + $balanceDiscountAmount*0.01*$multipleSign);
        if($purchaseTotalsShippingDiscountAmount < 0) {
            $isError = true;
        }
        $purchaseTotals->shippingDiscountAmount = $purchaseTotalsShippingDiscountAmount;

        $request->purchaseTotals = $purchaseTotals;

        if($isError) {
            return $originalRequest;
        }
        return $request;
    }
}
