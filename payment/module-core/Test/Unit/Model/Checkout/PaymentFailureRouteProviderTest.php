<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Test\Unit\Model\Checkout;

use CyberSource\Core\Model\Checkout\PaymentFailureRouteProvider;
use PHPUnit\Framework\TestCase;

class PaymentFailureRouteProviderTest extends TestCase
{

    /**
     * @var \CyberSource\Core\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    /**
     * @var PaymentFailureRouteProvider
     */
    protected $subject;

    protected function setUp()
    {
        $this->configMock = $this->createMock(\CyberSource\Core\Model\Config::class);


    }


    public function testGetFailureRoutePathDefault()
    {

        $path = 'test/path';

        $this->subject = new PaymentFailureRouteProvider(
            $this->configMock,
            $path
        );

        static::assertEquals($path, $this->subject->getFailureRoutePath());

    }

    public function testGetFailureRoutePathCustom()
    {

        $path = 'test/path';

        $this->subject = new PaymentFailureRouteProvider(
            $this->configMock
        );

        $this->configMock->method('getOverrideErrorPageRoute')->willReturn($path);

        static::assertEquals($path, $this->subject->getFailureRoutePath());

    }


}
