<?php declare(strict_types=1);

namespace CyberSource\SecureAcceptance\Gateway\Response\Soap;

use PHPUnit\Framework\TestCase;

class TokenHandlerTest extends TestCase
{
    /** @var TokenHandler */
    private $tokenHandler;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReaderMock;

    /** @var \CyberSource\SecureAcceptance\Model\PaymentTokenManagement | \PHPUnit_Framework_MockObject_MockObject */
    private $paymentTokenManagementMock;

    /** @var \CyberSource\SecureAcceptance\Gateway\Config\Config | \PHPUnit_Framework_MockObject_MockObject */
    private $configMock;

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
        $this->paymentTokenManagementMock = $this->createMock(\CyberSource\SecureAcceptance\Model\PaymentTokenManagement::class);
        $this->configMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Config\Config::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Sales\Model\Order\Payment::class);
        $this->orderAdapterMock = $this->createMock(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class);

        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentDOMock->expects(static::any())->method('getOrder')->willReturn($this->orderAdapterMock);

        $this->tokenHandler = new TokenHandler(
            $this->subjectReaderMock,
            $this->paymentTokenManagementMock,
            $this->configMock
        );
    }

    public function testHandle()
    {

        $cardData = [
            'type'=> '001',
            'pan' => '411111XXXXXX1111',
            'expMo' => '01',
            'expYear' => '2020',
            'bin' => '411111',
            'last4' => '1111',
        ];

        $subject = ['payment' => $this->paymentDOMock];
        $response = ['card' => (object)['cardType' => $cardData['type']]];

        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($subject['payment']);
        $this->configMock->method('isMicroform')->willReturn(true);

        $this->paymentMock->expects(static::any())->method('getAdditionalInformation')->willReturnMap(
            [
                ['maskedPan', $cardData['pan']],
                [
                    'expDate',
                    implode('-', [$cardData['expMo'], $cardData['expYear']])
                ],
                ['is_active_payment_token_enabler', true]
            ]
        );

        $expectedToken = [
            'payment_token' => '1231231232132',
            'card_type' => '001',
            'cc_last4' => $cardData['last4'],
            'card_expiry_date' => implode('-', [$cardData['expMo'], $cardData['expYear']]),
            'card_bin' => $cardData['bin'],
            'instrument_id' => '213123123123',
        ];

        $this->paymentTokenManagementMock
            ->expects(static::any())
            ->method('getTokenFromPayment')
            ->with($this->paymentMock)
            ->willReturn($expectedToken['payment_token']);

        $this->paymentTokenManagementMock
            ->expects(static::any())
            ->method('getInstrumentIdFromPayment')
            ->with($this->paymentMock)
            ->willReturn($expectedToken['instrument_id']);

        $this->paymentMock
            ->expects(static::once())
            ->method('setAdditionalInformation')
            ->with('token_data', $expectedToken);

        $this->tokenHandler->handle($subject, $response);

    }
}
