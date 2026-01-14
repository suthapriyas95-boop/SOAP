<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Test\Unit\Model\Logger;

use CyberSource\Core\Model\Logger\Censor;
use PHPUnit\Framework\TestCase;

class CensorTest extends TestCase
{

    /**
     * @var Censor
     */
    protected $censor;

    protected function setUp()
    {
        $this->censor = new Censor();
    }

    /**
     * @param $input
     * @param $filteredInput
     * @dataProvider dataProviderTestCensor
     */
    public function testCensor($input, $filteredInput)
    {
        static::assertSame($filteredInput, $this->censor->censor($input));
    }

    public function dataProviderTestCensor()
    {
        return [
            ['input' => 'asdas', 'filteredInput' => 'asdas'],
            ['input' => ['someInt' => 1111], ['someInt' => '1111']],
            ['input' => ['someInt' => 4111111111111111], ['someInt' => 'xxxx']],
            ['input' => ['someString' => '4111111111111111'], ['someString' => 'xxxx']],
        ];
    }
}
