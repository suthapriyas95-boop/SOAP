<?php declare(strict_types = 1);

namespace CyberSource\SecureAcceptance\Gateway\Validator\Sop;

use PHPUnit\Framework\TestCase;

class TransactionTypeValidatorTest extends TestCase
{
    /**
     * @var \Magento\Payment\Gateway\Validator\ResultInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resultMock;

    /** @var TransactionTypeValidator */
    private $transactionTypeValidator;

    /** @var \Magento\Payment\Gateway\Validator\ResultInterfaceFactory | \PHPUnit_Framework_MockObject_MockObject */
    private $resultFactoryMock;

    /** @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader | \PHPUnit_Framework_MockObject_MockObject */
    private $subjectReaderMock;

    protected function setUp()
    {
        $this->resultFactoryMock = $this->createMock(\Magento\Payment\Gateway\Validator\ResultInterfaceFactory::class);
        $this->subjectReaderMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader::class);

        $this->resultMock = $this->createMock(\Magento\Payment\Gateway\Validator\ResultInterface::class);

        $this->transactionTypeValidator = new TransactionTypeValidator(
            $this->resultFactoryMock,
            $this->subjectReaderMock
        );
    }

    public function testValidatePass()
    {
        $response = ['req_transaction_type' => 'create_payment_token'];
        $subject = ['response' => $response];
        $this->subjectReaderMock->method('readResponse')->with($subject)->willReturn($subject['response']);

        $this->resultFactoryMock
            ->expects(static::once())
            ->method('create')
            ->with([
                'isValid' => true,
                'failsDescription' => []
            ])
            ->willReturn($this->resultMock)
        ;

        $this->assertEquals($this->resultMock, $this->transactionTypeValidator->validate($subject));
    }

    public function testValidateFail()
    {
        $response = ['req_transaction_type' => 'wrong'];
        $subject = ['response' => $response];
        $this->subjectReaderMock->method('readResponse')->with($subject)->willReturn($subject['response']);

        $this->resultFactoryMock
            ->expects(static::once())
            ->method('create')
            ->with([
                'isValid' => false,
                'failsDescription' => [__('Invalid Transaction Type')]
            ])
            ->willReturn($this->resultMock)
        ;

        $this->assertEquals($this->resultMock, $this->transactionTypeValidator->validate($subject));
    }
}
