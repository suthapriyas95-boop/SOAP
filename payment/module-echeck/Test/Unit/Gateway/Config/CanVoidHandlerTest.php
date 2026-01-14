<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Test\Unit\Gateway\Config;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Http;

class CanVoidHandlerTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        Bootstrap::create(BP, $_SERVER)->createApplication(Http::class);
        $this->configMock = $this
            ->getMockBuilder(\CyberSource\ECheck\Gateway\Config\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->infoInterfaceMock = $this
            ->getMockBuilder(\Magento\Payment\Model\InfoInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subjectReaderMock = $this
            ->getMockBuilder(\Magento\Payment\Gateway\Helper\SubjectReader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentDataObjectMock = $this
            ->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $helper = new ObjectManager($this);
        $this->config = $helper->getObject(
            \CyberSource\ECheck\Gateway\Config\CanVoidHandler::class,
            [
                'subjectReader' => $this->subjectReaderMock
            ]
        );
    }
    
    public function testCapture()
    {
        $this->assertEquals(null, $this->config->handle(['payment' => $this->paymentDataObjectMock], 1));
    }
}
