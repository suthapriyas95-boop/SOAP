<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\WeChatPay\Model\Config\Source;

/**
 * Class CheckStatusResponse
 * @codeCoverageIgnore
 */
class CheckStatusResponse implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            ['value' => '', 'label' => __('Settled')],
            ['value' => 'TC200000', 'label' => __('Pending')],
            ['value' => 'TC200001', 'label' => __('Abandoned')],
            ['value' => 'TC200010', 'label' => __('Failed')],
        ];
    }

}
