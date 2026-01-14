<?php

namespace CyberSource\SecureAcceptance\Controller\Adminhtml\Transparent;

use CyberSource\Core\Model\Logger;
use CyberSource\SecureAcceptance\Gateway\Request\AbstractRequest;
use Magento\Framework\Exception\LocalizedException;

class Response extends \CyberSource\Core\Action\CsrfIgnoringAction
{
    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    private $checkoutSession;

    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    private $quoteManagement;

    /**
     * @var \Magento\Framework\View\Result\LayoutFactory
     */
    private $resultLayoutFactory;

    /**
     * @var \CyberSource\SecureAcceptance\Helper\RequestDataBuilder
     */
    private $requestDataBuilder;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Config\Config
     */
    private $config;

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Config\SaConfigProviderInterface
     */
    private $configProvider;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \Magento\Framework\Registry
     */
    private $coreRegistry;

    /**
     * @var \Magento\Sales\Model\AdminOrder\Create
     */
    private $orderCreateModel;

    /**
     * @var \Magento\GiftMessage\Model\Save
     */
    private $giftmessageSaveModel;

    /**
     * @var \Laminas\Stdlib\ParametersFactory
     */
    private $parametersFactory;
	
	/**
     * @var \CyberSource\Core\Model\Logger
     */
    protected $logger;

    /**
     * Response constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Session\SessionManagerInterface $sessionManager
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     * @param \CyberSource\SecureAcceptance\Helper\RequestDataBuilder $requestDataBuilder
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \CyberSource\SecureAcceptance\Gateway\Config\Config $config
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Sales\Model\AdminOrder\Create $orderCreateModel
     * @param \Laminas\Stdlib\ParametersFactory $parametersFactory
     * @param \Magento\GiftMessage\Model\Save $giftmessageSaveModel
	 * @param \CyberSource\Core\Model\Logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \CyberSource\SecureAcceptance\Helper\RequestDataBuilder $requestDataBuilder,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \CyberSource\SecureAcceptance\Gateway\Config\Config $config,
        \CyberSource\SecureAcceptance\Gateway\Config\SaConfigProviderInterface $configProvider,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Sales\Model\AdminOrder\Create $orderCreateModel,
        \Magento\GiftMessage\Model\Save $giftmessageSaveModel,
        \Laminas\Stdlib\ParametersFactory $parametersFactory,
        Logger $logger
    ) {
        parent::__construct($context);

        $this->checkoutSession = $sessionManager;
        $this->quoteManagement = $quoteManagement;
        $this->resultLayoutFactory = $resultLayoutFactory;
        $this->requestDataBuilder = $requestDataBuilder;
        $this->quoteRepository = $quoteRepository;
        $this->config = $config;
        $this->configProvider = $configProvider;
        $this->orderRepository = $orderRepository;
        $this->coreRegistry = $coreRegistry;
        $this->orderCreateModel = $orderCreateModel;
        $this->parametersFactory = $parametersFactory;
        $this->giftmessageSaveModel = $giftmessageSaveModel;
        $this->logger = $logger;
    }

    /**
     *
     * Main action method.
     *
     * Processes SOP response and saves the order.
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        /** @var array $transparentResponse */
        $transparentResponse = $this->getRequest()->getParams();
        $this->logger->debug(['response' => $transparentResponse]);

        $message = false;

        try {
            /**
             * Validate cybersource signature
             */
            if (!$this->isValidSignature($transparentResponse)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Payment could not be processed.'));
            }

            // rewriting CyberSource postback with magento order data to proceed natively
            $this->substitutePostWithOrderData();

            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $this->orderCreateModel->getQuote();

            $quote->setCustomerId($this->orderCreateModel->getSession()->getCustomerId());

            $this->orderCreateModel->setPaymentMethod(\CyberSource\SecureAcceptance\Model\Ui\ConfigProvider::CODE);
            $this->orderCreateModel->setPaymentData(['method' => \CyberSource\SecureAcceptance\Model\Ui\ConfigProvider::CODE]);

            if ($giftmessages = $this->getRequest()->getPost('giftmessage')) {
                $this->giftmessageSaveModel->setGiftmessages($giftmessages)->saveAllInQuote();
            }

            $this->orderCreateModel->recollectCart();
            $this->orderCreateModel->saveQuote();

            $this->coreRegistry->register(AbstractRequest::TRANSPARENT_RESPONSE_KEY, $transparentResponse);

            $order = $this->orderCreateModel
                ->setIsValidate(true)
                ->importPostData($this->getRequest()->getPost('order'))
                ->createOrder();

            $this->messageManager->addSuccessMessage('Your order has been successfully created!');

            $successUrl = $this->_url->getUrl(
                'sales/order/view',
                ['order_id' => $order->getId(), '_secure' => $this->getRequest()->isSecure()]
            );

            if ($this->isResponseRedirect()) {
                return $this->resultRedirectFactory->create()->setUrl($successUrl);
            }

            $message = $this->messageManager->getMessages()->getLastAddedMessage();
            $message->setIsSticky(true);

            $parameters['order_success'] = $successUrl;
        } catch (\Exception $e) {
            $parameters['error'] = true;
            $parameters['error_msg'] = $e->getMessage();
        }

        if (($this->isResponseRedirect() || $this->config->isSilent()) && isset($parameters['error_msg'])) {
            $this->messageManager->addErrorMessage($parameters['error_msg']);
            return $this->resultRedirectFactory->create()->setPath('sales/order_create/index');
        }

        $this->coreRegistry->register(\Magento\Payment\Block\Transparent\Iframe::REGISTRY_KEY, $parameters);

        $resultLayout = $this->resultLayoutFactory->create();
        $resultLayout->addDefaultHandle();
        $resultLayout->getLayout()->getUpdate()->load(['cybersource_iframe_payment_response']);

        if ($message && $message->getIsSticky()) {
            $resultLayout->getLayout()->getBlock('dummy'); // trigger layout build to clear messages except our one
            $message->setIsSticky(false);
        }

        return $resultLayout;
    }

    /**
     * @return bool
     */
    private function isResponseRedirect()
    {
        return !$this->config->isSilent() && !$this->config->getUseIFrame();
    }

    /**
     * Validate response signature with secret key.
     *
     * @param $response
     * @return bool
     */
    private function isValidSignature($response)
    {
        $storeId = $this->getRequest()->getParam('req_' . \CyberSource\SecureAcceptance\Helper\RequestDataBuilder::KEY_STORE_ID);
        return $this->requestDataBuilder->validateSignature($response, $this->configProvider->getSecretKey($storeId));
    }

    /**
     * @return $this
     */
    private function substitutePostWithOrderData()
    {
        $orderData = $this->checkoutSession->getOrderDataPost();

        if(empty($orderData)){
            throw new \Magento\Framework\Exception\LocalizedException(__('Sorry, Order data not found. Please try again'));
        }
        /** @var \Laminas\Stdlib\Parameters $postParams */
        $postParams = $this->parametersFactory->create();
        $postParams->fromString($orderData);

        $this->getRequest()->setPost($postParams);

        $this->checkoutSession->unsOrderDataPost();

        return $this;
    }
}
