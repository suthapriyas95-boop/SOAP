<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\WeChatPay\Test\Unit\Gateway\Validator;

use CyberSource\WeChatPay\Gateway\Validator\ResponseCodeValidator;
use PHPUnit\Framework\TestCase;

class ResponseCodeValidatorTest extends TestCase
{
    /**
     * @var \Magento\Payment\Gateway\Validator\ResultInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultFactoryMock;

    /**
     * @var ResponseCodeValidator
     */
    protected $validator;

    /**
     * @var \Magento\Payment\Gateway\Validator\ResultInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultMock;

    protected function setUp()
    {
        $this->resultFactoryMock = $this->createMock(\Magento\Payment\Gateway\Validator\ResultInterfaceFactory::class);

        $this->resultMock = $this->createMock(\Magento\Payment\Gateway\Validator\ResultInterface::class);

        $this->validator = new ResponseCodeValidator($this->resultFactoryMock);
    }

    public function testValidate()
    {
        $subject = [
            'response' => [
                'reasonCode' => 100,
            ],
        ];

        $this->resultFactoryMock
            ->expects(static::once())
            ->method('create')
            ->with([
                'isValid' => true,
                'failsDescription' => [],
            ])
            ->willReturn($this->resultMock);

        static::assertEquals($this->resultMock, $this->validator->validate($subject));
    }

    public function testValidateFail()
    {
        $subject = [
            'response' => [
                'reasonCode' => 101,
            ],
        ];

        $this->resultFactoryMock
            ->expects(static::once())
            ->method('create')
            ->with([
                'isValid' => false,
                'failsDescription' => [__('Gateway rejected the transaction.')],
            ])
            ->willReturn($this->resultMock);

        static::assertEquals($this->resultMock, $this->validator->validate($subject));
    }
}
