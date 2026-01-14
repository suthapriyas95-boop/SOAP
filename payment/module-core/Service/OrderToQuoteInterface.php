<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\Core\Service;


interface OrderToQuoteInterface
{

    /**
     * @param $orderId
     * @param null $quote
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function convertOrderToQuote($orderId, $quote = null);

}
