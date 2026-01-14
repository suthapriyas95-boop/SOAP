<?php declare(strict_types = 1);

namespace CyberSource\SecureAcceptance\Gateway\Request\Soap;

use PHPUnit\Framework\TestCase;

class CvnBuilderTest extends TestCase
{
    /**
     * @var \Magento\Vault\Model\PaymentToken|\PHPUnit_Framework_MockObject_MockObject
     */
    private $vaultPaymentTokenMock;

    /**
     * @var \Magento\Payment\Gateway\Data\OrderAdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderAdapterMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDOMock;

    /** @var CvnBuilder */
    private $cvnBuilder;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReaderMock;

    /** @var \CyberSource\SecureAcceptance\Gateway\Config\Config | \PHPUnit_Framework_MockObject_MockObject */
    private $configMock;

    /** @var string */
    private $isAdmin;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);
        $this->configMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Config\Config::class);
        $this->isAdmin = null;

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createPartialMock(\Magento\Payment\Model\InfoInterface::class, [
            'getExtensionAttributes',
            'getVaultPaymentToken',
            'encrypt',
            'decrypt',
            'setAdditionalInformation',
            'hasAdditionalInformation',
            'unsAdditionalInformation',
            'getAdditionalInformation',
            'getMethodInstance',
        ]);
        $this->orderAdapterMock = $this->createMock(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class);

        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);
        $this->paymentDOMock->expects(static::any())->method('getOrder')->willReturn($this->orderAdapterMock);

        $this->vaultPaymentTokenMock = $this->createMock(\Magento\Vault\Model\PaymentToken::class);

        $this->cvnBuilder = new CvnBuilder(
            $this->subjectReaderMock,
            $this->configMock,
            $this->isAdmin
        );
    }

    public function testBuild()
    {
        $cvv = '123';
        $subject = ['payment'=> $this->paymentDOMock];
        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($subject['payment']);


        $this->paymentMock->method('getExtensionAttributes')->willReturnSelf();
        $this->paymentMock->method('getVaultPaymentToken')->willReturn($this->vaultPaymentTokenMock);

        $this->paymentMock->method('getAdditionalInformation')->with('cvv')->willReturn($cvv);

        $this->configMock->method('getValue')->with('enable_cvv')->willReturn(true);

        $this->assertEquals(['card' => ['cvNumber' => $cvv]], $this->cvnBuilder->build($subject));
    }

    public function testBuildAdmin()
    {
        $this->cvnBuilder = new CvnBuilder(
            $this->subjectReaderMock,
            $this->configMock,
            '1'
        );

        $cvv = '123';
        $subject = ['payment'=> $this->paymentDOMock];
        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($subject['payment']);

        $this->paymentMock->method('getExtensionAttributes')->willReturnSelf();
        $this->paymentMock->method('getVaultPaymentToken')->willReturn($this->vaultPaymentTokenMock);

        $this->paymentMock->method('getAdditionalInformation')->with('cvv')->willReturn($cvv);

        $this->configMock->method('getValue')->with('enable_admin_cvv')->willReturn(true);

        $this->assertEquals(['card' => ['cvNumber' => $cvv]], $this->cvnBuilder->build($subject));
    }

    public function testBuildNoToken()
    {
        $cvv = '123';
        $subject = ['payment'=> $this->paymentDOMock];
        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($subject['payment']);

        $this->paymentMock->method('getExtensionAttributes')->willReturnSelf();
        $this->paymentMock->method('getVaultPaymentToken')->willReturn(null);

        $this->paymentMock->method('getAdditionalInformation')->with('cvv')->willReturn($cvv);

        $this->configMock->method('getValue')->with('enable_admin_cvv')->willReturn(true);

        $this->assertEquals([], $this->cvnBuilder->build($subject));
    }

    public function testBuildNoCvv()
    {
        $cvv = null;
        $subject = ['payment'=> $this->paymentDOMock];
        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($subject['payment']);

        $this->paymentMock->method('getExtensionAttributes')->willReturnSelf();
        $this->paymentMock->method('getVaultPaymentToken')->willReturn($this->vaultPaymentTokenMock);

        $this->paymentMock->method('getAdditionalInformation')->with('cvv')->willReturn($cvv);

        $this->configMock->method('getValue')->with('enable_admin_cvv')->willReturn(true);

        $this->assertEquals([], $this->cvnBuilder->build($subject));
    }
}
