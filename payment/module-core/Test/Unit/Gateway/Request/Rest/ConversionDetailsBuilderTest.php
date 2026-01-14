<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Gateway\Request\Rest;

use CyberSource\Core\Gateway\Request\Rest\ConversionDetailsBuilder;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class ConversionDetailsBuilderTest extends TestCase
{
    /**
     * @var \CyberSource\Core\Model\Config|MockObject
     */
    private $configMock;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime|MockObject
     */
    private $dateTimeMock;

    /**
     * @var \CyberSource\Core\Gateway\Request\Rest\ConversionDetailsBuilder
     */
    private $requestBuilder;

    protected function setUp()
    {

        $this->markTestSkipped('Replace with rest get command test');

        $this->configMock = $this->createMock(\CyberSource\Core\Model\Config::class);
        $this->dateTimeMock = $this->createMock(\Magento\Framework\Stdlib\DateTime\DateTime::class);

        $this->requestBuilder = new ConversionDetailsBuilder(
            $this->configMock,
            $this->dateTimeMock
        );
    }


    public function testBuild()
    {

        $subject = [];

        $currentTimeStamp = 1580342400;

        $expected = [
            'startTime' => '2020-01-30T00:00:00Z',
            'endTime' => '2020-01-31T00:00:00Z',
            'organizationId' => 'testtest',
        ];

        $this->dateTimeMock->method('gmtTimestamp')->willReturn($currentTimeStamp);

        $this->dateTimeMock
            ->expects(self::at(1))
            ->method('gmtDate')
            ->with('c', $currentTimeStamp - 24 * 3600)
            ->willReturn($expected['startTime']);

        $this->dateTimeMock
            ->expects(self::at(2))
            ->method('gmtDate')
            ->with('c', $currentTimeStamp)
            ->willReturn($expected['endTime'])
        ;

        $this->configMock->method('getMerchantId')->willReturn($expected['organizationId']);

        static::assertEquals($expected, $this->requestBuilder->build($subject));

    }


}
