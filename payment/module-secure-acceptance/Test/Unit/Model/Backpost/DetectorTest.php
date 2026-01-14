<?php
/*
 * Copyright © 2021 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Test\Unit\Model\Backpost;

use CyberSource\SecureAcceptance\Model\Backpost\Detector;
use PHPUnit\Framework\TestCase;

class DetectorTest extends TestCase
{

    /**
     * @var \Magento\Framework\HTTP\Header|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $headerMock;

    /**
     * @var Detector
     */
    protected $detector;

    protected function setUp()
    {

        $this->headerMock = $this->createMock(\Magento\Framework\HTTP\Header::class);

        $this->detector = new \CyberSource\SecureAcceptance\Model\Backpost\Detector(
            $this->headerMock
        );
    }

    /**
     * @param $uaString
     * @param $isBackpost
     * @dataProvider dataProviderTestIsBackpost
     */
    public function testIsBackpost($uaString, $isBackpost)
    {
        $this->headerMock->method('getHttpUserAgent')->willReturn($uaString);
        static::assertEquals($isBackpost, $this->detector->isBackpost(), 'Failed for UA:' . $uaString);
    }

    public function dataProviderTestIsBackpost()
    {
        return [
            [
                'uastring' => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:47.0) Gecko/20100101 Firefox/47.0',
                'isBackpost' => false,
            ],
            [
                'uastring' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36',
                'isBackpost' => false,
            ],
            [
                'uastring' => 'CyberSource Secure Acceptance Back Post Agent',
                'isBackpost' => true,
            ],
            [
                'uastring' => 'DEADBEEF Secure Acceptance Back Post Agent',
                'isBackpost' => true,
            ],
        ];
    }

}
