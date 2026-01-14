<?php declare(strict_types = 1);

namespace CyberSource\SecureAcceptance\Gateway\Request\Soap;

use PHPUnit\Framework\TestCase;

class MitDataBuilderTest extends TestCase
{
    private $vaultPaymentTokenMock;
    /**
     * @var \Magento\Payment\Gateway\Data\OrderAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderAdapterMock;

    /**
     * @var \Magento\Payment\Model\InfoInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDOMock;

    /** @var MitDataBuilder */
    private $mitDataBuilder;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReaderMock;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createPartialMock(\Magento\Payment\Model\InfoInterface::class, [
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

        $this->mitDataBuilder = new MitDataBuilder(
            $this->subjectReaderMock
        );
    }

    /**
     * @param $expected
     * @dataProvider dataProviderTestBuild
     */
    public function testBuild($expected, $isEmpty, $tokenDetails = [])
    {
        $subject = ['payment' => $this->paymentDOMock];

        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($subject['payment']);

        $this->paymentMock->method('getExtensionAttributes')->willReturnSelf();
        $this->paymentMock->method('getVaultPaymentToken')->willReturn($this->vaultPaymentTokenMock);

        $this->vaultPaymentTokenMock->method('isEmpty')->willReturn($isEmpty);
        $this->vaultPaymentTokenMock->method('getTokenDetails')->willReturn(json_encode($tokenDetails));

        $this->assertEquals($expected, $this->mitDataBuilder->build($subject));
    }


    public function dataProviderTestBuild()
    {
        return [
            [
                'expected' => [],
                'isEmpty' => true,
            ],
            [
                'expected' => [
                    'subsequentAuth' => 'true',
                    'subsequentAuthStoredCredential' => 'true',
                    'subsequentAuthFirst' => 'true',
                ],
                'isEmpty' => false,
            ],
            [
                'expected' => [
                    'subsequentAuth' => 'true',
                    'subsequentAuthStoredCredential' => 'true',
                    'subsequentAuthTransactionID' => '123132323'
                ],
                'isEmpty' => false,
                'tokenDetails' => ['paymentNetworkTransactionID' => '123132323'],
            ],
            [
                'expected' => [
                    'subsequentAuth' => 'true',
                    'subsequentAuthStoredCredential' => 'true',
                    'subsequentAuthFirst' => 'true',
                ],
                'isEmpty' => false,
                'tokenDetails' => [],
            ],
        ];
    }
}
