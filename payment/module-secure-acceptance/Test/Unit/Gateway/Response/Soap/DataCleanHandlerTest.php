<?php declare(strict_types = 1);

namespace CyberSource\SecureAcceptance\Gateway\Response\Soap;

use PHPUnit\Framework\TestCase;

class DataCleanHandlerTest extends TestCase
{
    /** @var DataCleanHandler */
    private $dataCleanHandler;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReaderMock;

    /** @var \CyberSource\SecureAcceptance\Gateway\Config\Config | \PHPUnit_Framework_MockObject_MockObject */
    private $configMock;

    /** @var array */
    private $additionalKeys;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDOMock;

    /**
     * @var \Magento\Sales\Model\Order\Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    /**
     * @var \Magento\Payment\Gateway\Data\OrderAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderAdapterMock;


    protected function setUp()
    {
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);
        $this->configMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Config\Config::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Sales\Model\Order\Payment::class);
        $this->orderAdapterMock = $this->createMock(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class);

        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentDOMock->expects(static::any())->method('getOrder')->willReturn($this->orderAdapterMock);


    }

    public function testHandle()
    {

        $this->additionalKeys = ['some', 'other', 'keys'];

        $this->dataCleanHandler = new DataCleanHandler(
            $this->subjectReaderMock,
            $this->configMock,
            $this->additionalKeys
        );

        $subject = ['payment' => $this->paymentDOMock];

        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($subject['payment']);

        $this->paymentMock
            ->expects(static::exactly(count($this->additionalKeys) + 3))
            ->method('unsAdditionalInformation')->withConsecutive(
                ...array_merge(
                    [['microformPublicKey'], ['signature'], ['signedFields']],
                    array_map(function($element){
                        return [$element];
                    },$this->additionalKeys)
                )
            );

        $this->dataCleanHandler->handle($subject, []);

    }
}
