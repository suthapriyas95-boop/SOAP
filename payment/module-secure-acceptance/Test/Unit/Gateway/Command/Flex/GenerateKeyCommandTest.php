<?php declare(strict_types=1);

namespace CyberSource\SecureAcceptance\Gateway\Command\Flex;

use PHPUnit\Framework\TestCase;

class GenerateKeyCommandTest extends TestCase
{
    /**
     * @var \Magento\Payment\Gateway\Validator\ResultInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $validationResultMock;

    /**
     * @var \Magento\Payment\Gateway\Http\TransferInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $transferObjectMock;

    /**
     * @var \Magento\Payment\Gateway\Command\Result\ArrayResult|\PHPUnit_Framework_MockObject_MockObject
     */
    private $arrayResultMock;

    /** @var GenerateKeyCommand */
    private $generateKeyCommand;

    /** @var \Magento\Payment\Gateway\Http\TransferFactoryInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $transferFactoryMock;

    /** @var \Magento\Payment\Gateway\Command\Result\ArrayResultFactory | \PHPUnit_Framework_MockObject_MockObject */
    private $arrayResultFactoryMock;

    /** @var \Magento\Payment\Gateway\Request\BuilderInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $requestBuilderMock;

    /** @var \Magento\Payment\Gateway\Validator\ValidatorInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $validatorMock;

    /** @var \Magento\Payment\Gateway\Http\ClientInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $clientMock;

    protected function setUp()
    {
        $this->transferFactoryMock = $this->createMock(\Magento\Payment\Gateway\Http\TransferFactoryInterface::class);
        $this->arrayResultFactoryMock = $this->createMock(\Magento\Payment\Gateway\Command\Result\ArrayResultFactory::class);
        $this->requestBuilderMock = $this->createMock(\Magento\Payment\Gateway\Request\BuilderInterface::class);
        $this->validatorMock = $this->createMock(\Magento\Payment\Gateway\Validator\ValidatorInterface::class);
        $this->clientMock = $this->createMock(\Magento\Payment\Gateway\Http\ClientInterface::class);
        $this->arrayResultMock = $this->createMock(\Magento\Payment\Gateway\Command\Result\ArrayResult::class);
        $this->transferObjectMock = $this->createMock(\Magento\Payment\Gateway\Http\TransferInterface::class);
        $this->validationResultMock = $this->createMock(\Magento\Payment\Gateway\Validator\ResultInterface::class);

        $this->generateKeyCommand = new GenerateKeyCommand(
            $this->transferFactoryMock,
            $this->arrayResultFactoryMock,
            $this->requestBuilderMock,
            $this->validatorMock,
            $this->clientMock
        );
    }


    /**
     * @param $validationResult
     * @param $expectedException
     *
     * @dataProvider dataProviderTestExecute
     */
    public function testExecute($validationResult, $expectedException = null)
    {

        $subject = ['payment' => 'somedata'];
        $request = ['param' => 'value'];
        $response = ['some' => 'response'];

        $this->requestBuilderMock->method('build')->with($subject)->willReturn($request);

        $this->transferFactoryMock->method('create')->with($request)->willReturn($this->transferObjectMock);

        $this->clientMock->method('placeRequest')->with($this->transferObjectMock)->willReturn($response);

        $this->validatorMock
            ->expects(static::any())
            ->method('validate')
            ->with(array_merge($subject,
                ['response' => $response]))
            ->willReturn($this->validationResultMock);

        $this->validationResultMock->method('isValid')->willReturn($validationResult);

        if ($expectedException) {
            $this->expectException($expectedException);
        }

        $this->arrayResultFactoryMock->method('create')->with(['array'=>$response])->willReturn($this->arrayResultMock);

        static::assertEquals($this->arrayResultMock, $this->generateKeyCommand->execute($subject));
    }

    public function dataProviderTestExecute()
    {
        return [
            [false, \Magento\Payment\Gateway\Command\CommandException::class],
            [true, null],
        ];
    }

}
