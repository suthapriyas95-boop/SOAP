<?php declare(strict_types=1);

namespace CyberSource\SecureAcceptance\Controller\SecureAcceptance;

use CyberSource\SecureAcceptance\Helper\RequestDataBuilder;
use PHPUnit\Framework\TestCase;

class TokenProcessTest extends TestCase
{
    /**
     * @var \CyberSource\Core\Model\Checkout\PaymentFailureRouteProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentFailureProviderMock;

    /** @var TokenProcess */
    private $tokenProcess;

    /** @var \Magento\Framework\App\Action\Context | \PHPUnit_Framework_MockObject_MockObject */
    private $contextMock;

    /** @var \Magento\Payment\Gateway\Command\CommandManagerInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $commandManagerMock;

    /** @var \Magento\Quote\Model\QuoteManagement | \PHPUnit_Framework_MockObject_MockObject */
    private $quoteManagementMock;

    /** @var \Magento\Quote\Api\CartRepositoryInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $cartRepositoryMock;

    /** @var \Magento\Framework\Registry | \PHPUnit_Framework_MockObject_MockObject */
    private $registryMock;

    /** @var \Magento\Framework\View\Result\LayoutFactory | \PHPUnit_Framework_MockObject_MockObject */
    private $layoutFactoryMock;

    /** @var \CyberSource\Core\Model\LoggerInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $loggerMock;

    /** @var \CyberSource\SecureAcceptance\Gateway\Config\Config | \PHPUnit_Framework_MockObject_MockObject */
    private $configMock;

    /** @var \Magento\Quote\Model\Quote | \PHPUnit_Framework_MockObject_MockObject */
    private $quoteMock;

    /** @var \Magento\Framework\App\RequestInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $requestMock;

    /** @var \Magento\Framework\Controller\ResultFactory | \PHPUnit_Framework_MockObject_MockObject */
    private $resultFactoryMock;

    /** @var \Magento\Framework\View\Result\Page | \PHPUnit_Framework_MockObject_MockObject */
    private $pageResultMock;

    /** @var \Magento\Framework\View\Layout | \PHPUnit_Framework_MockObject_MockObject */
    private $layoutMock;

    /** @var \Magento\Framework\View\Layout\ProcessorInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $layoutProcessorMock;

    /** @var \Magento\Framework\View\Result\Layout | \PHPUnit_Framework_MockObject_MockObject */
    private $resultLayoutMock;

    /** @var \Magento\Framework\UrlInterface | \PHPUnit_Framework_MockObject_MockObject */
    private $urlMock;

    /** @var \Magento\Framework\Message\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private  $messageManagerMock;

    protected function setUp()
    {
        $this->contextMock = $this->createMock(\Magento\Framework\App\Action\Context::class);
        $this->commandManagerMock = $this->createMock(\Magento\Payment\Gateway\Command\CommandManagerInterface::class);
        $this->quoteManagementMock = $this->createMock(\Magento\Quote\Model\QuoteManagement::class);
        $this->cartRepositoryMock = $this->createMock(\Magento\Quote\Api\CartRepositoryInterface::class);
        $this->registryMock = $this->createMock(\Magento\Framework\Registry::class);
        $this->layoutFactoryMock = $this->createMock(\Magento\Framework\View\Result\LayoutFactory::class);
        $this->loggerMock = $this->createMock(\CyberSource\Core\Model\LoggerInterface::class);
        $this->configMock = $this->createMock(\CyberSource\SecureAcceptance\Gateway\Config\Config::class);
        $this->quoteMock = $this->createMock(\Magento\Quote\Model\Quote::class);
        $this->requestMock = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $this->resultFactoryMock = $this->createMock(\Magento\Framework\Controller\ResultFactory::class);
        $this->pageResultMock = $this->createMock(\Magento\Framework\View\Result\Page::class);
        $this->layoutMock = $this->createMock(\Magento\Framework\View\Layout::class);
        $this->layoutProcessorMock = $this->createMock(\Magento\Framework\View\Layout\ProcessorInterface::class);
        $this->resultLayoutMock = $this->createMock(\Magento\Framework\View\Result\Layout::class);
        $this->urlMock = $this->createMock(\Magento\Framework\UrlInterface::class);
        $this->messageManagerMock = $this->createMock(\Magento\Framework\Message\ManagerInterface::class);
        $this->paymentFailureProviderMock = $this->createMock(\CyberSource\Core\Model\Checkout\PaymentFailureRouteProviderInterface::class);

        $this->paymentFailureProviderMock->method('getFailureRoutePath')->willReturn('checkout/cart');

        $this->contextMock
            ->expects(static::any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->contextMock
            ->expects(static::any())
            ->method('getResultFactory')
            ->willReturn($this->resultFactoryMock);
        $this->contextMock
            ->expects(static::any())
            ->method('getUrl')
            ->willReturn($this->urlMock);
        $this->tokenProcess = new TokenProcess(
            $this->contextMock,
            $this->commandManagerMock,
            $this->quoteManagementMock,
            $this->cartRepositoryMock,
            $this->registryMock,
            $this->layoutFactoryMock,
            $this->messageManagerMock,
            $this->loggerMock,
            $this->paymentFailureProviderMock,
            $this->configMock
        );
    }

    public function testExecute()
    {
        $paramValue = 'param_value';
        $params = ['param1', 'param2'];
        $result = [
            'email' => 'param_value',
            'payload' => [
                'method' => \CyberSource\SecureAcceptance\Model\Ui\ConfigProvider::CODE,
                'extension_attributes' => [
                    'agreement_ids' => explode(
                        ',',
                        $paramValue ?? ''
                    ),
                ]
            ]
        ];
        $payment = $this->createMock(\Magento\Quote\Model\Quote\Payment::class);

        $this->requestMock
            ->expects(static::any())
            ->method('getParam')
            ->with($this->logicalOr(
                'req_' . RequestDataBuilder::KEY_QUOTE_ID,
                'req_' . RequestDataBuilder::KEY_AGREEMENT_IDS,
                'req_bill_to_email'
            ))
            ->willReturn($paramValue);
        $this->cartRepositoryMock
            ->expects(static::once())
            ->method('get')
            ->with($paramValue)
            ->willReturn($this->quoteMock);
        $this->quoteMock
            ->expects(static::once())
            ->method('getId')
            ->willReturn(1);
        $this->quoteMock
            ->expects(static::once())
            ->method('getIsActive')
            ->willReturn(true);
        $this->quoteMock
            ->expects(static::once())
            ->method('getPayment')
            ->willReturn($payment);
        $this->requestMock
            ->expects(static::once())
            ->method('getParams')
            ->willReturn($params);
        $this->commandManagerMock
            ->expects(static::once())
            ->method('executeByCode')
            ->with(
                \CyberSource\SecureAcceptance\Gateway\Command\TokenHandleResponseCommand::COMMAND_NAME,
                $payment,
                ['response' => $params]
            );
        $this->cartRepositoryMock
            ->expects(static::once())
            ->method('save')
            ->willReturn($this->quoteMock);
        $this->configMock
            ->expects(static::exactly(2))
            ->method('isSilent')
            ->willReturn(false);
        $this->configMock
            ->expects(static::exactly(1))
            ->method('getUseIFrame')
            ->willReturn(false);

        $this->registryMock
            ->expects(static::once())
            ->method('register')
            ->with(\Magento\Payment\Block\Transparent\Iframe::REGISTRY_KEY, $result);

        $this->resultFactoryMock
            ->expects(static::once())
            ->method('create')
            ->with(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE)
            ->willReturn($this->pageResultMock);
        $this->pageResultMock
            ->expects(static::once())
            ->method('getLayout')
            ->willReturn($this->layoutMock);
        $this->layoutMock
            ->expects(static::once())
            ->method('getUpdate')
            ->willReturn($this->layoutProcessorMock);
        $this->layoutProcessorMock
            ->expects(static::once())
            ->method('addHandle')
            ->with(['cybersource_iframe_payment_response_redirect']);

        $this->assertEquals($this->pageResultMock, $this->tokenProcess->execute());
    }

    public function testExecuteWithNotActiveQuote()
    {
        $paramValue = 'param_value';
        $urlValue = 'url_value';

        $this->requestMock
            ->expects(static::any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['req_' . RequestDataBuilder::KEY_QUOTE_ID, null, $paramValue,],
                    ['req_bill_to_email', null, 'test@example.org',],
                ]
            );

        $this->cartRepositoryMock
            ->expects(static::once())
            ->method('get')
            ->with($paramValue)
            ->willReturn($this->quoteMock);
        $this->quoteMock
            ->expects(static::once())
            ->method('getId')
            ->willReturn(1);
        $this->quoteMock
            ->expects(static::once())
            ->method('getIsActive')
            ->willReturn(false);
        $this->urlMock
            ->expects(static::once())
            ->method('getUrl')
            ->with('checkout/cart')
            ->willReturn($urlValue);
        $this->registryMock
            ->expects(static::once())
            ->method('register')
            ->with(\Magento\Payment\Block\Transparent\Iframe::REGISTRY_KEY, ['redirect' => $urlValue]);
        $this->layoutFactoryMock
            ->expects(static::once())
            ->method('create')
            ->willReturn($this->resultLayoutMock);
        $this->resultLayoutMock
            ->expects(static::once())
            ->method('addDefaultHandle');
        $this->resultLayoutMock
            ->expects(static::once())
            ->method('getLayout')
            ->willReturn($this->layoutMock);
        $this->layoutMock
            ->expects(static::once())
            ->method('getUpdate')
            ->willReturn($this->layoutProcessorMock);
        $this->layoutProcessorMock
            ->expects(static::once())
            ->method('load')
            ->with(['cybersource_iframe_payment_response']);

        $this->assertEquals($this->resultLayoutMock, $this->tokenProcess->execute());
    }

    public function testExecuteWithLocalizedException()
    {
        $paramValue = 'param_value';
        $result = [
            'error_msg' => 'Unable to load card data.',
            'payload' => [
                'method' => \CyberSource\SecureAcceptance\Model\Ui\ConfigProvider::CODE,
                'extension_attributes' => [
                    'agreement_ids' => explode(
                        ',',
                        $paramValue ?? ''
                    ),
                ]
            ]
        ];

        $this->requestMock
            ->expects(static::exactly(2))
            ->method('getParam')
            ->with($this->logicalOr(
                'req_' . RequestDataBuilder::KEY_QUOTE_ID,
                'req_' . RequestDataBuilder::KEY_AGREEMENT_IDS
            ))
            ->willReturn($paramValue);
        $this->cartRepositoryMock
            ->expects(static::once())
            ->method('get')
            ->with($paramValue)
            ->willReturn(null);
        $this->configMock
            ->expects(static::exactly(3))
            ->method('isSilent')
            ->willReturn(false);
        $this->configMock
            ->expects(static::exactly(2))
            ->method('getUseIFrame')
            ->willReturn(true);
        $this->registryMock
            ->expects(static::once())
            ->method('register')
            ->with(\Magento\Payment\Block\Transparent\Iframe::REGISTRY_KEY, $result);
        $this->layoutFactoryMock
            ->expects(static::once())
            ->method('create')
            ->willReturn($this->resultLayoutMock);
        $this->resultLayoutMock
            ->expects(static::once())
            ->method('addDefaultHandle');
        $this->resultLayoutMock
            ->expects(static::once())
            ->method('getLayout')
            ->willReturn($this->layoutMock);
        $this->layoutMock
            ->expects(static::once())
            ->method('getUpdate')
            ->willReturn($this->layoutProcessorMock);
        $this->layoutProcessorMock
            ->expects(static::once())
            ->method('load')
            ->with(['cybersource_iframe_payment_response_hosted_iframe']);

        $this->assertEquals($this->resultLayoutMock, $this->tokenProcess->execute());
    }

    public function testExecuteWithException()
    {
        $result = [
            'error_msg' => 'Unable to handle token response',
        ];

        $this->requestMock
            ->expects(static::once())
            ->method('getParam')
            ->with('req_' . RequestDataBuilder::KEY_QUOTE_ID)
            ->willThrowException(new \Exception());
        $this->configMock
            ->expects(static::exactly(3))
            ->method('isSilent')
            ->willReturn(true);
        $this->registryMock
            ->expects(static::once())
            ->method('register')
            ->with(\Magento\Payment\Block\Transparent\Iframe::REGISTRY_KEY, $result);
        $this->layoutFactoryMock
            ->expects(static::once())
            ->method('create')
            ->willReturn($this->resultLayoutMock);
        $this->resultLayoutMock
            ->expects(static::once())
            ->method('addDefaultHandle');
        $this->resultLayoutMock
            ->expects(static::once())
            ->method('getLayout')
            ->willReturn($this->layoutMock);
        $this->layoutMock
            ->expects(static::once())
            ->method('getUpdate')
            ->willReturn($this->layoutProcessorMock);
        $this->layoutProcessorMock
            ->expects(static::once())
            ->method('load')
            ->with(['cybersource_iframe_payment_response']);

        $this->assertEquals($this->resultLayoutMock, $this->tokenProcess->execute());
    }
}
