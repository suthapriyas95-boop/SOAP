<?php

namespace CyberSource\SecureAcceptance\Controller\Adminhtml\Transparent;

use CyberSource\Core\Model\Logger;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;

class RequestSilentData extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Session\SessionManager
     */
    protected $sessionManager;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Payment\Model\MethodInterface
     */
    protected $paymentMethod;

    /**
     * @var \CyberSource\SecureAcceptance\Helper\RequestDataBuilder
     */
    protected $requestDataBuilder;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    private $formkeyValidator;

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Config\Config
     */
    private $config;

    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    private $customerFactory;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    private $session;

    /**
     * @var \CyberSource\SecureAcceptance\Helper\Vault
     */
    private $vaultHelper;
	
	/**
     * @var \CyberSource\Core\Model\Logger
     */
    protected $logger;

    /**
     * RequestSilentData constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Session\SessionManager $sessionManager
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Payment\Model\MethodInterface $paymentMethod
     * @param \CyberSource\SecureAcceptance\Helper\RequestDataBuilder $requestDataBuilder
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formkeyValidator
     * @param \CyberSource\SecureAcceptance\Gateway\Config\Config $config
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \CyberSource\SecureAcceptance\Helper\Vault $vaultHelper
     * @param \Magento\Backend\Model\Session\Quote $session
	 * @param \CyberSource\Core\Model\Logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Session\SessionManager $sessionManager,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Payment\Model\MethodInterface $paymentMethod,
        \CyberSource\SecureAcceptance\Helper\RequestDataBuilder $requestDataBuilder,
        \Magento\Framework\Data\Form\FormKey\Validator $formkeyValidator,
        \CyberSource\SecureAcceptance\Gateway\Config\Config $config,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \CyberSource\SecureAcceptance\Helper\Vault $vaultHelper,
        \Magento\Backend\Model\Session\Quote $session,
        Logger $logger
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->sessionManager = $sessionManager;
        $this->paymentMethod = $paymentMethod;
        $this->requestDataBuilder = $requestDataBuilder;

        parent::__construct($context);
        $this->formkeyValidator = $formkeyValidator;
        $this->config = $config;
        $this->quoteRepository = $quoteRepository;
        $this->customerFactory = $customerFactory;
        $this->session = $session;
        $this->vaultHelper = $vaultHelper;
        $this->logger = $logger;
    }

    /**
     *
     * Main action method.
     *
     * Receives card type, builds SOP requests and returns it as json.
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /* @var Quote $quote */
        $quote = $this->sessionManager->getQuote();

        if (!$quote or !$quote instanceof Quote) {
            return $this->getErrorResponse();
        }

        if (!$this->formkeyValidator->validate($this->getRequest())) {
            return $this->getErrorResponse();
        }

        try {
            $cardType = $this->getRequest()->getParam('cc_type');
            $orderData = $this->getRequest()->getParam('order_data');
            $paramVaultEnabled = $this->getRequest()->getParam('vault_enabled', null) == 'true';

            $this->session->setOrderDataPost($orderData);

            $data = [];

            $quote->reserveOrderId();

            $isVaultEnabled = $paramVaultEnabled && $this->config->isVaultEnabledConfiguredOption() && $this->config->isVaultEnabledAdmin();
            $this->vaultHelper->setVaultEnabled($isVaultEnabled);
            $quote->getPayment()->setAdditionalInformation(\Magento\Vault\Model\Ui\VaultConfigProvider::IS_ACTIVE_CODE, $isVaultEnabled);

            $this->quoteRepository->save($quote);

            if ($this->config->isSilent()) {
                $data['fields'] = $this->requestDataBuilder->buildSilentRequestData(null, null, $cardType); 
            } else {
                $data['fields'] = $this->requestDataBuilder->buildRequestData();
            }
			$this->logger->debug(['request' => $data]);

            return $this->resultJsonFactory->create()->setData(
                [
                    $this->paymentMethod->getCode() => $data,
                    'success' => true,
                    'error' => false
                ]
            );
        } catch (LocalizedException $e) {
            return $this->getErrorResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->getErrorResponse();
        }
    }

    /**
     *
     * Returns error JSON.
     *
     * @param null|string $message
     * @return \Magento\Framework\Controller\Result\Json
     */
    private function getErrorResponse($message = null)
    {
        $message = $message ? $message : __('Your payment has been declined. Please try again.');
        return $this->resultJsonFactory->create()->setData(
            [
                'success' => false,
                'error' => true,
                'error_messages' => $message
            ]
        );
    }
}
