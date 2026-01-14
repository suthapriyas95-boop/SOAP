<?php declare(strict_types = 1);

namespace CyberSource\SecureAcceptance\Gateway\Validator\Flex;

use PHPUnit\Framework\TestCase;

class MicroformResponseValidatorTest extends TestCase
{
    /**
     * @var \CyberSource\SecureAcceptance\Model\Jwt\JwtProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $jwtProcessorMock;

    /** @var MicroformResponseValidator */
    private $microformResponseValidator;

    /** @var \Magento\Payment\Gateway\Validator\ResultInterfaceFactory | \PHPUnit_Framework_MockObject_MockObject */
    private $resultFactoryMock;

    /** @var \CyberSource\SecureAcceptance\Gateway\Config\Config | \PHPUnit_Framework_MockObject_MockObject */
    private $configMock;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReaderMock;

    /**
     * @var SignatureValidator\ValidatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $validatorMock;

    /** @var bool */
    private $isAdminHtmlMock;

    /**
     * @var \Magento\Sales\Model\Order\Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    /**
     * @var \Magento\Payment\Gateway\Validator\ResultInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resultMock;

    protected function setUp()
    {
        $this->resultFactoryMock = $this->createMock(\Magento\Payment\Gateway\Validator\ResultInterfaceFactory::class);
        $this->resultMock = $this->createMock(\Magento\Payment\Gateway\Validator\ResultInterface::class);
        $this->configMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Config\Config::class);
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);
        $this->jwtProcessorMock = $this->createMock(\CyberSource\SecureAcceptance\Model\Jwt\JwtProcessorInterface::class);
        $this->validatorMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Validator\Flex\SignatureValidator\ValidatorInterface::class);
        $this->isAdminHtmlMock = false;

        $this->paymentMock = $this->createMock(\Magento\Sales\Model\Order\Payment::class);

        $this->microformResponseValidator = new MicroformResponseValidator(
            $this->resultFactoryMock,
            $this->configMock,
            $this->subjectReaderMock,
            $this->jwtProcessorMock,
            $this->validatorMock,
            $this->isAdminHtmlMock
        );
    }


    /**
     * @dataProvider dataProviderTestValidate
     *
     * @param $methodCode
     * @param $isMicroform
     * @param $additionalData
     * @param $signatureValidationResult
     * @param $expectedValidationResult
     */
    public function testValidate($methodCode, $isMicroform, $additionalData, $signatureValidationResult, $expectedValidationResult, $expectedExceptionMessage = null)
    {

        $validationSubject = ['payment' => $this->paymentMock];

        $this->paymentMock->expects(static::any())->method('getMethod')->willReturn($methodCode);

        $this->paymentMock->expects(static::any())->method('getAdditionalInformation')->willReturnMap(
            [
                [null, $additionalData],
                ['flexJwt', $additionalData['flexJwt']],
                ['microformPublicKey', $additionalData['microformPublicKey']],
            ]
        );

        $this->configMock->expects(static::any())->method('isMicroform')->willReturn($isMicroform);

        if ($expectedExceptionMessage) {
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        $this->jwtProcessorMock
            ->expects(static::any())
            ->method('verifySignature')
            ->with( $additionalData['flexJwt'], $additionalData['microformPublicKey'])
            ->willReturn($signatureValidationResult)
        ;

        $this->resultFactoryMock
            ->expects(static::any())
            ->method('create')
            ->with($expectedValidationResult)
            ->willReturn($this->resultMock)
        ;

        static::assertEquals($this->resultMock, $this->microformResponseValidator->validate($validationSubject));
    }

    public function dataProviderTestValidate()
    {
        return [
            [
                'methodCode' => 'chcybersource',
                'isMicroform' => true,
                'additionalData' => [
                    'flexJwt' => 'someJWT',
                    'microformPublicKey'=> 'someRASpubkey',
                ],
                'signatureValidationResult' => true,
                'expectedValidationResult' => [
                    'isValid' => true,
                    'failsDescription' => []
                ],
            ],
            [
                'methodCode' => 'chcybersource',
                'isMicroform' => false,
                'additionalData' => [
                    'flexJwt' => 'someJWT',
                    'microformPublicKey'=> 'someRASpubkey',
                ],
                'signatureValidationResult' => false,
                'expectedValidationResult' => [
                    'isValid' => true,
                    'failsDescription' => []
                ],
            ],
            [
                'methodCode' => 'someothermethod',
                'isMicroform' => true,
                'additionalData' => [
                    'flexJwt' => 'someJWT',
                    'microformPublicKey'=> 'someRASpubkey',
                ],
                'signatureValidationResult' => false,
                'expectedValidationResult' => [
                    'isValid' => true,
                    'failsDescription' => []
                ],
            ],
            [
                'methodCode' => 'chcybersource',
                'isMicroform' => true,
                'additionalData' => [
                    'flexJwt' => 'someJWT',
                    'microformPublicKey'=> 'someRASpubkey',
                ],
                'signatureValidationResult' => false,
                'expectedValidationResult' => [
                    'isValid' => false,
                    'failsDescription' => ['Invalid token signature.']
                ],
            ],
        ];
    }

}
