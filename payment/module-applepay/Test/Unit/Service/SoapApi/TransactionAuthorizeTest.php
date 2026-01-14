<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */
namespace CyberSource\ApplePay\Test\Service\SoapApi;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class CancelTest
 * @package CyberSource\BankTransfer\Test\Unit\Controller\Index
 * @codingStandardsIgnoreStart
 */
class TransactionAuthorizeTest extends \PHPUnit\Framework\TestCase
{
    
    /**
     *
     * @var CyberSource\ApplePay\Block\Form 
     */
    private $unit;
    
    protected function setUp()
    {
        $this->sessionMock = $this
            ->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->loggerMock = $this
            ->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cybersourceApiMock = $this
            ->getMockBuilder(\CyberSource\ApplePay\Service\SoapApi\Wallet::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transferMock = $this
            ->getMockBuilder(\Magento\Payment\Gateway\Http\TransferInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $helper = new ObjectManager($this);
        $this->unit = $helper->getObject(
            \CyberSource\ApplePay\Service\SoapApi\TransactionAuthorize::class,
            [
                'session' => $this->sessionMock,
                'gatewayAPI' => $this->cybersourceApiMock,
                'logger' => $this->loggerMock
            ]
        );
    }
    
    public function testPlaceRequest()
    {
        $this->cybersourceApiMock
            ->method('walletPay')
            ->willReturn('');
        $this->assertEquals(
            ['response' => null], 
            $this->unit->placeRequest($this->transferMock)
        );
    }
}