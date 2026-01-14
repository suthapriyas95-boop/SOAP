<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Controller\Index;

class LoadSilentData extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Config\Config
     */
    protected $config;

    /**
     * \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var \Magento\Payment\Gateway\Command\CommandManagerInterface
     */
    private $commandManager;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    private $formKeyValidator;

    /**
     * @var \Magento\Framework\Session\SessionManager
     */
    private $sessionManager;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \CyberSource\Core\Model\LoggerInterface
     */
    private $logger;

    /**
     * LoadSilentData constructor.
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \CyberSource\SecureAcceptance\Gateway\Config\Config $config,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Payment\Gateway\Command\CommandManagerInterface $commandManager,
        \Magento\Framework\Session\SessionManager $sessionManager,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \CyberSource\Core\Model\LoggerInterface $logger,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->commandManager = $commandManager;
        $this->sessionManager = $sessionManager;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->formKeyValidator = $formKeyValidator;
    }

    public function execute()
    {

        if (!$this->config->getIsLegacyMode()) {
            $this->_forward('TokenRequest', 'SecureAcceptance');
            return;
        }

        $result = $this->resultJsonFactory->create();
        $data = [];

        try {
            if (!$this->formKeyValidator->validate($this->getRequest())) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Invalid formkey.'));
            }

            $order = $this->orderRepository->get($this->sessionManager->getLastOrderId());

            $commandResult = $this->commandManager->executeByCode(
                'sop_request',
                $order->getPayment(),
                [
                    'amount' => $order->getBaseTotalDue(),
                    'card_type' => $this->getRequest()->getParam('cc_type'),
                    'agreementIds' => $this->getRequest()->getParam('agreement'),
                ]
            );

            $data = [
                'success' => true,
                \CyberSource\SecureAcceptance\Model\Ui\ConfigProvider::CODE => ['fields' => $commandResult->get()],
            ];
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $data['error_messages'] = $e->getMessage();
            $this->logger->error($e->getMessage());
        } catch (\Exception $e) {
            $data['error_messages'] = __('An error occurred.');
            $this->logger->error($e->getMessage());
        }

        $result = $result->setData($data);
        return $result;
    }
}
