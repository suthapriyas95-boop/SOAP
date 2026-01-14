<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\BankTransfer\Helper;

use CyberSource\BankTransfer\Model\Config;
use CyberSource\Core\Helper\AbstractDataBuilder;
use Magento\Framework\App\Helper\Context;

class RequestDataBuilder extends AbstractDataBuilder
{
    /**
     * @var Config
     */
    private $gatewayConfig;
    /**
     * RequestDataBuilder constructor.
     *
     * @param Context $context
     * @param Config $gatewayConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Helper\Data $data
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Grid\CollectionFactory $orderGridCollectionFactory
     * @param \Magento\Backend\Model\Auth $auth
     * @param \Magento\GiftMessage\Model\Message $giftMessage
     */
    public function __construct(
        Context $context,
        Config $gatewayConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
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

        $this->gatewayConfig = $gatewayConfig;
    }

    /**
     * @param array $items
     * @param \stdClass $request
     * @param $shippingInclTax
     * @return mixed
     */
    public function buildRequestItems(array $items, \stdClass $request, $shippingInclTax)
    {
        $isBundle = false;
        foreach ($items as $i => $item) {

            if (empty($item->getPrice()) && $item->getParentItemId()) {
                continue;
            }

            $qty = $item->getQty();
            if (empty($qty)) {
                $qty = 1;
            }

            $amount = ($item->getPrice() - ($item->getDiscountAmount() / $qty));
            $requestItem = new \stdClass();
            $requestItem->id = $i;
            $requestItem->productName = $item->getName();
            $requestItem->productSKU = $item->getSku();
            $requestItem->quantity = (int) $qty;
            $requestItem->productCode = 'default';
            $requestItem->unitPrice = $this->formatAmount($amount);
            $requestItem->totalAmount = $this->formatAmount($amount * $qty);
            $requestItem->taxAmount = $this->formatAmount($item->getTaxAmount());
            $requestItem->parentId = $item->getParentItemId();

            $request->item[] = $requestItem;

            if ($item->getProductType() === \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                $isBundle = true;
                $i++;
                foreach ($item->getQtyOptions() as $option) {
                    $product = $option->getProduct();
                    $requestItem = new \stdClass();
                    $requestItem->id = $i++;
                    $requestItem->productName = $product->getName();
                    $requestItem->productSKU = $product->getSku();
                    $requestItem->quantity = $product->getQuoteItemQty();
                    $requestItem->productCode = 'default';
                    $requestItem->unitPrice = $this->formatAmount(0);
                    $requestItem->totalAmount = $this->formatAmount(0);
                    $requestItem->taxAmount = $this->formatAmount(0);

                    $request->item[] = $requestItem;
                }
            }
        }

        $shippingCost = $shippingInclTax;
        $shippingCostItem = new \stdClass();
        $shippingCostItem->id = count($request->item) + 1;
        $shippingCostItem->productCode = 'shipping_and_handling';
        $shippingCostItem->unitPrice = $this->formatAmount($shippingCost);
        $shippingCostItem->totalAmount = $this->formatAmount($shippingCost);
        $shippingCostItem->parentId = null;
        $request->item[] = $shippingCostItem;

        if (property_exists($request, 'item') && is_array($request->item) && !$isBundle) {
            foreach ($request->item as $key => $item) {
                if ($item->unitPrice == 0 && $item->parentId !== null && $item->productCode != 'shipping_and_handling') {
                    unset($request->item[$key]);
                }
            }

            $request->item = array_values($request->item);
        }

        foreach ($request->item as $key => $item) {
            if (property_exists($item, 'parentId')) {
                unset($request->item[$key]->parentId);
            }
        }

        return $request;
    }

    /**
     * @param $quoteAddress
     * @return \stdClass
     */
    public function buildAddress($quoteAddress)
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
        $address->email = $quoteAddress->getEmail();
        $address->company = $quoteAddress->getCompany();
        $address->phoneNumber = $quoteAddress->getTelephone();
        $address->firstName = $quoteAddress->getFirstname();
        $address->lastName = $quoteAddress->getLastname();

        if ($quoteAddress->getAddressType() == \Magento\Quote\Model\Quote\Address::TYPE_BILLING) {
            //$address->ipAddress = $this->getRemoteAddress();
            $address->phoneNumber = $quoteAddress->getTelephone();
        }

        return $address;
    }


    /**
     * @param $store
     * @param $merchantId
     * @param \Magento\Quote\Model\Quote $quote
     * @param $bankCode
     * @param null $deviceId
     * @return /stdClass
     */
    public function buildSaleService($store, $merchantId, $quote, $bankCode, $deviceId=null)
    {
        $request = new \stdClass();

        $apSaleService = new \stdClass();
        $apSaleService->run = 'true';
        $apSaleService->cancelURL = $store->getBaseUrl() . 'cybersourcebt/index/cancel';
        $apSaleService->successURL = $store->getBaseUrl() . 'cybersourcebt/index/success';
        $apSaleService->failureURL = $store->getBaseUrl() . 'cybersourcebt/index/failure';

        $request->merchantID = $merchantId;
        $request->partnerSolutionID = self::PARTNER_SOLUTION_ID;
        $developerId = $this->gatewayConfig->getValue(
            "payment/chcybersource/developer_id",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $request->merchantReferenceCode = $quote->getReservedOrderId();

        switch ($bankCode) {
            case 'sofort':
                $request->apPaymentType = 'SOF';
                break;
            case 'bancontact':
                $request->apPaymentType = 'MCH';
                break;
            default:
                $request->apPaymentType = 'IDL';
                $apSaleService->paymentOptionID = $bankCode;
        }

        $request->apSaleService = $apSaleService;

        $purchaseTotals = new \stdClass();
        $purchaseTotals->grandTotalAmount = $quote->getBaseGrandTotal();
        $purchaseTotals->currency = $quote->getBaseCurrencyCode();
        $request->purchaseTotals = $purchaseTotals;

        $invoiceHeader = new \stdClass();
        $storeName = $this->gatewayConfig->getValue(
            "payment/cybersource_bank_transfer/bank_store_name",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $invoiceHeader->merchantDescriptor = empty($storeName) ? "Online Store" : $storeName;
        $request->invoiceHeader = $invoiceHeader;

        $request->billTo = $this->buildAddress($quote->getBillingAddress());

        if ($quote->getShippingAddress()) {
            $request->shipTo = $this->buildAddress($quote->getShippingAddress());
        }

        $request = $this->buildRequestItems($quote->getAllVisibleItems(), $request, $quote->getShippingAddress()->getShippingInclTax());

        $request->merchantDefinedData = $this->buildDecisionManagerFields($quote);

        if (!empty($deviceId)) {
            $request->deviceFingerprintID = $deviceId;
        }

        return $request;
    }


    /**
     * @param $requestId
     * @param $order
     * @param $paymentMethod
     * @param $merchantId
     * @return /stdClass
     */
    public function buildRefundService($requestId, $order, $paymentMethod, $merchantId)
    {
        $request = new \stdClass();
        $request->merchantID = $merchantId;
        $request->partnerSolutionID = RequestDataBuilder::PARTNER_SOLUTION_ID;
        $developerId = $this->gatewayConfig->getValue(
            "payment/chcybersource/developer_id",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $request->merchantReferenceCode = $order->getIncrementId();
        switch ($paymentMethod) {
            case 'sofort':
                $request->apPaymentType = 'SOF';
                break;
            case 'bancontact':
                $request->apPaymentType = 'MCH';
                break;
            default:
                $request->apPaymentType = 'IDL';
        }
        $creditMemoRefundAmount = $order->getBaseGrandTotal();
        if($order->getBaseAdjustmentNegative() != NULL && $order->getBaseAdjustmentPositive() != NULL ){
            $adjustRefund = $order->getBaseGrandTotal() - $order->getBaseAdjustmentNegative();  
            $creditMemoRefundAmount = $adjustRefund + $order->getBaseAdjustmentPositive();
            if($order->getShippingRefunded()!=0){
                $adjustRefundShipping = $creditMemoRefundAmount - $order->getShippingAmount();
                $creditMemoRefundAmount = $adjustRefundShipping + $order->getShippingRefunded();
            }
        }
        $purchaseTotals = new \stdClass();
        $purchaseTotals->grandTotalAmount = $this->formatAmount($creditMemoRefundAmount);
        $purchaseTotals->currency = $order->getBaseCurrencyCode();
        $request->purchaseTotals = $purchaseTotals;
        $apRefundService = new \stdClass();
        $apRefundService->run = 'true';
        $apRefundService->refundRequestID = $requestId;
        $request->apRefundService = $apRefundService;

        return $request;
    }


    /**
     * @param $requestId
     * @param $orderId
     * @param $paymentMethod
     * @param $merchantId
     * @return /stdClass
     */
    public function buildCheckStatusService($requestId, $orderId, $paymentMethod, $merchantId){
        $request = new \stdClass();
        $request->merchantID = $merchantId;
        $request->partnerSolutionID = RequestDataBuilder::PARTNER_SOLUTION_ID;
        $developerId = $this->gatewayConfig->getValue(
            "payment/chcybersource/developer_id",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $request->merchantReferenceCode = $orderId;
        switch ($paymentMethod) {
            case 'sofort':
                $request->apPaymentType = 'SOF';
                break;
            case 'bancontact':
                $request->apPaymentType = 'MCH';
                break;
            default:
                $request->apPaymentType = 'IDL';
        }
        $apCheckStatusService = new \stdClass();
        $apCheckStatusService->run = 'true';
        $apCheckStatusService->checkStatusRequestID = $requestId;
        $request->apCheckStatusService = $apCheckStatusService;

        return $request;
    }

    

}