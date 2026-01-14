<?php
/*
 * Copyright © 2021 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\Tax\Service;


interface TaxServiceInterface
{
    /**
     *
     * @param \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteTaxDetails
     * @param int|null $storeId
     *
     * @return mixed
     */
    public function getTaxForOrder(
        \Magento\Tax\Api\Data\QuoteDetailsInterface $quoteTaxDetails,
        $storeId = null
    );
}
