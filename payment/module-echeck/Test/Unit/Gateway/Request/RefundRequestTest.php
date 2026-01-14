<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Test\Unit\Gateway\Request;

use CyberSource\ECheck\Gateway\Config\Config;
use CyberSource\ECheck\Gateway\Request\RefundRequest;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Payment;
use CyberSource\ECheck\Gateway\Request\VoidRequest;

class RefundRequestTest extends \PHPUnit\Framework\TestCase
{
    
    private $counter = 0;
    
    public function testBuild()
    {
        $txnId = 'fcd7f001e9274fdefb14bff91c799306';
        $merchantId = 'chtest';
        $invoiceId = '000000135';

        $expectation = [
            'merchantID' => 'chtest',
            'merchantReferenceCode' => '000000135',
            'voidService' => (object) [
                'run' => "true",
                'voidRequestID' => $txnId
            ],
            'partnerSolutionID' => \CyberSource\Core\Helper\AbstractDataBuilder::PARTNER_SOLUTION_ID
        ];

        $configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configMock->expects(static::once())
            ->method('getMerchantId')
            ->willReturn($merchantId);

        $orderMock = $this->createMock(OrderAdapterInterface::class);

        $orderMock->expects(static::once())
            ->method('getOrderIncrementId')
            ->willReturn($invoiceId);

        $payment = $this->createMock(PaymentDataObjectInterface::class);
        $this->orderPayment = $this->createMock(OrderPaymentInterface::class);

        $this->orderPayment->expects(static::any())
            ->method('getLastTransId')
            ->willReturn($txnId);

        $payment->expects(static::any())
            ->method('getOrder')
            ->willReturn($orderMock);

        $payment->expects(static::any())
            ->method('getPayment')
            ->will($this->returnCallback(function () {
                $this->counter++;
                return ($this->counter == 2) ? null : $this->orderPayment;
            }));

        $remoteAddressMock = $this->getMockBuilder(\Magento\Framework\HTTP\PhpEnvironment\RemoteAddress::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $checkoutSessionMock = $this->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        $customerSessionMock = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        $orderCollectionFactoryMock = $this->getMockBuilder(\Magento\Sales\Model\ResourceModel\Order\CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        $backendAuthMock = $this->getMockBuilder(\Magento\Backend\Model\Auth::class)
            ->disableOriginalConstructor()
            ->getMock();
            
        $giftMessageMock = $this->getMockBuilder(\Magento\GiftMessage\Model\Message::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ConfigInterface $configMock */
        $request = new RefundRequest(
            $configMock,
            $remoteAddressMock,
            $checkoutSessionMock,
            $customerSessionMock,
            $orderCollectionFactoryMock,
            $backendAuthMock,
            $giftMessageMock
        );

        static::assertEquals(
            $expectation,
            $request->build(['payment' => $payment])
        );
        
        try {
            static::assertEquals(
                $expectation,
                $request->build([])
            );
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('Payment data object should be provided', $e->getMessage());
        }
        
        try {
            $request->build(['payment' => $payment]);
        } catch (\LogicException $e) {
            $this->assertEquals('Order payment should be provided.', $e->getMessage());
        }
    }
}
