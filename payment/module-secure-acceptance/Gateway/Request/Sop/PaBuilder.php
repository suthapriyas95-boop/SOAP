<?php
/*
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */


namespace CyberSource\SecureAcceptance\Gateway\Request\Sop;

use Magento\Vault\Model\Ui\VaultConfigProvider;


class PaBuilder implements \Magento\Payment\Gateway\Request\BuilderInterface
{
    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader
     */
    private $subjectReader;

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Config\Config
     */
    private $config;

    /**
     * Secure Acceptance Payer-Authentication request Builder
     *
     * @param \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader
     * @param \CyberSource\SecureAcceptance\Gateway\Config\Config $config
     */
    public function __construct(
        \CyberSource\SecureAcceptance\Gateway\Helper\SubjectReader $subjectReader,
        \CyberSource\SecureAcceptance\Gateway\Config\Config $config
    ){
        $this->subjectReader = $subjectReader;
        $this-> config = $config;
    }
    
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        $result = [
            'payer_auth_enroll_service_run' => 'true',
        ];

        if($this->isScaRequired($payment)){
            $result['payer_authentication_challenge_code'] = '04';
        }
        return $result;
    }
    
    /**
     * Returns whether Strong Customer Authentication is required or not
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return boolean
     */
    private function isScaRequired($payment){
        $result = false;
        $storeId = $payment->getOrder()->getStoreId();
        if ($payment->getAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE)){
            if($this->config->isScaEnforcedOnCardSaveSa($storeId)){
                $result = true;
            }  
        }
        else if($this->config->isScaEnforcedWithoutCardSaveSa($storeId)){
            $result = true;
        }
        return $result;
    }
}
