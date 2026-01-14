<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Gateway\Request\Rest;

use PHPUnit\Framework\TestCase;

class SfsFileIdBuilderTest extends TestCase
{


    /**
     * @var SfsFileIdBuilder
     */
    protected $builder;

    protected function setUp()
    {
        $this->builder = new SfsFileIdBuilder();
    }

    public function testBuild()
    {

        $subject = ['fileId' => 123];

        static::assertEquals(
            ['url_params' => [$subject['fileId']]],
            $this->builder->build($subject)
        );
    }

    public function testBuildException()
    {

        $subject = ['fileId' => null];

        $this->expectException(\InvalidArgumentException::class);

        static::assertEquals(
            ['url_params' => [$subject['fileId']]],
            $this->builder->build($subject)
        );
    }
}
