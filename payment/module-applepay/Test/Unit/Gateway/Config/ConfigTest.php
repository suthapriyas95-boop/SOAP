<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */
namespace CyberSource\ApplePay\Test\Unit\Gateway\Config;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class CancelTest
 * @package CyberSource\BankTransfer\Test\Unit\Controller\Index
 * @codingStandardsIgnoreStart
 */
class ConfigTest extends \PHPUnit\Framework\TestCase
{
    
    /**
     *
     * @var CyberSource\ApplePay\Block\Form 
     */
    private $unit;
    
    protected $cardTypes;
    
    protected function setUp()
    {
        $this->cardTypes = [
            'AE' => 'American Express',
            'VI' => 'Visa',
            'MC' => 'MasterCard',
            'DI' => 'Discover',
            'JCB' => 'JCB'
        ];
        $helper = new ObjectManager($this);
        $this->unit = $this->getMockBuilder(\CyberSource\ApplePay\Gateway\Config\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        
    }
    
    public function testIsActive()
    {
        $this->assertEquals(null, $this->unit->isActive());
    }
    
    public function testGetTitle()
    {
        $this->assertEquals(null, $this->unit->getTitle());
    }
    
    public function testIsCvvEnabled()
    {
        $this->unit->method('isCvvEnabled')
            ->willReturn(true);
        $this->assertEquals(true, $this->unit->isCvvEnabled());
    }
}