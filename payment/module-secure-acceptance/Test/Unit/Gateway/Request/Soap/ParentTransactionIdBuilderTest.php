<?php declare(strict_types=1);

namespace CyberSource\SecureAcceptance\Gateway\Request\Soap;

use PHPUnit\Framework\TestCase;

class ParentTransactionIdBuilderTest extends TestCase
{
    /**
     * @var \Magento\Payment\Gateway\Data\OrderAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderAdapterMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDOMock;

    /** @var ParentTransactionIdBuilder */
    private $parentTransactionIdBuilder;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReaderMock;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createPartialMock(\Magento\Sales\Model\Order\Payment::class, [
            'getParentTransactionId',
            'getRefundTransactionId',
        ]);
        $this->orderAdapterMock = $this->createMock(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class);

        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentDOMock->expects(static::any())->method('getOrder')->willReturn($this->orderAdapterMock);
    }


    /**
     * @param $expected
     * @param string $parentTransactionName
     * @param string $transactionId
     * @param string $refundTransactionId
     *
     * @dataProvider dataProviderTestBuild
     */
    public function testBuild(
        $expected,
        $parentTransactionName,
        $transactionId,
        $refundTransactionId
    ) {
        $subject = ['payment' => $this->paymentDOMock];

        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($subject['payment']);

        $this->parentTransactionIdBuilder = new ParentTransactionIdBuilder(
            $this->subjectReaderMock,
            $parentTransactionName
        );

        $this->paymentMock->method('getParentTransactionId')->willReturn($transactionId);
        $this->paymentMock->method('getRefundTransactionId')->willReturn($refundTransactionId);

        $this->assertEquals(
            $expected,
            $this->parentTransactionIdBuilder->build($subject)
        );
    }

    public function dataProviderTestBuild()
    {
        return [
            [
                'expected' => [
                    'voidRequestID' => '23232322'
                ],
                'parentTransactionName' => 'voidRequestID',
                'transactionId' => '23232322',
                'refundTransactionId' => '32323',
            ],
            [
                'expected' => [
                    'captureRequestID' => '32323'
                ],
                'parentTransactionName' => 'captureRequestID',
                'transactionId' => null,
                'refundTransactionId' => '32323',
            ],
        ];
    }
}
