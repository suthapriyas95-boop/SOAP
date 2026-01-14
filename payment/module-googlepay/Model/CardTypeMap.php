<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\GooglePay\Model;


class CardTypeMap
{

    const TYPE_MAP = [
        'AE' => 'AMEX',
        'DI' => 'DISCOVER',
        'JCB' => 'JCB',
        'MC' => 'MASTERCARD',
        'VI' => 'VISA',
    ];

    public function toMagentoType($cardType)
    {
        $magentoType = array_search($cardType, static::TYPE_MAP);

        if ($magentoType === false) {
            throw new \InvalidArgumentException('No matching card type found');
        }

        return $magentoType;
    }

    public function toGooglePayType($cardType)
    {
        if (!isset(static::TYPE_MAP[$cardType])) {
            throw new \InvalidArgumentException('No matching card type found');
        }

        return static::TYPE_MAP[$cardType];
    }

}
