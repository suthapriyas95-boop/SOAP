<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */
namespace CyberSource\ApplePay\Test\Unit\Gateway\Response;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class CancelTest
 * @package CyberSource\BankTransfer\Test\Unit\Controller\Index
 * @codingStandardsIgnoreStart
 */
class ApplePayCaptureHandlerTest extends \PHPUnit\Framework\TestCase
{
    
    /**
     *
     * @var CyberSource\ApplePay\Block\Form 
     */
    private $unit;
    
    protected $subjectReader;
    
    protected $order;
    
    protected function setUp()
    {
        $this->subjectReader = $this
            ->getMockBuilder(\CyberSource\ApplePay\Gateway\Helper\SubjectReader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentDataMock = $this
            ->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->order = $this
            ->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $helper = new ObjectManager($this);
        $this->unit = $helper->getObject(
            \CyberSource\ApplePay\Gateway\Response\CaptureResponseHandler::class,
            ['subjectReader' => $this->subjectReader]
        );
    }
    
    public function testHandle()
    {
        $this->subjectReader
            ->method('readPayment')
            ->will($this->returnValue($this->paymentDataMock));
        $this->paymentDataMock
            ->method('getPayment')
            ->will($this->returnValue($this->paymentMock));
        $this->paymentMock
            ->method('getOrder')
            ->will($this->returnValue($this->order));
        $this->assertEquals(null, $this->unit->handle(
                [
                    'payment' => $this->paymentDataMock
                ],
                [
                    'requestID' => 'req_id',
                    'reasonCode' => 100,
                    'decision' => 'decision'
                ]
            )
        );
        try {
            $this->unit->handle(
                [
                    'payment' => $this->paymentDataMock
                ],
                [
                    'requestID' => 'req_id',
                    'reasonCode' => 100,
                    'decision' => 'decision'
                ]
            );
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals(
                'Payment data object should be provided', 
                $e->getMessage()
            );
        }
    }
}