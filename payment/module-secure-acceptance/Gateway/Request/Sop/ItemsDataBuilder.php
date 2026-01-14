<?php
/*
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\SecureAcceptance\Gateway\Request\Sop;


class ItemsDataBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    use \Magento\Payment\Helper\Formatter;

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
     * @var string|null
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
     *
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        /** @var \Magento\Sales\Api\Data\OrderItemInterface[] $items */
        $items = $paymentDO->getOrder()->getItems();

        $result = [];
        foreach ($items as $key => $item) {
            $type = $item->getProductType();

            if ($this->shouldSkipItem($item)) {
                continue;
            }

            $basePrice = ($this->taxConfig->priceIncludesTax($item->getStoreId()))
                ? $item->getBasePriceInclTax()
                : $item->getBasePrice();

            $qtyOrdered = $item->getQtyOrdered();
            $price = $basePrice - $item->getBaseDiscountAmount() / $qtyOrdered;

            $result[] = [
                'code' => $type,
                'name' =>  $item->getName(),
                'quantity' => (int)$qtyOrdered,
                'sku' => $item->getSku(),
                'tax_amount' => $this->formatPrice($item->getBaseTaxAmount()),
                'unit_price' => $this->formatPrice($price),
            ];
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
