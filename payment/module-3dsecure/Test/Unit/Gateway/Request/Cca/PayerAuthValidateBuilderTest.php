<?php declare(strict_types = 1);

namespace CyberSource\ThreeDSecure\Gateway\Request\Cca;

use PHPUnit\Framework\TestCase;

class PayerAuthValidateBuilderTest extends TestCase
{
    /**
     * @var \Lcobucci\JWT\Token|\PHPUnit_Framework_MockObject_MockObject
     */
    private $jwtMock;

    /**
     * @var \Magento\Payment\Gateway\Command\Result\ArrayResult|\PHPUnit_Framework_MockObject_MockObject
     */
    private $commandResultMock;

    /**
     * @var \Magento\Payment\Gateway\Data\AddressAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $billingAddressMock;

    /**
     * @var \Magento\Payment\Gateway\Data\OrderAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderMock;

    /**
     * @var \Magento\Payment\Model\InfoInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDOMock;

    /**
     * @var \Magento\Payment\Gateway\CommandInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $commandMock;

    /** @var PayerAuthValidateBuilder */
    private $payerAuthValidateBuilder;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReader;

    /** @var \Magento\Payment\Gateway\Command\CommandPoolInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $commandPoolMock;

    protected function setUp()
    {
        $this->subjectReader = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);
        $this->commandPoolMock = $this->createMock(\Magento\Payment\Gateway\Command\CommandPoolInterface::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Payment\Model\InfoInterface::class);
        $this->orderMock = $this->createMock(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class);
        $this->billingAddressMock = $this->createMock(\Magento\Payment\Gateway\Data\AddressAdapterInterface::class);

        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentDOMock->expects(static::any())->method('getOrder')->willReturn($this->orderMock);
        $this->orderMock->expects(static::any())->method('getBillingAddress')->willReturn($this->billingAddressMock);

        $this->commandMock = $this->createMock(\Magento\Payment\Gateway\CommandInterface::class);
        $this->commandResultMock = $this->createMock(\Magento\Payment\Gateway\Command\Result\ArrayResult::class);

        $this->jwtMock = $this->createMock(\Lcobucci\JWT\Token::class);

        $this->payerAuthValidateBuilder = new PayerAuthValidateBuilder(
            $this->subjectReader,
            $this->commandPoolMock
        );
    }

    public function testBuild()
    {

        $subject = [];

        $processorTransactionId = '123123123123';
        $commandResult = [
            'parsedToken' => $this->jwtMock,
        ];

        $this->commandPoolMock->expects(static::any())->method('get')->with('processToken')->willReturn($this->commandMock);

        $this->commandMock->expects(static::atLeastOnce())->method('execute')->with($subject)->willReturn($this->commandResultMock);
        $this->commandResultMock->expects(static::any())->method('get')->willReturn($commandResult);

        $payload = json_decode(json_encode(
            [
                'Payment' => [
                    'ProcessorTransactionId' => $processorTransactionId,
                ]
            ]
        ));

        $this->jwtMock->expects(static::any())->method('getClaim')->with('Payload')->willReturn($payload);

        $expectedRequest = [
            'payerAuthValidateService' => [
                'run' => 'true',
                'authenticationTransactionID' => $processorTransactionId,
            ]
        ];

        $this->assertEquals($expectedRequest, $this->payerAuthValidateBuilder->build($subject));
    }
}
