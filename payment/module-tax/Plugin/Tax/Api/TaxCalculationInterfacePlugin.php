<?php
/*
 * Copyright © 2021 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\Tax\Plugin\Tax\Api;


class TaxCalculationInterfacePlugin
{

    /**
     * @var \CyberSource\Tax\Model\Config
     */
    private $config;

    /**
     * @var \CyberSource\Tax\Model\Calculator
     */
    private $calculator;

    public function __construct(
        \CyberSource\Tax\Model\Config $config,
        \CyberSource\Tax\Model\Calculator $calculator
    ) {
        $this->config = $config;
        $this->calculator = $calculator;
    }

    /**
     * @param \Magento\Tax\Api\TaxCalculationInterface $subject
     * @param callable $proceed
     * @param \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteDetails
     * @param null $storeId
     * @param bool $round
     *
     * @return \Magento\Tax\Api\Data\TaxDetailsInterface
     */
    public function aroundCalculateTax(
        \Magento\Tax\Api\TaxCalculationInterface $subject,
        callable $proceed,
        \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteDetails,
        $storeId = null,
        $round = true
    ) {
        if (!$this->config->isTaxEnabled($storeId)) {
            return $proceed($quoteDetails, $storeId, $round);
        }

        return $this->calculator->calculate($quoteDetails, $storeId, $round);
    }

}
