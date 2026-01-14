<?php

namespace CyberSource\ThreeDSecure\Gateway\Request\Cca;

use CyberSource\ThreeDSecure\Gateway\Validator\PaEnrolledValidator;
use Magento\Vault\Model\Ui\VaultConfigProvider;

class PayerAuthEnrollBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    const TRANSACTION_MODE_ECOMMERCE = 'S';
    const DEVICE_CHANEL = 'Browser';

    /**
     * @var \CyberSource\ThreeDSecure\Gateway\Command\Cca\PayerAuthSetUpBuilderCommand
     */
    private $payerAuthSetUpBuilderCommand;

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Config\Config
     */
    private $config;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $url;

    /**
     * @var  \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    private $remoteAddress;

     /**
     * @var \Magento\Framework\Session\StorageInterface
     */
    private  $sessionStorage;

    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader,
        \CyberSource\SecureAcceptance\Gateway\Config\Config $config,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \CyberSource\ThreeDSecure\Gateway\Command\Cca\PayerAuthSetUpBuilderCommand $payerAuthSetUpBuilderCommand,
        \Magento\Framework\Session\StorageInterface $sessionStorage
    ) {
        $this->subjectReader = $subjectReader;
        $this->config = $config;
        $this->url = $url;
        $this->remoteAddress = $remoteAddress;
        $this->payerAuthSetUpBuilderCommand = $payerAuthSetUpBuilderCommand;
        $this->sessionStorage = $sessionStorage;
    }

    /**
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        $order = $paymentDO->getOrder();
        $browserDetails = $this->sessionStorage->getData('browser_details');

        if (!$referenceId = $payment->getAdditionalInformation(
            $this->payerAuthSetUpBuilderCommand::KEY_PAYER_AUTH_ENROLL_REFERENCE_ID)
        ) {
            throw new \InvalidArgumentException('3D Secure initialization is required. Reload the page and try again.');
        }

        $payerAuthEnrollService_returnURL = $this->url->getUrl('cybersource3ds/Payment/ReturnController');

        $requestArr = [
            'payerAuthEnrollService' => [
                'run' => 'true',
                'mobilePhone' => $order->getBillingAddress()->getTelephone() ?? '',
                'referenceID'=> $referenceId,
                'returnURL' => $payerAuthEnrollService_returnURL,
                'transactionMode' => self::TRANSACTION_MODE_ECOMMERCE,
                'httpAccept' => $_SERVER['HTTP_ACCEPT'],
                'httpUserAgent' => $_SERVER['HTTP_USER_AGENT'],
                'httpUserAccept' => $_SERVER['HTTP_ACCEPT'],
                'deviceChannel' => SELF::DEVICE_CHANEL,
            ],
                'billTo' => [
                'httpBrowserColorDepth' => $browserDetails['ColorDepth'],
                'httpBrowserJavaEnabled' => $browserDetails['JavaEnabled'],
                'httpBrowserJavaScriptEnabled' =>$browserDetails['JavaScriptEnabled'],
                'httpBrowserLanguage' => $browserDetails['Language'],
                'httpBrowserScreenHeight' => $browserDetails['ScreenHeight'],
                'httpBrowserScreenWidth' => $browserDetails['ScreenWidth'],
                'httpBrowserTimeDifference' => $browserDetails['TimeDifference'],
                'ipAddress' => $this->remoteAddress->getRemoteAddress(),
            ],
        ];

        if ($this->isScaRequired($payment)) {
            $requestArr['payerAuthEnrollService']['challengeCode'] = '04';
        }

        return $requestArr;
    }

    /**
     * Returns whether Strong Customer Authentication is required or not
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return boolean
     */
    private function isScaRequired($payment)
    {
        $result = false;
        $storeId = $payment->getOrder()->getStoreId();

        if ($payment->getAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE) && ($this->config->isScaEnforcedOnCardSaveSoap($storeId))) {
            $result = true;
        }

        if ($payment->getAdditionalInformation(PaEnrolledValidator::KEY_SCA_REQUIRED)) {
            $result = true;
            $payment->unsAdditionalInformation(PaEnrolledValidator::KEY_SCA_REQUIRED);
        }

        return $result;
    }
}
