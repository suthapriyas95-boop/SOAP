<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Address\Controller\Index;

use CyberSource\Core\Service\CyberSourceSoapAPI;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Quote\Model\QuoteManagement;
use Magento\Checkout\Model\Cart;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

class Address extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var QuoteManagement
     */
    private $quoteManagement;

    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CyberSourceSoapAPI
     */
    private $cyberSourceAPI;

    /**
     * @var OrderPaymentRepositoryInterface
     */
    private $orderPaymentRepository;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Directory\Model\Region
     */
    private $regionModel;
    
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address
     */
    private $quoteAddress;
    

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        SessionManagerInterface $checkoutSession,
        QuoteManagement $quoteManagement,
        Cart $cart,
        StoreManagerInterface $storeManager,
        CyberSourceSoapAPI $cyberSourceAPI,
        \Magento\Directory\Model\Region $regionModel,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Quote\Model\Quote\Address $quoteAddress
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->quoteManagement = $quoteManagement;
        $this->cart = $cart;
        $this->storeManager = $storeManager;
        $this->cyberSourceAPI = $cyberSourceAPI;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->scopeConfig = $scopeConfig;
        $this->regionModel = $regionModel;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->quoteAddress = $quoteAddress;
        parent::__construct($context);
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $region_id = $this->_request->getParam('region_id');
        if (!empty($region_id) && is_numeric($region_id)) {
             $region = $this->regionModel->load($region_id);
        }

        $shippingAddress = [
            'city' => $this->_request->getParam('city'),
            'country' => $this->_request->getParam('country'),
            'firstname' => $this->_request->getParam('firstname'),
            'lastname' => $this->_request->getParam('lastname'),
            'postcode' => $this->_request->getParam('postcode'),
            'region_code' => (!empty($region)) ? $region->getCode() : '',
            'street1' => $this->_request->getParam('street1'),
            'street2' => $this->_request->getParam('street2'),
            'telephone' => $this->_request->getParam('telephone')
        ];

        $this->quoteAddress->setData('city', $this->_request->getParam('city'));
        $this->quoteAddress->setData('country', $this->_request->getParam('country'));
        $this->quoteAddress->setData('firstname', $this->_request->getParam('firstname'));
        $this->quoteAddress->setData('lastname', $this->_request->getParam('lastname'));
        $this->quoteAddress->setData('postcode', $this->_request->getParam('postcode'));
        $this->quoteAddress->setData('region_code', (!empty($region)) ? $region->getCode() : '');
        $this->quoteAddress->setData('street1', $this->_request->getParam('street1'));
        $this->quoteAddress->setData('street2', $this->_request->getParam('street2'));
        $this->quoteAddress->setData('telephone', $this->_request->getParam('telephone'));

        $merchantId = $this->scopeConfig->getValue(
            "payment/chcybersource/merchant_id",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $data = [
            'needCheck' => (bool)$this->scopeConfig->getValue(
                "payment/chcybersource/address_check_enabled",
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ),
            'needForce' => (bool)$this->scopeConfig->getValue(
                "payment/chcybersource/address_force_normal",
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ),
            'updateFields' => [],
            'needUpdate' => false,
            'isValid' => false,
            'message' => '',
            'normalizationData' => []
        ];
        
        $needUpdate = false;
        $fieldsUpdate = [];
        $displayAddress = [];
                
        if ($this->scopeConfig->getValue(
            "payment/chcybersource/address_check_enabled",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        )) {
            $result = $this->cyberSourceAPI->checkAddress(
                $merchantId,
                uniqid('avs_request_' . $this->checkoutSession->getQuoteId() . '_'),
                $shippingAddress,
                $this->quoteAddress
            );

            if (empty($result) || $result->reasonCode != 100) {
                return $this->handleError($result, $data);
            } else {
                $data['isValid'] = true;
                $davReply = json_decode(json_encode($result->davReply), 1);

                $fieldsToCheck = [
                    'standardizedAddress1' => 'street1',
                    'standardizedAddress2' => 'street2',
                    'standardizedCity' => 'city',
                    'standardizedPostalCode' => 'postcode',
                    'standardizedState' => 'region_code',
                ];

                foreach ($fieldsToCheck as $cybersourceKey => $magentoKey) {
                    if (!empty($davReply[$cybersourceKey]) && $davReply[$cybersourceKey] != $shippingAddress[$magentoKey]) {
                        $needUpdate = true;
                        $fieldsUpdate[$magentoKey] = $davReply[$cybersourceKey];
                        if (preg_match('/^street(\d+)/', $magentoKey, $matches)) {
                            $streetIndex = $matches[1] - 1;
                            $data['normalizationData']['street[' . ($streetIndex) . ']'] = $davReply[$cybersourceKey];

                            if ($streetIndex == 0) {
                                $data['normalizationData']['street[' . ($streetIndex + 1) . ']'] = '';
                            }

                        } else {
                            $data['normalizationData'][$magentoKey] = $davReply[$cybersourceKey];
                        }
                    }
                }

                if ($needUpdate) {
                    $country = $this->_request->getParam('country');

                    if ($country == 'US' && ($fieldsUpdate['region_code'] ?? false)) {
                        $regionId = $this->regionModel->loadByCode($fieldsUpdate['region_code'], $country)->getId();
                        
                        $fieldsUpdate['region_id'] = $regionId;
                        $data['normalizationData']['region_id'] = $regionId;

                        $fieldsUpdate['region'] = $this->regionModel->load($regionId)->getName();
                        $data['normalizationData']['region'] = $this->regionModel->load($regionId)->getName();
                    }

                    foreach ($davReply as $key => $value) {
                        if (preg_match('/^standardized(.+)/', $key, $match) &&
                            !in_array($match[1], ['CSP', 'ISOCountry', 'AddressNoApt'])
                        ) {
                            $displayAddress[] = $match[1].': '.$value;
                        }
                    }
                    $data['updateFields'] = implode(',', $fieldsUpdate);
                    $data['message'] = __(
                        'Our address verification system has suggested your address should read as follows.' .
                        'Please review and confirm the suggested address is correct.'
                    ).'<br /><br />' . implode('<br />', $displayAddress);
                }
            }
        }
        $data['needUpdate'] = $needUpdate;
        $data['updateFields'] = $fieldsUpdate;
        return $this->sendJsonResult($data);
    }

    private function handleError($result, $data)
    {

        if (empty($result)) {
            // we must pass validation if we didn't receive any response, for example if SOAP key wasn't configured -
            // this is magento marketplace requirement
            $data['isValid'] = true;
            return $this->sendJsonResult($data);
        }

        if ($result->reasonCode == 102 && !empty($result->invalidField)) {
            $data['message'] = __(
                'Address validation  failed. Please check input address data. Field ' . $result->invalidField
            );
        } else {
            $data['message'] = __('Validation address failed. Please check input address data.');
        }

        return $this->sendJsonResult($data);
    }

    private function sendJsonResult($data)
    {
        return $this->resultJsonFactory->create()->setData($data);
    }
}
