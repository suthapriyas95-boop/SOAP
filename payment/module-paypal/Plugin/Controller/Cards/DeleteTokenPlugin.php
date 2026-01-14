<?php

namespace CyberSource\PayPal\Plugin\Controller\Cards;

use CyberSource\PayPal\Helper\RequestDataBuilder;
use CyberSource\PayPal\Service\CyberSourcePayPalSoapAPI;
use Magento\Framework\Exception\LocalizedException;
use Magento\Vault\Model\PaymentTokenManagement;
use Magento\Framework\App\Request\Http;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Customer\Model\Session;

class DeleteTokenPlugin
{
    /**
     * @var PaymentTokenManagement
     */
    private $paymentTokenManagement;

    /**
     * @var RequestDataBuilder
     */
    private $requestDataBuilder;

    /**
     * @var CyberSourcePayPalSoapAPI
     */
    private $cyberSourcePayPalSoapApi;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @param PaymentTokenManagement $paymentTokenManagement
     * @param RequestDataBuilder $requestDataBuilder
     * @param CyberSourcePayPalSoapAPI $cyberSourceSoapApi
     * @param Session $customerSession
     */
    public function __construct(
        PaymentTokenManagement $paymentTokenManagement,
        RequestDataBuilder $requestDataBuilder,
        CyberSourcePayPalSoapAPI $cyberSourceSoapApi,
        Session $customerSession
    ) {
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->requestDataBuilder = $requestDataBuilder;
        $this->cyberSourcePayPalSoapApi = $cyberSourceSoapApi;
        $this->customerSession = $customerSession;
    }

    /**
     * @param \Magento\Vault\Controller\Cards\DeleteAction $subject
     * @param \Closure $proceed
     * @return mixed
     *
     * @throws LocalizedException
     */
    public function aroundExecute(\Magento\Vault\Controller\Cards\DeleteAction $subject, \Closure $proceed)
    {
        $request = $subject->getRequest();

        if (! $paymentToken = $this->getPaymentToken($request)) {
            throw new LocalizedException(__('Unable to find Billing Agreement'));
        }

        if ($paymentToken->getPaymentMethodCode() != \CyberSource\PayPal\Model\Config::CODE) {
            return $proceed();
        }

        $this->cyberSourcePayPalSoapApi->cancelBillingAgreementService(
            $this->requestDataBuilder->buildCancelBillingAgreementService($paymentToken->getGatewayToken())
        );

        return $proceed();
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
