<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Gateway\Request\Rest;

use PHPUnit\Framework\TestCase;

class TimeIntervalBuilderTest extends TestCase
{

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dateTimeMock;

    /**
     * @var TimeIntervalBuilder
     */
    protected $builder;

    protected function setUp()
    {

        $this->dateTimeMock = $this->createMock(\Magento\Framework\Stdlib\DateTime\DateTime::class);

        $this->builder = new TimeIntervalBuilder($this->dateTimeMock);
    }

    public function testBuild()
    {

        $subject = [];

        $currentTimeStamp = 1000000;

        $this->dateTimeMock->method('gmtTimestamp')->willReturn($currentTimeStamp);

        $this->dateTimeMock->method('gmtDate')->willReturnCallback(function ($format, $value) {
            static::assertEquals('c', $format);
            return $value;
        });

        static::assertEquals(
            [
                'startTime' => $currentTimeStamp - 24 * 3600,
                'endTime' => $currentTimeStamp,
            ],
            $this->builder->build($subject)
        );

    }

    public function testBuildFromSubject()
    {

        $currentTimeStamp = 1000000;

        $subject = [
            'startTime' => $currentTimeStamp,
            'endTime' => $currentTimeStamp,
        ];

        static::assertEquals(
            [
                'startTime' => $currentTimeStamp,
                'endTime' => $currentTimeStamp,
            ],
            $this->builder->build($subject)
        );

    }

    public function testBuildWithInterval()
    {

        $subject = ['interval' => 1000];

        $currentTimeStamp = 1000000;

        $this->dateTimeMock->method('gmtTimestamp')->willReturn($currentTimeStamp);

        $this->dateTimeMock->method('gmtDate')->willReturnCallback(function ($format, $value) {
            static::assertEquals('c', $format);
            return $value;
        });

        static::assertEquals(
            [
                'startTime' => $currentTimeStamp - $subject['interval'],
                'endTime' => $currentTimeStamp,
            ],
            $this->builder->build($subject)
        );

    }

}
