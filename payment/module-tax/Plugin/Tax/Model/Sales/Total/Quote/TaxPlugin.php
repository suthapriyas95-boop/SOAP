<?php
/*
 * Copyright © 2021 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Tax\Plugin\Tax\Model\Sales\Total\Quote;

class TaxPlugin
{
    /**
     * @var \CyberSource\Tax\Model\FinalTaxCollectionSemaphore
     */
    private $finalTaxCollectionSemaphore;

    public function __construct(
        \CyberSource\Tax\Model\FinalTaxCollectionSemaphore $finalTaxCollectionSemaphore
    ) {
        $this->finalTaxCollectionSemaphore = $finalTaxCollectionSemaphore;
    }

    public function beforeCollect()
    {
        $this->finalTaxCollectionSemaphore->setIsFinalTax(true);
    }

    public function afterCollect()
    {
        $this->finalTaxCollectionSemaphore->setIsFinalTax(false);
    }

}
