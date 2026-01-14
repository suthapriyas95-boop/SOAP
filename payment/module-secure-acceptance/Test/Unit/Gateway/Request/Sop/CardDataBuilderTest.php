<?php declare(strict_types=1);

namespace CyberSource\SecureAcceptance\Gateway\Request\Sop;

use PHPUnit\Framework\TestCase;

class CardDataBuilderTest extends TestCase
{
    /**
     * @var \Magento\Sales\Model\Order\Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDOMock;

    /**
     * @var \CyberSource\SecureAcceptance\Helper\RequestDataBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestDataBuilderMock;

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subjectReaderMock;

    /** @var CardDataBuilder */
    private $cardDataBuilder;

    /** @var \CyberSource\SecureAcceptance\Gateway\Config\Config | \PHPUnit_Framework_MockObject_MockObject */
    private $configMock;

    protected function setUp()
    {
        $this->configMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Config\Config::class);
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);
        $this->requestDataBuilderMock = $this->createMock(\CyberSource\SecureAcceptance\Helper\RequestDataBuilder::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Sales\Model\Order\Payment::class);

        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);

        $this->cardDataBuilder = new CardDataBuilder(
            $this->configMock,
            $this->subjectReaderMock,
            $this->requestDataBuilderMock
        );
    }

    public function testBuildHosted()
    {

        $subject = ['payment' => $this->paymentDOMock];

        $this->configMock->method('isSilent')->willReturn(false);

        $this->assertEquals(
            ['payment_method' => 'card',],
            $this->cardDataBuilder->build($subject)
        );

    }

    public function testBuildSilent()
    {

        $subject = ['payment' => $this->paymentDOMock];

        $result = [
            'payment_method' => 'card',
            'unsigned_field_names' => 'card_number,card_expiry_date,card_cvn',
            'card_type' => '001',
        ];

        $this->configMock->method('isSilent')->willReturn(true);

        $this->subjectReaderMock->method('readPayment')->willReturn($this->paymentDOMock);

        $this->paymentMock->method('getAdditionalInformation')->with('cardType')->willReturn('VI');

        $this->requestDataBuilderMock->method('getCardType')->with('VI')->willReturn('001');

        $this->assertEquals(
            $result,
            $this->cardDataBuilder->build($subject)
        );

    }

}
