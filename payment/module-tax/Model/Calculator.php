<?php
/*
 * Copyright © 2021 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\Tax\Model;


/**
 * Class Calculator
 */
class Calculator
{


    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var \Magento\Tax\Api\Data\TaxDetailsInterfaceFactory
     */
    private $taxDetailsFactory;

    /**
     * @var \Magento\Tax\Api\Data\TaxDetailsItemInterfaceFactory
     */
    private $taxDetailsItemFactory;

    /**
     * @var \Magento\Tax\Api\Data\AppliedTaxInterfaceFactory
     */
    private $appliedTaxFactory;

    /**
     * @var \Magento\Tax\Api\Data\AppliedTaxRateInterfaceFactory
     */
    private $appliedTaxRateFactory;

    /**
     * @var \CyberSource\Tax\Service\CyberSourceSoapAPI
     */
    private $taxService;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var FinalTaxCollectionSemaphore
     */
    private $finalTaxCollectionSemaphore;

    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        Config $config,
        FinalTaxCollectionSemaphore $finalTaxCollectionSemaphore,
        \Magento\Tax\Api\Data\TaxDetailsInterfaceFactory $taxDetailsFactory,
        \Magento\Tax\Api\Data\TaxDetailsItemInterfaceFactory $taxDetailsItemFactory,
        \Magento\Tax\Api\Data\AppliedTaxInterfaceFactory $appliedTaxFactory,
        \Magento\Tax\Api\Data\AppliedTaxRateInterfaceFactory $appliedTaxRateFactory,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \CyberSource\Tax\Service\TaxServiceInterface $taxService
    ) {

        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->finalTaxCollectionSemaphore = $finalTaxCollectionSemaphore;
        $this->taxDetailsFactory = $taxDetailsFactory;
        $this->taxDetailsItemFactory = $taxDetailsItemFactory;
        $this->appliedTaxFactory = $appliedTaxFactory;
        $this->appliedTaxRateFactory = $appliedTaxRateFactory;
        $this->priceCurrency = $priceCurrency;
        $this->taxService = $taxService;
    }

    /**
     * @param \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteDetails
     * @param null $storeId
     * @param bool $round
     *
     * @return \Magento\Tax\Api\Data\TaxDetailsInterface
     */
    public function calculate(
        \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteDetails,
        $storeId = null,
        $round = true
    ) {

        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getStoreId();
        }

        // empty quote
        if (!$quoteDetails->getShippingAddress() || !$quoteDetails->getItems()) {
            return $this->getEmptyTaxDetails($quoteDetails);
        }

        if (
            !$this->isTaxApplicable($quoteDetails->getShippingAddress()->getCountryId())
            || !$this->isTaxApplicableForCustomerTaxClass($quoteDetails->getCustomerTaxClassKey()->getValue())
            || !$this->finalTaxCollectionSemaphore->isFinalTax()
        ) {
            return $this->getEmptyTaxDetails($quoteDetails);
        }

        $taxResponse = $this->taxService->getTaxForOrder($quoteDetails, $storeId);

        if (!$taxResponse) {
            return $this->getEmptyTaxDetails($quoteDetails);
        }

        return $this->prepareTax($quoteDetails, $taxResponse);

    }

    /**
     * @param $quoteDetails
     * @param $response
     *
     * @return \Magento\Tax\Api\Data\TaxDetailsInterface
     */
    private function prepareTax($quoteDetails, $response)
    {
        $taxReply = $this->parseTaxReply($response);

        if (!$taxReply || $taxReply['totalTaxAmount'] == 0) {
            return $this->getEmptyTaxDetails($quoteDetails);
        }

        $details = $this->taxDetailsFactory->create();

        $subtotal = 0;
        $items = [];

        foreach ($quoteDetails->getItems() as $key => $quoteItem) {

            $taxReplyItem = $taxReply['item'][$key] ?? null;

            $taxItem = $this->getTaxDetailsForItem($quoteItem, $taxReplyItem);
            $subtotal += $taxItem->getRowTotal();
            $items[$taxItem->getCode()] = $taxItem;
        }

        $details->setSubtotal($subtotal)
            ->setTaxAmount($taxReply['totalTaxAmount'] ?? 0)
            ->setDiscountTaxCompensationAmount(0)
            ->setItems($items)
        ;

        return $details;
    }

    /**
     * @param array $response
     *
     * @return array
     */
    private function parseTaxReply($response)
    {
        $taxReply = $response['taxReply'] ?? [];

        if (empty($taxReply)) {
            return $taxReply;
        }

        if (isset($taxReply['item']['id'])) {
            // a single item is returned, wrapping it into array
            $taxReply['item'] = [$taxReply['item']];
        }

        return $taxReply;
    }

    /**
     * @param \Magento\Tax\Api\Data\QuoteDetailsItemInterface $taxQuoteDetailsItem
     * @param null $taxReplyItem
     *
     * @return \Magento\Tax\Api\Data\TaxDetailsItemInterface
     */
    private function getTaxDetailsForItem(
        \Magento\Tax\Api\Data\QuoteDetailsItemInterface $taxQuoteDetailsItem,
        $taxReplyItem = null
    ) {
        if (!$taxReplyItem) {
            return $this->getEmptyDetailsTaxItem($taxQuoteDetailsItem);
        }

        $taxDetailsItem = $this->taxDetailsItemFactory->create();

        $rowTotal = (
                $taxQuoteDetailsItem->getUnitPrice()
                * $taxQuoteDetailsItem->getQuantity()
            );

        $rowTax = (float)($taxReplyItem['totalTaxAmount'] ?? 0);
        $taxableAmount = (float)($taxReplyItem['taxableAmount'] ?? 0);
        $taxRate = $taxableAmount ? $rowTax / $taxableAmount : 0;
        $qty = $taxQuoteDetailsItem->getQuantity();

        $taxDetailsItem
            ->setCode($taxQuoteDetailsItem->getCode())
            ->setType($taxQuoteDetailsItem->getType())
            ->setRowTax($rowTax)
            ->setPrice($taxQuoteDetailsItem->getUnitPrice())
            ->setPriceInclTax($taxQuoteDetailsItem->getUnitPrice() + $rowTax / $qty)
            ->setRowTotal($rowTotal)
            ->setRowTotalInclTax($rowTotal + $rowTax)
            ->setDiscountTaxCompensationAmount(0)
            ->setDiscountAmount($taxQuoteDetailsItem->getDiscountAmount())
            ->setAssociatedItemCode($taxQuoteDetailsItem->getAssociatedItemCode())
            ->setTaxPercent(100 * $taxRate)
            ->setAppliedTaxes(
                $this->getAppliedRatesForItem($taxQuoteDetailsItem, $taxReplyItem)
            );

        return $taxDetailsItem;
    }

    /**
     * @param \Magento\Tax\Api\Data\QuoteDetailsItemInterface $quoteTaxDetailsItem
     * @param array|null $taxReplyItem
     *
     * @return \Magento\Tax\Api\Data\AppliedTaxInterface[]
     */
    private function getAppliedRatesForItem(
        \Magento\Tax\Api\Data\QuoteDetailsItemInterface $quoteTaxDetailsItem,
        $taxReplyItem = null
    ) {
        $jurisdictions = $taxReplyItem['jurisdiction'] ?? [];

        $appliedTaxes = [];
        foreach ($jurisdictions as $jurisdiction) {

            $taxAmount = (float)($jurisdiction['taxAmount'] ?? 0);
            if ($taxAmount == 0) {
                continue;
            }

            $rates = [];

            $title = $jurisdiction['taxName'] ?? 'Tax';
            $percent = 100 * $jurisdiction['rate'];
            $rate = $this->appliedTaxRateFactory->create()
                ->setTitle($title)
                ->setCode($title)
                ->setPercent($percent);
            $rates[$rate->getCode()] = $rate;

            $appliedTax = $this->appliedTaxFactory->create()
                ->setRates($rates)
                ->setTaxRateKey($title)
                ->setPercent($percent)
                ->setAmount($taxAmount)
                ;

            $appliedTaxes[$appliedTax->getTaxRateKey()] = $appliedTax;
        }

        return $appliedTaxes;
    }

    private function isTaxApplicable($countryId)
    {
        $isEnabled = $this->config->isTaxEnabled();
        $isCountryApplicable = in_array(
            $countryId,
            explode(',', $this->config->getTaxCountries() ?? '')
        );

        return ($isEnabled && $isCountryApplicable);
    }

    private function getEmptyTaxDetails(\Magento\Tax\Api\Data\QuoteDetailsInterface $quoteDetails)
    {

        $details = $this->taxDetailsFactory->create();

        $subtotal = 0;
        $items = [];

        foreach ($quoteDetails->getItems() as $quoteItem) {
            $taxItem = $this->getEmptyDetailsTaxItem($quoteItem);
            $subtotal += $taxItem->getRowTotal();
            $items[$taxItem->getCode()] = $taxItem;
        }

        $details->setSubtotal($subtotal)
            ->setTaxAmount(0)
            ->setDiscountTaxCompensationAmount(0)
            ->setAppliedTaxes([])
            ->setItems($items);

        return $details;
    }

    private function getEmptyDetailsTaxItem(\Magento\Tax\Api\Data\QuoteDetailsItemInterface $quoteDetailsItem)
    {
        $taxDetailsItem = $this->taxDetailsItemFactory->create();

        $rowTotal = (
                $quoteDetailsItem->getUnitPrice()
                * $quoteDetailsItem->getQuantity()
            );

        $taxDetailsItem
            ->setCode($quoteDetailsItem->getCode())
            ->setType($quoteDetailsItem->getType())
            ->setRowTax(0)
            ->setPrice($quoteDetailsItem->getUnitPrice())
            ->setPriceInclTax($quoteDetailsItem->getUnitPrice())
            ->setRowTotal($rowTotal)
            ->setRowTotalInclTax($rowTotal)
            ->setDiscountTaxCompensationAmount(0)
            ->setDiscountAmount($quoteDetailsItem->getDiscountAmount())
            ->setAssociatedItemCode($quoteDetailsItem->getAssociatedItemCode())
            ->setTaxPercent(0)
            ->setAppliedTaxes([]);

        return $taxDetailsItem;
    }

    private function isTaxApplicableForCustomerTaxClass($customerTaxClassId)
    {
        $isTaxClassExcluded = in_array(
            $customerTaxClassId,
            explode(',', $this->config->getCustomerTaxClassExclude() ?? '')
        );

        return !$isTaxClassExcluded;
    }

}
