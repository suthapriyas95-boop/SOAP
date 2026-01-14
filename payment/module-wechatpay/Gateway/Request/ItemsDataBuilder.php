<?php
/**
 * Copyright © 2021 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\WeChatPay\Gateway\Request;


class ItemsDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    /**
     * @var \CyberSource\WeChatPay\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    protected $checkoutSession;

    /**
     * @param \CyberSource\WeChatPay\Gateway\Helper\SubjectReader $subjectReader
     */
    public function __construct(
        \CyberSource\WeChatPay\Gateway\Helper\SubjectReader $subjectReader,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->subjectReader = $subjectReader;
        $this->checkoutSession = $checkoutSession;
    }
  
    /**
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentDO->getPayment();

        /** @var OrderAdapterInterface $order */
        $order = $paymentDO->getOrder();
        
        $request = new \stdClass();
        $isBundle = false;
        if($payment->getCreditmemo()) {

            $creditmemoItems= $payment->getCreditmemo()->getAllItems();
            
        }
        if(!empty($creditmemoItems)) {
        
            $items = $creditmemoItems;
        }
        else {
            
            $items = $order->getItems();
        }
        
        foreach ($items as $i => $item) {

           /** @var \Magento\Sales\Model\Order\Item $item */
           $qty = (!empty($item->getQty()) ? $item->getQty() : $item->getQtyOrdered());

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
            $requestItem->taxAmount = $this->formatAmount($item->getTaxAmount());
            $requestItem->totalAmount = $this->formatAmount(($amount * $qty) + $requestItem->taxAmount);
            $requestItem->parentId = $item->getParentItemId();
            
            $request->item[] = $requestItem;
        }
        
        $quoteShippingAddress =  $this->checkoutSession->getQuote()->getShippingAddress();
        $shippingCostItem = new \stdClass();
        $shippingCostItem->id = count($request->item) + 1;
        $shippingCostItem->productCode = 'shipping_and_handling';
        if($payment->getCreditmemo()){
            $shippingAmount= $payment->getCreditmemo()->getShippingAmount();
        }
        
        if(!empty($shippingAmount)){
            
            $shippingCostItem->unitPrice = $this->formatAmount($shippingAmount);

        }
       else{
            $shippingCostItem->unitPrice = $this->formatAmount($quoteShippingAddress->getBaseShippingAmount());
        }

        $shippingCostItem->totalAmount =  $this->formatAmount($shippingCostItem->unitPrice);
        $shippingCostItem->taxAmount = $this->formatAmount($quoteShippingAddress->getBaseShippingTaxAmount());
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
        return (array) $request;
   
    }
    protected function formatAmount($amount)
    {
        return sprintf('%.2F', $amount);
    }
}
