<?php declare(strict_types = 1);

namespace CyberSource\SecureAcceptance\Gateway\Response\Soap;

use PHPUnit\Framework\TestCase;

class DecisionManagerHandlerTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDOMock;

    /** @var DecisionManagerHandler */
    private $decisionManagerHandler;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReaderMock;

    protected function setUp()
    {
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);

        $this->paymentDOMock = $this->createMock(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class);
        $this->paymentMock = $this->createMock(\Magento\Sales\Model\Order\Payment::class);

        $this->paymentDOMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);

        $this->decisionManagerHandler = new DecisionManagerHandler(
            $this->subjectReaderMock
        );
    }


    /**
     * @param $reasonCode
     * @param $isFraud
     * @dataProvider dataProviderTestHandle
     */
    public function testHandle($reasonCode, $isFraud)
    {

        $subject = ['payment' => $this->paymentDOMock];
        $response = ['reasonCode'=> $reasonCode];

        $this->subjectReaderMock->method('readPayment')->with($subject)->willReturn($subject['payment']);

        $this->paymentMock
            ->expects($isFraud ? static::once() : static::never())
            ->method('setIsFraudDetected')
            ->with(true)
        ;

        $this->paymentMock
            ->expects($isFraud ? static::once() : static::never())
            ->method('setIsTransactionPending')
            ->with(true)
        ;

        $this->decisionManagerHandler->handle($subject, $response);
    }

    public function dataProviderTestHandle()
    {
        return [
            ['reasonCode' => 100, false],
            ['reasonCode' => 480, true],
        ];
    }
}
