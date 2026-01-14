<?php declare(strict_types = 1);

namespace CyberSource\ThreeDSecure\Gateway\Request\Cca;

use PHPUnit\Framework\TestCase;

class PayerAuthEnrollBuilderTest extends TestCase
{
    /**
     * @var \Magento\Payment\Gateway\Data\AddressAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $billingAddressMock;

    /**
     * @var \Magento\Payment\Gateway\Data\OrderAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderMock;

    /**
     * @var \Magento\Quote\Api\Data\PaymentInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDOMock;

    /** @var PayerAuthEnrollBuilder */
    private $payerAuthEnrollBuilder;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReader;

    /** @var \CyberSource\SecureAcceptance\Model\PaymentTokenManagement | \PHPUnit_Framework_MockObject_MockObject */
    private $paymentTokenManagement;

    /** @var \Magento\Framework\App\RequestInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $requestMock;

    protected function setUp()
    {
        $this->subjectReader = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);
        $this->paymentTokenManagement = $this->createMock(\CyberSource\SecureAcceptance\Model\PaymentTokenManagement::class);
        $this->requestMock = $this->createMock(\Magento\Framework\App\Request\Http::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Payment\Model\InfoInterface::class);
        $this->orderMock = $this->createMock(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class);
        $this->billingAddressMock = $this->createMock(\Magento\Payment\Gateway\Data\AddressAdapterInterface::class);

        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentDOMock->expects(static::any())->method('getOrder')->willReturn($this->orderMock);
        $this->orderMock->expects(static::any())->method('getBillingAddress')->willReturn($this->billingAddressMock);

        $this->payerAuthEnrollBuilder = new PayerAuthEnrollBuilder(
            $this->subjectReader,
            $this->paymentTokenManagement,
            $this->requestMock
        );
    }

    public function testBuild()
    {
        $phone = '1234567890';
        $referenceId = '123456456465465465';
        $acceptHeader = '*/*';
        $userAgentHeader = 'Chrome';

        $subject = ['payment' => $this->paymentDOMock];
        $this->subjectReader->expects(static::any())->method('readPayment')->with($subject)->willReturn($this->paymentDOMock);

        $this->billingAddressMock->expects(static::any())->method('getTelephone')->willReturn($phone);
        $this->paymentMock->expects(static::any())->method('getAdditionalInformation')->with('payer_auth_enroll_reference_id')->willReturn($referenceId);
        $this->requestMock->expects(static::any())->method('getHeader')->willReturnMap([
            ['accept', false, $acceptHeader],
            ['user-agent', false, $userAgentHeader]
        ]);


        $expectedRequest = [
            'payerAuthEnrollService' => [
                'run' => 'true',
                'mobilePhone' => $phone,
                'referenceID' => $referenceId,
                'transactionMode' => 'S',
                'httpAccept' => $acceptHeader,
                'httpUserAgent' => $userAgentHeader,
            ],
        ];

        $this->assertEquals($expectedRequest, $this->payerAuthEnrollBuilder->build($subject));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage 3D Secure initialization is required. Reload the page and try again.
     */
    public function testException()
    {
        $phone = '1234567890';
        $referenceId = null;
        $acceptHeader = '*/*';
        $userAgentHeader = 'Chrome';

        $subject = ['payment' => $this->paymentDOMock];
        $this->subjectReader->expects(static::any())->method('readPayment')->with($subject)->willReturn($this->paymentDOMock);

        $this->billingAddressMock->expects(static::any())->method('getTelephone')->willReturn($phone);
        $this->paymentMock->expects(static::any())->method('getAdditionalInformation')->with('payer_auth_enroll_reference_id')->willReturn($referenceId);

        $expectedRequest = [
            'payerAuthEnrollService' => [
                'run' => 'true',
                'mobilePhone' => $phone,
                'referenceID' => $referenceId,
                'transactionMode' => 'S',
                'httpAccept' => $acceptHeader,
                'httpUserAgent' => $userAgentHeader,
            ],
        ];

        $this->assertEquals($expectedRequest, $this->payerAuthEnrollBuilder->build($subject));
    }
}
