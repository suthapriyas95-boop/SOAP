<?php declare(strict_types = 1);

namespace CyberSource\SecureAcceptance\Gateway\Request\Soap;

use PHPUnit\Framework\TestCase;

class ServiceRequestTest extends TestCase
{
    /** @var ServiceRequest */
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

        $this->serviceRequest = new ServiceRequest(
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
