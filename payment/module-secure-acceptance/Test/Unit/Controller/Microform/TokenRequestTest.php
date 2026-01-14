<?php declare(strict_types = 1);

namespace CyberSource\SecureAcceptance\Controller\Microform;

use PHPUnit\Framework\TestCase;

class TokenRequestTest extends TestCase
{
    /**
     * @var \Magento\Payment\Gateway\Command\ResultInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $commandResultMock;

    /**
     * @var \Magento\Framework\Controller\Result\Json|\PHPUnit_Framework_MockObject_MockObject
     */
    private $jsonResultMock;

    /**
     * @var \Magento\Quote\Model\Quote\Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    /**
     * @var \Magento\Quote\Model\Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteMock;

    /**
     * @var \Magento\Framework\Controller\ResultFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resultFactoryMock;

    /**
     * @var \Magento\Framework\App\Request\Http|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    /** @var TokenRequest */
    private $tokenRequestController;

    /** @var \Magento\Framework\App\Action\Context | \PHPUnit_Framework_MockObject_MockObject */
    private $contextMock;

    /** @var \Magento\Payment\Gateway\Command\CommandManagerInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $commandManagerMock;

    /** @var \Magento\Checkout\Model\Session | \PHPUnit_Framework_MockObject_MockObject */
    private $sessionManagerMock;

    /** @var \Magento\Framework\Data\Form\FormKey\Validator | \PHPUnit_Framework_MockObject_MockObject */
    private $formKeyValidatorMock;

    /** @var \Magento\Framework\Controller\Result\JsonFactory | \PHPUnit_Framework_MockObject_MockObject */
    private $resultJsonFactoryMock;

    /** @var \Magento\Quote\Model\QuoteRepository | \PHPUnit_Framework_MockObject_MockObject */
    private $quoteRepositoryMock;

    /** @var \CyberSource\Core\Model\LoggerInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $loggerMock;

    protected function setUp()
    {
        $this->contextMock = $this->createMock(\Magento\Framework\App\Action\Context::class);
        $this->commandManagerMock = $this->createMock(\Magento\Payment\Gateway\Command\CommandManagerInterface::class);
        $this->sessionManagerMock = $this->createMock(\Magento\Checkout\Model\Session::class);
        $this->formKeyValidatorMock = $this->createMock(\Magento\Framework\Data\Form\FormKey\Validator::class);
        $this->resultJsonFactoryMock = $this->createMock(\Magento\Framework\Controller\Result\JsonFactory::class);
        $this->quoteRepositoryMock = $this->createMock(\Magento\Quote\Model\QuoteRepository::class);
        $this->loggerMock = $this->createMock(\CyberSource\Core\Model\LoggerInterface::class);

        $this->requestMock = $this->createMock(\Magento\Framework\App\Request\Http::class);
        $this->resultFactoryMock = $this->createMock(\Magento\Framework\Controller\ResultFactory::class);

        $this->contextMock
            ->expects(static::any())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->contextMock
            ->expects(static::any())
            ->method('getResultFactory')
            ->willReturn($this->resultFactoryMock);

        $this->quoteMock = $this->createMock(\Magento\Quote\Model\Quote::class);
        $this->paymentMock = $this->createMock(\Magento\Quote\Model\Quote\Payment::class);

        $this->jsonResultMock = $this->createMock(\Magento\Framework\Controller\Result\Json::class);

        $this->commandResultMock = $this->createMock(\Magento\Payment\Gateway\Command\ResultInterface::class);

        $this->tokenRequestController = new TokenRequest(
            $this->contextMock,
            $this->commandManagerMock,
            $this->sessionManagerMock,
            $this->formKeyValidatorMock,
            $this->resultJsonFactoryMock,
            $this->quoteRepositoryMock,
            $this->loggerMock
        );
    }

    public function testExecute()
    {
        $this->resultJsonFactoryMock->method('create')->willReturn($this->jsonResultMock);

        $expectedResult = [
            'success' => true,
            'token' => '123123123123123',
        ];

        $commandResult = [
            'keyId' => $expectedResult['token'],
        ];

        $this->requestMock->method('isPost')->willReturn(true);
        $this->sessionManagerMock->method('getQuote')->willReturn($this->quoteMock);

        $this->quoteMock->method('getId')->willReturn(1);
        $this->quoteMock->method('getPayment')->willReturn($this->paymentMock);

        $this->formKeyValidatorMock->method('validate')->with($this->requestMock)->willReturn(true);

        $this->commandManagerMock
            ->method('executeByCode')
            ->with('generate_flex_key')
            ->willReturn($this->commandResultMock);

        $this->commandResultMock->method('get')->willReturn($commandResult);

        $this->quoteRepositoryMock->expects(static::once())->method('save')->with($this->quoteMock);

        $this->jsonResultMock->expects(static::once())->method('setData')->with($expectedResult);

        static::assertEquals($this->jsonResultMock, $this->tokenRequestController->execute());
    }

}
