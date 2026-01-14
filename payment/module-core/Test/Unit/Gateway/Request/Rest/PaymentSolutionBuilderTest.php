<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Gateway\Request\Rest;

use CyberSource\Core\Gateway\Request\Rest\PaymentSolutionBuilder;
use PHPUnit\Framework\TestCase;

class PaymentSolutionBuilderTest extends TestCase
{

    /**
     * @var \CyberSource\Core\Gateway\Request\Rest\PaymentSolutionBuilder
     */
    protected $builder;

    protected function setUp()
    {


    }

    public function testBuild()
    {

        $solutionId = '232323';

        $this->builder = new PaymentSolutionBuilder($solutionId);

        static::assertEquals(
            [
                'processingInformation' => [
                    'paymentSolution' => $solutionId,
                ]
            ],
            $this->builder->build([])
        );

    }

    public function testBuildEmpty()
    {

        $solutionId = null;

        $this->builder = new PaymentSolutionBuilder($solutionId);

        static::assertEquals(
            [],
            $this->builder->build([])
        );

    }
}
