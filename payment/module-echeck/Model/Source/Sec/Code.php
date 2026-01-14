<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Model\Source\Sec;

class Code implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 0, 'label' => __('Default')],
            ['value' => 'ARC', 'label' => __('ARC')],
            ['value' => 'CCD', 'label' => __('CCD')],
            ['value' => 'POP', 'label' => __('POP')],
            ['value' => 'PPD', 'label' => __('PPD')],
            ['value' => 'TEL', 'label' => __('TEL')],
            ['value' => 'WEB', 'label' => __('WEB')]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            0 => __('Default'),
            'arc' => __('ARC'),
            'ccd' => __('CCD'),
            'pop' => __('POP'),
            'ppd' => __('PPD'),
            'tel' => __('TEL'),
            'web' => __('WEB')
        ];
    }
}
