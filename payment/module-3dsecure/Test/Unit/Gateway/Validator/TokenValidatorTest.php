<?php declare(strict_types=1);

namespace CyberSource\ThreeDSecure\Gateway\Validator;

use PHPUnit\Framework\TestCase;

class TokenValidatorTest extends TestCase
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dateTime;

    /**
     * @var \Lcobucci\JWT\Token|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tokenMock;

    /**
     * @var \Magento\Payment\Gateway\Validator\ResultInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $validationResultMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject | \CyberSource\ThreeDSecure\Gateway\Config\Config
     */
    private $configMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject | \Lcobucci\JWT\Signer\Hmac\Sha256
     */
    private $shaMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject | \Lcobucci\JWT\ValidationData
     */
    private $validationDataMock;

    /** @var TokenValidator */
    private $subject;

    /** @var \Magento\Payment\Gateway\Validator\ResultInterfaceFactory | \PHPUnit_Framework_MockObject_MockObject */
    private $resultFactory;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReader;

    protected function setUp()
    {
        $this->resultFactory = $this->createMock(\Magento\Payment\Gateway\Validator\ResultInterfaceFactory::class);
        $this->validationResultMock = $this->createMock(\Magento\Payment\Gateway\Validator\ResultInterface::class);
        $this->subjectReader = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);
        $this->dateTime = $this->createMock(\Magento\Framework\Stdlib\DateTime\DateTime:: class);

        $this->configMock = $this->createMock(\CyberSource\ThreeDSecure\Gateway\Config\Config::class);
        $this->shaMock = $this->createMock(\Lcobucci\JWT\Signer\Hmac\Sha256::class);
        $this->validationDataMock = $this->createMock(\Lcobucci\JWT\ValidationData::class);
        $this->tokenMock = $this->createMock(\Lcobucci\JWT\Token::class);

        $this->subject = new TokenValidator(
            $this->resultFactory,
            $this->subjectReader,
            $this->configMock,
            $this->shaMock,
            $this->validationDataMock,
            $this->dateTime
        );
    }

    public function testValid()
    {
        $apiKey = 'asd';
        $subject = ['response' => $this->tokenMock];

        $this->configMock->expects(static::any())->method('getApiKey')->willReturn($apiKey);
        $this->tokenMock->expects(static::once())->method('verify')->with($this->shaMock, $apiKey)->willReturn(true);

        $currentTimestamp = 36000;

        $this->dateTime->expects(static::any())->method('gmtTimestamp')->willReturn($currentTimestamp);

        $this->validationDataMock->expects(static::once())->method('setCurrentTime')->with($currentTimestamp);
        $this->tokenMock->expects(static::once())->method('validate')->with($this->validationDataMock)->willReturn(true);

        $this->resultFactory
            ->expects(static::once())
            ->method('create')
            ->with([
                'isValid' => true,
                'failsDescription' => []
            ])
            ->willReturn($this->validationResultMock);


        $this->assertEquals($this->validationResultMock, $this->subject->validate($subject));
        ;
    }

    public function testInvalidSignature()
    {
        $apiKey = 'asd';
        $subject = ['response' => $this->tokenMock];

        $this->configMock->expects(static::any())->method('getApiKey')->willReturn($apiKey);
        $this->tokenMock->expects(static::once())->method('verify')->with($this->shaMock, $apiKey)->willReturn(false);

        $currentTimestamp = 36000;

        $this->dateTime->expects(static::any())->method('gmtTimestamp')->willReturn($currentTimestamp);

        $this->validationDataMock->expects(static::any())->method('setCurrentTime')->with($currentTimestamp);
        $this->tokenMock->expects(static::any())->method('validate')->with($this->validationDataMock)->willReturn(true);

        $this->resultFactory
            ->expects(static::once())
            ->method('create')
            ->with([
                'isValid' => false,
                'failsDescription' => ['Invalid JWT token']
            ])
            ->willReturn($this->validationResultMock);


        $this->assertEquals($this->validationResultMock, $this->subject->validate($subject));
        ;
    }

    public function testInvalidExpDate()
    {
        $apiKey = 'asd';
        $subject = ['response' => $this->tokenMock];

        $this->configMock->expects(static::any())->method('getApiKey')->willReturn($apiKey);
        $this->tokenMock->expects(static::once())->method('verify')->with($this->shaMock, $apiKey)->willReturn(true);

        $currentTimestamp = 36000;

        $this->dateTime->expects(static::any())->method('gmtTimestamp')->willReturn($currentTimestamp);

        $this->validationDataMock->expects(static::once())->method('setCurrentTime')->with($currentTimestamp);
        $this->tokenMock->expects(static::once())->method('validate')->with($this->validationDataMock)->willReturn(false);

        $this->resultFactory
            ->expects(static::once())
            ->method('create')
            ->with([
                'isValid' => false,
                'failsDescription' => ['JWT token has expired']
            ])
            ->willReturn($this->validationResultMock);

        $this->assertEquals($this->validationResultMock, $this->subject->validate($subject));
        ;
    }
}
