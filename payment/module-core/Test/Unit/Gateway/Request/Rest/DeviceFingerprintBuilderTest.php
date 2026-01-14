<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Gateway\Request\Rest;

use CyberSource\Core\Gateway\Request\Rest\DeviceFingerprintBuilder;
use PHPUnit\Framework\TestCase;

class DeviceFingerprintBuilderTest extends TestCase
{

    /**
     * @var \Magento\Checkout\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkoutSessionMock;

    /**
     * @var \CyberSource\Core\Gateway\Request\Rest\DeviceFingerprintBuilder
     */
    protected $dfBuilder;

    protected function setUp()
    {
        $this->checkoutSessionMock = $this->createPartialMock(
            \Magento\Checkout\Model\Session::class,
            ['getFingerprintId']
        );

    }

    public function testBuild()
    {

        $sessionId = 'somesessionid';

        $this->checkoutSessionMock->method('getFingerprintId')->willReturn($sessionId);

        $this->dfBuilder = new DeviceFingerprintBuilder($this->checkoutSessionMock, false);

        static::assertEquals(
            [
                'deviceInformation' => [
                    'fingerprintSessionId' => $sessionId,
                ]
            ],
            $this->dfBuilder->build([])
        );
    }

    public function testBuildAdmin()
    {

        $sessionId = 'somesessionid';

        $this->checkoutSessionMock->method('getFingerprintId')->willReturn($sessionId);

        $this->dfBuilder = new DeviceFingerprintBuilder($this->checkoutSessionMock, true);

        static::assertEquals(
            [],
            $this->dfBuilder->build([])
        );
    }

}


