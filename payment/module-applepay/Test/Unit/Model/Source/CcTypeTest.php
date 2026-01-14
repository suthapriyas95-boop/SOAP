<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */
namespace CyberSource\ApplePay\Test\Unit\Model\Source;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class CancelTest
 * @package CyberSource\BankTransfer\Test\Unit\Controller\Index
 * @codingStandardsIgnoreStart
 */
class CcTypeTest extends \PHPUnit\Framework\TestCase
{
    
    /**
     *
     * @var CyberSource\ApplePay\Block\Form 
     */
    private $unit;
    
    protected function setUp()
    {
        $this->paymentConfigMock = $this
            ->getMockBuilder(\Magento\Payment\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentConfigMock
            ->method('getCcTypes')
            ->will($this->returnValue([]));
        $helper = new ObjectManager($this);
        $this->unit = $helper->getObject(
            \CyberSource\ApplePay\Model\Source\CcType::class,
            ['_paymentConfig' => $this->paymentConfigMock]
        );
    }
    
    public function testGetAllowedTypes()
    {
        $this->assertEquals(
            ['VI', 'MC', 'AE', 'DI', 'JCB', 'MI', 'DN', 'CUP'], 
            $this->unit->getAllowedTypes()
        );
    }
    
    public function testGetCcTypeLabelMap()
    {

        $this->assertEquals(
            ['CUP' => 'China Union Pay'], 
            $this->unit->getCcTypeLabelMap()
        );
    }
    
    public function testToOptionArray()
    {
        $this->assertEquals(
            [['value' => 'CUP', 'label' => 'China Union Pay']],
            $this->unit->toOptionArray()
        );
    }
}