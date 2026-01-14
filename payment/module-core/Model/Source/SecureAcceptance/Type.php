<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Model\Source\SecureAcceptance;

/**
 * Class Type
 * @package CyberSource\Core\Model\Source\SecureAcceptance
 * @codeCoverageIgnore
 */
class Type implements \Magento\Framework\Option\ArrayInterface
{

    const SA_WEB = 'web';
    const SA_SOP = 'silent';
    const SA_FLEX_MICROFORM = 'flex';

    /**
     * Generate list of email templates
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::SA_WEB,
                'label' => __('Hosted Checkout (formerly Web/Mobile)')
            ],
            [
                'value' => self::SA_SOP,
                'label' => __('Checkout API (formerly Silent Order Post/SOP)')
            ],
            [
                'value' => self::SA_FLEX_MICROFORM,
                'label' => __('Flex Microform')
            ],
        ];
    }
}
