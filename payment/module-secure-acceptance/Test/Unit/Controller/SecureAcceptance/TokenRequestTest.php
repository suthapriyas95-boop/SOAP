<?php declare(strict_types = 1);

namespace CyberSource\SecureAcceptance\Controller\SecureAcceptance;

use PHPUnit\Framework\TestCase;

class TokenRequestTest extends TestCase
{
    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $formkeyValidatorMock;

    /**
     * @var \Magento\Quote\Model\Quote\Payment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMock;
    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;
    /**
     * @var \Magento\Payment\Gateway\Command\ResultInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $commandResultMock;
    /**
     * @var \Magento\Quote\Model\Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteMock;
    /**
     * @var \Magento\Framework\Controller\Result\Json|\PHPUnit_Framework_MockObject_MockObject
     */
    private $jsonResultMock;

    /** @var TokenRequest */
    private $tokenRequest;

    /** @var \Magento\Framework\App\Action\Context | \PHPUnit_Framework_MockObject_MockObject */
    private $contextMock;

    /** @var \Magento\Payment\Gateway\Command\CommandManagerInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $commandManagerMock;

    /** @var \Magento\Framework\Session\SessionManagerInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $sessionManagerMock;

    /** @var \Magento\Framework\Controller\Result\JsonFactory | \PHPUnit_Framework_MockObject_MockObject */
    private $resultJsonFactoryMock;

    /** @var \CyberSource\Core\Model\LoggerInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $loggerMock;

    protected function setUp()
    {
        $this->contextMock = $this->createMock(\Magento\Framework\App\Action\Context::class);
        $this->commandManagerMock = $this->createMock(\Magento\Payment\Gateway\Command\CommandManagerInterface::class);
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
        $this->resultJsonFactoryMock = $this->createMock(\Magento\Framework\Controller\Result\JsonFactory::class);
        $this->loggerMock = $this->createMock(\CyberSource\Core\Model\LoggerInterface::class);

        $this->quoteMock = $this->createMock(\Magento\Quote\Model\Quote::class);
        $this->paymentMock = $this->createMock(\Magento\Quote\Model\Quote\Payment::class);
        $this->jsonResultMock = $this->createMock(\Magento\Framework\Controller\Result\Json::class);

        $this->requestMock = $this->createMock(\Magento\Framework\App\Request\Http::class);
        $this->contextMock->method('getRequest')->willReturn($this->requestMock);
        $this->quoteMock->method('getPayment')->willReturn($this->paymentMock);
        $this->formkeyValidatorMock = $this->createMock(\Magento\Framework\Data\Form\FormKey\Validator::class);

        $this->commandResultMock = $this->createMock(\Magento\Payment\Gateway\Command\ResultInterface::class);

        $this->tokenRequest = new TokenRequest(
            $this->contextMock,
            $this->commandManagerMock,
            $this->sessionManagerMock,
            $this->resultJsonFactoryMock,
            $this->loggerMock,
            $this->formkeyValidatorMock
        );
    }

    public function testExecute()
    {
        $commandResult = ['some' => 'param'];
        $cctype = 'VI';
        $agreements = ['1','2'];

        $this->requestMock->expects(static::any())->method('isPost')->willReturn(true);
        $this->formkeyValidatorMock->method('validate')->with($this->requestMock)->willReturn(true);

        $this->resultJsonFactoryMock->expects(static::once())->method('create')->willReturn($this->jsonResultMock);

        $this->sessionManagerMock->expects(static::once())->method('getQuote')->willReturn($this->quoteMock);

        $this->requestMock->expects(static::any())->method('getParam')->willReturnMap(
            [
                ['cc_type', null, $cctype,],
                ['agreement', null, $agreements,],
            ]
        );

        $this->commandResultMock->method('get')->willReturn($commandResult);

        $this->commandManagerMock->expects(static::once())->method('executeByCode')->with(
            'create_token',
            $this->paymentMock,
            ['card_type' => $cctype, 'agreementIds' => $agreements]
        )->willReturn($this->commandResultMock);

        $this->quoteMock->method('getId')->willReturn(1);

        $this->jsonResultMock->expects(static::once())->method('setData')->with(
            [
                'success' => true,
                \CyberSource\SecureAcceptance\Model\Ui\ConfigProvider::CODE => ['fields' => $commandResult]
            ]
        );

        $this->assertEquals($this->jsonResultMock, $this->tokenRequest->execute());
    }

    public function testExecuteMissingQuote()
    {
        $commandResult = ['some' => 'param'];
        $cctype = 'VI';
        $agreements = ['1','2'];

        $this->requestMock->expects(static::any())->method('isPost')->willReturn(true);
        $this->formkeyValidatorMock->method('validate')->with($this->requestMock)->willReturn(true);

        $this->resultJsonFactoryMock->expects(static::once())->method('create')->willReturn($this->jsonResultMock);

        $this->sessionManagerMock->expects(static::once())->method('getQuote')->willReturn($this->quoteMock);

        $this->requestMock->expects(static::any())->method('getParam')->willReturnMap(
            [
                ['cc_type', null, $cctype,],
                ['agreement', null, $agreements,],
            ]
        );

        $this->commandResultMock->method('get')->willReturn($commandResult);

        $this->commandManagerMock->expects(static::never())->method('executeByCode')->with(
            'create_token',
            $this->paymentMock,
            ['card_type' => $cctype, 'agreementIds' => $agreements]
        )->willReturn($this->commandResultMock);

        $this->quoteMock->method('getId')->willReturn(null);

        $this->jsonResultMock->expects(static::once())->method('setData')->with(
            [
                'error' => 'Unable to build Token request',
            ]
        );

        $this->assertEquals($this->jsonResultMock, $this->tokenRequest->execute());
    }

    public function testExecuteWithCommandException()
    {
        $commandResult = ['some' => 'param'];
        $cctype = 'VI';
        $agreements = ['1','2'];

        $this->requestMock->expects(static::any())->method('isPost')->willReturn(true);
        $this->formkeyValidatorMock->method('validate')->with($this->requestMock)->willReturn(true);

        $this->resultJsonFactoryMock->expects(static::once())->method('create')->willReturn($this->jsonResultMock);

        $this->sessionManagerMock->expects(static::once())->method('getQuote')->willReturn($this->quoteMock);

        $this->requestMock->expects(static::any())->method('getParam')->willReturnMap(
            [
                ['cc_type', null, $cctype,],
                ['agreement', null, $agreements,],
            ]
        );

        $this->commandResultMock->method('get')->willReturn($commandResult);

        $exception = new \Exception('test');

        $this->commandManagerMock->expects(static::once())->method('executeByCode')->with(
            'create_token',
            $this->paymentMock,
            ['card_type' => $cctype, 'agreementIds' => $agreements]
        )->willThrowException($exception);

        $this->quoteMock->method('getId')->willReturn(1);

        $this->jsonResultMock->expects(static::once())->method('setData')->with(
            [
                'error' => __('Unable to build Token request'),
            ]
        );

        $this->assertEquals($this->jsonResultMock, $this->tokenRequest->execute());
    }
}
