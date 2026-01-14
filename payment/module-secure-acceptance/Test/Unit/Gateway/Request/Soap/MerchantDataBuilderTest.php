<?php declare(strict_types = 1);

namespace CyberSource\SecureAcceptance\Gateway\Request\Soap;

use PHPUnit\Framework\TestCase;

class MerchantDataBuilderTest extends TestCase
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
    /** @var MerchantDataBuilder */
    private $merchantDataBuilder;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReaderMock;

    /** @var \CyberSource\SecureAcceptance\Gateway\Config\Config | \PHPUnit_Framework_MockObject_MockObject */
    private $gatewayConfigMock;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);
        $this->gatewayConfigMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Config\Config::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Payment\Model\InfoInterface::class);
        $this->orderAdapterMock = $this->createMock(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class);

        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentDOMock->expects(static::any())->method('getOrder')->willReturn($this->orderAdapterMock);

        $this->merchantDataBuilder = new MerchantDataBuilder(
            $this->subjectReaderMock,
            $this->gatewayConfigMock
        );
    }

    public function testBuild()
    {

        $subject = ['payment' => $this->paymentDOMock];
        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($subject['payment']);

        $expected = [
            'partnerSolutionID' => 'KT41CM4M',
            'storeId' => 1,
            'merchantID' => 'cybertest',
            'developerId' => '123'
        ];

        $this->orderAdapterMock->method('getStoreId')->willReturn($expected['storeId']);

        $this->gatewayConfigMock->method('getValue')->with('merchant_id', $expected['storeId'])->willReturn($expected['merchantID']);
        $this->gatewayConfigMock->method('getDeveloperId')->willReturn($expected['developerId']);

        $this->assertEquals($expected, $this->merchantDataBuilder->build($subject));
    }
}
