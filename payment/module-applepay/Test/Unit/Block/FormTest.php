<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */
namespace CyberSource\ApplePay\Test\Unit\Block;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class CancelTest
 * @package CyberSource\BankTransfer\Test\Unit\Controller\Index
 * @codingStandardsIgnoreStart
 */
class FormTest extends \PHPUnit\Framework\TestCase
{
    
    /**
     *
     * @var CyberSource\ApplePay\Block\Form 
     */
    private $block;
    
    protected function setUp()
    {
        $this->contextMock = $this
            ->getMockBuilder(\Magento\Framework\View\Element\Template\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sessionQuoteMock = $this
            ->getMockBuilder(\Magento\Backend\Model\Session\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteMock = $this
            ->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->gatewayConfigMock = $this
            ->getMockBuilder(\CyberSource\ApplePay\Gateway\Config\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configMock = $this
            ->getMockBuilder(\Magento\Payment\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->ccTypeMock = $this
            ->getMockBuilder(\CyberSource\ApplePay\Model\Source\CcType::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->addressMock = $this
            ->getMockBuilder(\Magento\Quote\Api\Data\AddressInterface::class)
            ->getMock();
        $helper = new ObjectManager($this);
        $this->block = $this->getMockBuilder(\CyberSource\ApplePay\Block\Form::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->block->method('isVaultEnabled')
            ->willReturn(false);
    }
    
    public function testGetCcAvailableTypes()
    {
        $this->block
            ->method('getCcAvailableTypes')
            ->will($this->returnValue([]));
        $this->ccTypeMock
            ->method('getCcTypeLabelMap')
            ->will($this->returnValue([]));
        $this->sessionQuoteMock
            ->method('getQuote')
            ->will($this->returnValue($this->quoteMock));
        $this->quoteMock
            ->method('getBillingAddress')
            ->will($this->returnValue($this->addressMock));
        $this->assertEquals([], $this->block->getCcAvailableTypes());
    }
    
    public function testUseCvv()
    {
        $this->assertEquals(null, $this->block->useCvv());
    }
    
    public function testIsVaultEnabled()
    {
        $this->assertEquals(false, $this->block->isVaultEnabled());
    }
}