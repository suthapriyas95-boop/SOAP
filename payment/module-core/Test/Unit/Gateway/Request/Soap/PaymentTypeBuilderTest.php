<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Test\Unit\Gateway\Request\Soap;

use CyberSource\Core\Gateway\Request\Soap\PaymentTypeBuilder;
use PHPUnit\Framework\TestCase;

class PaymentTypeBuilderTest extends TestCase
{
    /**
     * @var PaymentTypeBuilder
     */
    protected $builder;

    protected function setUp()
    {
        $this->builder = new PaymentTypeBuilder('WQR');
    }

    public function testBuild()
    {
        $result = [
            'apPaymentType' => 'WQR',
        ];

        static::assertEquals($result, $this->builder->build([]));
    }
}
