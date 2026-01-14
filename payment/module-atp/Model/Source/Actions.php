<?php

namespace CyberSource\Atp\Model\Source;

use Magento\Framework\Option\ArrayInterface;

class Actions implements ArrayInterface
{
    const ACTION_ACCEPT = 'ACCEPT';
    const ACTION_CHALLENGE = 'CHALLENGE';
    const ACTION_REJECT = 'REJECT';

    public function toOptionArray()
    {
        return [
            [
                'value' => self::ACTION_ACCEPT,
                'label' => __('Accept')
            ],
            [
                'value' => self::ACTION_CHALLENGE,
                'label' => __('Challenge')
            ],
            [
                'value' => self::ACTION_REJECT,
                'label' => __('Reject')
            ]
        ];
    }
}
