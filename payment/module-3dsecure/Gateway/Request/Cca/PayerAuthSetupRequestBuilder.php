<?php

namespace CyberSource\ThreeDSecure\Gateway\Request\Cca;

class PayerAuthSetupRequestBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var \CyberSource\SecureAcceptance\Model\PaymentTokenManagement
     */
    private $paymentTokenManagement;

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Config\Config
     */
    private $config;

     /**
     * @var \Magento\Checkout\Model\Session
     */
    private $modelCheckoutSession;

     /**
     * @var \Magento\Framework\Session\StorageInterface
     */
    private  $sessionStorage;

    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader,
        \CyberSource\SecureAcceptance\Model\PaymentTokenManagement $paymentTokenManagement,
        \CyberSource\SecureAcceptance\Gateway\Config\Config $config,
        \Magento\Checkout\Model\Session $modelCheckoutSession,
        \Magento\Framework\Session\StorageInterface $sessionStorage
    ) {
        $this->subjectReader = $subjectReader;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->config = $config;
        $this->modelCheckoutSession = $modelCheckoutSession;
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
        $storeId = $paymentDO->getOrder()->getStoreId();
        $token = $this->paymentTokenManagement->getTokenFromPayment($payment);
        $quote = $this->modelCheckoutSession->getQuote();
        $transientToken =$payment->getAdditionalInformation('transientToken');
        $quote->reserveOrderId();
        $cardDetails = $this->sessionStorage->getData('browser_details');
        $requestArr = [
            'merchantID' => $this->config->getMerchantId($storeId),
            'merchantReferenceCode' => $quote->getReservedOrderId(),
            'payerAuthSetupService' => [
                'run' => 'true',
            ],
        ];
        if($this->config->isMicroform() && $transientToken!= null){
            $expDate = $payment->getAdditionalInformation('expDate');
            list($month, $year) = explode("-", $expDate);
            $requestArr['tokenSource'] = [
                'transientToken' => $transientToken,
            ];
            
            $requestArr['card'] = [
                'expirationMonth' => $month,
                'expirationYear' => $year,
            ];
        }
        elseif($this->config->isSilent() && $cardDetails['method']!="chcybersource_cc_vault")
        {   
            $requestArr['card'] = [
            'accountNumber' =>  $cardDetails['accountNumber'],
            'expirationMonth' => $cardDetails['cardExpMonth'],
            'expirationYear' => $cardDetails['cardExpYear'],
            'cardType' => $cardDetails['cardType'],
            ];       
        }
        else
        {
            $requestArr['recurringSubscriptionInfo'] = [
                'subscriptionID' => $token,
            ]; 
        }
        return $requestArr;
    }
}
       
