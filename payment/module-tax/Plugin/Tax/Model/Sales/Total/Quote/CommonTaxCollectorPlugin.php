<?php
/*
 * Copyright © 2021 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Tax\Plugin\Tax\Model\Sales\Total\Quote;

class CommonTaxCollectorPlugin
{

    /**
     * @var \Magento\Tax\Api\Data\QuoteDetailsItemExtensionInterfaceFactory
     */
    private $quoteDetailsItemExtensionFactory;

    public function __construct(\Magento\Tax\Api\Data\QuoteDetailsItemExtensionInterfaceFactory $quoteDetailsItemExtensionFactory)
    {
        $this->quoteDetailsItemExtensionFactory = $quoteDetailsItemExtensionFactory;
    }

    /**
     * This plugin method populates some necessary fields of the quote item details model
     *
     * @param \Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector $subject
     * @param \Magento\Tax\Api\Data\QuoteDetailsItemInterface $result
     *
     * @return \Magento\Tax\Api\Data\QuoteDetailsItemInterface
     */
    public function afterMapItem(
        \Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector $subject,
        $result,
        \Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory $itemDataObjectFactory,
        \Magento\Quote\Model\Quote\Item\AbstractItem $item,
        $priceIncludesTax,
        $useBaseCurrency,
        $parentCode = null
    ) {

        $taxQuoteDetailsExtensionAttributes = $this->getQuoteDetailsItemExtensionAttributes($result);

        $taxQuoteDetailsExtensionAttributes
            ->setProductName($item->getName())
            ->setSku($item->getSku())
            ->setProductType($item->getProductType())
            ->setPriceType($item->getProduct()->getPriceType())
        ;

        return $result;
    }

    /**
     * @param \Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector $subject
     * @param \Magento\Tax\Api\Data\QuoteDetailsItemInterface|null $result
     * @param \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     * @param bool $useBaseCurrency
     *
     * @return \Magento\Tax\Api\Data\QuoteDetailsItemInterface|null
     */
    public function afterGetShippingDataObject(
        \Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector $subject,
        $result,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total,
        $useBaseCurrency
    ) {

        if (!$result) {
            return $result;
        }

        $taxQuoteDetailsExtensionAttributes = $this->getQuoteDetailsItemExtensionAttributes($result)
            ->setProductName('Shipping')
            ->setSku(\Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector::ITEM_CODE_SHIPPING)
            ->setProductType(\Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector::ITEM_CODE_SHIPPING);

        return $result;
    }

    /**
     * @param \Magento\Tax\Api\Data\QuoteDetailsItemInterface $quoteDetailsItem
     *
     * @return \Magento\Tax\Api\Data\QuoteDetailsItemExtensionInterface
     */
    private function getQuoteDetailsItemExtensionAttributes(\Magento\Tax\Api\Data\QuoteDetailsItemInterface $quoteDetailsItem)
    {
        $extensionAttributes = $quoteDetailsItem->getExtensionAttributes();
        if (!$extensionAttributes) {
            $extensionAttributes = $this->quoteDetailsItemExtensionFactory->create();
            $quoteDetailsItem->setExtensionAttributes($extensionAttributes);
        }
        return $extensionAttributes;
    }


    public function afterMapAddress(
        \Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector $subject,
        \Magento\Customer\Api\Data\AddressInterface $result,
        \Magento\Quote\Model\Quote\Address $quoteAddress
    ) {
        $result->getRegion()
            ->setRegion($quoteAddress->getRegion())
            ->setRegionCode($quoteAddress->getRegionCode())
        ;

        $result
            ->setFirstname($quoteAddress->getFirstname())
            ->setLastname($quoteAddress->getLastname())
        ;

        return $result;
    }
}
