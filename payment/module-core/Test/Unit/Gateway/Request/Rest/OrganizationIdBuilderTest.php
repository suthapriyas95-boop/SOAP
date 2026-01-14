<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Gateway\Request\Rest;

use CyberSource\Core\Gateway\Request\Rest\OrganizationIdBuilder;
use PHPUnit\Framework\TestCase;

class OrganizationIdBuilderTest extends TestCase
{


    /**
     * @var \CyberSource\Core\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    /**
     * @var \CyberSource\Core\Gateway\Request\Rest\OrganizationIdBuilder
     */
    protected $builder;

    protected function setUp()
    {

        $this->configMock = $this->createMock(\CyberSource\Core\Model\Config::class);

        $this->builder = new \CyberSource\Core\Gateway\Request\Rest\OrganizationIdBuilder(
            $this->configMock
        );
    }

    public function testBuild()
    {

        $storeId = 5;
        $mid = 'testtest';

        $this->configMock->method('getMerchantId')->with($storeId)->willReturn($mid);

        static::assertEquals(['organizationId' => $mid], $this->builder->build(['store_id' => $storeId]));
    }
}
