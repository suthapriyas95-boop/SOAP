<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Checkout\Model\Session;
use CyberSource\SecureAcceptance\Model\Ui\ConfigProvider;

class DataAssignObserver extends AbstractDataAssignObserver
{
    const KEY_FLEX_SIGNED_FIELDS = 'signedFields';
    const KEY_FLEX_SIGNATURE = 'signature';
    const KEY_FLEX_TOKEN = 'token';
    const KEY_CARD_TYPE = 'ccType';
    const KEY_EXP_DATE = 'expDate';
    const KEY_FLEX_MASKED_PAN = 'maskedPan';

    /**
     * @var  \Magento\Framework\Session\SessionManagerInterface
     */
    protected $session;

    /**
     * @var \CyberSource\SecureAcceptance\Gateway\Config\Config
     */
    private $config;

    /**
     * @var \CyberSource\SecureAcceptance\Model\PaymentTokenManagement
     */
    private $paymentTokenManagement;

    /**
     * @var \CyberSource\SecureAcceptance\Model\Jwt\JwtProcessorInterface
     */
    private $jwtProcessor;

    /**
     * @param \Magento\Framework\Session\SessionManagerInterface $session
     * @param \CyberSource\SecureAcceptance\Model\PaymentTokenManagement $paymentTokenManagement
     * @param \CyberSource\SecureAcceptance\Gateway\Config\Config $config
     */
    public function __construct(
        \Magento\Framework\Session\SessionManagerInterface $session,
        \CyberSource\SecureAcceptance\Model\Jwt\JwtProcessorInterface $jwtProcessor,
        \CyberSource\SecureAcceptance\Model\PaymentTokenManagement $paymentTokenManagement,
        \CyberSource\SecureAcceptance\Gateway\Config\Config $config
    ) {
        $this->session = $session;
        $this->config = $config;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->jwtProcessor = $jwtProcessor;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        //TODO: split this observer into several ones to enforce SRP
        $this->assignMicroformData($observer);
        $this->assignCardType($observer);
        $this->assignCvv($observer);
        $this->assignCardExpirationDate($observer);
    }

    private function assignMicroformData($observer)
    {

        if (!$this->config->isMicroform()) {
            return;
        }

        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        $additionalData = new DataObject($additionalData);
        $payment = $this->readPaymentModelArgument($observer);

        if (!$token = $additionalData->getDataByKey(static::KEY_FLEX_TOKEN)) {
            return;
        }

        $payment->setAdditionalInformation('flexJwt', $token);

        if ($flexPaymentToken = $this->jwtProcessor->getFlexPaymentToken($token)) {
            $payment->setAdditionalInformation('transientToken', $flexPaymentToken);
        }

        if ($cardData = $this->jwtProcessor->getCardData($token)) {
            $payment->setAdditionalInformation(static::KEY_FLEX_MASKED_PAN, $cardData['number'] ?? null);
            $payment->setAdditionalInformation('cardType', $cardData['type'] ?? null);
        }

    }

    private function assignCvv($observer)
    {
        $data = $this->readDataArgument($observer);

        // Passing CVN for:
        // stored cards+config CVN enabled
        // or Secure acceptance (i.e. not microform)
        if (
            (!$this->_isVaultCCMethod($data) || !$this->_isCvvEnabled())
            && $this->config->isMicroform()
        ) {
            return;
        }

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_array($additionalData)) {
            return;
        }

        $additionalData = new DataObject($additionalData);
        if (!$cvv = $additionalData->getDataByKey('cvv') ?: $additionalData->getDataByKey('vault_cvv')) {
            return;
        }

        $payment = $this->readPaymentModelArgument($observer);
        $payment->setAdditionalInformation('cvv', $cvv);

        $this->session->setData('cvv', $cvv);

    }

    /**
     * @param Data
     * @return boolean
     */
    private function _isVaultCCMethod($data)
    {
        if ($data->getData(PaymentInterface::KEY_METHOD) != ConfigProvider::CC_VAULT_CODE) {
            return false;
        }
        return true;
    }

    /**
     * @return boolean
     */
    private function _isCvvEnabled()
    {
        return
            $this->config->getValue("enable_cvv") || $this->config->getValue("enable_admin_cvv");
    }

    private function assignCardType(Observer $observer)
    {
        if (
            !$this->config->isMicroform()
            && !$this->config->isSilent()
        ) {
            return;
        }

        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        $additionalData = new DataObject($additionalData);
        $payment = $this->readPaymentModelArgument($observer);

        if (!$cardType = $additionalData->getDataByKey(static::KEY_CARD_TYPE)) {
            return;
        }

        $payment->setAdditionalInformation('cardType', $cardType);
    }

    private function assignCardExpirationDate(Observer $observer)
    {
        if (
            !$this->config->isMicroform()
            && !(
                !$this->config->getIsLegacyMode()
                && $this->config->isSilent()
                && $this->config->getTokenPassExpirationDate()
            )
        ) {
            return;
        }

        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        $additionalData = new DataObject($additionalData);
        $payment = $this->readPaymentModelArgument($observer);

        if ($ccExpDate = $additionalData->getDataByKey(static::KEY_EXP_DATE)) {
            $payment->setAdditionalInformation(static::KEY_EXP_DATE, $ccExpDate);
        }
    }
}
