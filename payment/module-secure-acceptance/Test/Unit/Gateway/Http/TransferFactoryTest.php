<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Magento\SamplePaymentProvider\Test\Unit\Gateway\Http;

use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferInterface;
use CyberSource\SecureAcceptance\Gateway\Http\TransferFactory;
use CyberSource\SecureAcceptance\Gateway\Request\MockDataRequest;

/**
 * Class TransferFactoryTest
 * @package Magento\SamplePaymentProvider\Test\Unit\Gateway\Http
 * @test
 */
class TransferFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $request = [
            'parameter' => 'value',
            'uri' => 'http://example.org/',
            'method' => 'POST'
//            MockDataRequest::FORCE_RESULT => 1
        ];

        $transferBuilder = $this->getMockBuilder(TransferBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $transferObject = $this->createMock(TransferInterface::class);

        $expectedArray = $request;
        unset($expectedArray['uri']);
        unset($expectedArray['method']);

        $transferBuilder->expects(static::once())
            ->method('setUri')
            ->with($request['uri'])
            ->willReturnSelf();
        $transferBuilder->expects(static::once())
            ->method('setBody')
            ->with(json_encode($expectedArray))
            ->willReturnSelf();
        $transferBuilder->expects(static::once())
            ->method('setMethod')
            ->with('POST')
            ->willReturnSelf();
//        $transferBuilder->expects(static::once())
//            ->method('setHeaders')
//            ->with(
//                [
//                    'force_result' => 1
//                ]
//            )
//            ->willReturnSelf();

        $transferBuilder->expects(static::once())
            ->method('build')
            ->willReturn($transferObject);

        $transferFactory = new TransferFactory($transferBuilder);

        static::assertSame(
            $transferObject,
            $transferFactory->create($request)
        );
    }
}
