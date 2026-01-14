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
class InfoTest extends \PHPUnit\Framework\TestCase
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
        $this->block = $helper->getObject(
            \CyberSource\ApplePay\Block\Info::class,
            [
                'context' => $this->contextMock,
                '_paymentConfig' => $this->configMock,
                'sessionQuote' => $this->sessionQuoteMock,
                'gatewayConfig' => $this->gatewayConfigMock,
                'ccType' => $this->ccTypeMock,
            ]
        );
    }
    
    public function test()
    {
        
    }
}