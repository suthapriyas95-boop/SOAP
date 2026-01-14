<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Gateway\Request\Rest;

use CyberSource\Core\Gateway\Request\Rest\CaptureBuilder;
use PHPUnit\Framework\TestCase;

class CaptureBuilderTest extends TestCase
{

    /**
     * @var \CyberSource\Core\Gateway\Request\Rest\CaptureBuilder
     */
    protected $captureBuilder;

    protected function setUp()
    {

        $this->captureBuilder = new CaptureBuilder();

    }

    public function testBuild()
    {
        static::assertEquals(
            [
                'processingInformation' => [
                    'capture' => 'true',
                ]
            ],
            $this->captureBuilder->build([]));
    }
}
