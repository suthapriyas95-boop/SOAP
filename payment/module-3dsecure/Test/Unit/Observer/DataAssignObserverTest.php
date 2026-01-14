<?php declare(strict_types=1);

namespace CyberSource\ThreeDSecure\Observer;

use PHPUnit\Framework\TestCase;

class DataAssignObserverTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentExtensionMock;

    /**
     * @var \Magento\Framework\DataObject | \PHPUnit_Framework_MockObject_MockObject
     */
    private $dataMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject | \Magento\Payment\Model\InfoInterface
     */
    private $paymentModelMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject | \Magento\Framework\Event\Observer
     */
    private $observerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $eventMock;

    /** @var DataAssignObserver */
    private $dataAssignObserver;

    /** @var \Magento\Quote\Api\Data\PaymentExtensionInterfaceFactory | \PHPUnit_Framework_MockObject_MockObject */
    private $extensionFactory;

    protected function setUp()
    {
        $this->extensionFactory = $this->createMock(\Magento\Quote\Api\Data\PaymentExtensionInterfaceFactory::class);
        $this->dataAssignObserver = new DataAssignObserver(
            $this->extensionFactory
        );

        $this->observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->eventMock = $this->createMock(\Magento\Framework\Event::class);
        $this->dataMock = $this->createMock(\Magento\Framework\DataObject::class);

        $this->paymentModelMock = $this->getMockBuilder(\Magento\Payment\Model\Info::class)
            ->disableOriginalConstructor()
            ->setMethods(['setExtensionAttributes', 'getExtensionAttributes', 'setAdditionalInformation'])
            ->getMock()
        ;

        $this->observerMock->expects(static::any())->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects(static::any())->method('getDataByKey')->willReturnMap(
            [
                ['data', $this->dataMock],
                ['payment_model', $this->paymentModelMock]
            ]
        );
    }

    public function testExtensionAttributes()
    {
        $ccaResponse = 'testvalue';

        $this->paymentExtensionMock = $this
            ->getMockBuilder(\Magento\Quote\Api\Data\PaymentExtension::class)
            ->setMethods(['getCcaResponse', 'setCcaResponse'])
            ->getMock();

        $additionalData = ['extension_attributes' => $this->paymentExtensionMock];
        $this->paymentModelMock->expects(static::any())->method('getExtensionAttributes')->willReturn($this->paymentExtensionMock);
        $this->paymentExtensionMock->expects(static::once())->method('getCcaResponse')->willReturn($ccaResponse);

        $this->dataMock->expects(static::any())->method('getDataByPath')->with('additional_data')->willReturn($additionalData);

        $this->paymentModelMock->expects(static::atLeastOnce())->method('setExtensionAttributes')->with($this->paymentExtensionMock);
        $this->paymentExtensionMock->expects(static::atLeastOnce())->method('setCcaResponse')->with($ccaResponse);

        $this->dataAssignObserver->execute($this->observerMock);
    }

    public function testSessionId()
    {

        $sessionId = 'testvalue';
        $additionalData = ['sessionId' => $sessionId];

        $this->dataMock->expects(static::any())->method('getDataByPath')->with('additional_data')->willReturn($additionalData);

        $this->paymentModelMock
            ->expects(static::once())
            ->method('setAdditionalInformation')
            ->with(
                'payer_auth_enroll_session_id',
                $sessionId
            );

        $this->dataAssignObserver->execute($this->observerMock);
    }
}
