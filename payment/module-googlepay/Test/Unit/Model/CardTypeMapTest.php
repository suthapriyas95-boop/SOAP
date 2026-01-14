<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\GooglePay\Model;

use PHPUnit\Framework\TestCase;

class CardTypeMapTest extends TestCase
{

    /**
     * @var CardTypeMap
     */
    protected $cardTypeMap;

    protected function setUp()
    {
        $this->cardTypeMap = new CardTypeMap();
    }

    /**
     *   'AE' => 'AMEX',
    'DI' => 'DISCOVER',
    'JCB' => 'JCB',
    'MC' => 'MASTERCARD',
    'VI' => 'VISA',
     *
     */

    /**
     * @param $type
     * @param $magentoType
     * @dataProvider dataProviderTestType
     */
    public function testToMagentoType($type, $magentoType)
    {
        static::assertEquals($magentoType, $this->cardTypeMap->toMagentoType($type));
    }

    /**
     * @param $type
     * @param $magentoType
     */
    public function testToMagentoTypeUnknown()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->cardTypeMap->toMagentoType('MIR');
    }

    public function dataProviderTestType()
    {
        return [
            [
                'type' => 'AMEX',
                'magentoType' => 'AE',
            ],
            [
                'type' => 'DISCOVER',
                'magentoType' => 'DI',
            ],
            [
                'type' => 'JCB',
                'magentoType' => 'JCB',
            ],
            [
                'type' => 'MASTERCARD',
                'magentoType' => 'MC',
            ],
            [
                'type' => 'VISA',
                'magentoType' => 'VI',
            ],
        ];
    }

    /**
     * @param $type
     * @param $magentoType
     * @dataProvider dataProviderTestType
     */
    public function testToGooglePayType($type, $magentoType)
    {
        static::assertEquals($type, $this->cardTypeMap->toGooglePayType($magentoType));
    }

    /**
     * @param $type
     * @param $magentoType
     */
    public function testToGooglePayUnknown()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->cardTypeMap->toGooglePayType('MI');
    }
}
