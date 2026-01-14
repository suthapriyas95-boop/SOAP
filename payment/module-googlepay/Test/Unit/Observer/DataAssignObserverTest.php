<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\GooglePay\Observer;

class DataAssignObserverTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var DataAssignObserver
     */
    protected $observer;

    protected function setUp()
    {

        $this->observer = new DataAssignObserver();

    }

    public function testExecute()
    {

        $observerContainer = $this->createMock(\Magento\Framework\Event\Observer::class);
        $event = $this->createMock(\Magento\Framework\Event::class);
        $paymentMethodFacade = $this->createMock(\Magento\Payment\Model\MethodInterface::class);
        $paymentModel = $this->createMock(\Magento\Payment\Model\InfoInterface::class);

        $additionalData = [
            'paymentToken' => 'someToken',
        ];

        $dataObject = new \Magento\Framework\DataObject(
            [\Magento\Quote\Api\Data\PaymentInterface::KEY_ADDITIONAL_DATA => $additionalData,]
        );

        $paymentMethodFacade->method('getCode')->willReturn('cybersource_googlepay');

        $observerContainer->method('getEvent')->willReturn($event);

        $event
            ->method('getDataByKey')
            ->willReturnMap(
                [
                    [\Magento\Payment\Observer\AbstractDataAssignObserver::METHOD_CODE, $paymentMethodFacade],
                    [\Magento\Payment\Observer\AbstractDataAssignObserver::DATA_CODE, $dataObject],
                    [\Magento\Payment\Observer\AbstractDataAssignObserver::MODEL_CODE, $paymentModel],
                ]
            );

        $paymentModel->expects(static::once())->method('setAdditionalInformation')->with('paymentToken', base64_encode($additionalData['paymentToken']));

        $this->observer->execute($observerContainer);
    }
}
