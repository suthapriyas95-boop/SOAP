<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace CyberSource\SecureAcceptance\Plugin\Controller\Cards;

use CyberSource\SecureAcceptance\Helper\RequestDataBuilder;
use CyberSource\SecureAcceptance\Service\CyberSourceSoapApi;
use Magento\Vault\Model\PaymentTokenManagement;
use Magento\Framework\App\Request\Http;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Customer\Model\Session;

/**
 * Class DeleteActionPlugin
 * Plugin for delete token from CyberSource when deleting it from Magento
 *
 * @package CyberSource\PayPal\Model\Express
 */
class DeleteTokenPlugin
{
    /**
     * @var PaymentTokenManagement
     */
    private $paymentTokenManagement;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var RequestDataBuilder
     */
    private $requestDataBuilder;

    /**
     * @var CyberSourceSoapApi
     */
    private $cyberSourceSoapApi;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    private $redirect;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @var \Magento\Framework\App\ResponseInterface
     */
    private $response;

    /**
     * DeleteTokenPlugin constructor.
     * @param PaymentTokenManagement $paymentTokenManagement
     * @param Session $customerSession
     * @param RequestDataBuilder $requestDataBuilder
     * @param CyberSourceSoapApi $cyberSourceSoapApi
     * @param \Magento\Framework\App\Response\RedirectInterface $redirect
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        PaymentTokenManagement $paymentTokenManagement,
        Session $customerSession,
        RequestDataBuilder $requestDataBuilder,
        CyberSourceSoapApi $cyberSourceSoapApi,
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\ResponseInterface $response
    ) {
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->customerSession = $customerSession;
        $this->requestDataBuilder = $requestDataBuilder;
        $this->cyberSourceSoapApi = $cyberSourceSoapApi;
        $this->redirect = $redirect;
        $this->messageManager = $messageManager;
        $this->response = $response;
    }

    public function aroundExecute(\Magento\Vault\Controller\Cards\DeleteAction $subject, \Closure $proceed)
    {
        $request = $subject->getRequest();
        $paymentToken = $this->getPaymentToken($request);

        if ($paymentToken !== null && !empty($paymentToken->getData())) {

            if ($paymentToken->getPaymentMethodCode() != \CyberSource\SecureAcceptance\Model\Ui\ConfigProvider::CODE) {
                return $proceed();
            }

            $requestData = $this->requestDataBuilder->buildDeleteTokenRequest($paymentToken);

            $result = $this->cyberSourceSoapApi->run($requestData);

            if ($result && $result->reasonCode !== 100) {
                $this->messageManager->addErrorMessage(__('Deletion failure. Please try again. Error: '. $result->reasonCode));
                $this->redirect->redirect($subject->getResponse(), 'vault/cards/listaction');
            } else {
                return $proceed();
            }
        }
    }

    /**
     * @param Http $request
     * @return PaymentTokenInterface|null
     */
    private function getPaymentToken(Http $request)
    {
        $publicHash = $request->getPostValue(PaymentTokenInterface::PUBLIC_HASH);

        if ($publicHash === null) {
            return null;
        }

        return $this->paymentTokenManagement->getByPublicHash(
            $publicHash,
            $this->customerSession->getCustomerId()
        );
    }
}
