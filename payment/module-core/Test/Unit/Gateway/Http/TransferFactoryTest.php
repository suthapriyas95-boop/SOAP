<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Test\Unit\Gateway\Http;

use CyberSource\Core\Gateway\Http\TransferFactory;
use PHPUnit\Framework\TestCase;

class TransferFactoryTest extends TestCase
{
    /** @var TransferFactory */
    private $transferFactory;

    /** @var \Magento\Payment\Gateway\Http\TransferBuilder | \PHPUnit_Framework_MockObject_MockObject */
    private $transferBuilderMock;

    protected function setUp()
    {
        $this->transferBuilderMock = $this->createMock(\Magento\Payment\Gateway\Http\TransferBuilder::class);
        $this->transferFactory = new TransferFactory(
            $this->transferBuilderMock
        );
    }

    public function testCreate()
    {
        $request = ['some'=>'param'];
        $result = 'asdasd';

        $this->transferBuilderMock->expects(static::once())->method('setBody')->with($request)->willReturnSelf();
        $this->transferBuilderMock->expects(static::once())->method('build')->willReturn($result);

        static::assertEquals($result, $this->transferFactory->create($request));
    }

}
