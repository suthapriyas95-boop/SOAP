<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\BankTransfer\Model\Config;

use Magento\Framework\Option\ArrayInterface;

class PaymentMethod implements ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'ideal',
                'label' => __('iDeal'),
            ],
            [
                'value' => 'sofort',
                'label' => __('Sofort')
            ],
            [
                'value' => 'bancontact',
                'label' => __('Bancontact')
            ]
        ];
    }
}
