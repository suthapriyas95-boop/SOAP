<?php declare(strict_types=1);

namespace CyberSource\ThreeDSecure\Gateway\Command\Cca;

use PHPUnit\Framework\TestCase;

class CreateTokenTest extends TestCase
{
    /**
     * @var \Magento\Payment\Gateway\Command\Result\ArrayResult|\PHPUnit_Framework_MockObject_MockObject
     */
    private $arrayResultMock;

    /**
     * @var \Magento\Payment\Gateway\Command\ResultInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $commandResultMock;

    /**
     * @var \Magento\Payment\Gateway\Data\OrderAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObject|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDoMock;

    /**
     * @var \CyberSource\ThreeDSecure\Gateway\Request\Jwt\TokenBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tokenBuilderInterfaceMock;

    /**
     * @var \Magento\Payment\Gateway\CommandInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subscriptionRetrieveCommandMock;

    /** @var CreateToken */
    private $createToken;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReader;

    /** @var \Magento\Payment\Gateway\Request\BuilderInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $requestBuilder;

    /** @var \Magento\Payment\Gateway\Command\Result\ArrayResultFactory | \PHPUnit_Framework_MockObject_MockObject */
    private $arrayResultFactory;

    /** @var \Lcobucci\JWT\BuilderFactory | \PHPUnit_Framework_MockObject_MockObject */
    private $builderFactory;

    /** @var \Lcobucci\JWT\Signer\Hmac\Sha256 | \PHPUnit_Framework_MockObject_MockObject */
    private $sha256;

    /** @var \Magento\Framework\Math\Random | \PHPUnit_Framework_MockObject_MockObject */
    private $random;

    /** @var \Lcobucci\JWT\Signer\KeyFactory | \PHPUnit_Framework_MockObject_MockObject */
    private $keyFactory;

    /** @var \CyberSource\ThreeDSecure\Gateway\Config\Config | \PHPUnit_Framework_MockObject_MockObject */
    private $configMock;

    /** @var \CyberSource\Core\Model\LoggerInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    protected function setUp()
    {
        $this->subjectReader = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);
        $this->requestBuilder = $this->createMock(\Magento\Payment\Gateway\Request\BuilderInterface::class);
        $this->arrayResultFactory = $this->createMock(\Magento\Payment\Gateway\Command\Result\ArrayResultFactory::class);
        $this->random = $this->createMock(\Magento\Framework\Math\Random::class);
        $this->configMock = $this->createMock(\CyberSource\ThreeDSecure\Gateway\Config\Config::class);
        $this->logger = $this->createMock(\CyberSource\Core\Model\LoggerInterface::class);
        $this->subscriptionRetrieveCommandMock = $this->createMock(\Magento\Payment\Gateway\CommandInterface::class);
        $this->tokenBuilderInterfaceMock = $this->createMock(\CyberSource\ThreeDSecure\Gateway\Request\Jwt\TokenBuilderInterface::class);

        $this->paymentDoMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObject::class);
        $this->paymentMock = $this->createMock(\Magento\Payment\Model\InfoInterface::class);
        $this->subscriptionRetrieveCommandMock = $this->createMock(\Magento\Payment\Gateway\CommandInterface::class);
        $this->commandResultMock = $this->createMock(\Magento\Payment\Gateway\Command\ResultInterface::class);
        $this->arrayResultMock = $this->createMock(\Magento\Payment\Gateway\Command\Result\ArrayResult::class);

        $this->paymentDoMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);

        $this->createToken = new CreateToken(
            $this->subjectReader,
            $this->requestBuilder,
            $this->arrayResultFactory,
            $this->random,
            $this->configMock,
            $this->subscriptionRetrieveCommandMock,
            $this->logger,
            $this->tokenBuilderInterfaceMock
        );
    }

    public function testExecuteNoBin()
    {
        $commandSubject = [
            'payment' => $this->paymentDoMock,
        ];

        $jwtParams = [
            'referenceId' => 'ref_asdasdasd',
            'payload' => 'asdasdasd',
            'apiKey' => 'asdasd',
            'orgUnitId' => '123123',
            'apiId' => '12312323123',
        ];

        $token = '1231wpdv[qpmjppvnip34n]vp3np]v34';

        $this->subjectReader->expects(static::any())->method('readPayment')->with($commandSubject)->willReturn($this->paymentDoMock);

        $this->commandResultMock->expects(static::any())->method('get')->willReturn([]);
        $this->subscriptionRetrieveCommandMock->expects(static::once())->method('execute')->with($commandSubject)->willReturn($this->commandResultMock);

        $this->requestBuilder->expects(static::once())->method('build')->with($commandSubject)->willReturn($jwtParams['payload']);
        $this->random->expects(static::once())->method('getUniqueHash')->with('ref_')->willReturn($jwtParams['referenceId']);

        $this->configMock->expects(static::any())->method('getOrgUnitId')->willReturn($jwtParams['orgUnitId']);
        $this->configMock->expects(static::any())->method('getApiId')->willReturn($jwtParams['apiId']);
        $this->configMock->expects(static::any())->method('getApiKey')->willReturn($jwtParams['apiKey']);

        $this->paymentMock
            ->expects(static::once())
            ->method('setAdditionalInformation')
            ->with(
                \CyberSource\ThreeDSecure\Gateway\Command\Cca\CreateToken::PAYER_AUTH_ENROLL_REFERENCE_ID,
                $jwtParams['referenceId']
            );

        $this->tokenBuilderInterfaceMock
            ->expects(static::once())
            ->method('buildToken')
            ->with(
                $jwtParams['referenceId'],
                $jwtParams['payload'],
                $jwtParams['orgUnitId'],
                $jwtParams['apiId'],
                $jwtParams['apiKey']
            )
            ->willReturn($token)
        ;

        $this->arrayResultFactory
            ->expects(static::once())
            ->method('create')
            ->with(['array' => ['token' => $token]])
            ->willReturn($this->arrayResultMock)
        ;

        $this->assertEquals($this->arrayResultMock, $this->createToken->execute($commandSubject));
    }

    public function testExecuteWithBin()
    {
        $commandSubject = [
            'payment' => $this->paymentDoMock,
        ];

        $cardBin = '411111';
        $cardNumber = '4111111111111111';

        $commandSubjectWithBin = array_merge($commandSubject, [
            'cardBin' => $cardBin
        ]);

        $jwtParams = [
            'referenceId' => 'ref_asdasdasd',
            'payload' => 'asdasdasd',
            'apiKey' => 'asdasd',
            'orgUnitId' => '123123',
            'apiId' => '12312323123',
        ];

        $token = '1231wpdv[qpmjppvnip34n]vp3np]v34';

        $this->subjectReader->expects(static::any())->method('readPayment')->with($commandSubject)->willReturn($this->paymentDoMock);

        $this->commandResultMock->expects(static::any())->method('get')->willReturn([
            'cardAccountNumber' => $cardNumber,
            'cardType' => '001',
        ]);
        $this->subscriptionRetrieveCommandMock->expects(static::once())->method('execute')->with($commandSubject)->willReturn($this->commandResultMock);

        $this->requestBuilder->expects(static::once())->method('build')->with($commandSubjectWithBin)->willReturn($jwtParams['payload']);
        $this->random->expects(static::once())->method('getUniqueHash')->with('ref_')->willReturn($jwtParams['referenceId']);

        $this->configMock->expects(static::any())->method('getOrgUnitId')->willReturn($jwtParams['orgUnitId']);
        $this->configMock->expects(static::any())->method('getApiId')->willReturn($jwtParams['apiId']);
        $this->configMock->expects(static::any())->method('getApiKey')->willReturn($jwtParams['apiKey']);

        $this->paymentMock
            ->expects(static::exactly(2))
            ->method('setAdditionalInformation')
            ->withConsecutive(
                [
                    'cardType',
                    '001'
                ],
                [
                    \CyberSource\ThreeDSecure\Gateway\Command\Cca\CreateToken::PAYER_AUTH_ENROLL_REFERENCE_ID,
                    $jwtParams['referenceId']
                ]
            );

        $this->tokenBuilderInterfaceMock
            ->expects(static::once())
            ->method('buildToken')
            ->with(
                $jwtParams['referenceId'],
                $jwtParams['payload'],
                $jwtParams['orgUnitId'],
                $jwtParams['apiId'],
                $jwtParams['apiKey']
            )
            ->willReturn($token)
        ;

        $this->arrayResultFactory
            ->expects(static::once())
            ->method('create')
            ->with(['array' => ['token' => $token]])
            ->willReturn($this->arrayResultMock)
        ;

        $this->assertEquals($this->arrayResultMock, $this->createToken->execute($commandSubject));
    }
}
