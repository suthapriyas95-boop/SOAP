<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Gateway\Request\Rest;

use CyberSource\Core\Gateway\Request\Rest\PartnerInformationBuilder;
use PHPUnit\Framework\TestCase;

class PartnerInformationBuilderTest extends TestCase
{


    /**
     * @var \CyberSource\Core\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    /**
     * @var \CyberSource\Core\Gateway\Helper\SubjectReader|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subjectReaderMock;

    /**
     * @var \CyberSource\Core\Gateway\Request\Rest\PartnerInformationBuilder
     */
    protected $builder;

    protected function setUp()
    {

        $this->configMock = $this->createMock(\CyberSource\Core\Model\Config::class);
        $this->subjectReaderMock = $this->createMock(\CyberSource\Core\Gateway\Helper\SubjectReader::class);

        $this->builder = new \CyberSource\Core\Gateway\Request\Rest\PartnerInformationBuilder(
            $this->subjectReaderMock,
            $this->configMock
        );
    }

    public function testBuild()
    {
        $subject = [];

        $did = 123123123;
        $this->configMock->method('getDeveloperId')->willReturn($did);

        static::assertEquals(
            [
                'clientReferenceInformation' => [
                    'partner' => [
                        'developerId' => $did,
                        'solutionId' => \CyberSource\Core\Helper\AbstractDataBuilder::PARTNER_SOLUTION_ID,
                    ]
                ]
            ],
            $this->builder->build($subject)
        );
    }
}
