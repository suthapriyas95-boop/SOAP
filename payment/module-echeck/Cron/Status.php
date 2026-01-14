<?php
/**
 * Copyright © 2020 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\ECheck\Cron;

use Exception;
use CyberSource\Core\Model\Config;
use CyberSource\Core\Model\LoggerInterface;
use Magento\Framework\Serialize\Serializer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Stdlib\DateTime\DateTime;

class Status
{
    const API_HOST = 'api.cybersource.com';
    const TEST_API_HOST = 'apitest.cybersource.com';
    const CONTENT_TYPES_JSON = [ 'application/json', 'application/hal+json'];
	const _PYMT_WCSG_ALGORITHM_SHA256_ = 'sha256';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Serializer\Json
     */
    private $jsonSerializer;

    /**
     * @var string
     */
    private $requestPath;

    /**
     * @var string
     */
    private $requestMethod;

    /**
     * @var int|null
     */
    private $storeId = null;

    /**
     * @var string
     */
    private $contentType;
	
	/**
    * @var \Magento\Framework\App\Config\ScopeConfigInterface
    */
    private $scopeConfig;
	
	/**
     * @var Curl
     */
    private $curl;
	
	/**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $dateTime;
	
	/**
     * @var \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory
     */
    protected $paymentCollectionFactory;
	
	/**
     * @var \Magento\Sales\Model\OrderRepository
     */
    private $orderRepository;
	
	/**
     * @var \CyberSource\ECheck\Gateway\Config\Config
     */
    private $echeckConfig;
	
	/**
     *
     * @var \Magento\Framework\DataObject
    */
    private $postObject;
    
    /**
     * @var  \Magento\Framework\Mail\Template\TransportBuilder
     */
    private $transportBuilder;


    /**
     * @param Config $config
     * @param LoggerInterface $logger
     * @param Serializer\Json $jsonSerializer
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\HTTP\Client\Curl $curl
	 * @param @var \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
	 * @param \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory $paymentCollectionFactory
	 * @param \Magento\Sales\Model\OrderRepository $orderRepository
	 * @param \CyberSource\ECheck\Gateway\Config\Config $echeckConfig
	 * @param \Magento\Framework\DataObject $postObject
	 * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param null $requestPath
     * @param null $requestMethod
     * @param string $contentType
     * @param null $storeId
     */

    public function __construct(
        Config $config,
        LoggerInterface $logger,
        Serializer\Json $jsonSerializer,
		ScopeConfigInterface $scopeConfig,
		Curl $curl,
		DateTime $dateTime,
		\Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory $paymentCollectionFactory,
		\Magento\Sales\Model\OrderRepository $orderRepository,
		\CyberSource\ECheck\Gateway\Config\Config $echeckConfig,
		\Magento\Framework\DataObject $postObject,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        $requestPath,
        $requestMethod,
        $contentType = 'application/json;',
        $storeId = null
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->jsonSerializer = $jsonSerializer;
        $this->scopeConfig = $scopeConfig;
        $this->curl = $curl;
		$this->dateTime = $dateTime;
		$this->paymentCollectionFactory = $paymentCollectionFactory;
		$this->orderRepository = $orderRepository;
		$this->echeckConfig = $echeckConfig;
		$this->postObject = $postObject;
        $this->transportBuilder = $transportBuilder;
        $this->requestPath = $requestPath;
        $this->requestMethod = $requestMethod;
        $this->storeId = $storeId;
        $this->contentType = $contentType;
    }

    public function execute()
    {
		if (!(bool)(int)$this->echeckConfig->isActive()) { return $this; }	
		try{
			$response =  $transactionIdArr = $resultArr = [];
			$storeId = $this->storeId;
			$merchantId = $this->config->getMerchantId($storeId);
			$restKeyId = $this->config->getRestKeyId($storeId);
			$restKeyValue = $this->config->getRestKeyValue($storeId);
			$requestMethod = $this->requestMethod;
			$requestPath = $this->requestPath;
			$requestHost = $this->getApiHost();
			$currentDate = $this->dateTime->gmtDate("D, d M Y G:i:s \G\M\T");
            $endpointUrl = 'https://' . $requestHost . $requestPath;
			
			$acceptEventType= array_map('strtoupper', $this->echeckConfig->getAcceptEventType());
			$rejectEventType= array_map('strtoupper', $this->echeckConfig->getRejectEventType());
			$pendingEventType= array_map('strtoupper', $this->echeckConfig->getPendingEventType());
			
			$eventTypeArr = [
				'acceptEventType' =>  $acceptEventType,
				'rejectEventType' =>  $rejectEventType,
				'pendingEventType' => $pendingEventType,
			];

            $paymentCollection = $this->paymentCollectionFactory->create();
            $paymentCollection->addFieldToFilter('main_table.method', 'cybersourceecheck');
            $paymentCollection->addFieldToFilter('order_table.status', 'payment_review');
            $paymentCollection->getSelect()->joinleft(
                ['order_table' => $paymentCollection->getTable('sales_order')],
                'main_table.parent_id = order_table.entity_id',
                ['status', 'quote_id']
            );
            $paymentCollection->load();

            foreach ($paymentCollection as $payment) {
                $transactionIdArr[] = $payment->getLastTransId();
            }

            if(!empty($transactionIdArr)){
                $transactionIds = implode(" OR ",$transactionIdArr);
                $queryArr["query"] = "id:($transactionIds) AND merchantId:$merchantId";
                $queryArr["sort"] = "id:asc";
                $payload = json_encode($queryArr);

				$generalSettings = [
					'merchant_id' => $merchantId,
					'merchant_key_id' => $restKeyId,
					'merchant_secret_key' => $restKeyValue,
					'resource' => mb_convert_encoding($requestPath,'UTF-8'),
					'httpMethod' => strtolower($requestMethod ?? ''),
					'currentDate' => $currentDate,
					'requestHost' => $requestHost,
					'payload' => $payload,
					'endpointUrl' => $endpointUrl,
                    'contentType' => $this->contentType,
                ];
				
				$signedHeaders = $this->getHttpSignaturePost($generalSettings);
				
				$this->curl->setOption(CURLOPT_HTTPHEADER, $signedHeaders);
				$this->curl->post($endpointUrl, $payload);
				$responseBody = $this->curl->getBody();
               
                $response = $this->jsonSerializer->unserialize($responseBody);

                $this->logger->debug($response);

                if(isset($response['count']) && $response['count'] > 0){

                    for($i=0; $i < $response['count']; $i++){
                        $transactionId =  $response['_embedded']['transactionSummaries'][$i]['id'];
                        $resultArr[$transactionId]['tansactionId'] = $transactionId;
                        $resultArr[$transactionId]['eventStatus'] = strtoupper($response['_embedded']['transactionSummaries'][$i]['processorInformation']['eventStatus'] ?? '');
                    }

                    $paymentCollectionUpdateOrder = $this->paymentCollectionFactory->create();
                    $paymentCollectionUpdateOrder->getSelect()->joinleft(
                        ['order_table' => $paymentCollection->getTable('sales_order')],
                        'main_table.parent_id = order_table.entity_id',['status', 'quote_id']
                    );
                    $paymentCollectionUpdateOrder->addFieldToFilter('order_table.status', 'payment_review');
                    $paymentCollectionUpdateOrder->addFieldToFilter('main_table.method', 'cybersourceecheck');
                    $paymentCollectionUpdateOrder->addFieldToFilter('main_table.last_trans_id', ['in' => array_keys($resultArr)]);
                    $paymentCollectionUpdateOrder->load();

                    foreach ($paymentCollectionUpdateOrder as $paymentUpdateOrder) {
                        $this->updateOrder($resultArr[$paymentUpdateOrder->getLastTransId()]['eventStatus'], $paymentUpdateOrder->getOrder(), $eventTypeArr);
                    }
                }
            }
		} catch (Exception $e) {
			$this->logger->error($e->getMessage());
			throw $e;
		} finally {
			$this->logger->debug($resultArr);   
		}

		return $response;
	}
	
     /*
     POST Method for REST API Signature
    */
	public function getHttpSignaturePost($generalSettings)
    {
        $digest = $this->generateDigest($generalSettings['payload']);
        $signatureString = 'host: ' . $generalSettings['requestHost'] . "\ndate: " .
        $generalSettings['currentDate'] . "\nrequest-target: " .
        $generalSettings['httpMethod'] . ' ' . $generalSettings['resource'] . "\ndigest: SHA-256=" .
        $digest . "\nv-c-merchant-id: " . $generalSettings['merchant_id'];
        $headerString = 'host date request-target digest v-c-merchant-id';
        $signatureByteString = mb_convert_encoding($signatureString,'UTF-8');
        $decodeKey = $this->getBase64Decode($generalSettings['merchant_secret_key']);
        $signature = $this->getBase64Encode(hash_hmac(
            self::_PYMT_WCSG_ALGORITHM_SHA256_,
            $signatureByteString,
            $decodeKey,
            true
        ));
        $signatureHeader = ['keyid="' . $generalSettings['merchant_key_id'] .
            '"', 'algorithm="HmacSHA256"', 'headers="' .
            $headerString . '"', 'signature="' . $signature . '"', ];
        $signatureToken = 'Signature: ' . implode(', ', $signatureHeader);
        $host = 'Host: ' . $generalSettings['requestHost'];
        $vcMerchantId = 'v-c-merchant-id: ' . $generalSettings['merchant_id'];
        $headers = [$vcMerchantId, $signatureToken, $host, 'Date: ' . $generalSettings['currentDate']];
        $digestArray = ['Digest: SHA-256=' . $digest, 'Accept:'.$generalSettings['contentType'], 'Content-Type:'.$generalSettings['contentType']];
        $headers = array_merge($headers, $digestArray);

        return $headers;
    }
	
	private function updateOrder($eventType, $order, $eventTypeArr)
    {
        /** @var \Magento\Sales\Model\Order $order */

        $updateStatus = true;
        if (!in_array($eventType, $eventTypeArr['acceptEventType'])
            && !in_array($eventType, $eventTypeArr['rejectEventType'])
            && !in_array($eventType, $eventTypeArr['pendingEventType'])
        ) {
            $this->sendEmail($order, $eventType, 'cybersource_echeck_unknown');
            $updateStatus = false;
        }
        $inCounter = 0;
        if ($updateStatus && in_array($eventType, $eventTypeArr['acceptEventType'])) {
            $inCounter++;
        }
        if ($updateStatus && in_array($eventType, $eventTypeArr['rejectEventType'])) {
            $inCounter++;
        }
        if ($updateStatus && $inCounter > 1) {
            $this->sendEmail($order, $eventType, 'cybersource_echeck_multi');
            $updateStatus = false;
        }
        if ($updateStatus && in_array($eventType, $eventTypeArr['acceptEventType'])) {
            $order->getPayment()->accept();
            $this->orderRepository->save($order);
        }
        if ($updateStatus && in_array($eventType, $eventTypeArr['rejectEventType'])) {
            $order->getPayment()->deny();
            $this->orderRepository->save($order);
        }
    }
	
	public function sendEmail($order, $eventType, $templateId = 'cybersource_echeck_unknown')
    {
        $emailTempVariables = ['order' => $order, 'event_type' => $eventType];
		
        $sender = $this->scopeConfig->getValue(
            "payment/chcybersource/dm_fail_sender",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $senderName = $this->scopeConfig->getValue(
            "trans_email/ident_".$sender."/name",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $senderEmail = $this->scopeConfig->getValue(
            "trans_email/ident_".$sender."/email",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $email = $this->scopeConfig->getValue(
            "trans_email/ident_general/email",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $this->postObject->setData($emailTempVariables);
        $sender = [
            'name' => $senderName,
            'email' => $senderEmail,
        ];
        $transport = $this->transportBuilder->setTemplateIdentifier($templateId)
        ->setTemplateOptions([
            'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
            'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID
        ])
        ->setTemplateVars(['data' => $this->postObject])
        ->setFrom($sender)
        ->addTo($email)
        ->setReplyTo($senderEmail)
        ->getTransport();
        $transport->sendMessage();
    }
	
    /*
     Get Method for REST API Signature
    */
	private function getHttpSignatureGet($generalSettings)
    {  

        $signatureString = 'host: ' . $generalSettings['requestHost'] . "\ndate: " .
        $generalSettings['currentDate'] . "\nrequest-target: " . $generalSettings['httpMethod'] . ' ' .
        $generalSettings['resource'] . "\nv-c-merchant-id: " . $generalSettings['merchant_id'];
        $headerString = 'host date request-target v-c-merchant-id';
        $signatureByteString = mb_convert_encoding($signatureString,'UTF-8');
        $decodeKey = $this->getBase64Decode($generalSettings['merchant_secret_key']);
        $signature = $this->getBase64Encode(hash_hmac(self::_PYMT_WCSG_ALGORITHM_SHA256_, $signatureByteString, $decodeKey, true));
        $signatureHeader = [
            'keyid="' . $generalSettings['merchant_key_id'] . '"',
            'algorithm="HmacSHA256"',
            'headers="' . $headerString . '"',
            'signature="' . $signature . '"',
        ];
        $signatureToken = 'Signature:' . implode(', ', $signatureHeader);
        $host = 'Host:' . $generalSettings['requestHost'];
        $vcMerchantId = 'v-c-merchant-id:' . $generalSettings['merchant_id'];
        $headers = [
            $vcMerchantId,
            $signatureToken,
            $host,
            'Date:' .  $generalSettings['currentDate'],
        ];

        return $headers;
    }
	
	private function getBase64Encode($inputString)
    {
		return base64_encode($inputString);

    }

    private function getBase64Decode($inputString)
    {
        return base64_decode($inputString);
    }
	
	private function generateDigest($requestPayload)
    {
        $utf8EncodedString = mb_convert_encoding($requestPayload,'UTF-8');
        $digestEncode = hash(self::_PYMT_WCSG_ALGORITHM_SHA256_, $utf8EncodedString, true);
        return $this->getBase64Encode($digestEncode);
    }

    /**
     * @param int|null $storeId
     *
     * @return string
     */
    private function getApiHost($storeId = null)
    {
        return $this->config->getUseTestWsdl($storeId) ? self::TEST_API_HOST : self::API_HOST;
    }

}
