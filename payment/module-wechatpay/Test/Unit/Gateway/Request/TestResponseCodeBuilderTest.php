<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\WeChatPay\Test\Unit\Gateway\Request;

use CyberSource\WeChatPay\Gateway\Request\TestResponseCodeBuilder;
use PHPUnit\Framework\TestCase;

class TestResponseCodeBuilderTest extends TestCase
{
    /**
     * @var \CyberSource\WeChatPay\Gateway\Config\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    /**
     * @var TestResponseCodeBuilder
     */
    protected $builder;

    protected function setUp()
    {
        $this->configMock = $this->createMock(\CyberSource\WeChatPay\Gateway\Config\Config::class);

        $this->builder = new TestResponseCodeBuilder(
            $this->configMock
        );
    }

    /**
     * @param $testMode
     * @param $configuredValue
     * @param $expected
     * @dataProvider dataProviderTestBuild
     */
    public function testBuild($testMode, $configuredValue, $expected)
    {
        $subject = [];

        $this->configMock->method('isTestMode')->willReturn($testMode);
        $this->configMock->method('getTestStatusResponseCode')->willReturn($configuredValue);

        static::assertEquals($expected, $this->builder->build($subject));
    }

    public function dataProviderTestBuild()
    {
        return [
            [
                'testMode' => false,
                'configuredValue' => 'somevalue',
                'expected' => [],
            ],
            [
                'testMode' => true,
                'configuredValue' => '',
                'expected' => [],
            ],
            [
                'testMode' => true,
                'configuredValue' => 'TC0000000',
                'expected' => [
                    'reconciliationID' => 'TC0000000',
                ],
            ],
        ];
    }
}
