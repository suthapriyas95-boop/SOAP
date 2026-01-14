<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\KlarnaFinancial\Service;

use CyberSource\KlarnaFinancial\Helper\RequestDataBuilder;
use Magento\Framework\Session\SessionManagerInterface;
use CyberSource\Core\Model\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use CyberSource\Core\Service\MultiMidAbstractConnection;
use CyberSource\KlarnaFinancial\Gateway\Config\Config;

class CyberSourceSoap extends MultiMidAbstractConnection
{
    /**
     * @var RequestDataBuilder
     */
    private $requestDataBuilder;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;
	
	 /**
     * @var Config
     */
    private $gatewayConfig;

    /**
     * CyberSourceSoap constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param RequestDataBuilder $dataBuilder
     * @param SessionManagerInterface $checkoutSession
     * @param \SoapClient|null $client
	 * @param Config $gatewayConfig
     * @throws \Exception
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        RequestDataBuilder $dataBuilder,
        SessionManagerInterface $checkoutSession,
		Config $gatewayConfig,
        ?\SoapClient $client = null
    ) {
		$this->gatewayConfig = $gatewayConfig;
		$storeId = $this->getCurrentStoreId();
        parent::__construct($scopeConfig, 
        $logger,
         $this->gatewayConfig->getMerchantId($storeId), 
         $this->gatewayConfig->getP12Certificate($storeId), 
         $this->gatewayConfig->getP12AccessKey($storeId));

        $this->requestDataBuilder = $dataBuilder;
        $this->checkoutSession = $checkoutSession;

        /**
         * Added soap client as parameter to be able to mock in unit tests.
         */
        if ($client !== null) {
            $this->setSoapClient($client);
        }
    }

    public function placeRequest($request)
    {
        $result = null;

        try {
			$request->merchantID = $this->getMid();
            $dmenabled = 'false';
            $request->decisionManager = [
                'enabled' => $dmenabled,
            ];
            $this->logger->debug([__METHOD__ => (array) $request]);
            $result = $this->client->runTransaction($request);
            $this->logger->debug([__METHOD__ => (array) $result]);

            if ($result->reasonCode == 100) {
                $this->checkoutSession->setKlarnaSessionRequestId($result->requestID);
                return $result->apSessionsReply->processorToken ?? null;
            }

            $this->logger->error("Unable to initialize Klarna Session. Error code: " . $result->reasonCode);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }

        return null;
    }
}
