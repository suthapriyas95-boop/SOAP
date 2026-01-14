<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\WeChatPay\Test\Unit\Gateway\Validator;

use CyberSource\WeChatPay\Gateway\Validator\CurrencyValidator;
use PHPUnit\Framework\TestCase;

class CurrencyValidatorTest extends TestCase
{
    /**
     * @var \Magento\Payment\Gateway\Validator\ResultInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultFactoryMock;

    /**
     * @var \CyberSource\WeChatPay\Gateway\Config\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    /**
     * @var CurrencyValidator
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
        $this->configMock = $this->createMock(\CyberSource\WeChatPay\Gateway\Config\Config::class);

        $this->validator = new CurrencyValidator(
            $this->resultFactoryMock,
            $this->configMock
        );
    }

    public function testValidate()
    {
        $subject = ['currency' => 'USD'];

        $this->configMock->method('getValue')->with('currency')->willReturn('USD,EUR');

        $this->resultFactoryMock
            ->method('create')
            ->with([
                'isValid' => true,
                'failsDescription' => []
            ])
            ->willReturn($this->resultMock);

        static::assertEquals($this->resultMock, $this->validator->validate($subject));
    }

    public function testValidateFail()
    {
        $subject = ['currency' => 'USD'];

        $this->configMock->method('getValue')->with('currency')->willReturn('EUR,CAD');

        $this->resultFactoryMock
            ->method('create')
            ->with([
                'isValid' => false,
                'failsDescription' => [__('The currency is not supported by WeChat Pay.')]
            ])
            ->willReturn($this->resultMock);

        static::assertEquals($this->resultMock, $this->validator->validate($subject));
    }

    public function testValidateFailEmpty()
    {
        $subject = ['currency' => 'USD'];

        $this->configMock->method('getValue')->with('currency')->willReturn('');

        $this->resultFactoryMock
            ->method('create')
            ->with([
                'isValid' => false,
                'failsDescription' => [__('The currency is not supported by WeChat Pay.')]
            ])
            ->willReturn($this->resultMock);

        static::assertEquals($this->resultMock, $this->validator->validate($subject));
    }
}
