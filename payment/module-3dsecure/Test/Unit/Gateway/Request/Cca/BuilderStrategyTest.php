<?php declare(strict_types = 1);

namespace CyberSource\ThreeDSecure\Gateway\Request\Cca;

use PHPUnit\Framework\TestCase;

class BuilderStrategyTest extends TestCase
{
    /**
     * @var \CyberSource\SecureAcceptance\Helper\RequestDataBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestDataBuilderMock;

    /**
     * @var \CyberSource\ThreeDSecure\Gateway\Config\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configMock;

    /** @var BuilderStrategy */
    private $builderStrategy;

    /** @var \CyberSource\ThreeDSecure\Gateway\Request\Cca\PayerAuthEnrollBuilder | \PHPUnit_Framework_MockObject_MockObject */
    private $enrollBuilder;

    /** @var \CyberSource\ThreeDSecure\Gateway\Request\Cca\PayerAuthValidateBuilder | \PHPUnit_Framework_MockObject_MockObject */
    private $validateBuilder;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReader;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface  | \PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDOMock;

    /**
     * @var \Magento\Payment\Model\InfoInterface | \Magento\Quote\Api\Data\PaymentInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    private $buildSubject = [];

    protected function setUp()
    {
        $this->enrollBuilder = $this->createMock(\CyberSource\ThreeDSecure\Gateway\Request\Cca\PayerAuthEnrollBuilder::class);
        $this->validateBuilder = $this->createMock(\CyberSource\ThreeDSecure\Gateway\Request\Cca\PayerAuthValidateBuilder::class);
        $this->subjectReader = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);
        $this->configMock = $this->createMock(\CyberSource\ThreeDSecure\Gateway\Config\Config::class);
        $this->requestDataBuilderMock = $this->createMock(\CyberSource\SecureAcceptance\Helper\RequestDataBuilder::class);

        $this->builderStrategy = new BuilderStrategy(
            $this->enrollBuilder,
            $this->validateBuilder,
            $this->subjectReader,
            $this->requestDataBuilderMock,
            $this->configMock
        );

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Quote\Model\Quote\Payment::class);
        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);

        $this->subjectReader->expects(static::any())->method('readPayment')->with($this->buildSubject)->willReturn($this->paymentDOMock);
    }

    public function testNotEnabled()
    {
        $this->configMock->expects(static::once())->method('isEnabled')->willReturn(false);
        $this->assertEquals([], $this->builderStrategy->build([]));
    }

    public function testEnrollBuild()
    {

        $subject = [];
        $enrollResult = ['some' => 'data'];

        $this->configMock->expects(static::once())->method('isEnabled')->willReturn(true);
        $this->configMock->method('getEnabledCards')->willReturn(['VI', 'MC', 'JCB']);

        $this->paymentMock->method('getAdditionalInformation')->willReturn('001');
        $this->requestDataBuilderMock->method('getCardType')->with('001')->willReturn('VI');

        $this->paymentMock->expects(static::once())->method('getExtensionAttributes')->willReturn(null);
        $this->enrollBuilder->expects(static::once())->method('build')->willReturn($enrollResult);

        $this->assertEquals($enrollResult, $this->builderStrategy->build($subject));
    }

    public function testValidateBuild()
    {
        $subject = [];
        $validateResult = ['some' => 'data'];

        $this->configMock->expects(static::once())->method('isEnabled')->willReturn(true);
        $this->configMock->method('getEnabledCards')->willReturn(['VI', 'MC', 'JCB']);

        $this->paymentMock->method('getAdditionalInformation')->willReturn('001');
        $this->requestDataBuilderMock->method('getCardType')->with('001')->willReturn('VI');

        $extensionAttributesMock = $this->getMockBuilder(\Magento\Quote\Api\Data\PaymentExtensionInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCcaResponse'])
            ->getMock();

        $extensionAttributesMock->expects(static::once())->method('getCcaResponse')->willReturn('asdasdasd');

        $this->paymentMock->expects(static::once())->method('getExtensionAttributes')->willReturn($extensionAttributesMock);

        $this->validateBuilder->expects(static::once())->method('build')->willReturn($validateResult);

        $this->assertEquals($validateResult, $this->builderStrategy->build($subject));
    }

    public function testCardTypeNotEnabled()
    {
        $subject = [];
        $validateResult = [];

        $this->configMock->expects(static::once())->method('isEnabled')->willReturn(true);
        $this->configMock->method('getEnabledCards')->willReturn(['MC', 'JCB']);

        $this->paymentMock->method('getAdditionalInformation')->willReturn('001');
        $this->requestDataBuilderMock->method('getCardType')->with('001')->willReturn('VI');

        $this->assertEquals($validateResult, $this->builderStrategy->build($subject));
    }
}
