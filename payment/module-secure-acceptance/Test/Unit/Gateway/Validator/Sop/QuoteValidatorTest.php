<?php declare(strict_types=1);

namespace CyberSource\SecureAcceptance\Gateway\Validator\Sop;

use PHPUnit\Framework\TestCase;

class QuoteValidatorTest extends TestCase
{
    /**
     * @var \Magento\Payment\Gateway\Validator\ResultInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resultMock;

    /** @var QuoteValidator */
    private $quoteValidator;

    /** @var \Magento\Payment\Gateway\Validator\ResultInterfaceFactory | \PHPUnit_Framework_MockObject_MockObject */
    private $resultFactoryMock;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReaderMock;

    protected function setUp()
    {
        $this->resultFactoryMock = $this->createMock(\Magento\Payment\Gateway\Validator\ResultInterfaceFactory::class);
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);

        $this->resultMock = $this->createMock(\Magento\Payment\Gateway\Validator\ResultInterface::class);

        $this->quoteValidator = new QuoteValidator(
            $this->resultFactoryMock,
            $this->subjectReaderMock
        );
    }

    public function testValidatePass()
    {

        $quoteId = '3';
        $response = ['some' => 'response', 'req_merchant_secure_data1' => $quoteId];

        $paymentDoMock = $this->createPartialMock(
            \Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class,
            ['getOrder', 'getPayment', 'getId']
        );

        $paymentDoMock->method('getOrder')->willReturnSelf();
        $paymentDoMock->method('getId')->willReturn($quoteId);

        $subject = ['response' => $response, 'payment' => $paymentDoMock];

        $this->subjectReaderMock->method('readResponse')->willReturn($subject['response']);
        $this->subjectReaderMock->method('readPayment')->willReturn($subject['payment']);

        $this->resultFactoryMock
            ->expects(static::once())
            ->method('create')
            ->with([
                'isValid' => true,
                'failsDescription' => []
            ])
            ->willReturn($this->resultMock);

        $this->assertEquals($this->resultMock, $this->quoteValidator->validate($subject));
    }


    public function testValidateFail()
    {

        $quoteId = '3';
        $response = ['some' => 'response', 'req_merchant_secure_data1' => $quoteId];

        $paymentDoMock = $this->createPartialMock(
            \Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class,
            ['getOrder', 'getPayment', 'getId']
        );

        $paymentDoMock->method('getOrder')->willReturnSelf();
        $paymentDoMock->method('getId')->willReturn('4');

        $subject = ['response' => $response, 'payment' => $paymentDoMock];

        $this->subjectReaderMock->method('readResponse')->willReturn($subject['response']);
        $this->subjectReaderMock->method('readPayment')->willReturn($subject['payment']);

        $this->resultFactoryMock
            ->expects(static::once())
            ->method('create')
            ->with([
                'isValid' => false,
                'failsDescription' => [__('Incorrect Quote ID')]
            ])
            ->willReturn($this->resultMock);

        $this->assertEquals($this->resultMock, $this->quoteValidator->validate($subject));
    }
}
