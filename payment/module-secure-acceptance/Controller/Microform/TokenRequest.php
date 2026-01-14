<?php
/**
 *
 */

namespace CyberSource\SecureAcceptance\Controller\Microform;
use Magento\Framework\Url\DecoderInterface;
use CyberSource\SecureAcceptance\Gateway\Config\Config;


class TokenRequest extends \Magento\Framework\App\Action\Action
{

    const COMMAND_CODE = 'generate_flex_key';

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var \Magento\Payment\Gateway\Command\CommandManagerInterface
     */
    private $commandManager;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var \CyberSource\Core\Model\LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    private $formKeyValidator;

    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    private $quoteRepository;

     /**
     * @var \Magento\Framework\Url\DecoderInterface
     */
    protected $urlDecoder;

      /**
     * @var \Magento\Framework\Session\StorageInterface
     */
    private $sessionStorage;
    
    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Config\Config
     */
    private $config;

    /**
     * TokenRequest constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Payment\Gateway\Command\CommandManagerInterface $commandManager
     * @param \Magento\Framework\Session\SessionManagerInterface $sessionManager
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \CyberSource\Core\Model\LoggerInterface $logger
     * @param \CyberSource\SecureAcceptance\Gateway\Config\Config $config
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Payment\Gateway\Command\CommandManagerInterface $commandManager,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \CyberSource\Core\Model\LoggerInterface $logger,
        DecoderInterface $urlDecoder,
        \Magento\Framework\Session\StorageInterface $sessionStorage,
        Config $config

    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->commandManager = $commandManager;
        $this->sessionManager = $sessionManager;
        $this->logger = $logger;
        $this->formKeyValidator = $formKeyValidator;
        $this->quoteRepository = $quoteRepository;
        $this->urlDecoder = $urlDecoder;
        $this->sessionStorage = $sessionStorage;
        $this->config = $config;
    }


    /**
     * Creates SA request JSON
     *
     * @return \Magento\Framework\Controller\ResultInterface|\Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {

        $result = $this->resultJsonFactory->create();
       
        try {

            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $this->sessionManager->getQuote();
           
            if (!$this->getRequest()->isPost()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Wrong method.'));
            }

            if (!$quote || !$quote->getId()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Unable to load cart data.'));
            }

            if (!$this->formKeyValidator->validate($this->getRequest())) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Invalid formkey.'));
            }
            
           if ($this->config->isMicroform()) {

            $commandResult = $this->commandManager->executeByCode(
                self::COMMAND_CODE,
                $quote->getPayment()
            );

            $commandResult = $commandResult->get();

            $captureContextValue = $commandResult['response'];
            $decodedCaptureResponse = json_decode($this->urlDecoder->decode(explode('.', $captureContextValue)[1]));

            $ctxData = $decodedCaptureResponse->ctx[0]->data ?? null;
            if ($ctxData) {
                $quoteExtension = $quote->getExtensionAttributes();
                if (!$quoteExtension) {
                    $quoteExtension = $this->quoteRepository->create();
                }
                if (!$quoteExtension->getClientLibraryIntegrity()) {
                    $quoteExtension->setClientLibraryIntegrity($ctxData->clientLibraryIntegrity ?? null);
                }
                if (!$quoteExtension->getClientLibrary()) {
                    $quoteExtension->setClientLibrary($ctxData->clientLibrary ?? null);
                }
                if (!$quoteExtension->getClientLibraryIntegrity()) {
                    $quoteExtension->setClientLibraryIntegrity($ctxData->clientLibraryIntegrity ?? null);
                }
                $quote->setExtensionAttributes($quoteExtension);
                $this->quoteRepository->save($quote);
                $result->setData(
                    [
                        'success' => true,
                        'token' => $commandResult['response'],
                        'clientLibrary' => $ctxData->clientLibrary,
                        'clientLibraryIntegrity' => $ctxData->clientLibraryIntegrity,
                    ]
                );
            }
        }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            $result->setData(['error_msg' => __('Unable to build Token request.')]);
        }

        return $result;
    }
}