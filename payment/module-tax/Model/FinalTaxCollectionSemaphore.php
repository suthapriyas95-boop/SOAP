<?php
/*
 * Copyright © 2021 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\Tax\Model;


class FinalTaxCollectionSemaphore
{

    /**
     * @var bool
     */
    private $isFinalTax = false;

    /**
     * @return bool
     */
    public function isFinalTax(): bool
    {
        return $this->isFinalTax;
    }

    /**
     * @param bool $isFinalTax
     */
    public function setIsFinalTax(bool $isFinalTax)
    {
        $this->isFinalTax = $isFinalTax;
    }


}
