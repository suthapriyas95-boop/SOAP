<?php declare(strict_types = 1);

namespace CyberSource\ThreeDSecure\Controller\Cca;

use PHPUnit\Framework\TestCase;

class RequestTokenTest extends TestCase
{
    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $formKeyValidatorMock;

    /**
     * @var \Magento\Payment\Gateway\Command\ResultInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $commandResultMock;

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    /**
     * @var \Magento\Quote\Model\Quote\Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;

    /**
     * @var \Magento\Framework\Controller\Result\Json|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resultJsonMock;

    /**
     * @var \Magento\Quote\Model\Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteMock;

    /** @var RequestToken */
    private $requestToken;

    /** @var \Magento\Framework\App\Action\Context | \PHPUnit_Framework_MockObject_MockObject */
    private $contextMock;

    /** @var \Magento\Payment\Gateway\Command\CommandManagerInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $commandManager;

    /** @var \Magento\Framework\Session\SessionManagerInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $sessionManagerMock;

    /** @var \Magento\Quote\Api\CartRepositoryInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $cartRepository;

    /** @var \Magento\Framework\Controller\Result\JsonFactory | \PHPUnit_Framework_MockObject_MockObject */
    private $jsonFactory;

    /** @var \CyberSource\Core\Model\LoggerInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    protected function setUp()
    {
        $this->contextMock = $this->createMock(\Magento\Framework\App\Action\Context::class);
        $this->commandManager = $this->createMock(\Magento\Payment\Gateway\Command\CommandManagerInterface::class);

        $this->sessionManagerMock = $this->createPartialMock(
            \Magento\Framework\Session\SessionManagerInterface::class,
            [
                'getQuote',
                'start',
                'writeClose',
                'isSessionExists',
                'getSessionId',
                'getName',
                'setName',
                'destroy',
                'clearStorage',
                'getCookieDomain',
                'getCookiePath',
                'getCookieLifetime',
                'setSessionId',
                'regenerateId',
                'expireSessionCookie',
                'getSessionIdForHost',
                'isValidForHost',
                'isValidForPath',

            ]
        );

        $this->cartRepository = $this->createMock(\Magento\Quote\Api\CartRepositoryInterface::class);
        $this->jsonFactory = $this->createMock(\Magento\Framework\Controller\Result\JsonFactory::class);
        $this->resultJsonMock  = $this->createMock(\Magento\Framework\Controller\Result\Json::class);
        $this->logger = $this->createMock(\CyberSource\Core\Model\LoggerInterface::class);
        $this->quoteMock = $this->createPartialMock(\Magento\Quote\Model\Quote::class, ['getId', 'getBaseGrandTotal', 'getPayment']);
        $this->paymentMock = $this->createMock(\Magento\Quote\Model\Quote\Payment::class);
        $this->requestMock = $this->createMock(\Magento\Framework\App\Request\Http::class);
        $this->commandResultMock = $this->createMock(\Magento\Payment\Gateway\Command\ResultInterface::class);
        $this->formKeyValidatorMock = $this->createMock(\Magento\Framework\Data\Form\FormKey\Validator::class);

        $this->contextMock->expects(static::any())->method('getRequest')->willReturn($this->requestMock);
        $this->quoteMock->expects(static::any())->method('getPayment')->willReturn($this->paymentMock);
        $this->sessionManagerMock->expects(static::any())->method('getQuote')->willReturn($this->quoteMock);
        $this->jsonFactory->expects(static::once())->method('create')->willReturn($this->resultJsonMock);

        $this->requestToken = new RequestToken(
            $this->contextMock,
            $this->commandManager,
            $this->sessionManagerMock,
            $this->cartRepository,
            $this->jsonFactory,
            $this->formKeyValidatorMock,
            $this->logger
        );
    }

    public function testExecute()
    {

        $token = '123123123123123';
        $quoteId = '123123';
        $params = ['amount' => '123.22'];

        $request = ['method' => 'chcybersource', 'additional_data' => ['asdasdasd' => 'asdasdasd']];

        $this->requestMock->expects(static::any())->method('getParam')->willReturnMap([
                ['method',null, $request['method']],
                ['additional_data',null, $request['additional_data']]
            ]);

        $this->requestMock->expects(static::any())->method('isPost')->willReturn(true);
        $this->formKeyValidatorMock->expects(static::any())->method('validate')->with($this->requestMock)->willReturn(true);

        $this->quoteMock->expects(static::any())->method('getId')->willReturn($quoteId);
        $this->quoteMock->expects(static::any())->method('getBaseGrandTotal')->willReturn($params['amount']);

        $this->paymentMock->expects(static::once())->method('importData')->with($request);

        $this->cartRepository->expects(static::once())->method('save')->with($this->quoteMock);

        $this->commandManager
            ->expects(static::once())
            ->method('executeByCode')
            ->with('createToken', $this->paymentMock, $params)
            ->willReturn($this->commandResultMock);

        $this->commandResultMock->expects(static::once())->method('get')->willReturn(['token'=>$token]);

        $this->resultJsonMock->expects(static::once())->method('setData')->with(['success'=> true, 'token' => $token]);
        $this->assertEquals($this->resultJsonMock, $this->requestToken->execute());
    }


    public function testExecuteWithCommandException()
    {

        $quoteId = '123123';
        $params = ['amount' => '123.22'];

        $request = ['method' => 'chcybersource', 'additional_data' => ['asdasdasd' => 'asdasdasd']];

        $this->requestMock->expects(static::any())->method('getParam')->willReturnMap([
            ['method',null, $request['method']],
            ['additional_data',null, $request['additional_data']]
        ]);

        $this->requestMock->expects(static::any())->method('isPost')->willReturn(true);
        $this->formKeyValidatorMock->expects(static::any())->method('validate')->with($this->requestMock)->willReturn(true);

        $this->quoteMock->expects(static::any())->method('getId')->willReturn($quoteId);
        $this->quoteMock->expects(static::any())->method('getBaseGrandTotal')->willReturn($params['amount']);

        $this->paymentMock->expects(static::once())->method('importData')->with($request);

        $this->cartRepository->expects(static::once())->method('save')->with($this->quoteMock);

        $this->commandManager
            ->expects(static::once())
            ->method('executeByCode')
            ->with('createToken', $this->paymentMock, $params)
            ->willReturn($this->commandResultMock);

        $phase = __('Something wrong');
        $exception = new \Magento\Payment\Gateway\Command\CommandException($phase);

        $this->commandResultMock->expects(static::once())->method('get')->willThrowException($exception);

        $this->resultJsonMock->expects(static::once())->method('setData')->with(['success'=> false, 'error_msg' => $phase]);
        $this->assertEquals($this->resultJsonMock, $this->requestToken->execute());
    }



    public function testExecuteWithException()
    {

        $quoteId = '123123';
        $params = ['amount' => '123.22'];

        $request = ['method' => 'chcybersource', 'additional_data' => ['asdasdasd' => 'asdasdasd']];

        $this->requestMock->expects(static::any())->method('getParam')->willReturnMap([
            ['method',null, $request['method']],
            ['additional_data',null, $request['additional_data']]
        ]);

        $this->requestMock->expects(static::any())->method('isPost')->willReturn(true);
        $this->formKeyValidatorMock->expects(static::any())->method('validate')->with($this->requestMock)->willReturn(true);

        $this->quoteMock->expects(static::any())->method('getId')->willReturn($quoteId);
        $this->quoteMock->expects(static::any())->method('getBaseGrandTotal')->willReturn($params['amount']);

        $this->paymentMock->expects(static::once())->method('importData')->with($request);

        $this->cartRepository->expects(static::once())->method('save')->with($this->quoteMock);

        $this->commandManager
            ->expects(static::once())
            ->method('executeByCode')
            ->with('createToken', $this->paymentMock, $params)
            ->willReturn($this->commandResultMock);

        $phase = 'Something wrong';
        $exception = new \Exception($phase);

        $this->commandResultMock->expects(static::once())->method('get')->willThrowException($exception);

        $this->resultJsonMock->expects(static::once())->method('setData')->with(['success'=> false, 'error_msg' => __('Unable to handle request')]);
        $this->assertEquals($this->resultJsonMock, $this->requestToken->execute());
    }
}
