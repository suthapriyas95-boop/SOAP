<?php

namespace CyberSource\SecureAcceptance\Gateway\Response;

use CyberSource\SecureAcceptance\Gateway\Config\Config;
use CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader;
use CyberSource\SecureAcceptance\Helper\RequestDataBuilder;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use CyberSource\SecureAcceptance\Helper\Vault;
use CyberSource\SecureAcceptance\Model\PaymentTokenManagement;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;

class VaultDetailsHandler implements HandlerInterface
{
    /**
     * @var PaymentTokenFactoryInterface
     */
    protected $paymentTokenFactory;

    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    protected $paymentExtensionFactory;

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var RequestDataBuilder
     */
    private $helper;

    /**
     * @var Vault
     */
    private $vaultHelper;

    /**
     * @var PaymentTokenManagement
     */
    private $paymentTokenManagement;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;

    /**
     * Constructor
     *
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param PaymentTokenManagement $paymentTokenManagement
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param Config $config
     * @param SubjectReader $subjectReader
     * @param RequestDataBuilder $helper
     * @param Vault $vaultHelper
     */
    public function __construct(
        PaymentTokenFactoryInterface $paymentTokenFactory,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        PaymentTokenManagement $paymentTokenManagement,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        Config $config,
        SubjectReader $subjectReader,
        RequestDataBuilder $helper,
        Vault $vaultHelper
    ) {
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->config = $config;
        $this->subjectReader = $subjectReader;
        $this->helper = $helper;
        $this->vaultHelper = $vaultHelper;

    }

    /**
     * @inheritdoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        $payment = $this->getValidPaymentInstance($handlingSubject);

        if ($payment->getAdditionalInformation(AbstractResponseHandler::TOKEN_DATA) == null) {
            return;
        }

        // we must read the value of token_enabled flag and don't save a token if it's false or empty
        if (!$payment->getAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE)) {
            //erase token if it's not required to save
            $payment->unsAdditionalInformation(AbstractResponseHandler::TOKEN_DATA);
            return;
        }

        if (!$paymentToken = $this->getVaultPaymentToken($payment)) {
            return;
        }

        $extensionAttributes = $this->getExtensionAttributes($payment);
        $extensionAttributes->setVaultPaymentToken($paymentToken);

        $payment->unsAdditionalInformation(AbstractResponseHandler::TOKEN_DATA);

    }

    /**
     * Get payment extension attributes
     * @param InfoInterface $payment
     * @return OrderPaymentExtensionInterface
     */
    private function getExtensionAttributes(InfoInterface $payment)
    {
        $extensionAttributes = $payment->getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->paymentExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }
        return $extensionAttributes;
    }

    private function getVaultPaymentToken(\Magento\Sales\Api\Data\OrderPaymentInterface $payment)
    {
        $tokenData = $payment->getAdditionalInformation(AbstractResponseHandler::TOKEN_DATA);

        /** @var PaymentTokenInterface $paymentToken */
        $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);

        if (empty($tokenData) || $tokenData === null) {
            return $paymentToken;
        }

        $instrumentId = $tokenData['instrument_id'] ?? null;
        $customerId = $payment->getOrder()->getCustomerId();

        if ($customerId
            && $instrumentId
            && $existingToken = $this->paymentTokenManagement->getCustomerTokenWithInstrumentIdentifier(
                $customerId,
                $instrumentId
            )
        ) {
            $paymentToken = $existingToken;
        }

        $paymentToken->setGatewayToken($tokenData['payment_token']);
        $paymentToken->setExpiresAt($this->getExpirationDate($tokenData['card_expiry_date']));

        $resultToken = [
            'title' => $this->config->getVaultTitle(),
            'incrementId' => $payment->getOrder()->getIncrementId(),
            'type' => $this->helper->getCardType($tokenData['card_type'], true),
            'maskedCC' => $tokenData['cc_last4'],
            'expirationDate' => str_replace("-", "/", $tokenData['card_expiry_date']),
            'merchantId' => $this->config->getMerchantId($payment->getOrder()->getStoreId()),
            'instrumentIdentifierID' => $instrumentId,
        ];

        if (isset($tokenData['card_bin'])) {
            $resultToken['cardBIN'] = $tokenData['card_bin'];
        }

        $paymentToken->setTokenDetails($this->convertDetailsToJSON($resultToken));

        if ($paymentToken->getEntityId()) {
            // Update exiting vault token subscriptionID and other details in this place.
            // New tokens are saved in \Magento\Vault\Observer\AfterPaymentSaveObserver::execute method.
            $this->paymentTokenRepository->save($paymentToken);
        }

        return $paymentToken;
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
     * @param array $buildSubject
     * @return \Magento\Payment\Model\InfoInterface
     */
    protected function getValidPaymentInstance(array $buildSubject)
    {
        /** @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface $paymentDO */
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        /** @var \Magento\Payment\Model\InfoInterface $payment */
        $payment = $paymentDO->getPayment();

        ContextHelper::assertOrderPayment($payment);

        return $payment;
    }

    /**
     * @param string $token
     * @param InfoInterface $payment
     * @return bool
     */
    private function isTokenUnique($token, InfoInterface $payment)
    {
        $customerId = $payment->getOrder()->getCustomerId();
        $methodCode = $payment->getMethodInstance()->getCode();

        return !$this->paymentTokenManagement->getByGatewayToken($token, $methodCode, $customerId);
    }
}
