<?php declare(strict_types=1);

namespace CyberSource\SecureAcceptance\Gateway\Request\Sop;

use PHPUnit\Framework\TestCase;

class PaymentDataBuilderTest extends TestCase
{
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

    /** @var PaymentDataBuilder */
    private $paymentDataBuilder;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReaderMock;

    /** @var \Magento\Framework\Math\Random | \PHPUnit_Framework_MockObject_MockObject */
    private $randomMock;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);
        $this->randomMock = $this->createMock(\Magento\Framework\Math\Random::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Payment\Model\InfoInterface::class);

        $this->orderAdapterMock = $this->createMock(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class);

        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentDOMock->expects(static::any())->method('getOrder')->willReturn($this->orderAdapterMock);

        $this->paymentDataBuilder = new PaymentDataBuilder(
            $this->subjectReaderMock,
            $this->randomMock
        );
    }

    public function testBuild()
    {

        $expected = [
            'reference_number' => '000001',
            'currency' => 'USD',
            'amount' => '1.99',
            'transaction_uuid' => '123123123',
            'merchant_secure_data1' => 'orderid',
            'merchant_secure_data3' => 'storeid',
        ];

        $subject = ['payment' => $this->paymentDOMock, 'amount' => 1.992];

        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($subject['payment']);
        $this->subjectReaderMock->method('readAmount')->with($subject)->willReturn($subject['amount']);

        $this->randomMock->method('getUniqueHash')->willReturn($expected['transaction_uuid']);

        $this->orderAdapterMock->method('getOrderIncrementId')->willReturn('000001');
        $this->orderAdapterMock->method('getCurrencyCode')->willReturn('USD');

        $this->orderAdapterMock->method('getId')->willReturn($expected['merchant_secure_data1']);
        $this->orderAdapterMock->method('getStoreId')->willReturn($expected['merchant_secure_data3']);

        $this->assertEquals($expected, $this->paymentDataBuilder->build($subject));
    }

}
