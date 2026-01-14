<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Test\Unit\Gateway\Request;

use CyberSource\ECheck\Gateway\Config\Config;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use CyberSource\ECheck\Gateway\Request\CaptureRequest;

class CaptureRequestTest extends \PHPUnit\Framework\TestCase
{
    public function testBuild()
    {
        $txnId = 'fcd7f001e9274fdefb14bff91c799306';
        $merchantId = 'chtest';

        $expectation = [
            'merchantID' => 'chtest',
            'type' => 'transaction',
            'subtype' => 'transactionDetail',
            'versionNumber' => '1.5',
            'requestID' => 'fcd7f001e9274fdefb14bff91c799306',
        ];

        $configMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configMock->method('getMerchantId')->willReturn($merchantId);

        $remoteAddressMock = $this->getMockBuilder(\Magento\Framework\HTTP\PhpEnvironment\RemoteAddress::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentModel = $this->createMock(OrderPaymentInterface::class);
        
        $paymentInfo = $this->createMock(\Magento\Payment\Model\InfoInterface::class);

        $paymentInfo->expects(static::once())
            ->method('getLastTransId')
            ->willReturn($txnId);
        $paymentModel->expects(static::once())
            ->method('getPayment')
            ->willReturn($paymentInfo);

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
            
        $subjectReaderMock = $this->getMockBuilder(\CyberSource\ECheck\Gateway\Helper\SubjectReader::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ConfigInterface $configMock */
        $request = new CaptureRequest(
            $configMock,
            $remoteAddressMock,
            $checkoutSessionMock,
            $customerSessionMock,
            $orderCollectionFactoryMock,
            $backendAuthMock,
            $giftMessageMock,
            $subjectReaderMock
        );

        static::assertEquals(
            $expectation,
            $request->build(['payment' => $paymentModel])
        );
        
        try {
            static::assertEquals(
                $expectation,
                $request->build([])
            );
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('OrderPaymentInterface should be provided', $e->getMessage());
        }
    }
}
