<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Test\Unit\Gateway\Request\Soap;

use CyberSource\Core\Gateway\Request\Soap\ServiceRunBuilder;
use PHPUnit\Framework\TestCase;

class ServiceRunBuilderTest extends TestCase
{
    /** @var ServiceRunBuilder */
    private $serviceRequest;

    /** @var \Magento\Framework\ObjectManager\TMapFactory | \PHPUnit_Framework_MockObject_MockObject */
    private $tmapFactoryMock;

    protected function setUp()
    {
        $this->tmapFactoryMock = $this->createMock(\Magento\Framework\ObjectManager\TMapFactory::class);
    }

    public function testBuild()
    {
        $serviceName = 'ccAuthService';

        $builderStrings = ['oneBuilder', 'anotherBuilder'];

        /** @var \Magento\Payment\Gateway\Request\BuilderInterface[]|\PHPUnit_Framework_MockObject_MockObject[] $builders */
        $builders = [
            $this->createMock(\Magento\Payment\Gateway\Request\BuilderInterface::class),
            $this->createMock(\Magento\Payment\Gateway\Request\BuilderInterface::class),
        ];

        $this->tmapFactoryMock
            ->method('create')
            ->with([
                'array' => $builderStrings,
                'type' => \Magento\Payment\Gateway\Request\BuilderInterface::class
            ])
            ->willReturn($builders);

        $this->serviceRequest = new ServiceRunBuilder(
            $this->tmapFactoryMock,
            $serviceName,
            $builderStrings
        );

        $subject = ['some' => 'value'];

        $builders[0]->method('build')->with($subject)->willReturn(['builder0' => 'result0']);
        $builders[1]->method('build')->with($subject)->willReturn(['builder1' => 'result1']);

        $this->assertEquals(
            [
                $serviceName => [
                    'run' => 'true',
                    'builder0' => 'result0',
                    'builder1' => 'result1',
                ]
            ],
            $this->serviceRequest->build($subject)
        );
    }
}
