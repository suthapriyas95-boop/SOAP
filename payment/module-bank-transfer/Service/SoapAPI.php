<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\BankTransfer\Service;

use CyberSource\BankTransfer\Model\Config;
use CyberSource\BankTransfer\Helper\RequestDataBuilder;
use CyberSource\Core\Model\LoggerInterface;
use CyberSource\Core\Service\MultiMidAbstractConnection;
use Magento\Framework\App\Config\ScopeConfigInterface;


class SoapAPI extends MultiMidAbstractConnection{
    const SUCCESS_REASON_CODE = 100;

    /**
     * @var \SoapClient
     */
    public $client;

    /**
     * @var RequestDataBuilder
     */
    protected $dataBuilder;

    /**
     * @var Config
     */
    protected $_gatewayConfig;

    /**
     * @var String 
     */
    protected $paymentMethod;

    /**
     * CyberSourceSoapAPI constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param RequestDataBuilder $dataBuilder
     * @param Config $gatewayConfig
     * @param $paymentMethod
     * @param \SoapClient|null $client
     * @throws \Exception
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        RequestDataBuilder $dataBuilder,
        Config $gatewayConfig,
        $paymentMethod,
        ?\SoapClient $client = null
    ) {
        $storeId = $this->getCurrentStoreId();
        $this->_gatewayConfig = $gatewayConfig;
        $this->_gatewayConfig->setBankTransferPaymentMethod($paymentMethod);
        parent::__construct($scopeConfig,
        $logger, 
        $this->_gatewayConfig->getMerchantId($storeId),
        $this->_gatewayConfig->getP12Certificate($storeId), 
        $this->_gatewayConfig->getP12AccessKey($storeId));


        /**
         * Added soap client as parameter to be able to mock in unit tests.
         */
        if ($client !== null) {
            $this->setSoapClient($client);
        }

        $this->client = $this->getSoapClient();
        $this->dataBuilder = $dataBuilder;
        $this->paymentMethod = $paymentMethod;
    }


    /**
     * @param $quoteId
     * @return array
     */
    public function getListOfBanks($quoteId)
    {
        $data = [];
        $request = [];
        $request['apOptionsService'] = ['run' => 'true'];
        $request['merchantID'] = $this->merchantId;
        $request['merchantReferenceCode'] = $quoteId;
        $request['apPaymentType'] = 'IDL';

        try {
            $this->logger->debug([__METHOD__ => (array) $request]);
            $result = $this->client->runTransaction(json_decode(json_encode($request)));
            $this->logger->debug([(array) $result]);

            if ($result->reasonCode == 100) {
                foreach ($result->apOptionsReply->option as $opt) {
                    $data[$opt->id] = $opt->name;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error("bank list: " . $e->getMessage());
        }

        return $data;
    }


    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $store
     * @param null $deviceId
     * @return array
     */
    public function bankTransferSale($quote, $store, $bankcode, $deviceId = null)
    {
        $request = $this->dataBuilder->buildSaleService($store , $this->merchantId, $quote, $bankcode);

        $data = [];

        try {
            $this->logger->debug([__METHOD__ => (array) $request]);
            $result = $this->client->runTransaction($request);
            $this->logger->debug([(array) $result]);

            if (!empty($result) && $result->reasonCode == 100) {
                $data['redirect_url'] = $result->apSaleReply->merchantURL;
                $data['response'] = $result;
            } else {
                $data['redirect_url'] = $store->getBaseUrl() . 'cybersourcebt/index/failure';
            }
        } catch (\Exception $e) {
            $this->logger->error("bank transfer sale: " . $e->getMessage());
            $data['error'] = $e->getMessage();
        }

        return $data;
    }


    /**
     * @param $orderId
     * @param $requestId
     * @return null
     */
    public function checkBankTransferStatus($orderId, $requestId)
    {
        $request = $this->dataBuilder->buildCheckStatusService($requestId, $orderId, $this->paymentMethod, $this->merchantId);

        $result = null;
        try {
            $this->logger->debug([__METHOD__ => (array) $request]);
            $result = $this->client->runTransaction($request);
            $this->logger->debug([(array) $result]);
        } catch (\Exception $e) {
            $this->logger->error("check bank transfer status: " . $e->getMessage());
        }
        return $result;
    }


    /**
     * @param $order
     * @param $requestId
     * @param $paymentMethod
     * @return null
     */
    public function bankTransferRefund($order, $requestId)
    {
        $request = $this->dataBuilder->buildRefundService($requestId, $order, $this->paymentMethod, $this->merchantId);

        $result = null;
        try {
            $this->logger->debug([__METHOD__ => (array) $request]);
            $result = $this->client->runTransaction($request);
            $this->logger->debug([(array) $result]);
        } catch (\Exception $e) {
            $this->logger->error("check bank transfer status: " . $e->getMessage());
        }
        return $result;
    }
}

