<?php

namespace CyberSource\Core\Gateway\Request\Soap;

use Magento\Payment\Helper\Formatter;

class ItemsBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    use Formatter;

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var \Magento\Tax\Model\Config
     */
    private $taxConfig;

    /**
     * @var \CyberSource\Core\StringUtils\FilterInterface
     */
    private $filter;

    /**
     * @var string
     */
    private $objectName;

    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader,
        \Magento\Tax\Model\Config $taxConfig,
        ?string $objectName = null
    ) {
        $this->subjectReader = $subjectReader;
        $this->taxConfig = $taxConfig;
        $this->objectName = $objectName;
    }

    /**
     * Builds Order Data
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();

        $result = $this->{'get' . ucfirst($this->objectName ?? '') . 'Items'}($payment);

        return ['item' => $result];
    }


    private function getShippingOrderLineItem($payment, $id)
    {

        $amount = $payment->getData('base_shipping_amount');

        if (!$amount || $amount <= 0) {
            return [];
        }

        $shippingData = [
            'id' => $id,
            'productCode' => 'shipping_and_handling',
            'quantity' => 1,
            'unitPrice' => $this->formatPrice($amount),
        ];

        if (!$payment instanceof \Magento\Sales\Model\Order\Payment) {
            return $shippingData;
        }

        $order = $payment->getOrder();
        $shippingData['taxAmount'] = $this->formatPrice($order->getBaseShippingTaxAmount());

        return $shippingData;
    }

    private function getOrderItems($payment)
    {
        $result = $this->getItems($payment->getOrder()->getAllItems());

        if ($shippingItem = $this->getShippingOrderLineItem($payment, count($result))) {
            $result[] = $shippingItem;
        }

        return $result;
    }

    private function getCreditmemoItems($payment)
    {
        $result =  $this->getItems($payment->getCreditmemo()->getAllItems());
        if ($shippingItem = $this->getShippingOrderLineItem($payment, count($result))) {
            $result[] = $shippingItem;
        }
        return $result;
    }

    private function getInvoiceItems($payment)
    {
        $invoice = $payment->getInvoice();
        if (!$invoice) {
            $invoice = $payment->getCreatedInvoice();
        }
        $result =  $this->getItems($invoice->getAllItems());
        if ($shippingItem = $this->getShippingOrderLineItem($payment, count($result))) {
            $result[] = $shippingItem;
        }
        return $result;
    }

    private function getItems($items)
    {
        $result = [];
        $i = 0;
        foreach ($items as $key => $item) {
            //getProductType used for order items, getOrderItem for invoice, creditmemo
            $type = $item->getProductType() ?: $item->getOrderItem()->getProductType();

            if ($this->shouldSkipItem($item)) {
                continue;
            }

            //getQtyOrdered used for order items, getQty for invoice, creditmemo
            $qty = $item->getQty() ?: $item->getQtyOrdered();

            $basePrice = ($this->taxConfig->priceIncludesTax($item->getStoreId()))
                ? $item->getBasePriceInclTax()
                : $item->getBasePrice();

            $price = $basePrice - $item->getBaseDiscountAmount() / $qty;

            $result[$i] = [
                'id' => $i,
                'productName' => $item->getName(),
                'productSKU' => $item->getSku(),
                'productCode' => $type,
                'quantity' => (int)$qty,
                'unitPrice' => $this->formatPrice($price),
                'taxAmount' => $this->formatPrice($item->getBaseTaxAmount())
            ];
            $i++;
        }
        return $result;
    }

    private function shouldSkipItem($item)
    {
        if ($item->getBasePrice() == 0) {
            return true;
        }

        /** @var \Magento\Sales\Model\Order\Item $orderItem */
        $orderItem = $item->getOrderItem() ?: $item;

        if ($orderItem->getProductType() == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
            if (
                $orderItem->getProductOptionByCode('product_calculations')
                == \Magento\Catalog\Model\Product\Type\AbstractType::CALCULATE_CHILD
            ) {
                return true;
            }
        }

        return false;
    }
}
