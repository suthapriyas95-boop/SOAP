<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\Core\Service;

use CyberSource\Core\Helper\RequestDataBuilder;
use CyberSource\Core\Model\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\GiftMessage\Model\Message as GiftMessage;
use Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;

class CyberSourceSoapAPI extends AbstractConnection
{
    const SUCCESS_REASON_CODE = 100;

    /** @var  \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface */
    private $transactionBuilder;

    /**
     * @var \SoapClient
     */
    public $client;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    private $curl;

    /**
     * @var int
     */
    private $merchantReferenceCode;

    /**
     * @var RequestDataBuilder
     */
    protected $requestDataHelper;

    /**
     * @var bool $firstAttempt
     */
    private $firstAttempt = true;

    /**
     * @var \Magento\Backend\Model\Auth\Session $session
     */
    private $session;

    /**
     * @var \Magento\Sales\Model\Order\Payment
     */
    private $payment = null;

    /**
     * @var bool
     */
    private $isSuccessfullyVoid = false;

    /**
     * @var bool
     */
    private $isSuccessfullyReverse = false;

    /**
     * @var \Magento\Directory\Model\Region
     */
    private $regionModel;

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    private $remoteAddress;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var \Magento\Backend\Model\Auth
     */
    private $auth;

    /**
     * @var GiftMessage
     */
    private $giftMessage;

    /**
     * CyberSourceSoapAPI constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param BuilderInterface $transactionBuilder
     * @param RequestDataBuilder $requestDataHelper
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Directory\Model\Region $regionModel
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param \Magento\Backend\Model\Auth $auth
     * @param GiftMessage $giftMessage
     * @param \SoapClient|null $client
     * @throws \Exception
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        BuilderInterface $transactionBuilder,
        RequestDataBuilder $requestDataHelper,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Directory\Model\Region $regionModel,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Magento\Checkout\Model\Session $checkoutSession,
        OrderCollectionFactory $orderCollectionFactory,
        \Magento\Backend\Model\Auth $auth,
        GiftMessage $giftMessage,
        ?\SoapClient $client = null
    ) {
        parent::__construct($scopeConfig, $logger);

        /**
         * Added soap client as parameter to be able to mock in unit tests.
         */
        if ($client !== null) {
            $this->setSoapClient($client);
        }

        $this->client = $this->getSoapClient();
        $this->transactionBuilder = $transactionBuilder;
        $this->requestDataHelper = $requestDataHelper;
        $this->curl = $curl;
        $this->session = $authSession;
        $this->regionModel = $regionModel;
        $this->remoteAddress = $remoteAddress;
        $this->checkoutSession = $checkoutSession;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->auth = $auth;
        $this->giftMessage = $giftMessage;
    }

    /**
     * @param $payment
     */
    public function setPayment($payment)
    {
        $this->payment = $payment;
        $this->merchantReferenceCode = $payment->getOrder()->getIncrementId();
    }

    /**
     * @TODO: remove unused legacy from p2
     *
     * @return mixed
     */
    public function getAmount()
    {
        return $this->payment->getAmountAuthorized();
    }

    /**
     * Get merchant reference code
     *
     * @return int|null
     */
    public function getMerchantReferenceCode()
    {
        return $this->merchantReferenceCode;
    }

    /**
     * Build capture request
     *
     * @TODO: remove unused legacy from p2
     *
     * @param $amount
     * @return \stdClass $result
     * @throws \Exception
     */
    public function captureOrder($amount)
    {
        $request = new \stdClass();
        $request->partnerSolutionID = RequestDataBuilder::PARTNER_SOLUTION_ID;
        $developerId = $this->config->getValue(
            "payment/chcybersource/developer_id",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $request->merchantReferenceCode = $this->getMerchantReferenceCode();

        $requestToken = $this->payment->getCcTransId();

        $ccCaptureService = new \stdClass();
        $ccCaptureService->run = "true";
        $ccCaptureService->authRequestID = $requestToken;

        $invoicedItems = [];
        /** @var \Magento\Sales\Model\Order\Invoice\Item $invoiceItem */

        $invoiceItems = $this->payment->getOrder()->getInvoiceCollection()->getLastItem()->getItems();

        if ($invoiceItems && count($invoiceItems) > 0) {
            foreach ($this->payment->getOrder()->getInvoiceCollection()->getLastItem()->getItems() as $invoiceItem) {
                if ($invoiceItem->getQty() >= 1) {
                    $invoicedItems[] = $invoiceItem;
                }
            }
        }

        $request = $this->buildOrderItems($invoicedItems, $request);

        $request->ccCaptureService = $ccCaptureService;

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $this->payment->getOrder()->getBaseCurrencyCode();
        $amount = $this->requestDataHelper->formatAmount($amount);
        $purchaseTotals->grandTotalAmount = $amount;
        $request->purchaseTotals = $purchaseTotals;

        $result = null;
        try {
            $this->logger->debug([__METHOD__ => (array) $request]);
            $this->setCredentialsByStore($this->payment->getOrder()->getStoreId());
            $this->initSoapClient();
            $request->merchantID = $this->merchantId;
            $result = $this->client->runTransaction($request);
            $this->logger->debug([(array) $result]);
        } catch (\Exception $e) {

            $this->logger->error($e->getMessage());

        }
        return $result;
    }

    /**
     * @TODO: remove unused legacy from p2
     *
     * @param $items
     * @param \stdClass $request
     * @return \stdClass
     */
    private function buildOrderItems($items, \stdClass $request)
    {
        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($items as $i => $item) {
            $requestItem = new \stdClass();
            $requestItem->id = $i;
            $requestItem->productName = $item->getName();
            $requestItem->productSKU = $item->getSku();
            $requestItem->quantity = (int) $item->getQty();
            $requestItem->productCode = 'default';
            $requestItem->unitPrice = $this->requestDataHelper->formatAmount($item->getPrice());
            $requestItem->taxAmount = $this->requestDataHelper->formatAmount($item->getTaxAmount());
            $request->item[] = $requestItem;
        }

        if (isset($request->item)) {
            foreach ($request->item as $key => $item) {
                if ($item->unitPrice == 0) {
                    unset($request->item[$key]);
                }
            }

            $request->item = array_values($request->item);
        }

        return $request;
    }

    /**
     * @TODO: remove unused legacy from p2
     *
     * @param $tokenData
     * @param bool $dmEnabled
     * @param bool $isCaptureRequest
     * @param null $quote
     * @param null $amount
     * @param \Magento\Sales\Model\Order $order
     * @return \stdClass
     * @throws LocalizedException
     */
    public function tokenPayment(
        $tokenData,
        $dmEnabled = true,
        $isCaptureRequest = false,
        $quote = null,
        $amount = null,
        $order = null,
        $isAuthorizedPayment = false
    ) {

        if ($order !== null) {
            $request = $this->requestDataHelper->buildTokenPaymentDataFromOrder(
                $tokenData,
                $order,
                $this->session->isLoggedIn(),
                $amount,
                $dmEnabled,
                $isCaptureRequest,
                $isAuthorizedPayment
            );
            $storeId = $order->getStoreId();
        } else {
            $request = $this->requestDataHelper->buildTokenPaymentData(
                $tokenData,
                $quote,
                $this->session->isLoggedIn(),
                $amount,
                $dmEnabled,
                $isCaptureRequest
            );
            $storeId = $tokenData['store_id'];
        }
        $response = null;
        try {
            $this->setCredentialsByStore($storeId);
            $this->initSoapClient();
            $this->logger->debug([__METHOD__ => (array) $request]);
            $response = $this->client->runTransaction($request);
            $this->logger->debug([__METHOD__ => (array) $response]);
            if ($response->reasonCode !== self::SUCCESS_REASON_CODE &&
                $response->decision != 'REVIEW') {
                throw new LocalizedException(
                    $this->requestDataHelper->wrapGatewayError(
                        "Unable to place order"
                    )
                );
            }
        } catch (\SoapFault $soapFault) {

            $this->logger->error($soapFault->getMessage());

        }

        return $response;
    }

    /**
     * Build retrieve profile request
     *
     * @TODO: remove unused legacy from p2
     *
     * @param string $subscriptionId
     * @return \stdClass
     * @throws \Exception
     */
    public function retrieveProfile($subscriptionId, $merchantReferenceCode, $storeId)
    {
        $request = new \stdClass();
        $request->merchantID = $this->merchantId;
        $request->partnerSolutionID = RequestDataBuilder::PARTNER_SOLUTION_ID;
        $developerId = $this->config->getValue(
            "payment/chcybersource/developer_id",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $request->merchantReferenceCode = $merchantReferenceCode;

        $recurringSubscriptionInfo = new \stdClass();
        $recurringSubscriptionInfo->subscriptionID = $subscriptionId;

        $request->recurringSubscriptionInfo = $recurringSubscriptionInfo;

        $paySubscriptionRetrieveService = new \stdClass();
        $paySubscriptionRetrieveService->run = "true";

        $request->paySubscriptionRetrieveService = $paySubscriptionRetrieveService;

        $response = null;
        try {
            $this->setCredentialsByStore($storeId);
            $this->initSoapClient();
            $this->logger->debug([__METHOD__ => (array) $request]);
            $response = $this->client->runTransaction($request);
            $this->logger->debug([__METHOD__ => (array) $response]);
        } catch (\SoapFault $soapFault) {

            $this->logger->error($soapFault->getMessage());

            throw new LocalizedException(__($soapFault->getMessage()));
        }

        return $response;
    }

    /**
     * Create profile from transaction
     *
     * @param array $data
     * @param int $storeId
     * @return \stdClass
     */
    public function convertToProfile($data, $storeId = null)
    {
        $request = $this->requestDataHelper->buildTokenByTransaction($data);
        $result = null;
        try {
            if (!empty($storeId)) {
                $this->setCredentialsByStore($storeId);
                $this->initSoapClient();
            }
            $result = $this->client->runTransaction($request);
        } catch (\Exception $e) {

            $this->logger->error("convert error: " . $e->getMessage());

        }
        return $result;
    }

    /**
     * Reverse authorized payment at Cybersource
     *
     * @return \stdClass
     * @throws \Exception
     */
    public function reverseOrderPayment($storeId)
    {
        $request = new \stdClass();
        $request->partnerSolutionID = RequestDataBuilder::PARTNER_SOLUTION_ID;
        $developerId = $this->config->getValue(
            "payment/chcybersource/developer_id",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $request->merchantReferenceCode = $this->getMerchantReferenceCode();

        $requestToken = $this->payment->getCcTransId();

        $ccAuthReversalService = new \stdClass();
        $ccAuthReversalService->run = "true";
        $ccAuthReversalService->authRequestID = $requestToken;
        $request->ccAuthReversalService = $ccAuthReversalService;

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $this->payment->getOrder()->getBaseCurrencyCode();
        $amount = $this->requestDataHelper->formatAmount($this->payment->getOrder()->getBaseGrandTotal());
        $purchaseTotals->grandTotalAmount =  $amount;
        $request->purchaseTotals = $purchaseTotals;

        $result = null;
        try {
            $this->setCredentialsByStore($storeId);
            $this->initSoapClient();
            $request->merchantID = $this->merchantId;
            $this->logger->debug([__METHOD__ => (array) $request]);
            $result = $this->client->runTransaction($request);
            $this->logger->debug([(array) $result]);
            $transaction = $this->buildTransaction(
                $result,
                \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND
            );
            if (100 == $result->reasonCode) {
                $this->isSuccessfullyReverse = true;
                $this->payment->addTransactionCommentsToOrder($transaction, "Successfully reverse");
            } elseif (in_array($result->reasonCode, [150, 151, 152])) {
                $status = $this->getTransactionStatus($request->merchantReferenceCode, gmdate('Ymd'), $storeId);
                if ($status == 'error' && $this->firstAttempt) {
                    $this->firstAttempt = false;
                    $this->reverseOrderPayment($storeId);
                }
            } else {
                $this->payment->addTransactionCommentsToOrder($transaction, "Unable to reverse");
                throw new LocalizedException($this->requestDataHelper->wrapGatewayError("Unable to reverse payment"));
            }
        } catch (\Exception $e) {

            $this->logger->error($e->getMessage());

            $status = $this->getTransactionStatus($request->merchantReferenceCode, gmdate('Ymd'), $storeId);
            if ($status == 'error' && $this->firstAttempt) {
                $this->firstAttempt = false;
                $this->reverseOrderPayment($storeId);
            }
        }
        return $result;
    }

    /**
     * Cancel payment and attach transaction info
     *
     * @TODO: remove unused legacy from p2
     *
     * @return \stdClass
     */
    public function voidOrderPayment($storeId)
    {
        $request = new \stdClass();
        $request->partnerSolutionID = RequestDataBuilder::PARTNER_SOLUTION_ID;
        $developerId = $this->config->getValue(
            "payment/chcybersource/developer_id",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $request->merchantReferenceCode = $this->getMerchantReferenceCode();

        $voidService = new \stdClass();
        $voidService->run = "true";
        $voidService->voidRequestID = $this->payment->getLastTransId();

        $request->voidService = $voidService;

        $result = null;
        try {
            $this->setCredentialsByStore($storeId);
            $this->initSoapClient();
            $request->merchantID = $this->merchantId;
            $this->logger->debug([__METHOD__ => (array) $request]);
            $result = $this->client->runTransaction($request);
            $this->logger->debug([(array) $result]);
            $transaction = $this->buildTransaction($result, \Magento\Sales\Model\Order\Payment\Transaction::TYPE_VOID);
            if (100 == $result->reasonCode) {
                $this->isSuccessfullyVoid = true;
                $this->payment->addTransactionCommentsToOrder($transaction, "Successfully void");
            } elseif (in_array($result->reasonCode, [150, 151, 152])) {
                $status = $this->getTransactionStatus($request->merchantReferenceCode, gmdate('Ymd'), $storeId);
                if ($status == 'error' && $this->firstAttempt) {
                    $this->firstAttempt = false;
                    $this->reverseOrderPayment($storeId);
                }
            } else {
                $this->payment->addTransactionCommentsToOrder($transaction, "Unable to void payment");
            }
        } catch (\Exception $e) {

            $this->logger->error($e->getMessage());

            $status = $this->getTransactionStatus($request->merchantReferenceCode, gmdate('Ymd'), $storeId);
            if ($status == 'error' && $this->firstAttempt) {
                $this->firstAttempt = false;
                $this->voidOrderPayment($storeId);
            }
        }
        return $result;
    }

    /**
     * Refund a captured order
     *
     * @TODO: remove unused legacy from p2
     *
     * @param float $amount
     * @return bool
     */
    public function refundOrderPayment($amount)
    {
        $request = new \stdClass();
        $request->partnerSolutionID = RequestDataBuilder::PARTNER_SOLUTION_ID;
        $developerId = $this->config->getValue(
            "payment/chcybersource/developer_id",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $request->merchantReferenceCode = $this->getMerchantReferenceCode();

        $ccCreditService = new \stdClass();
        $ccCreditService->run = "true";
        $ccCreditService->captureRequestID = $this->payment->getCcTransId();
        $request->ccCreditService = $ccCreditService;

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $this->payment->getOrder()->getBaseCurrencyCode();
        $purchaseTotals->grandTotalAmount =  $this->requestDataHelper->formatAmount($amount);
        $request->purchaseTotals = $purchaseTotals;

        $order = $this->payment->getOrder();
        foreach ($order->getAllVisibleItems() as $i => $item) {
            $requestItem = new \stdClass();
            $requestItem->id = $i;
            $requestItem->productName = $item->getName();
            $requestItem->productSKU = $item->getSku();
            $requestItem->quantity = (int) $item->getQtyOrdered();
            $requestItem->productCode = 'default';
            $requestItem->unitPrice = $item->getPrice();
            $requestItem->taxAmount = $item->getTaxAmount();
            $request->item[] = $requestItem;
        }

        $result = null;
        $success = false;
        try {
            $this->setCredentialsByStore($this->payment->getOrder()->getStoreId());
            $this->initSoapClient();
            $request->merchantID = $this->merchantId;
            $this->logger->debug([__METHOD__ => (array) $request]);
            $result = $this->client->runTransaction($request);
            $this->logger->debug([(array) $result]);

            if (100 == $result->reasonCode) {
                $transaction = $this->buildTransaction(
                    $result,
                    \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND
                );
                $this->payment->addTransactionCommentsToOrder($transaction, "Successfully refund");
                $success = true;
            } elseif (in_array($result->reasonCode, [150, 151, 152])) {
                $status = $this->getTransactionStatus($request->merchantReferenceCode, gmdate('Ymd'), $this->payment->getOrder()->getStoreId());
                if ($status == 'error' && $this->firstAttempt) {
                    $this->firstAttempt = false;
                    $this->reverseOrderPayment($this->payment->getOrder()->getStoreId());
                }
            }
        } catch (\Exception $e) {

            $this->logger->error('refund error = '.$e->getMessage());

            $status = $this->getTransactionStatus($request->merchantReferenceCode, gmdate('Ymd'), $this->payment->getOrder()->getStoreId());
            if ($status == 'error' && $this->firstAttempt) {
                $this->firstAttempt = false;
                $this->refundOrderPayment($amount);
            }
        }

        return $success;
    }

    /**
     *
     * @TODO: remove unused legacy from p2
     *
     * @return bool
     */
    public function isSuccessfullyVoided()
    {
        return $this->isSuccessfullyVoid;
    }

    /**
     *
     * @TODO: remove unused legacy from p2
     *
     * @return bool
     */
    public function isSuccessfullyReversed()
    {
        return $this->isSuccessfullyReverse;
    }

    /**
     * Build transaction object
     *
     * @TODO: remove unused legacy from p2
     *
     * @param \stdClass $result
     * @param $type
     * @return \Magento\Sales\Api\Data\TransactionInterface
     */
    private function buildTransaction(\stdClass $result, $type)
    {
        $trans = $this->transactionBuilder;

        $resultData = [
            "merchantReferenceCode" => $result->merchantReferenceCode,
            "requestID" => $result->requestID,
            "decision" => $result->decision,
            "reasonCode" => $result->reasonCode
        ];

        $transaction = $trans->setPayment($this->payment);

        $transaction->setOrder($this->payment->getOrder());
        $transaction->setTransactionId($result->requestID);
        $transaction->setAdditionalInformation(
            [
                \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => $resultData
            ]
        );
        $transaction->setFailSafe(true);
        $transactionBuilt = $transaction->build($type);

        return $transactionBuilt;
    }

    /**
     * Get On-Demand Single Transaction Report
     *
     * @TODO: remove unused legacy from p2
     *
     * @param int $quote_id
     * @param string $date, format yyyymmdd
     * @return string $status
     */
    public function getTransactionStatus($quote_id, $date, $storeId)
    {
        $url = $this->config->getValue(
            "payment/chcybersource/one_doc_report_url",
            'store',
            $storeId
        );

        $params = [];
        $params['merchantID'] = $this->config->getValue(
            "payment/chcybersource/merchant_id",
            'store',
            $storeId
        );
        $params['type'] = 'transaction';
        $params['subtype'] = 'transactionDetail';
        $params['merchantReferenceNumber'] = $quote_id;
        $params['targetDate'] = $date;
        $params['versionNumber'] = '1.5';

        $this->curl->setCredentials(
            $this->config->getValue(
                "payment/chcybersource/report_username",
                'store',
                $storeId
            ),
            $this->config->getValue(
                "payment/chcybersource/report_password",
                'store',
                $storeId
            )
        );

        $this->curl->post($url, $params);
        $status = 'error';

        try {
            $response = $this->curl->getBody();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        if (!empty($response)) {
            $xml = simplexml_load_string($response);
            if (!empty($xml->Requests->Request)) {
                $status = 'ok';
            }
        }
        return $status;
    }

    /**
     * @param $quoteId
     * @param $merchantId
     * @return array
     */
    public function getListOfBanks($quoteId, $merchantId)
    {
        $this->setBankTransferCredentials();
        $data = [];
        $request = [];
        $request['apOptionsService'] = ['run' => 'true'];
        $request['merchantID'] = $merchantId;
        $request['merchantReferenceCode'] = $quoteId;
        $request['apPaymentType'] = 'IDL';

        try {
            $this->initSoapClient();
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
     * @param $quoteAddress
     * @return \stdClass
     */
    private function buildAddress($quoteAddress)
    {
        /** @var \Magento\Quote\Model\Quote\Address $quoteAddress */
        $address = new \stdClass();
        $address->city =  $quoteAddress->getData('city');
        $address->country = $quoteAddress->getData('country_id');
        $address->postalCode = $quoteAddress->getData('postcode');
        $address->state = $quoteAddress->getRegionCode();
        $address->street1 = $quoteAddress->getStreetLine(1);
        $address->email = $quoteAddress->getEmail();
        $address->firstName = $quoteAddress->getFirstname();
        $address->lastName = $quoteAddress->getLastname();

        if ($quoteAddress->getAddressType() == \Magento\Quote\Model\Quote\Address::TYPE_BILLING) {
            //$address->ipAddress = $this->requestDataHelper->getRemoteAddress();
            $address->phoneNumber = $quoteAddress->getTelephone();
        }

        return $address;
    }

    /**
     * @param array $items
     * @param \stdClass $request
     * @param $shippingInclTax
     * @return mixed
     */
    private function buildRequestItems(array $items, \stdClass $request, $shippingInclTax)
    {
        $isBundle = false;
        foreach ($items as $i => $item) {

            if (empty($item->getPrice()) && $item->getParentItemId()) {
                continue;
            }

            $qty = $item->getQty();
            if (empty($qty)) {
                $qty = 1;
            }

            $amount = ($item->getPrice() - ($item->getDiscountAmount() / $qty));
            $requestItem = new \stdClass();
            $requestItem->id = $i;
            $requestItem->productName = $item->getName();
            $requestItem->productSKU = $item->getSku();
            $requestItem->quantity = (int) $qty;
            $requestItem->productCode = 'default';
            $requestItem->unitPrice = $this->requestDataHelper->formatAmount($amount);
            $requestItem->totalAmount = $this->requestDataHelper->formatAmount($amount * $qty);
            $requestItem->taxAmount = $this->requestDataHelper->formatAmount($item->getTaxAmount());
            $requestItem->parentId = $item->getParentItemId();

            $request->item[] = $requestItem;

            if ($item->getProductType() === \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {
                $isBundle = true;
                $i++;
                foreach ($item->getQtyOptions() as $option) {
                    $product = $option->getProduct();
                    $requestItem = new \stdClass();
                    $requestItem->id = $i++;
                    $requestItem->productName = $product->getName();
                    $requestItem->productSKU = $product->getSku();
                    $requestItem->quantity = $product->getQuoteItemQty();
                    $requestItem->productCode = 'default';
                    $requestItem->unitPrice = $this->requestDataHelper->formatAmount(0);
                    $requestItem->totalAmount = $this->requestDataHelper->formatAmount(0);
                    $requestItem->taxAmount = $this->requestDataHelper->formatAmount(0);

                    $request->item[] = $requestItem;
                }
            }
        }

        $shippingCost = $shippingInclTax;
        $shippingCostItem = new \stdClass();
        $shippingCostItem->id = count($request->item) + 1;
        $shippingCostItem->productCode = 'shipping_and_handling';
        $shippingCostItem->unitPrice = $this->requestDataHelper->formatAmount($shippingCost);
        $shippingCostItem->totalAmount = $this->requestDataHelper->formatAmount($shippingCost);
        $shippingCostItem->parentId = null;
        $request->item[] = $shippingCostItem;

        if (property_exists($request, 'item') && is_array($request->item) && !$isBundle) {
            foreach ($request->item as $key => $item) {
                if ($item->unitPrice == 0 && $item->parentId !== null && $item->productCode != 'shipping_and_handling') {
                    unset($request->item[$key]);
                }
            }

            $request->item = array_values($request->item);
        }

        foreach ($request->item as $key => $item) {
            if (property_exists($item, 'parentId')) {
                unset($request->item[$key]->parentId);
            }
        }

        return $request;
    }

    /**
     *
     * @param Quote $quote
     * @return \stdClass
     */
    public function buildDecisionManagerFields(Quote $quote)
    {
        $merchantDefinedData = new \stdClass;
        $merchantDefinedData->field1 = (int) $this->requestDataHelper->customerSession->isLoggedIn(); // Registered or Guest Account

        if ($this->requestDataHelper->customerSession->isLoggedIn()) {
            $merchantDefinedData->field2 = $this->requestDataHelper->customerSession->getCustomerData()->getCreatedAt(); // Account Creation Date

            $orders = $this->orderCollectionFactory->create()
                ->addFieldToFilter('customer_id', $this->requestDataHelper->customerSession->getCustomerId())
                ->setOrder('created_at', 'desc');

            $merchantDefinedData->field3 = count($orders); // Purchase History Count

            if ($orders->getSize() > 0) {
                $lastOrder = $orders->getFirstItem();
                $merchantDefinedData->field4 = $lastOrder->getData('created_at'); // Last Order Date
            }

            $merchantDefinedData->field5 = round((time() - strtotime($this->requestDataHelper->customerSession->getCustomerData()->getCreatedAt() ?? '')) / (3600*24));// Member Account Age (Days)
        }

        $orders = $this->orderCollectionFactory->create()
            ->addFieldToFilter('customer_email', $quote->getCustomerEmail());

        $merchantDefinedData->field6 = (int)(count($orders) > 0); // Repeat Customer
        $merchantDefinedData->field20 = $quote->getCouponCode(); //Coupon Code
        $merchantDefinedData->field21 = ($quote->getSubtotal() - $quote->getSubtotalWithDiscount()); // Discount

        $message = $this->giftMessage->load($quote->getGiftMessageId());
        $merchantDefinedData->field22 = ($message) ? $message->getMessage() : ''; // Gift Message
        $merchantDefinedData->field23 = ($this->auth->isLoggedIn()) ? 'call center' : 'web'; //order source

        return $merchantDefinedData;
    }


    /**
     * @param $merchantId
     * @param $reservedOrderId
     * @param array $shippingAddress
     * @param Address|null $billingAddress
     * @return string
     */
    public function checkAddress($merchantId, $reservedOrderId, array $shippingAddress, ?Address $billingAddress = null)
    {

        $request = new \stdClass();
        $request->merchantID = $merchantId;
        $request->partnerSolutionID = RequestDataBuilder::PARTNER_SOLUTION_ID;
        $developerId = $this->config->getValue(
            "payment/chcybersource/developer_id",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $request->merchantReferenceCode = $reservedOrderId;

        if (!empty($billingAddress)) {
            $billTo = new \stdClass();
            $billTo->country = $billingAddress->getCountry();
            if (in_array($billTo->country, ['CA', 'US'])) {
                $billTo->state = $billingAddress->getRegionCode();
            }
            $billTo->postalCode = $billingAddress->getPostcode();
            $billTo->street1 = $billingAddress->getData('street1');
            if ($billingAddress->getData('street2')) {
                $billTo->street2 = $billingAddress->getData('street2');
            }
            $billTo->city = $billingAddress->getCity();
            $request->billTo = $billTo;
        }

        if (!empty($shippingAddress)) {
            $shipTo = new \stdClass();
            $shipTo->country = $shippingAddress['country'];
            if (in_array($shipTo->country, ['CA', 'US'])) {
                $shipTo->state = $shippingAddress['region_code'];
            }
            $shipTo->postalCode = $shippingAddress['postcode'];
            $shipTo->firstName = $shippingAddress['firstname'];
            $shipTo->lastName = $shippingAddress['lastname'];
            $shipTo->street1 = $shippingAddress['street1'];
            if ($shippingAddress['street2']) {
                $shipTo->street2 = $shippingAddress['street2'];
            }
            $shipTo->city = $shippingAddress['city'];
            $shipTo->phoneNumber = $shippingAddress['telephone'];
            $request->shipTo = $shipTo;
        }

        $davService = new \stdClass();
        $davService->run = 'true';
        $request->davService = $davService;
        $result = '';
        try {
            $this->logger->debug([__METHOD__ => (array) $request]);
            $result = $this->client->runTransaction($request);
            $this->logger->debug([(array) $result]);
        } catch (\Exception $e) {
            $this->logger->error("error in address verification service: " . $e->getMessage());
        }
        return $result;
    }
}
