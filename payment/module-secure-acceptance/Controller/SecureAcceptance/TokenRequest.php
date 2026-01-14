<?php
/**
 *
 */

namespace CyberSource\SecureAcceptance\Controller\SecureAcceptance;

class TokenRequest extends \Magento\Framework\App\Action\Action
{

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
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \CyberSource\Core\Model\LoggerInterface $logger,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->commandManager = $commandManager;
        $this->sessionManager = $sessionManager;
        $this->logger = $logger;
        $this->formKeyValidator = $formKeyValidator;
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

            if (!$quote || !$quote->getId()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Unable to load card data.'));
            }

            if (!$this->getRequest()->isPost()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Wrong method.'));
            }

            if (!$this->formKeyValidator->validate($this->getRequest())) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Invalid formkey.'));
            }

            $commandResult = $this->commandManager->executeByCode(
                \CyberSource\SecureAcceptance\Gateway\Command\TokenCreateRequestCommand::COMMAND_CODE,
                $quote->getPayment(),
                [
                    'card_type' => $this->getRequest()->getParam('cc_type'),
                    'agreementIds' => $this->getRequest()->getParam('agreement')
                ]
            );

            $result->setData(
                [
                    'success' => true,
                    \CyberSource\SecureAcceptance\Model\Ui\ConfigProvider::CODE => ['fields' => $commandResult->get()]
                ]
            );
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            $result->setData(['error_messages' => $e->getMessage()]);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            $result->setData(['error_messages' => __('Unable to build Token request')]);
        }

        return $result;
    }
}
