<?php declare(strict_types=1);

namespace CyberSource\ThreeDSecure\Gateway\Command\Cca;

use PHPUnit\Framework\TestCase;

class ProcessTokenTest extends TestCase
{
    /**
     * @var \Magento\Payment\Gateway\Command\Result\ArrayResult|\PHPUnit_Framework_MockObject_MockObject
     */
    private $arrayResultMock;

    /**
     * @var \PHPUnit_Framework_MockObject_Builder_InvocationMocker
     */
    private $validationResultMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $extensionAttributesMock;

    /**
     * @var \Magento\Quote\Api\Data\PaymentInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDOMock;

    /** @var ProcessToken */
    private $processToken;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReader;

    /** @var \Magento\Payment\Gateway\Validator\ValidatorInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $validator;

    /** @var \Magento\Payment\Gateway\Command\Result\ArrayResultFactory | \PHPUnit_Framework_MockObject_MockObject */
    private $arrayResultFactory;

    /** @var \Lcobucci\JWT\Parser | \PHPUnit_Framework_MockObject_MockObject */
    private $jwtParser;

    /** @var \CyberSource\Core\Model\LoggerInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    protected function setUp()
    {
        $this->subjectReader = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);
        $this->validator = $this->createMock(\Magento\Payment\Gateway\Validator\ValidatorInterface::class);
        $this->arrayResultFactory = $this->createMock(\Magento\Payment\Gateway\Command\Result\ArrayResultFactory::class);
        $this->jwtParser = $this->createMock(\Lcobucci\JWT\Parser::class);
        $this->logger = $this->createMock(\CyberSource\Core\Model\LoggerInterface::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Quote\Api\Data\PaymentInterface::class);
        $this->extensionAttributesMock = $this
            ->createPartialMock(
                \Magento\Quote\Api\Data\PaymentExtensionInterface::class,
                ['getCcaResponse', 'setCcaResponse']
            );

        $this->arrayResultMock = $this->createMock(\Magento\Payment\Gateway\Command\Result\ArrayResult::class);
        $this->validationResultMock = $this->createMock(\Magento\Payment\Gateway\Validator\ResultInterface::class);

        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentMock->expects(static::any())->method('getExtensionAttributes')->willReturn($this->extensionAttributesMock);

        $this->processToken = new ProcessToken(
            $this->subjectReader,
            $this->validator,
            $this->arrayResultFactory,
            $this->jwtParser,
            $this->logger
        );
    }

    public function testExecute()
    {
        $subject = [
            'payment' => $this->paymentDOMock
        ];

        $ccaJwt = 'asdasdasd';
        $parsedJwtMock = $this->createMock(\Lcobucci\JWT\Token::class);

        $this->jwtParser->expects(static::any())->method('parse')->with($ccaJwt)->willReturn($parsedJwtMock);

        $this->extensionAttributesMock->expects(static::any())->method('getCcaResponse')->willReturn($ccaJwt);

        $this->subjectReader->expects(static::any())->method('readPayment')->with($subject)->willReturn($this->paymentDOMock);

        $this->validator
            ->expects(static::any())
            ->method('validate')
            ->with(array_merge($subject, ['response' => $parsedJwtMock]))
            ->willReturn($this->validationResultMock)
        ;

        $this->validationResultMock->expects(static::any())->method('isValid')->willReturn(true);
        $this->validationResultMock->expects(static::any())->method('getFailsDescription')->willReturn([]);

        $this->arrayResultFactory->expects(static::any())->method('create')
            ->with([
                'array' => [
                    'token' => $ccaJwt,
                    'parsedToken' => $parsedJwtMock
                ]
            ])
            ->willReturn($this->arrayResultMock)
        ;

        $this->assertEquals($this->arrayResultMock, $this->processToken->execute($subject));
    }


    public function testValidationErrors()
    {
        $subject = [
            'payment' => $this->paymentDOMock
        ];

        $ccaJwt = 'asdasdasd';
        $parsedJwtMock = $this->createMock(\Lcobucci\JWT\Token::class);

        $this->jwtParser->expects(static::any())->method('parse')->with($ccaJwt)->willReturn($parsedJwtMock);

        $this->extensionAttributesMock->expects(static::any())->method('getCcaResponse')->willReturn($ccaJwt);

        $this->subjectReader->expects(static::any())->method('readPayment')->with($subject)->willReturn($this->paymentDOMock);

        $this->validator
            ->expects(static::any())
            ->method('validate')
            ->with(array_merge($subject, ['response' => $parsedJwtMock]))
            ->willReturn($this->validationResultMock)
        ;

        $this->validationResultMock->expects(static::any())->method('isValid')->willReturn(false);
        $this->validationResultMock->expects(static::any())->method('getFailsDescription')->willReturn(['Some error']);

        $this->expectException(\Magento\Payment\Gateway\Command\CommandException::class);
        $this->expectExceptionMessage('Some error');

        $this->assertEquals($this->arrayResultMock, $this->processToken->execute($subject));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Token must be provided
     */
    public function testException()
    {
        $subject = [
            'payment' => $this->paymentDOMock
        ];

        $ccaJwt = null;

        $this->subjectReader->expects(static::any())->method('readPayment')->with($subject)->willReturn($this->paymentDOMock);

        $this->extensionAttributesMock->expects(static::any())->method('getCcaResponse')->willReturn($ccaJwt);

        $this->processToken->execute($subject);
    }
}
