<?php
/**
 * Copyright © 2018 CollinsHarper. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\KlarnaFinancial\Gateway\Http\Client;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use CyberSource\Core\Model\LoggerInterface;
use SoapClient as stdSoapClient;
use CyberSource\Core\Service\MultiMidAbstractConnection;
use CyberSource\KlarnaFinancial\Gateway\Config\Config;

class SOAPClient extends MultiMidAbstractConnection implements ClientInterface
{
	 /**
     * @var Config
     */
    private $gatewayConfig;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param stdSoapClient|null $client
	 * @param Config $gatewayConfig
     * @throws \Exception
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
		Config $gatewayConfig,
        ?stdSoapClient $client = null
    ) {
		$this->gatewayConfig = $gatewayConfig;
		$storeId = $this->getCurrentStoreId();
        parent::__construct($scopeConfig, 
        $logger,
         $this->gatewayConfig->getMerchantId($storeId), 
         $this->gatewayConfig->getP12Certificate($storeId), 
         $this->gatewayConfig->getP12AccessKey($storeId));
        /**
         * Added soap client as parameter to be able to mock in unit tests.
         */
        if ($client !== null) {
            $this->setSoapClient($client);
        }
    }

    /**
     * @param TransferInterface $transferObject
     * @return array
     * @throws LocalizedException
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $request = (object) $transferObject->getBody();
		$request->merchantID = $this->getMid();


        $dmenabled = 'false';
        $request->decisionManager = [
            'enabled' => $dmenabled,
        ];
        $log = [
            'request' => (array) $request,
            'client' => static::class
        ];
        $response = [];

        try {
            $response = $this->client->runTransaction($request);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            throw new LocalizedException(__('Unable to retrieve payment information'));
        } finally {
            $log['response'] = (array) $response;
            $this->logger->debug($log);
        }

        return (array) $response;
    }
}
