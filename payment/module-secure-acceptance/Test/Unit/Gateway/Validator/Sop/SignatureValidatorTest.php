<?php declare(strict_types=1);

namespace CyberSource\SecureAcceptance\Gateway\Validator\Sop;

use PHPUnit\Framework\TestCase;

class SignatureValidatorTest extends TestCase
{
    /**
     * @var \Magento\Payment\Gateway\Validator\ResultInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resultMock;

    /** @var SignatureValidator */
    private $signatureValidator;

    /** @var \Magento\Payment\Gateway\Validator\ResultInterfaceFactory | \PHPUnit_Framework_MockObject_MockObject */
    private $resultFactoryMock;

    /** @var \CyberSource\SecureAcceptance\Helper\RequestDataBuilder | \PHPUnit_Framework_MockObject_MockObject */
    private $requestDataBuilderMock;

    /** @var \CyberSource\SecureAcceptance\Gateway\Config\Config | \PHPUnit_Framework_MockObject_MockObject */
    private $configMock;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReaderMock;

    protected function setUp()
    {
        $this->resultFactoryMock = $this->createMock(\Magento\Payment\Gateway\Validator\ResultInterfaceFactory::class);
        $this->requestDataBuilderMock = $this->createMock(\CyberSource\SecureAcceptance\Helper\RequestDataBuilder::class);
        $this->configMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Config\Config::class);
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);

        $this->resultMock = $this->createMock(\Magento\Payment\Gateway\Validator\ResultInterface::class);

        $this->signatureValidator = new SignatureValidator(
            $this->resultFactoryMock,
            $this->requestDataBuilderMock,
            $this->configMock,
            $this->subjectReaderMock
        );
    }

    public function testValidatePass()
    {

        $response = ['some' => 'response'];
        $subject = ['response' => $response];
        $key = '12312312';

        $this->subjectReaderMock->method('readResponse')->with($subject)->willReturn($subject['response']);

        $this->configMock->method('isSilent')->willReturn(true);
        $this->configMock->method('getSopSecretKey')->willReturn($key);

        $this->requestDataBuilderMock
            ->expects(static::any())
            ->method('validateSignature')
            ->with($response, $key)
            ->willReturn(true)
        ;

        $this->resultFactoryMock
            ->expects(static::once())
            ->method('create')
            ->with([
                'isValid' => true,
                'failsDescription' => []
            ])
            ->willReturn($this->resultMock);

        $this->assertEquals($this->resultMock, $this->signatureValidator->validate($subject));
    }

    public function testValidateFail()
    {

        $response = ['some' => 'response'];
        $subject = ['response' => $response];
        $key = '12312312';

        $this->subjectReaderMock->method('readResponse')->with($subject)->willReturn($subject['response']);

        $this->configMock->method('isSilent')->willReturn(true);
        $this->configMock->method('getSopSecretKey')->willReturn($key);

        $this->requestDataBuilderMock->expects(static::any())->method('validateSignature')->with(
            $response,
            $key
        )->willReturn(false);

        $this->resultFactoryMock
            ->expects(static::once())
            ->method('create')
            ->with([
                'isValid' => false,
                'failsDescription' => [__('Invalid Signature')]
            ])
            ->willReturn($this->resultMock);

        $this->assertEquals($this->resultMock, $this->signatureValidator->validate($subject));
    }
}
