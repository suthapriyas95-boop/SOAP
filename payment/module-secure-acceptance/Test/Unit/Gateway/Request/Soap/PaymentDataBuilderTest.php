<?php declare(strict_types = 1);

namespace CyberSource\SecureAcceptance\Gateway\Request\Soap;

use PHPUnit\Framework\TestCase;

class PaymentDataBuilderTest extends TestCase
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

    /** @var PaymentDataBuilder */
    private $paymentDataBuilder;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReaderMock;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Payment\Model\InfoInterface::class);

        $this->orderAdapterMock = $this->createMock(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class);

        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentDOMock->expects(static::any())->method('getOrder')->willReturn($this->orderAdapterMock);


        $this->paymentDataBuilder = new PaymentDataBuilder(
            $this->subjectReaderMock
        );
    }

    public function testBuild()
    {

        $subject = ['payment' => $this->paymentDOMock, 'amount' => 1.992];

        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($subject['payment']);
        $this->subjectReaderMock->method('readAmount')->with($subject)->willReturn($subject['amount']);

        $this->orderAdapterMock->method('getOrderIncrementId')->willReturn('000001');
        $this->orderAdapterMock->method('getCurrencyCode')->willReturn('USD');

        $this->assertEquals(
            [
                'merchantReferenceCode' => '000001',
                'purchaseTotals' => [
                    'currency' => 'USD',
                    'grandTotalAmount' => '1.99',
                ],
            ],
            $this->paymentDataBuilder->build($subject)
        );
    }
}
