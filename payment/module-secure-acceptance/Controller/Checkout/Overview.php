<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Controller\Checkout;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Multishipping\Model\Checkout\Type\Multishipping\State;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;

class Overview extends \Magento\Multishipping\Controller\Checkout\Overview
{
    /**
     * @var \Magento\Vault\Api\PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * Overview constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param AccountManagementInterface $accountManagement
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $accountManagement,
        \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement
    ) {
        parent::__construct($context, $customerSession, $customerRepository, $accountManagement);
        $this->paymentTokenManagement = $paymentTokenManagement;
    }

    /**
     * Multishipping checkout place order page
     *
     * @return void
     */
    public function execute()
    {
        if (!$this->_validateMinimumAmount()) {
            return;
        }

        $this->_getState()->setActiveStep(State::STEP_OVERVIEW);

        try {
            $payment = $this->getRequest()->getPost('payment', []);

            if (!empty($payment)) {
                $payment['checks'] = [
                    \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_COUNTRY,
                    \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_CURRENCY,
                    \Magento\Payment\Model\Method\AbstractMethod::CHECK_ORDER_TOTAL_MIN_MAX,
                    \Magento\Payment\Model\Method\AbstractMethod::CHECK_ZERO_TOTAL,
                ];
                $this->assignToken($payment);
                $this->_getCheckout()->setPaymentMethod($payment);
            }

            $this->_getState()->setCompleteStep(State::STEP_BILLING);

            $this->_view->loadLayout();
            $this->_view->renderLayout();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
            $this->_redirect('*/*/billing/');
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('We cannot open the overview page.'));
            $this->_redirect('*/*/billing/');
        }
    }

    private function assignToken($payment)
    {
        if ($payment['method'] !== \CyberSource\SecureAcceptance\Model\Ui\ConfigProvider::CC_VAULT_CODE) {
            return;
        }

        $tokenId = $payment['token_public_hash'];
        $token = $this->getToken($tokenId, $this->_getCheckout()->getCustomer()->getId());

        if (!$token) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Please try again'));
        }

        /**
         * Prepare payment to be a vault payment
         * @see \Magento\Vault\Model\Method\Vault::attachTokenExtensionAttribute
         */
        $this->_getCheckout()->getQuote()->getPayment()->setAdditionalInformation(
            PaymentTokenInterface::PUBLIC_HASH,
            $token->getPublicHash()
        );

        $this->_getCheckout()->getQuote()->getPayment()->setAdditionalInformation(
            PaymentTokenInterface::CUSTOMER_ID,
            $this->_getCheckout()->getCustomer()->getId()
        );
    }

    /**
     * @param $publicHash
     * @param $customerId
     * @return \Magento\Vault\Api\Data\PaymentTokenInterface
     */
    private function getToken($publicHash, $customerId)
    {
        return $this->paymentTokenManagement->getByPublicHash($publicHash, $customerId);
    }
}
