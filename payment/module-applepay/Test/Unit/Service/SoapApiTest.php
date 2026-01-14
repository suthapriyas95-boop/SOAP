<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */
namespace CyberSource\ApplePay\Test\Service;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class CancelTest
 * @package CyberSource\BankTransfer\Test\Unit\Controller\Index
 * @codingStandardsIgnoreStart
 */
class SoapApiTest extends \PHPUnit\Framework\TestCase
{
    
    /**
     *
     * @var CyberSource\ApplePay\Block\Form 
     */
    private $unit;
    
    protected function setUp()
    {
        $this->loggerMock = $this
            ->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transferMock = $this
            ->getMockBuilder(\Magento\Payment\Gateway\Http\TransferBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $helper = new ObjectManager($this);
        $this->unit = $helper->getObject(
            \CyberSource\ApplePay\Service\CyberSourceSoap::class,
            [
                'transferBuilder' => $this->transferMock,
                'logger' => $this->loggerMock,
            ]
        );
    }
    
    public function testPlaceRequest()
    {
        $this->transferMock
            ->method('setBody')
            ->will($this->returnValue($this->transferMock));
        $this->transferMock
            ->method('setMethod')
            ->will($this->returnValue($this->transferMock));
        $requestBody = new \stdClass;
        $this->assertEquals(
            null, 
            $this->unit->request($requestBody)
        );
    }
}