<?php declare(strict_types = 1);

namespace CyberSource\SecureAcceptance\Gateway\Validator\Flex;

use PHPUnit\Framework\TestCase;

class GenerateKeyResponseValidatorTest extends TestCase
{
    /**
     * @var \CyberSource\SecureAcceptance\Model\Jwt\JwtProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $jwtProcessorMock;

    /** @var GenerateKeyResponseValidator */
    private $generateKeyResponseValidator;

    /** @var \Magento\Payment\Gateway\Validator\ResultInterfaceFactory | \PHPUnit_Framework_MockObject_MockObject */
    private $resultFactoryMock;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReaderMock;

    /**
     * @var \Magento\Payment\Gateway\Validator\ResultInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resultMock;

    protected function setUp()
    {
        $this->resultFactoryMock = $this->createMock(\Magento\Payment\Gateway\Validator\ResultInterfaceFactory::class);
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);
        $this->resultMock = $this->createMock(\Magento\Payment\Gateway\Validator\ResultInterface::class);
        $this->jwtProcessorMock = $this->createMock(\CyberSource\SecureAcceptance\Model\Jwt\JwtProcessorInterface::class);


        $this->generateKeyResponseValidator = new GenerateKeyResponseValidator(
            $this->resultFactoryMock,
            $this->jwtProcessorMock,
            $this->subjectReaderMock
        );
    }

    /**
     *
     * @dataProvider dataProviderTestValidate
     * @param $response
     * @param $expectedResult
     */
    public function testValidate($response, $expectedResult)
    {
        $validationSubject = [
            'response' => $response,
        ];

        $this->subjectReaderMock->expects(static::any())->method('readResponse')->with($validationSubject)->willReturn($validationSubject['response']);

        $this->resultFactoryMock->expects(static::any())->method('create')->with(
            $expectedResult
        )->willReturn($this->resultMock);

        static::assertEquals($this->resultMock, $this->generateKeyResponseValidator->validate($validationSubject));
    }

    public function dataProviderTestValidate()
    {
        return [
            [
                'response' => [],
                'expectedResult' => [
                    'isValid' => false,
                    'failsDescription' => [__('JWT is empty.')]
                ],
            ],
            [
                'response' => [
                    'keyId' => 'somejwt',
                ],
                'expectedResult' => [
                    'isValid' => true,
                    'failsDescription' => []
                ],
            ],
        ];
    }

}
