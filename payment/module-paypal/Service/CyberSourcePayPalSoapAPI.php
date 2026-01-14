<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\PayPal\Service;

use CyberSource\Core\Model\LoggerInterface;
use CyberSource\PayPal\Helper\RequestDataBuilder;
use CyberSource\Core\Service\AbstractConnection;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\AddressFactory;
use Magento\Sales\Model\Order\Payment;
use Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;

class CyberSourcePayPalSoapAPI extends AbstractConnection
{
    const SUCCESS_REASON_CODE = 100;

    /** @var  \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface */
    private $transactionBuilder;

    /**
     * @var \SoapClient
     */
    public $client;

    /**
     * @var int
     */
    private $merchantReferenceCode;

    /**
     * @var RequestDataBuilder
     */
    private $requestDataHelper;

    /** @var Payment $payment */
    private $payment = null;

    /** @var bool $isSuccessfullyVoid */
    public $isSuccessfullyVoid = false;

    /** @var bool $isSuccessfullyReverse */
    public $isSuccessfullyReverse = false;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    private $countryFactory;

    /**
     * Map for billing address import/export
     *
     * @var array
     */
    protected $_billingAddressMap = [
        'payer' => 'email',
        'firstName' => 'firstname',
        'lastName' => 'lastname',
        'country' => 'country_id', // iso-3166 two-character code
        'state' => 'region',
        'city' => 'city',
        'street1' => 'street1',
        'street2' => 'street2',
        'postalCode' => 'postcode',
        'phoneNumber' => 'telephone',
    ];

    /**
     * CyberSourcePayPalSoapAPI constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param BuilderInterface $transactionBuilder
     * @param RequestDataBuilder $requestDataHelper
     * @param DataObjectFactory $dataObjectFactory
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @throws \Exception
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        BuilderInterface $transactionBuilder,
        RequestDataBuilder $requestDataHelper,
        DataObjectFactory $dataObjectFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory
    ) {
        parent::__construct($scopeConfig, $logger);
        $this->client = $this->getSoapClient();
        $this->transactionBuilder = $transactionBuilder;
        $this->requestDataHelper = $requestDataHelper;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->countryFactory = $countryFactory;
    }

    public function setPayment($payment)
    {
        $this->payment = $payment;
        $this->merchantReferenceCode = $payment->getOrder()->getQuoteId();
    }

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

    private function reloadCredentials(&$request)
    {
        if (isset($request->storeId)) {
            $this->setCredentialsByStore($request->storeId);
            $this->initSoapClient();
            unset($request->storeId);
        }
    }

    /**
     * Retrieve PayPal Token for Express Checkout by calling method setService
     *
     * @param \stdClass $request
     * @return array
     * @throws \Exception
     */
    public function sessionService($request)
    {
        $result = null;
        $errorMessage = null;
        try {
            $this->reloadCredentials($request);
            $this->logger->debug([__METHOD__ => (array) $request]);
            $response = $this->client->runTransaction($request);
            $this->logger->debug([__METHOD__ => (array) $response]);

            if (null !== $response && isset($response->decision) && $response->decision == 'DECLINE') {
                throw new LocalizedException(__('Sorry but your transaction was unsuccessful.'));
            }

            if ($response === null || 'ERROR' === $response->decision || 100 != $response->reasonCode) {
                $message = "Unable to process request, check module configuration. Reason Code: " . $response->reasonCode;
                throw new LocalizedException(__($message));
            }

            if ($response !== null && 100 == $response->reasonCode) {
                $result = [
                    'paypalToken' => substr(
                        $response->apSessionsReply->merchantURL ?? '',
                        strrpos($response->apSessionsReply->merchantURL ?? '', 'token=') + 6
                    ),
                    'merchantURL' => $response->apSessionsReply->merchantURL,
                    'requestID' => $response->requestID,
                    'merchantReferenceCode' => $response->merchantReferenceCode,
                ];
            }

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }

        return $result;
    }

    /**
     * Get PayPal order details
     *
     * @param \stdClass $request
     * @return array
     * @throws \Exception
     */
    public function checkStatusService($request)
    {
        $response = null;
        try {
            $this->reloadCredentials($request);
            $this->logger->debug([__METHOD__ => (array) $request]);
            $result = $this->client->runTransaction($request);
            $this->logger->debug([__METHOD__ => (array) $result]);

            if (null !== $result && 100 == $result->reasonCode) {
                $shippingAddress = $this->convertPayPalAddressToAddress($result->shipTo);
                $billingAddress = $this->convertPayPalAddressToAddress($result->billTo);

                $response = [
                    'paypalPayerId' => $result->apReply->payerID,
                    'paypalEcSetRequestID' => $request->apCheckStatusService->sessionsRequestID,
                    'paypalEcSetRequestToken' => $result->requestToken,
                    'paypalCustomerEmail' => $result->billTo->email,
                    'billingAddress' => $billingAddress,
                    'shippingAddress' => $shippingAddress,
                    'merchantReferenceCode' => $request->merchantReferenceCode
                ];

            } else {
                throw new LocalizedException(__("Unable to retrieve details from PayPal"));
            }

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }

        return $response;
    }

    private function convertPayPalAddressToAddress($data)
    {
        $address = $this->dataObjectFactory->create();
        \Magento\Framework\DataObject\Mapper::accumulateByMap((array) $data, $address, $this->_billingAddressMap);
        $address->setExportedKeys(array_values($this->_billingAddressMap));

        // attempt to fetch region_id from directory
        if ($address->getCountryId() && $address->getRegion()) {
            $regions = $this->countryFactory->create()->loadByCode(
                $address->getCountryId()
            )->getRegionCollection()->addRegionCodeOrNameFilter(
                $address->getRegion()
            )->setPageSize(
                1
            );
            foreach ($regions as $region) {
                $address->setRegionId($region->getId());
                $address->setExportedKeys(array_merge($address->getExportedKeys(), ['region_id']));
                break;
            }
        }
        return $address;
    }

    /**
     * Perform PayPal Payment
     *
     * @param \stdClass $request
     * @return \stdClass
     * @throws \Exception
     */
    public function orderSetupService($request)
    {
        $response = null;
        try {
            $this->reloadCredentials($request);
            $this->logger->debug([__METHOD__ => (array) $request]);
            $result = $this->client->runTransaction($request);
            $this->logger->debug([__METHOD__ => (array) $result]);

            if (null !== $result && 100 == $result->reasonCode) {
                $response = $result;
            } else {
                throw new LocalizedException(__("Unable to setup order on PayPal"));
            }

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }
        return $response;
    }

    /**
     * Perform PayPal Payment
     *
     * @param \stdClass $request
     * @return \stdClass
     * @throws \Exception
     */
    public function authorizationService($request)
    {
        $response = null;
        try {
            $this->reloadCredentials($request);
            $this->logger->debug([__METHOD__ => (array) $request]);
            $result = $this->client->runTransaction($request);
            $this->logger->debug([__METHOD__ => (array) $result]);

            if (null !== $result && (100 == $result->reasonCode || 480 == $result->reasonCode)) {
                $response = $result;
            } else {
                $this->logger->error("PAYPAL AUTH RESPONSE: " . json_encode($result));
                throw new LocalizedException(__("Unable to Authorize order on PayPal"));
            }

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }
        return $response;
    }

    /**
     * Perform PayPal Payment
     *
     * @param \stdClass $request
     * @return \stdClass
     * @throws \Exception
     */
    public function captureService($request)
    {
        try {
            $this->reloadCredentials($request);
            $this->logger->debug([__METHOD__ => (array) $request]);
            $result = $this->client->runTransaction($request);
            $this->logger->debug([__METHOD__ => (array) $result]);

            if (!$result || !in_array($result->reasonCode, [100, 480])) {
                throw new LocalizedException(__("Unable to Capture order on PayPal"));
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }

        return $result;
    }

    /**
     * @param \stdClass $request
     * @return \stdClass
     * @throws \Exception
     */
    public function refundService($request)
    {
        $response = null;
        try {
            $this->reloadCredentials($request);
            $this->logger->debug([__METHOD__ => (array) $request]);
            $result = $this->client->runTransaction($request);
            $this->logger->debug([__METHOD__ => (array) $result]);

            if (null !== $result && 100 == $result->reasonCode) {
                $response = $result;
            } else {
                throw new LocalizedException(__("Unable to Refund order on PayPal"));
            }

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }
        return $response;
    }

    /**
     * @param \stdClass $request
     * @return \stdClass
     * @throws \Exception
     */
    public function authorizeReversalService($request)
    {
        try {
            $this->reloadCredentials($request);
            $this->logger->debug([__METHOD__ => (array) $request]);
            $result = $this->client->runTransaction($request);
            $this->logger->debug([__METHOD__ => (array) $result]);

            if (!$result | $result->reasonCode != 100) {
                throw new LocalizedException(__("Unable to Reverse Authorization order on PayPal"));
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }

        return $result;
    }

    /**
     * @param \stdClass $request
     * @return \stdClass
     * @throws \Exception
     */
    public function billingAgreementService($request)
    {
        try {
            $this->reloadCredentials($request);
            $this->logger->debug([__METHOD__ => (array) $request]);
            $result = $this->client->runTransaction($request);
            $this->logger->debug([__METHOD__ => (array) $result]);

            if (!$result || $result->reasonCode != 100) {
                throw new LocalizedException(__("Unable to sign up for PayPal Billing Agreement"));
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }

        return $result;
    }

    /**
     * @param \stdClass $request
     * @return \stdClass
     * @throws \Exception
     */
    public function saleService($request)
    {
        try {
            $this->reloadCredentials($request);
            $this->logger->debug([__METHOD__ => (array) $request]);
            $result = $this->client->runTransaction($request);
            $this->logger->debug([__METHOD__ => (array) $result]);

            if (!$result || !in_array($result->reasonCode, [100, 480])) {
                throw new LocalizedException(__("Unable to perform sale on PayPal"));
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }

        return $result;
    }

    /**
     * @param \stdClass|array $request
     * @return \stdClass
     * @throws \Exception
     */
    public function vaultSaleService($request)
    {
        try {
            $this->reloadCredentials($request);
            $this->logger->debug([__METHOD__ => (array) $request]);
            $result = $this->client->runTransaction($request);
            $this->logger->debug([__METHOD__ => (array) $result]);

            if (!$result || !in_array($result->reasonCode, [100, 480])) {
                throw new LocalizedException(__("Unable to perform vault sale on PayPal"));
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }

        return $result;
    }

    /**
     * @param \stdClass $request
     */
    public function cancelBillingAgreementService($request)
    {
        try {
            $this->reloadCredentials($request);
            $this->logger->debug([__METHOD__ => (array) $request]);
            $result = $this->client->runTransaction($request);
            $this->logger->debug([__METHOD__ => (array) $result]);

            if (!$result || !$result->reasonCode != 100) {
                throw new LocalizedException(__("Unable to cancel PayPal Billing Agreement"));
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Build transaction object
     *
     * @param \stdClass $result
     * @param $type
     * @return \Magento\Sales\Api\Data\TransactionInterface
     */
    public function buildTransaction(\stdClass $result, $type)
    {
        $trans = $this->transactionBuilder;

        $resultData = [
            "merchantReferenceCode" => $result->merchantReferenceCode,
            "requestID" => $result->requestID,
            "decision" => $result->decision,
            "reasonCode" => $result->reasonCode,
            "payPalEcSetReply" => (array) $result->payPalEcSetReply
        ];

        $transaction = $trans->setPayment($this->payment)
            ->setOrder($this->payment->getOrder())
            ->setTransactionId($result->requestID)
            ->setAdditionalInformation(
                [\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => $resultData]
            )
            ->setFailSafe(true)
            ->build($type);

        return $transaction;
    }
}
