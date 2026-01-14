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
class ApplePayAuthorizeHandlerTest extends \PHPUnit\Framework\TestCase
{
    
    /**
     *
     * @var CyberSource\ApplePay\Block\Form 
     */
    private $unit;
    
    protected $subjectReader;
    
    protected function setUp()
    {
        $this->paymentDataMock = $this
            ->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentMock = $this
            ->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subjectReader = $this
            ->getMockBuilder(\CyberSource\ApplePay\Gateway\Helper\SubjectReader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $helper = new ObjectManager($this);
        $this->unit = $helper->getObject(
            \CyberSource\ApplePay\Gateway\Response\AuthorizeResponseHandler::class,
            ['subjectReader' => $this->subjectReader]
        );
    }
    
    public function testHandle()
    {
        $this->paymentDataMock
            ->method('getPayment')
            ->will($this->returnValue($this->paymentMock));
        $this->subjectReader
            ->method('readPayment')
            ->will($this->returnValue($this->paymentDataMock));
        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = 'us';
        $purchaseTotals->grandTotalAmount = 10;
        $ccAuthReply = new \stdClass();
        $ccAuthReply->reconciliationID = 'reconciliationID';
        $ccAuthReply->authorizationCode = 'authorizationCode';
        $ccAuthReply->amount = 10;
        $this->assertEquals(null, $this->unit->handle(
                [
                    'payment' => $this->paymentDataMock
                ],
                [
                    'requestID' => 'req_id',
                    'purchaseTotals' => $purchaseTotals,
                    'ccAuthReply' => $ccAuthReply,
                    'requestToken' => 'requestToken',
                    'reasonCode' => 100,
                    'decision' => 'decision',
                    'merchantReferenceCode' => 'merchantReferenceCode'
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
                    'purchaseTotals' => $purchaseTotals,
                    'ccAuthReply' => $ccAuthReply,
                    'requestToken' => 'requestToken',
                    'reasonCode' => 100,
                    'decision' => 'decision',
                    'merchantReferenceCode' => 'merchantReferenceCode'
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