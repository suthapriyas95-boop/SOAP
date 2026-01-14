<?php declare(strict_types = 1);

namespace CyberSource\SecureAcceptance\Gateway\Response\Soap;

use PHPUnit\Framework\TestCase;

class MitDetailsHandlerTest extends TestCase
{
    /**
     * @var \Magento\Vault\Model\PaymentToken|\PHPUnit_Framework_MockObject_MockObject
     */
    private $vaultPaymentTokenMock;

    /**
     * @var \Magento\Payment\Gateway\Data\OrderAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderAdapterMock;

    /**
     * @var \Magento\Sales\Model\Order\Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDOMock;

    /** @var MitDetailsHandler */
    private $mitDetailsHandler;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReaderMock;

    /** @var \Magento\Vault\Model\PaymentTokenRepository | \PHPUnit_Framework_MockObject_MockObject */
    private $paymentTokenRepositoryMock;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);
        $this->paymentTokenRepositoryMock = $this->createMock(\Magento\Vault\Model\PaymentTokenRepository::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createPartialMock(\Magento\Sales\Model\Order\Payment::class, [
            'getExtensionAttributes',
            'getVaultPaymentToken',
            'encrypt',
            'decrypt',
            'setAdditionalInformation',
            'hasAdditionalInformation',
            'unsAdditionalInformation',
            'getAdditionalInformation',
            'getMethodInstance',
        ]);
        $this->orderAdapterMock = $this->createMock(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class);

        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentDOMock->expects(static::any())->method('getOrder')->willReturn($this->orderAdapterMock);

        $this->vaultPaymentTokenMock = $this->createMock(\Magento\Vault\Model\PaymentToken::class);

        $this->mitDetailsHandler = new MitDetailsHandler(
            $this->subjectReaderMock,
            $this->paymentTokenRepositoryMock
        );
    }

    /**
     * @param $request
     * @dataProvider dataProviderTestHandle
     */
    public function testHandle($isEmptyToken, $request)
    {

        $subject = ['payment' => $this->paymentDOMock];

        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($subject['payment']);

        $this->paymentMock->method('getExtensionAttributes')->willReturnSelf();
        $this->paymentMock->method('getVaultPaymentToken')->willReturn($this->vaultPaymentTokenMock);

        $this->vaultPaymentTokenMock->method('isEmpty')->willReturn($isEmptyToken);
        $this->vaultPaymentTokenMock->method('getTokenDetails')->willReturn('{}');

        $this->vaultPaymentTokenMock
            ->expects(static::once())
            ->method('setTokenDetails')
            ->with(json_encode(['paymentNetworkTransactionID' => $request['ccAuthReply']->paymentNetworkTransactionID]));

        $this->mitDetailsHandler->handle($subject, $request);

        $this->paymentTokenRepositoryMock->expects(static::any())->method('save')->with($this->vaultPaymentTokenMock);
    }

    public function dataProviderTestHandle()
    {
        return [
            [
                'isEmptyToken' => false,
                'request' => [
                    'ccAuthReply' => (object)[
                        'paymentNetworkTransactionID' => '123'
                    ],
                ]
            ]
        ];
    }
}
