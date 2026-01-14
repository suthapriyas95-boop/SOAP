<?php declare(strict_types=1);

namespace CyberSource\SecureAcceptance\Gateway\Request\Sop;

use PHPUnit\Framework\TestCase;

class TransactionTypeBuilderTest extends TestCase
{
    /**
     * @var \Magento\Payment\Model\MethodInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $methodInstanceMock;

    /** @var TransactionTypeBuilder */
    private $transactionTypeBuilder;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReader;

    /**
     * @var \Magento\Sales\Model\Order\Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDOMock;

    protected function setUp()
    {
        $this->subjectReader = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Sales\Model\Order\Payment::class);

        $this->methodInstanceMock = $this->createMock(\Magento\Payment\Model\MethodInterface::class);
        $this->paymentMock->method('getMethodInstance')->willReturn($this->methodInstanceMock);

        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);

        $this->transactionTypeBuilder = new TransactionTypeBuilder(
            $this->subjectReader
        );
    }

    /**
     * @dataProvider dataProviderTestBuild
     * 
     * @param $action
     * @param $expected
     */
    public function testBuild($action, $saveToken, $expected)
    {
        $subject = [];

        $this->subjectReader->method('readPayment')->with($subject)->willReturn($this->paymentDOMock);

        $this->methodInstanceMock->method('getConfigPaymentAction')->willReturn($action);

        $this->paymentMock->method('getAdditionalInformation')->with('is_active_payment_token_enabler')->willReturn($saveToken);

        $this->assertEquals($expected, $this->transactionTypeBuilder->build($subject));
    }

    public function dataProviderTestBuild()
    {
        return [
            [
                'action' => 'authorize',
                'saveToken' => false,
                'expected' => [
                    'transaction_type' => 'authorization',
                ],
            ],
            [
                'action' => 'authorize',
                'saveToken' => true,
                'expected' => [
                    'transaction_type' => 'authorization,create_payment_token',
                ],
            ],
            [
                'action' => 'authorize_capture',
                'saveToken' => false,
                'expected' => [
                    'transaction_type' => 'sale',
                ],
            ],
            [
                'action' => 'authorize_capture',
                'saveToken' => true,
                'expected' => [
                    'transaction_type' => 'sale,create_payment_token',
                ],
            ],
        ];

    }

}
