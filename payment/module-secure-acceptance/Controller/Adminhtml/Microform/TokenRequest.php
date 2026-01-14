<?php
/**
 *
 */

namespace CyberSource\SecureAcceptance\Controller\Adminhtml\Microform;

use Magento\Framework\UrlInterface;

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
     * @var UrlInterface
     */
    private $url;

    /**
     * TokenRequest constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Payment\Gateway\Command\CommandManagerInterface $commandManager
     * @param \Magento\Framework\Session\SessionManagerInterface $sessionManager
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \CyberSource\Core\Model\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Payment\Gateway\Command\CommandManagerInterface $commandManager,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \CyberSource\Core\Model\LoggerInterface $logger,
        UrlInterface $url
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->commandManager = $commandManager;
        $this->sessionManager = $sessionManager;
        $this->logger = $logger;
        $this->formKeyValidator = $formKeyValidator;
        $this->quoteRepository = $quoteRepository;
        $this->url = $url;
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

            $commandResult = $this->commandManager->executeByCode(
                self::COMMAND_CODE,
                $quote->getPayment()
            );

            $commandResult = $commandResult->get();

            $this->quoteRepository->save($quote);
			$responseArr = ['success' => true, 'error' => false, 'token' => $commandResult['response'], 'placeOrderUrl' => $this->url->getUrl('chcybersource/microform/flexPlaceOrder', ['_secure' => true])];
            $result->setData($responseArr);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->getErrorResponse($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            return $this->getErrorResponse();
        }

        return $result;
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
        $message = $message ? $message : __('Unable to build token request');
        $this->messageManager->addErrorMessage($message);
        $redirectUrl = $this->url->getUrl('sales/order/index',['_secure' => true]);
        return $this->resultJsonFactory->create()->setData(
            [
                'redirect_url' => $redirectUrl,
                'success' => false,
                'error' => true,
                'error_messages' => $message
            ]
        );
    }

}