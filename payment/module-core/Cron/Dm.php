<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Cron;

use CyberSource\Core\Model\LoggerInterface;
use Magento\Framework\App\Area;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\InvoiceRepository;
use Magento\Framework\App\State;
use CyberSource\Core\Model\Config;

class Dm
{

    const SECURE_ACCEPTANCE_METHOD = 'chcybersource';
    const PAYPAL_METHOD = 'cybersourcepaypal';

    /**
     * @var LoggerInterface
     */
    private $logger;

   /**
    * @var \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory
    */
    private $paymentCollectionFactory;

   /**
    * @var \Magento\Framework\App\Config\ScopeConfigInterface
    */
    private $scopeConfig;

   /** @var \CyberSource\Core\Service\CyberSourceSoapAPI
    *
    */
    private $cybersourceApi;

   /**
    * @var \Magento\Framework\HTTP\Client\Curl
    */
    private $curl;

   /**
    * @var \Magento\Sales\Api\OrderRepositoryInterface
    */
    private $orderRepository;
    
   /**
    * @var \Magento\Store\Model\StoreManagerInterface
    */
    private $storeManager;

    /**
     * @var  \Magento\Framework\Mail\Template\TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    private $invoiceService;
    
    /**
     *
     * @var string
     */
    private $newStatus;
    
    /**
     *
     * @var \Magento\Framework\Encryption\Encryptor
     */
    private $crypt;

    /**
     *
     * @var \Magento\Framework\DataObject
     */
    private $postObject;
    
    /**
     * @var  \CyberSource\Core\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Sales\Model\Order\Status $status
     */
    private $status;

    /**
     * @var InvoiceRepository
     */
    private $invoiceRepository;

    /**
     * @var State
     */
    private $appState;

    /**
     * @var Config
     */
    private $gatewayConfig;

    /**
     * Dm constructor.
     * @param LoggerInterface $logger
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory $paymentCollectionFactory
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \CyberSource\Core\Service\CyberSourceSoapAPI $cybersourceApi
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \CyberSource\Core\Helper\Data $helper
     * @param \Magento\Framework\DataObject $postObject
     * @param \Magento\Framework\Encryption\Encryptor $crypt
     * @param \Magento\Sales\Model\Order\Status $status
     * @param InvoiceRepository $invoiceRepository
     * @param State $appState
     */
    public function __construct(
        LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory $paymentCollectionFactory,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \CyberSource\Core\Service\CyberSourceSoapAPI $cybersourceApi,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \CyberSource\Core\Helper\Data $helper,
        \Magento\Framework\DataObject $postObject,
        \Magento\Framework\Encryption\Encryptor $crypt,
        \Magento\Sales\Model\Order\Status $status,
        InvoiceRepository $invoiceRepository,
        State $appState,
        Config $config
    ) {
        $this->logger = $logger;
        $this->paymentCollectionFactory = $paymentCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->curl = $curl;
        $this->cybersourceApi = $cybersourceApi;
        $this->orderRepository = $orderRepository;
        $this->storeManager = $storeManager;
        $this->transportBuilder = $transportBuilder;
        $this->helper = $helper;
        $this->invoiceService = $invoiceService;
        $this->postObject = $postObject;
        $this->crypt = $crypt;
        $this->status = $status;
        $this->invoiceRepository = $invoiceRepository;
        $this->appState = $appState;
        $this->gatewayConfig = $config;
    }
    
    public function sendEmail($order, $storeId)
    {
        $emailTempVariables = ['order' => $order];

        $sender = $this->scopeConfig->getValue(
            "payment/chcybersource/dm_fail_sender",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $senderName = $this->scopeConfig->getValue(
            "trans_email/ident_".$sender."/name",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $senderEmail = $this->scopeConfig->getValue(
            "trans_email/ident_".$sender."/email",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $email = $order->getCustomerEmail();
        $this->postObject->setData($emailTempVariables);
        $sender = [
            'name' => $senderName,
            'email' => $senderEmail,
        ];

        $emailTemplate = $this->scopeConfig->getValue(
            "payment/chcybersource/dm_fail_template",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        /**
         * Magerun uses emulateAreaCode('crontab') to run the cron jobs.
         * By emulating as crontab, email is not sent because of this class
         * vendor/magento/module-customer-sample-data/Magento/Framework/Mail/Transport/MailPlugin.php
         * the method $this->appState->isAreaCodeEmulated() will return true, preventing email to be sent.
         * By calling the emulateAreaCode method, it will enable/disable the isAreaCodeEmulated flag
         */
        $this->appState->emulateAreaCode(Area::AREA_ADMINHTML, function () {
        });

        try {
            $transport = $this->transportBuilder->setTemplateIdentifier($emailTemplate)
                ->setTemplateOptions(['area' => Area::AREA_FRONTEND, 'store' => $storeId])
                ->setTemplateVars(['data' => $this->postObject])
                ->setFrom($sender)
                ->addTo($email)
                ->setReplyTo($senderEmail)
                ->getTransport();

            $transport->sendMessage();

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        $this->logger->info("cancel email sent from store id " . $storeId . " to " . $email);
    }
    
    public function execute()
    {
        foreach ($this->storeManager->getStores() as $storeId => $store) {
            if (!$this->scopeConfig->getValue(
                "payment/chcybersource/enable_dm_cron",
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $store->getId()
            )) {
                continue; //we have to keep looking at other stores.
            }

            $url = $this->scopeConfig->getValue(
                "payment/chcybersource/report_url",
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $store->getId()
            );
            if ($url === null) {
                continue;
            }
            $params = $this->composeParams($store->getId());
            $this->curl->post($url, $params);
            $response = $this->curl->getBody();

            if (preg_match_all(
                '/<Conversion MerchantReferenceNumber="(.*?)" ConversionDate="([0-9-\s:]+)" RequestID="(\d+)">/',
                $response,
                $matches
            )) {
                $payment_info = $this->composePaymentInfo($response);

                $params['password'] = $this->crypt->decrypt($this->scopeConfig->getValue(
                    "payment/chcybersource/report_password",
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $store->getId()
                ));

                foreach ($payment_info as $order_id => $info) {
                    $this->processOrder($order_id, $params, $payment_info, $store->getId());
                }
            }
        }
        return $this;
    }
    
    private function processOrder($order_id, $params, $payment_info, $storeId)
    {
        $order = $this->orderRepository->get($order_id);

        $this->sendEmail($order, $storeId);

        if ($order->getStoreId() != $storeId) {
            //this order is placed on a different store, we'll process it later
            return;
        }

        $this->newStatus = null;
        if (!empty($payment_info[$order->getId()])) {
            $this->processSave($params, $payment_info, $order, $storeId);
        }

        if (!empty($this->newStatus)) {
            $order
                ->setStatus($this->getStatusByState($this->newStatus))
                ->setState($this->newStatus)
                ->save();
            if ($this->newStatus == 'canceled') {
                $this->processCancel($payment_info, $order, $storeId);
            }
        }
    }
    
    private function processCancel($payment_info, $order, $storeId)
    {
        $paymentMethod = $order->getPayment()->getMethod();

        if ($payment_info[$order->getId()]) {
            if ($paymentMethod == self::PAYPAL_METHOD) {
                /** @var \CyberSource\PayPal\Model\Payment $methodInstance */
                $methodInstance = $order->getPayment()->getMethodInstance();
                $methodInstance->cancel($order->getPayment());
            } else {
                $this->cybersourceApi->setPayment($payment_info[$order->getId()]['payment']);
                $this->cybersourceApi->reverseOrderPayment($storeId);
            }
        }

        /** @var \Magento\Sales\Model\Order $order */
        $invoice = $order->getInvoiceCollection()->getFirstItem();

        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        if (!$invoice->isEmpty()) {
            /** @var \Magento\Sales\Api\Data\InvoiceInterface $invoice */
            $invoice->setState(Invoice::STATE_CANCELED);
            $this->invoiceRepository->save($invoice);
        }

        $this->sendEmail($order, $storeId);
    }
    
    private function processSave($params, $payment_info, $order, $storeId)
    {
        $additional_information = $payment_info[$order->getId()]['payment']->getData('additional_information');

        $isTokenPaid = (!empty($additional_information['payment_token']));

        $skipStatuses = ['processing', 'canceled', 'closed'];
        if ($payment_info[$order->getId()]['type'] == 'capture') {
            if (!in_array($order->getState(), $skipStatuses)) {
                $this->saveCapture($params, $payment_info, $order, $isTokenPaid, $storeId);
            }
        } elseif ($payment_info[$order->getId()]['type'] == 'authorize'
            && !in_array($order->getState(), $skipStatuses)) {
            $this->saveAuthorize($params, $payment_info, $order, $isTokenPaid, $storeId);
        }
    }
    
    private function saveAuthorize($params, $payment_info, $order, $isTokenPaid, $storeId)
    {
        $this->newStatus = ($payment_info[$order->getId()]['NewDecision'] == 'ACCEPT') ? 'pending_payment' : 'canceled';

        if ($payment_info[$order->getId()]['NewDecision'] == 'ACCEPT') {
            //create offline invoice for settled payments on cybersource side

            if ($payment_info[$order->getId()]['settle']) {
                $invoice = $this->invoiceService->prepareInvoice($order);
                $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
                $invoice->setShippingAmount($order->getData('shipping_amount'));
                $invoice->setSubtotal($order->getData('subtotal'));
                $invoice->setBaseSubtotal($order->getData('base_subtotal'));
                $invoice->setGrandTotal($order->getData('grand_total'));
                $invoice->setBaseGrandTotal($order->getData('base_grand_total'));
                $invoice->register()->save();
                $this->newStatus = 'processing';
            }

            $paymentMethod = $order->getPayment()->getMethod();

            if ($paymentMethod == self::PAYPAL_METHOD || $paymentMethod == "cybersource_applepay") {

                $order->getPayment()->unsAdditionalInformation('is_fraud_detected');
                $this->orderRepository->save($order);

                /** @var \Magento\Sales\Model\Order $order */
                $invoice = $order->getInvoiceCollection()->getFirstItem();

                /** @var \Magento\Sales\Model\Order\Invoice $invoice */
                if (!$invoice->isEmpty() && $invoice->canCapture()) {
                    $invoice->capture();
                    $this->invoiceRepository->save($invoice);
                    $this->newStatus = 'processing';
                }
            }

            if ($order->getState() != 'pending_payment' && !$isTokenPaid && $paymentMethod == self::SECURE_ACCEPTANCE_METHOD) {
                $profile_data = [
                    'merchant_id' => $params['merchantID'],
                    'ref_id' => $order->getIncrementId(),
                    'request_id' => $payment_info[$order->getId()]['request_id']
                ];

                //create payment profile
                $result = $this->cybersourceApi->convertToProfile($profile_data, $storeId);

                $responses = [
                    'payment_token' => $result->paySubscriptionCreateReply->subscriptionID,
                    'reason_code' => $result->reasonCode,
                    'transaction_id' => $result->requestID,
                    'card_type' => $payment_info[$order->getId()]['payment']->getCcType(),
                    'card_expiry_date' => $payment_info[$order->getId()]['payment']->getCcExpMonth()
                        .'-'
                        .$payment_info[$order->getId()]['payment']->getCcExpYear(),
                    'reference_number' => $result->merchantReferenceCode,
                ];

                $order->getPayment()->setAdditionalInformation('payment_token', $responses['payment_token']);
            }
        }
    }
    
    private function saveCapture($params, $payment_info, $order, $isTokenPaid, $storeId)
    {
        $this->newStatus = ($payment_info[$order->getId()]['NewDecision'] == 'ACCEPT') ? 'processing' : 'canceled';
        if ($payment_info[$order->getId()]['NewDecision'] == 'ACCEPT') {
            $transactionId = $order->getPayment()->getTransactionId();

            /** @var \Magento\Sales\Model\Order $order */
            $invoice = $order->getInvoiceCollection()->getFirstItem();

            /** @var \Magento\Sales\Model\Order\Invoice $invoice */
            if (!$invoice->isEmpty() && $invoice->canCapture()) {
                $invoice->capture();

                if ($invoice->wasPayCalled()) {
                    /** @var \Magento\Sales\Model\Order\Item $item */
                    foreach ($order->getAllItems() as $item) {
                        $item->setQtyInvoiced($item->getQtyOrdered());
                        $item->save();
                    }
                    $this->invoiceRepository->save($invoice);
                }
            }
        }
        if ($payment_info[$order->getId()]['NewDecision'] == 'ACCEPT' && !$isTokenPaid) {
            $profile_data = [
                    'merchant_id' => $params['merchantID'],
                    'ref_id' => $order->getIncrementId(),
                    'request_id' => $payment_info[$order->getId()]['request_id']
            ];
            /**
             * Create payment profile only when subscription reply
             * i.e. PayPal do not have this subscription node
             */

            $result = $this->cybersourceApi->convertToProfile($profile_data, $storeId);
            if (property_exists($result, 'paySubscriptionCreateReply')) {
                $responses = [
                    'payment_token' => $result->paySubscriptionCreateReply->subscriptionID,
                    'reason_code' => $result->reasonCode,
                    'transaction_id' => $result->requestID,
                    'card_type' => $payment_info[$order->getId()]['payment']->getCcType(),
                    'card_expiry_date' => $payment_info[$order->getId()]['payment']->getCcExpMonth()
                        .'-'.$payment_info[$order->getId()]['payment']->getCcExpYear(),
                    'reference_number' => $result->merchantReferenceCode,
                ];

                $order->getPayment()->setAdditionalInformation('payment_token', $responses['payment_token']);
            }
        }
    }
    
    private function composePaymentInfo($response)
    {
        $data = $this->parseResponse($response);
        
        $payment_info = [];
        
        foreach ($data as $cc_trans_id => $temp) {
            $paymentCollection = $this->paymentCollectionFactory->create();

            $paymentCollection->addFieldToFilter('cc_trans_id', $cc_trans_id);

            $paymentCollection->load();

            foreach ($paymentCollection as $payment) {
                $paid = $payment->getData('amount_paid');

                $payment_info[$payment->getParentId()] = [
                    'type' => (empty($paid)) ? 'authorize' : 'capture',
                    'NewDecision' => $data[$payment->getCcTransId()]['NewDecision'],
                    'amount' => $payment->getData('amount_authorized'),
                    'payment' => $payment,
                    'request_id' => $payment->getCcTransId(),
                    'settle' => (int)$data[$payment->getCcTransId()]['settle'],
                ];
            }
        }
        
        return $payment_info;
    }

    private function parseResponse($response)
    {
        $xml = simplexml_load_string($response);
        $data = [];
        if (!empty($xml->Conversion)) {
            foreach ($xml->Conversion as $conversion) {
                $settle = false;
                foreach ($conversion->Notes->Note as $note) {
                    if (preg_match('/The card settlement succeeded/', (string)$note['Comment'])) {
                        $settle = true;
                    }
                }
                $data[(string)$conversion['RequestID']] = [
                    'OriginalDecision' => (string)$conversion->OriginalDecision,
                    'NewDecision' => (string)$conversion->NewDecision,
                    'settle' => $settle,
                ];
            }
        }
        return $data;
    }
    
    private function composeParams($storeId)
    {
        $params = [];

        $params['merchantID'] = $this->scopeConfig->getValue(
            "payment/chcybersource/merchant_id",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $params['username'] = $this->scopeConfig->getValue(
            "payment/chcybersource/report_username",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $params['password'] = $this->crypt->decrypt(
            $this->scopeConfig->getValue(
                "payment/chcybersource/report_password",
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            )
        );

        $start_ts = time()-23*3600;

        $end_ts = time();

        $params['startDate'] = gmdate('Y-m-d', $start_ts);

        $params['startTime'] = gmdate('H:i:s', $start_ts);

        $params['endDate'] = gmdate('Y-m-d', $end_ts);

        $params['endTime'] = gmdate('H:i:s', $end_ts);

        return $params;
    }
    
    /**
     * Returns any possible status for state
     *
     * @param string $state
     * @return string
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    private function getStatusByState($state)
    {
        return $this->status->loadDefaultByState($state)->getStatus();
    }
}
