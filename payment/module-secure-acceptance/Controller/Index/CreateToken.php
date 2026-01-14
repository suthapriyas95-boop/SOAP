<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Controller\Index;

use CyberSource\SecureAcceptance\Gateway\Config\Config;
use CyberSource\SecureAcceptance\Model\Ui\ConfigProvider;
use CyberSource\SecureAcceptance\Service\CyberSourceSoapApi;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use CyberSource\SecureAcceptance\Helper\RequestDataBuilder;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Model\PaymentTokenManagement;
use Magento\Vault\Model\PaymentTokenRepository;

class CreateToken extends \CyberSource\Core\Action\CsrfIgnoringAction
{
    /**
     * @var RequestDataBuilder
     */
    private $helper;

    /**
     * @var JsonFactory $resultJsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var CyberSourceSoapApi
     */
    private $cyberSourceSoapApi;

    /**
     * @var PaymentTokenFactoryInterface
     */
    private $paymentTokenFactory;

    /**
     * @var PaymentTokenRepository
     */
    private $paymentTokenRepository;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * CreateToken constructor.
     * @param Context $context
     * @param RequestDataBuilder $helper
     * @param JsonFactory $resultJsonFactory
     * @param CyberSourceSoapApi $cyberSourceSoapApi
     */
    public function __construct(
        Context $context,
        RequestDataBuilder $helper,
        JsonFactory $resultJsonFactory,
        CyberSourceSoapApi $cyberSourceSoapApi,
        PaymentTokenFactoryInterface $paymentTokenFactory,
        PaymentTokenRepository $paymentTokenRepository,
        Config $config,
        Session $customerSession,
        EncryptorInterface $encryptor
    ) {
        parent::__construct($context);

        $this->helper = $helper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->cyberSourceSoapApi = $cyberSourceSoapApi;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->config = $config;
        $this->customerSession = $customerSession;
        $this->encryptor = $encryptor;
    }

    public function execute()
    {
        $response = $this->_request->getParams();

        /**
         * When payment_token already exist in response, it's the return from cybersource for token creation
         */
        if (array_key_exists('reason_code', $response)) {

            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath($this->_url->getUrl('multishipping/checkout/billing'));

            if ($response['reason_code'] !== "100") {
                $this->messageManager->addErrorMessage(
                    __("Sorry but we are unable to create your token at this time. " . $response['reason_code'])
                );
            } else {
                $this->createVaultToken($response);
                $this->messageManager->addSuccessMessage(__("You have created token successful."));
            }

            return $resultRedirect;
        }

        $result = $this->resultJsonFactory->create();

        try {
            $requestData = $this->helper->buildCreateTokenRequest();
            $result->setHttpResponseCode(200);
            $result = $result->setData([
                'action_url' => $requestData['action_url'],
                'form_data' => $requestData
            ]);
        } catch (\Exception $e) {
            $result->setHttpResponseCode(400);
        }

        return $result;
    }

    private function createVaultToken($response)
    {
        try {
            $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);
            $paymentToken->setGatewayToken($response['payment_token']);
            $paymentToken->setExpiresAt($this->getExpirationDate($response['req_card_expiry_date']));
            $paymentToken->setIsVisible(true);
            $paymentToken->setIsActive(true);
            $paymentToken->setCustomerId($this->customerSession->getCustomerId());
            $paymentToken->setPaymentMethodCode(ConfigProvider::CODE);

            $paymentToken->setTokenDetails($this->convertDetailsToJSON([
                'title' => $this->config->getVaultTitle(),
                'incrementId' => $response['req_reference_number'],
                'type' => $this->helper->getCardType($response['req_card_type'], true),
                'maskedCC' => "****-****-****-" . substr($response['req_card_number'] ?? '', -4),
                'expirationDate' => str_replace("-", "/", $response['req_card_expiry_date']),
                'merchantId' => $this->config->getMerchantId()
            ]));

            $paymentToken->setPublicHash($this->generatePublicHash($paymentToken));

            $this->paymentTokenRepository->save($paymentToken);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
            throw $e;
        }
    }

    /**
     * @return string
     */
    private function getExpirationDate($cardExpiry)
    {
        $cardExpiry = explode("-", $cardExpiry ?? '');
        $expDate = new \DateTime(
            $cardExpiry[1]
            . '-'
            . $cardExpiry[0]
            . '-'
            . '01'
            . ' '
            . '00:00:00',
            new \DateTimeZone('UTC')
        );
        $expDate->add(new \DateInterval('P1M'));
        return $expDate->format('Y-m-d 00:00:00');
    }

    /**
     * Convert payment token details to JSON
     * @param array $details
     * @return string
     */
    private function convertDetailsToJSON($details)
    {
        $json = \Laminas\Json\Json::encode($details);
        return $json ? $json : '{}';
    }

    /**
     * Generate vault payment public hash
     *
     * @param PaymentTokenInterface $paymentToken
     * @return string
     */
    protected function generatePublicHash(PaymentTokenInterface $paymentToken)
    {
        $hashKey = $paymentToken->getGatewayToken();
        if ($paymentToken->getCustomerId()) {
            $hashKey = $paymentToken->getCustomerId();
        }

        $hashKey .= $paymentToken->getPaymentMethodCode()
            . $paymentToken->getType()
            . $paymentToken->getTokenDetails();

        return $this->encryptor->getHash($hashKey);
    }
}
