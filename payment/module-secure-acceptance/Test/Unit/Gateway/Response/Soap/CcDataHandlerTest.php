<?php declare(strict_types = 1);

namespace CyberSource\SecureAcceptance\Gateway\Response\Soap;

use PHPUnit\Framework\TestCase;

class CcDataHandlerTest extends TestCase
{
    /** @var CcDataHandler */
    private $ccDataHandler;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReaderMock;

    /** @var \CyberSource\SecureAcceptance\Gateway\Config\Config | \PHPUnit_Framework_MockObject_MockObject */
    private $configMock;

    /** @var \CyberSource\SecureAcceptance\Helper\RequestDataBuilder | \PHPUnit_Framework_MockObject_MockObject */
    private $requestDataBuilderMock;

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
        $this->requestDataBuilderMock = $this->createMock(\CyberSource\SecureAcceptance\Helper\RequestDataBuilder::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Sales\Model\Order\Payment::class);
        $this->orderAdapterMock = $this->createMock(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class);

        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentDOMock->expects(static::any())->method('getOrder')->willReturn($this->orderAdapterMock);

        $this->ccDataHandler = new CcDataHandler(
            $this->subjectReaderMock,
            $this->configMock,
            $this->requestDataBuilderMock
        );
    }

    public function testHandle()
    {

        $cardData = [
            'type'=> '001',
            'magentoType' => 'VI',
            'pan' => '4111111111111111',
            'maskedPan' => '411111XXXXXX1111',
            'expMo' => '01',
            'expYear' => '2020',
            'bin' => '411111',
            'last4' => '1111',
        ];

        $subject = ['payment' => $this->paymentDOMock];
        $response = ['card' => (object)['cardType' => $cardData['type']]];

        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($subject['payment']);
        $this->configMock->method('isMicroform')->willReturn(true);

        $this->paymentMock->method('getMethod')->willReturn('chcybersource');

        $this->paymentMock->expects(static::any())->method('getAdditionalInformation')->willReturnMap(
            [
                ['maskedPan', $cardData['pan']],
                [
                    'expDate',
                    implode('-', [$cardData['expMo'], $cardData['expYear']])
                ],
            ]
        );

        $this->paymentMock
            ->expects(static::exactly(2))
            ->method('setAdditionalInformation')
            ->withConsecutive(
                ['cardNumber', $cardData['maskedPan']],
                ['cardType', $cardData['type']]
            );

        $this->requestDataBuilderMock
            ->method('getCardType')
            ->with($cardData['type'], true)
            ->willReturn($cardData['magentoType']);

        $this->paymentMock->expects(static::once())->method('setCcType')->with($cardData['magentoType']);
        $this->paymentMock->expects(static::once())->method('setCcLast4')->with($cardData['last4']);
        $this->paymentMock->expects(static::once())->method('setCcExpMonth')->with($cardData['expMo']);
        $this->paymentMock->expects(static::once())->method('setCcExpYear')->with($cardData['expYear']);

        $this->ccDataHandler->handle($subject, $response);

    }

}
