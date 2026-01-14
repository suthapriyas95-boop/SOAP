<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\KlarnaFinancial\Helper;

use CyberSource\Core\Helper\AbstractDataBuilder;
use Magento\Checkout\Helper\Data as CheckoutHelper;
use CyberSource\KlarnaFinancial\Gateway\Config\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;

class RequestDataBuilder extends AbstractDataBuilder
{
    const PAYMENT_TYPE = 'KLI';
    const SESSION_TYPE_UPDATE = 'U';
    const SESSION_TYPE_CREATE = 'N';
    const DEFAULT_BILL_TO_COUNTRY = 'US';
    const DEFAULT_BILL_TO_STATE = 'NY';
    const DEFAULT_BILL_TO_POSTCODE = '10001';
    const CAPTURE_TRANSACTION_ID =  'CaptureTransactionId';
	const PAYMENT_FLOW_MODE = 'inline';
	const PAYMENT_METHOD_NAME = 'pay_now';

    /**
     * @var Config
     */
    private $gatewayConfig;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var \Magento\Framework\Locale\Resolver
     */
    private $locale;

    /**
     * @var \Magento\Customer\Model\Address
     */
    private $address;

    /**
     * @var \Magento\Store\Model\Information
     */
    private $storeInformation;

    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    private $regionFactory;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Store\Model\Information $storeInformation
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param CheckoutHelper $checkoutHelper
     * @param Config $gatewayConfig
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Framework\Locale\Resolver $locale
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Grid\CollectionFactory $orderGridCollectionFactory
     * @param \Magento\Backend\Model\Auth $auth
     * @param \Magento\GiftMessage\Model\Message $giftMessage
     * @param \Magento\Customer\Model\Address $address
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\Information $storeInformation,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        CheckoutHelper $checkoutHelper,
        Config $gatewayConfig,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\Locale\Resolver $locale,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Grid\CollectionFactory $orderGridCollectionFactory,
        \Magento\Backend\Model\Auth $auth,
        \Magento\GiftMessage\Model\Message $giftMessage,
        \Magento\Customer\Model\Address $address
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
        $this->quoteRepository = $quoteRepository;
        $this->locale = $locale;
        $this->address = $address;
        $this->storeInformation = $storeInformation;
        $this->regionFactory = $regionFactory;
    }

    /**
     * @param bool $updateMode
     * @return \stdClass
     * @throws LocalizedException
     */
    public function buildSessionRequest($updateMode = false)
    {
        $quote = $this->checkoutSession->getQuote();
        $email = $quote->getCustomerEmail();

        $request = new \stdClass();

        $request->merchantReferenceCode = $quote->getReservedOrderId();
        $request->partnerSolutionID = self::PARTNER_SOLUTION_ID;
        $request->deviceFingerprintID = $this->checkoutSession->getData('fingerprint_id');

        $request = $this->buildRequestItems($quote->getAllVisibleItems(), $request);

        $request->apPaymentType = self::PAYMENT_TYPE;

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $quote->getQuoteCurrencyCode();
        $purchaseTotals->discountAmount = ($quote->getBaseSubtotal() - $quote->getBaseSubtotalWithDiscount());
        $purchaseTotals->grandTotalAmount = $quote->getGrandTotal();
        $request->purchaseTotals = $purchaseTotals;

        $request = $this->balancePurchaseTotalsAndLineItems($request);
        $successUrl = $this->_getUrl('checkout/onepage/success');
        $cancelOrFailureUrl = $this->_getUrl('*/*/cancel');

        $apSessionsService = new \stdClass();
        $apSessionsService->run = "true";
        $apSessionsService->cancelURL = $cancelOrFailureUrl;
        $apSessionsService->failureURL = $cancelOrFailureUrl;
        $apSessionsService->successURL = $successUrl;
        $apSessionsService->sessionsType = self::SESSION_TYPE_CREATE;
		$apSessionsService->paymentFlowMode = self::PAYMENT_FLOW_MODE;
		$apSessionsService->paymentMethodName = self::PAYMENT_METHOD_NAME;
		
		
        if ($updateMode && $this->checkoutSession->getKlarnaSessionRequestId()) {
            $request->billTo = $this->buildAddress($quote->getBillingAddress(), $email);

            $request->shipTo = $quote->getIsVirtual()
                ? $this->buildAddress($quote->getBillingAddress(), $email)
                : $this->buildAddress($quote->getShippingAddress(), $email);

            $apSessionsService->sessionsRequestID = $this->checkoutSession->getKlarnaSessionRequestId();
            $apSessionsService->sessionsType = self::SESSION_TYPE_UPDATE;
            $request->apSessionsService = $apSessionsService;

            return $request;
        }

        $request->billTo = $quote->getBillingAddress()->getCountryId()
            ? (object)[
                'country' => $quote->getBillingAddress()->getCountryId(),
                'state' => $quote->getBillingAddress()->getRegionCode(),
                'postalCode' => $quote->getBillingAddress()->getPostcode(),
            ]
            : $this->buildDefaultAddress($quote->getBillingAddress());


        $request->apSessionsService = $apSessionsService;
        return $request;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return \stdClass
     */
    public function buildAuthorizationRequestData(\Magento\Payment\Model\InfoInterface $payment)
    {
        $quote = $this->checkoutSession->getQuote();
        $email = $quote->getCustomerEmail();

        $quote->collectTotals();

        $request = new \stdClass();

        $request->merchantReferenceCode = $quote->getReservedOrderId();
        $request->partnerSolutionID = self::PARTNER_SOLUTION_ID;
        $request->deviceFingerprintID = $this->checkoutSession->getData('fingerprint_id');

        $request->billTo = $this->buildAddress($quote->getBillingAddress(), $email);
        $request->shipTo = $quote->getIsVirtual()
            ? $this->buildAddress($quote->getBillingAddress(), $email)
            : $this->buildAddress($quote->getShippingAddress(), $email);

        $request = $this->buildRequestItems($quote->getAllVisibleItems(), $request);

        $request->apPaymentType = self::PAYMENT_TYPE;

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $quote->getQuoteCurrencyCode();
        $purchaseTotals->discountAmount = ($quote->getBaseSubtotal() - $quote->getBaseSubtotalWithDiscount());
        $purchaseTotals->grandTotalAmount = $quote->getGrandTotal();
        $request->purchaseTotals = $purchaseTotals;

        $request = $this->balancePurchaseTotalsAndLineItems($request);
        
        $apAuthService = new \stdClass();
        $apAuthService->run = "true";
        $apAuthService->preapprovalToken = $payment->getAdditionalInformation("authorizationToken");

        $request->apAuthService = $apAuthService;
        return $request;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param $amount
     * @return \stdClass
     */
    public function buildCaptureRequestData(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        /** @var Order $order */
        $order = $payment->getOrder();
        $email = $order->getCustomerEmail();
        $request = new \stdClass();

        $request->merchantReferenceCode = $order->getIncrementId();
        $request->partnerSolutionID = self::PARTNER_SOLUTION_ID;

        $request->apPaymentType = self::PAYMENT_TYPE;
        $request->deviceFingerprintID = $this->checkoutSession->getData('fingerprint_id');

        $apCaptureService = new \stdClass();
        $apCaptureService->run = "true";
        $apCaptureService->authRequestID = $payment->getCcTransId();

        $this->buildCaptureSequence($payment, $apCaptureService, $amount);

        $request->apCaptureService = $apCaptureService;

        /** @var Invoice $invoice */
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
        $request->billTo = $this->buildAddress($order->getBillingAddress(), $email);
        $request->shipTo = $order->getIsVirtual()
            ? $this->buildAddress($order->getBillingAddress(), $email)
            : $this->buildAddress($order->getShippingAddress(), $email);

        $request = $this->buildRequestItems($invoicedItems, $request, $invoice);

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $order->getOrderCurrencyCode();
        $purchaseTotals->discountAmount = abs($invoice->getBaseDiscountAmount());
        $purchaseTotals->grandTotalAmount = $this->formatAmount($amount);
        $request->purchaseTotals = $purchaseTotals;
        $request = $this->balancePurchaseTotalsAndLineItems($request);

        return $request;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return \stdClass
     */
    public function buildVoidRequestData(\Magento\Payment\Model\InfoInterface $payment)
    {
        /** @var Order $order */
        $order = $payment->getOrder();
        $email = $order->getCustomerEmail();
        $request = new \stdClass();

        $request->merchantReferenceCode = $order->getIncrementId();
        $request->partnerSolutionID = self::PARTNER_SOLUTION_ID;
        $request->apPaymentType = self::PAYMENT_TYPE;

        $request->billTo = $this->buildAddress($order->getBillingAddress(), $email);
        $request->shipTo = $order->getIsVirtual()
            ? $this->buildAddress($order->getBillingAddress(), $email)
            : $this->buildAddress($order->getShippingAddress(), $email);

       
        $apAuthReversalService = new \stdClass();
        $apAuthReversalService->run = "true";
        $apAuthReversalService->authRequestID = $payment->getCcTransId();
        $request->apAuthReversalService = $apAuthReversalService;

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $order->getOrderCurrencyCode();
        $purchaseTotals->grandTotalAmount = $this->formatAmount($order->getGrandTotal());
        $request->purchaseTotals = $purchaseTotals;

        return $request;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return \stdClass
     */
    public function buildRefundRequestData(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        /** @var Order $order */
        $order = $payment->getOrder();

        $request = new \stdClass();

        $request->merchantReferenceCode = $order->getIncrementId();
        $request->partnerSolutionID = self::PARTNER_SOLUTION_ID;

        /** @var Creditmemo $creditMemo */
        $creditMemo = $payment->getCreditmemo();
        $email = $order->getCustomerEmail();
        $request = $this->buildRequestItems($creditMemo->getAllItems(), $request, $creditMemo);

        $request->apPaymentType = self::PAYMENT_TYPE;
        $request->billTo = $this->buildAddress($creditMemo->getBillingAddress(), $email);
        $request->shipTo = $order->getIsVirtual()
            ? $this->buildAddress($order->getBillingAddress(), $email)
            : $this->buildAddress($order->getShippingAddress(), $email);

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $order->getOrderCurrencyCode();
        $purchaseTotals->discountAmount = abs($creditMemo->getBaseDiscountAmount());
        $purchaseTotals->grandTotalAmount = (float)$this->formatAmount($amount);
        $request->purchaseTotals = $purchaseTotals;

        $request = $this->balancePurchaseTotalsAndLineItems($request);
        
        $apRefundService = new \stdClass();
        $apRefundService->run = "true";
        $apRefundService->refundRequestID = $payment->getAdditionalInformation(self::CAPTURE_TRANSACTION_ID);


        $request->apRefundService = $apRefundService;

        return $request;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address $quoteAddress
     * @param string $email
     * @return \stdClass
     * @throws LocalizedException
     */
    private function buildAddress($quoteAddress, $email)
    {
        $address = new \stdClass();

        if (! $email) {
            throw new LocalizedException(__('Email is required.'));
        }

        if (! $quoteAddress->getCountryId()) {
            return $this->buildDefaultAddress($quoteAddress, $email);
        }

        $address->email = $email;
        $address->company = $quoteAddress->getCompany();
        $address->phoneNumber = $quoteAddress->getTelephone();
        $address->city = $quoteAddress->getCity();
        $address->country = $quoteAddress->getCountryId();
        $address->postalCode = $quoteAddress->getPostcode();
        $address->district = $quoteAddress->getRegionCode();
        $address->state = $quoteAddress->getRegionCode();
        
        if($quoteAddress instanceof \Magento\Payment\Gateway\Data\AddressAdapterInterface)
        {
            $address->street1 = $quoteAddress->getStreetLine1();
            $address->street2 = $quoteAddress->getStreetLine2();
        }
        else{
            $address->street1 = $quoteAddress->getStreetLine(1);
            $address->street2 = $quoteAddress->getStreetLine(2);
        }

        $address->firstName = $quoteAddress->getFirstname();
        $address->lastName = $quoteAddress->getLastname();
        $address->language = str_replace("_", "-", $this->locale->getLocale());

        return $address;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Address $quoteAddress
     * @param string $email
     * @return \stdClass
     */
    private function buildDefaultAddress($quoteAddress, $email = null)
    {
        $address = new \stdClass();
        $address->email = $email;

        $storeInfo = $this->storeInformation->getStoreInformationObject(
            $quoteAddress->getQuote()->getStore()
        );

        $address->country = $storeInfo->getCountryId() ?: self::DEFAULT_BILL_TO_COUNTRY;
        $region = $this->regionFactory->create()->loadByName(
            $storeInfo->getRegion(),
            $address->country
        );
        $address->state = $region->getCode() ?: self::DEFAULT_BILL_TO_STATE;
        $address->postalCode = $storeInfo->getPostcode() ?: self::DEFAULT_BILL_TO_POSTCODE;

        return $address;
    }

    /**
     * @param array $items
     * @param \stdClass $request
     * @param null $order
     * @return mixed
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function buildRequestItems(array $items, \stdClass $request, $object = null)
    {
        $index = 0;
        foreach ($items as $i => $item) {
            /** @var \Magento\Sales\Model\Order\Item $item */
            $qty = (!empty($item->getQty()) ? $item->getQty() : $item->getQtyOrdered());
            if (empty($qty)) {
                $qty = 1;
            }
            
            $requestItem = new \stdClass();
            $requestItem->id = $i;
            $requestItem->productName = $item->getName();
            $requestItem->productSKU = $item->getSku();
            $requestItem->quantity = (int)$qty;
            $requestItem->productCode = 'default';
            $requestItem->unitPrice = $this->formatAmount($item->getPrice());
            $requestItem->taxAmount = $this->formatAmount($item->getTaxAmount() + $item->getDiscountTaxCompensationAmount() + $item->getWeeeTaxAppliedRowAmount());
            $requestItem->totalAmount = $this->formatAmount($requestItem->unitPrice * $qty + $requestItem->taxAmount);
            $request->item[] = $requestItem;
            $index = $i;
        }

        /** @var Quote $quote */
        $quote = $this->checkoutSession->getQuote();

        if (is_null($object)) {
            $shippingAddress = $quote->getShippingAddress();
            $shippingCost = $shippingAddress->getBaseShippingAmount();
            $shippingCostTax = $shippingAddress->getBaseShippingTaxAmount();
        }
        else {
            /** @var Order $object */
            $shippingCost = $object->getBaseShippingAmount();
            $shippingCostTax = $object->getBaseShippingTaxAmount();
        }

        $shippingCostItem = new \stdClass();
        $shippingCostItem->id = ++$index;
        $shippingCostItem->productName = "shipping";
        $shippingCostItem->productSKU = "shipping";
        $shippingCostItem->quantity = (int)1;
        $shippingCostItem->productCode = 'shipping';
        $shippingCostItem->unitPrice = $this->formatAmount($shippingCost);
        $shippingCostItem->taxAmount = $this->formatAmount($shippingCostTax);
        $shippingCostItem->totalAmount = $this->formatAmount($shippingCost + $shippingCostItem->taxAmount);
        $request->item[] = $shippingCostItem;

        if (property_exists($request, 'item') && is_array($request->item)) {
            foreach ($request->item as $key => $item) {
                if ($item->unitPrice == 0) {
                    unset($request->item[$key]);
                }
            }

            $request->item = array_values($request->item);
        }

        return $request;
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
        $discount = abs((float)$purchaseTotals->discountAmount) ?? 0;
        $grandTotal = (float)$purchaseTotals->grandTotalAmount ?? 0;

        $lineItemsTotal = 0;
        $productCount = 0;
        $isShippingAmountIncluded = false;
        forEach($items as $item){
            $lineItemsTotal += $item->totalAmount;
            if($item->productCode != 'shipping'){
                $productCount++;
            }
            else 
                $isShippingAmountIncluded = true;
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

        $iterationCount = $productCount;
        if($isShippingAmountIncluded){
            $iterationCount++;
        }
        
        if($balanceEachLineItemAmount != 0 || $imbalanceToAddTillZero !=  0){
            for($i=0;$i<$iterationCount;$i++){
                if($items[$i]->productCode != 'shipping'){
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

                    if($unitPrice < 0 || $tax < 0 || $rowTotal < 0) {
                        $isError = true;
                        break;
                    }
    
                    $items[$i]->unitPrice = $this->formatAmount($unitPrice);
                    $items[$i]->taxAmount = $this->formatAmount($tax);
                    $items[$i]->totalAmount = $this->formatAmount($rowTotal);
                }
            }
        }
        
        $request->item = $items;
        
        $purchaseTotalsDiscountAmount = $this->formatAmount($discount + $balanceDiscountAmount*0.01*$multipleSign);
        if($purchaseTotalsDiscountAmount < 0){
            $isError = true;
        }
        $purchaseTotals->discountAmount = $purchaseTotalsDiscountAmount;
        $request->purchaseTotals = $purchaseTotals;

        if($isError){
            return $originalRequest;
        }
        return $request;
    }
}
