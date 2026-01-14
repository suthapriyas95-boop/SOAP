<?php declare(strict_types = 1);

namespace CyberSource\SecureAcceptance\Gateway\Request\Soap;

use PHPUnit\Framework\TestCase;

class CaptureSequenceBuilderTest extends TestCase
{
    /**
     * @var \Magento\Payment\Gateway\Data\OrderAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderAdapterMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject | \Magento\Sales\Model\Order
     */
    private $orderMock;

    /** @var CaptureSequenceBuilder */
    private $captureSequenceBuilder;

    /**
     * @var \Magento\Payment\Model\InfoInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDOMock;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReaderMock;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createPartialMock(\Magento\Payment\Model\InfoInterface::class, [
            'getOrder',
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


        $this->orderMock = $this->createPartialMock(\Magento\Sales\Model\Order::class, [
            'getInvoiceCollection',
            'getSize',
            'getBaseGrandTotal',
            'getBaseTotalDue',
        ]);

        $this->captureSequenceBuilder = new CaptureSequenceBuilder(
            $this->subjectReaderMock
        );
    }

    /**
     * @param $expected
     * @param $invoices
     * @param $grandTotal
     * @param $amount
     * @param $due
     * @dataProvider dataProviderTestBuild
     */
    public function testBuild($expected, $invoices, $grandTotal, $amount, $due)
    {

        $subject = ['payment' => $this->paymentDOMock, 'amount' => $amount];

        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($subject['payment']);
        $this->subjectReaderMock->method('readAmount')->with($subject)->willReturn($subject['amount']);

        $this->orderMock->expects(static::any())->method('getInvoiceCollection')->willReturnSelf();
        $this->orderMock->expects(static::any())->method('getSize')->willReturn($invoices);
        $this->orderMock->expects(static::any())->method('getBaseGrandTotal')->willReturn($grandTotal);
        $this->orderMock->expects(static::any())->method('getBaseTotalDue')->willReturn($due);

        $this->paymentMock->method('getOrder')->willReturn($this->orderMock);

        $this->assertEquals($expected, $this->captureSequenceBuilder->build($subject));
    }

    public function dataProviderTestBuild()
    {
        return [
            [
                'expected' => [],
                'invoices' => 1,
                'grandTotal' => 10.00,
                'amount' => 10.00,
                'due' => 0.00,
            ],
            [
                'expected' => ['totalCount' => 99, 'sequence' => 2],
                'invoices' => 1,
                'grandTotal' => 10.00,
                'amount' => 1.00,
                'due' => 9.00,
            ],
            [
                'expected' => ['totalCount' => 99, 'sequence' => 99],
                'invoices' => 1,
                'grandTotal' => 10.00,
                'amount' => 9.00,
                'due' => 9.00,
            ],
        ];
    }
}
