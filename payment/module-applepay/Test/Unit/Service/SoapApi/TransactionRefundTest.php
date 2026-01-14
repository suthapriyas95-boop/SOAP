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
class TransactionRefundTest extends \PHPUnit\Framework\TestCase
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
        $this->paymentRepoMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order\Payment\Repository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $helper = new ObjectManager($this);
        $this->unit = $helper->getObject(
            \CyberSource\ApplePay\Service\SoapApi\TransactionRefund::class,
            [
                'session' => $this->sessionMock,
                'gatewayAPI' => $this->cybersourceApiMock,
                'logger' => $this->loggerMock,
                'orderPaymentRepository' => $this->paymentRepoMock
            ]
        );
    }
    
    public function testPlaceRequest()
    {
        $this->transferMock
            ->method('getBody')
            ->will($this->returnValue(['payment_id' => 1]));
        $this->paymentRepoMock
            ->method('get')
            ->will($this->returnValue($this->paymentMock));
        $this->paymentMock
            ->method('getOrder')
            ->will($this->returnValue($this->orderMock));
        $this->assertEquals(
            ['response' => null], 
            $this->unit->placeRequest($this->transferMock)
        );
    }
}