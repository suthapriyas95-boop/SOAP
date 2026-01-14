<?php
/**
 * Copyright © 2018 CyberSource. All rights reserved.
 * See accompanying LICENSE.txt for applicable terms of use and license.
 */

namespace CyberSource\SecureAcceptance\Helper;

use CyberSource\Core\Model\Source\AuthIndicator;
use CyberSource\SecureAcceptance\Gateway\Config\Config;
use CyberSource\Core\Helper\AbstractDataBuilder;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Group;
use Magento\Framework\Locale\Resolver;
use Magento\Quote\Model\Quote;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Checkout\Helper\Data as CheckoutHelper;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Vault\Api\Data\PaymentTokenInterface;

class RequestDataBuilder extends AbstractDataBuilder
{
    const TAX_AMOUNT = 'merchant_defined_data6';
    const USE_IFRAME = 'merchant_defined_data11';
    const REQ_USE_IFRAME = 'req_merchant_defined_data11';
    const PAY_URL = 'pay_url';
    const PAY_TEST_URL = 'pay_test_url';
    const KEY_ORDER_ID = 'merchant_secure_data1';
    const KEY_QUOTE_ID = 'merchant_secure_data1';
    const KEY_SID = 'merchant_secure_data2';
    const KEY_STORE_ID = 'merchant_secure_data3';
    const KEY_SCOPE = 'merchant_secure_data4';
    const KEY_AGREEMENT_IDS = 'merchant_defined_data12';

    const TYPE_SALE = 'sale';
    const TYPE_AUTHORIZATION = 'authorization';
    const TYPE_CREATE_TOKEN = 'create_payment_token';
    const CC_CAPTURE_SERVICE_TOTAL_COUNT = 99;
    const CARD_TYPE_SELECTION_INDICATOR_BY_CARDHOLDER = '1';

    /**
     * @var Resolver
     */
    private $resolver;

    /**
     * @var string
     */
    private $requestUrl = '';

    /**
     * @var Config
     */
    private $gatewayConfig;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    private $customerModel;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    private $orderRepository;

    /**
     * @var Vault
     */
    private $vaultHelper;

    /**
     * @var array
     */
    private $requestUrls = [
        self::PAY_URL => 'https://secureacceptance.cybersource.com/pay',
        self::PAY_TEST_URL => 'https://testsecureacceptance.cybersource.com/pay'
    ];

    /**
     * @var \CyberSource\SecureAcceptance\Model\SignatureManagementInterface
     */
    private $signatureManagement;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $encryptor;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Grid\CollectionFactory
     */
    private $orderGridCollectionFactory;

    /**
     * @var \Magento\Tax\Model\Config
     */
    private $taxConfig;

    /**
     * @var \CyberSource\Core\StringUtils\FilterInterface
     */
    private $filter;

    /**
     * RequestDataBuilder constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param CollectionFactory $orderCollectionFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Grid\CollectionFactory $orderGridCollectionFactory
     * @param \Magento\Framework\Session\SessionManagerInterface $checkoutSession
     * @param Resolver $resolver
     * @param CheckoutHelper $checkoutHelper
     * @param Config $gatewayConfig
     * @param \Magento\Backend\Model\Auth $auth
     * @param \Magento\Customer\Model\Customer $customerModel
     * @param \Magento\GiftMessage\Model\Message $giftMessage
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \CyberSource\SecureAcceptance\Model\SignatureManagementInterface $signatureManagement
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param Vault $vaultHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\Grid\CollectionFactory $orderGridCollectionFactory,
        \Magento\Framework\Session\SessionManagerInterface $checkoutSession,
        Resolver $resolver,
        CheckoutHelper $checkoutHelper,
        Config $gatewayConfig,
        \Magento\Backend\Model\Auth $auth,
        \Magento\Customer\Model\Customer $customerModel,
        \Magento\GiftMessage\Model\Message $giftMessage,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \CyberSource\SecureAcceptance\Model\SignatureManagementInterface $signatureManagement,
        \CyberSource\Core\StringUtils\FilterInterface $filter,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Tax\Model\Config $taxConfig,
        Vault $vaultHelper
    ) {
        parent::__construct(
            $context,
            $customerSession,
            $checkoutSession,
            $checkoutHelper,
            $orderCollectionFactory,
            $orderGridCollectionFactory,
            $auth,
            $giftMessage
        );
        $this->resolver = $resolver;
        $this->gatewayConfig = $gatewayConfig;
        $this->locale = str_replace('_', '-', strtolower($this->resolver->getLocale() ?? ''));
        $this->customerModel = $customerModel;
        $this->urlBuilder = $context->getUrlBuilder();
        $this->orderRepository = $orderRepository;
        $this->vaultHelper = $vaultHelper;
        $this->signatureManagement = $signatureManagement;
        $this->filter = $filter;
        $this->encryptor = $encryptor;
        $this->orderGridCollectionFactory = $orderGridCollectionFactory;
        $this->taxConfig = $taxConfig;
    }

    /**
     * @param null $guestEmail
     * @param null $token
     * @param null $cardType
     * @param \Magento\Sales\Model\Order|null $order
     * @return array
     * @throws LocalizedException
     */
    public function buildSilentRequestData(
        $guestEmail = null,
        $token = null,
        $cardType = null,
        ?\Magento\Sales\Model\Order $order = null,
        $currencyCode = null,
        $additionalFields = []
    ) {
        $cardType = $this->getCardType($cardType);

        $quote = $this->checkoutSession->getQuote();
        if (! $quote->getId()) {
            throw new LocalizedException(
                __('Sorry we can\'t place an order. Some error happens during order placement.')
            );
        }

        /**
         * Order object is passed when placed with vault over multishipping
         */
        if ($order) {
            $referenceNumber = $order->getIncrementId();
            $billingAddress = $order->getBillingAddress();
            $shippingAddress = $order->getShippingAddress();
            $orderItems = $order->getAllVisibleItems();
            $amount = $order->getBaseGrandTotal();
        } else {
            if (!$quote->getReservedOrderId()) {
                $quote->reserveOrderId()->save();
            }
            $referenceNumber = $quote->getReservedOrderId();
            $billingAddress = $quote->getBillingAddress();
            $shippingAddress = $quote->getShippingAddress();
            $orderItems = $quote->getAllVisibleItems();
            $amount = $quote->getBaseGrandTotal();
        }
        $isCvvEnabled = $this->gatewayConfig->getValue("enable_cvv") || $this->gatewayConfig->getValue("enable_admin_cvv");
        if ($isCvvEnabled && $this->checkoutSession->getCvv()) {
            $cvv = $this->checkoutSession->getCvv();
            $this->checkoutSession->unsCvv();
        } else {
            $this->checkoutSession->unsCvv();
        }

        $unsignedFields = 'card_type,card_number,card_expiry_date';
        if (! $this->gatewayConfig->getIgnoreCvn()) {
            $unsignedFields .= ',card_cvn';
        }

        $params = [
            'access_key' => ($token) ? $this->gatewayConfig->getSopAccessKey() : $this->gatewayConfig->getSopAuthAccessKey(),
            'profile_id' => ($token) ? $this->gatewayConfig->getSopProfileId() : $this->gatewayConfig->getSopAuthProfileId(),
            'ignore_avs' => $this->gatewayConfig->getIgnoreAvs() ? 'true' : 'false',
            'ignore_cvn' => $this->gatewayConfig->getIgnoreCvn() ? 'true' : 'false',
            'transaction_uuid' => uniqid(),
            'unsigned_field_names' => ($token) ? 'payment_token' : $unsignedFields,
            'signed_date_time' => gmdate("Y-m-d\\TH:i:s\\Z"),
            'locale' => $this->locale,
            'transaction_type' => $this->getTransactionType(),
            'reference_number' => $referenceNumber,
            'amount' => $this->formatAmount($amount),
            'currency' => $currencyCode ? $currencyCode : $quote->getCurrency()->getData('base_currency_code'),
            'payment_method' => 'card',
            'partner_solution_id' => self::PARTNER_SOLUTION_ID,
            //payer auth fields
            'payer_auth_enroll_service_run' => 'true'
        ];

        if (!$quote->isVirtual()) {
            $params['tax_amount'] = $this->formatAmount($shippingAddress->getBaseTaxAmount());
        }

        if (isset($cardType)) {
            $params['card_type'] = $cardType;
            $params['card_type_selection_indicator'] = self::CARD_TYPE_SELECTION_INDICATOR_BY_CARDHOLDER;
        }

        if (isset($cvv)) {
            $params['card_cvn'] = $cvv;
            if ($params['unsigned_field_names'] == 'payment_token') {
                $params['unsigned_field_names'] = $params['unsigned_field_names'].',card_cvn';
            }
        }

        $params[static::KEY_QUOTE_ID] = $quote->getId();
        $customerEmail = (!empty(trim($guestEmail ?? '')) && $guestEmail != 'null') ? trim($guestEmail ?? '') : $quote->getCustomerEmail();
        $params = array_merge($params, $this->buildBillingAddress($billingAddress, $customerEmail, true));
        $params = array_merge($params, $this->buildShippingAddress($shippingAddress, $customerEmail, true, $quote->isVirtual()));
        $params = $this->buildDecisionManagerFieldsForSA($quote, $params);
        $params = array_merge($params, $this->buildOrderItems($orderItems));

        if ($shippingAddress->getBaseShippingAmount() > 0 ) {
            $params = array_merge($params, $this->getShippingOrderLineItem($shippingAddress->getBaseShippingAmount(), $params['line_item_count']));
            $params['line_item_count'] = $params['line_item_count'] + 1;
        }

        if (!empty($token) || $token !== null) {
            $params['payment_token'] = $token;
        }
        $params = array_merge($params, $additionalFields);

        $fingerprintId = $this->checkoutSession->getData('fingerprint_id');
        if (!empty($fingerprintId)) {
            $params['device_fingerprint_id'] = $fingerprintId;
        }

        //Admin initiated transaction
        if (empty($quote->getRemoteIp())) {
            $params['e_commerce_indicator'] = 'moto';
        }


        $params[static::KEY_SID] = $this->encryptor->encrypt($this->checkoutSession->getSessionId());

        $params[static::KEY_STORE_ID] = $this->checkoutSession->getStore()
            ? $this->checkoutSession->getStore()->getId()
            : $this->checkoutSession->getQuote()->getStoreId();

        $params = $this->filterParams($params);

        $params['signed_field_names'] = $this->getSignedFields($params);
        $secretKey = ($token) ? $this->gatewayConfig->getSopSecretKey() : $this->gatewayConfig->getSopAuthSecretKey();
        $params['signature'] = $this->sign($params, $secretKey);

        return $params;
    }

    /**
     * @return array
     */
    public function buildRequestData()
    {
        $quote = $this->checkoutSession->getQuote();
        $billingAddress = $quote->getBillingAddress();

        $params = $this->buildBasePaymentData($quote, $billingAddress);

        $params['customer_email'] = $quote->getCustomerEmail();
        $params['customer_lastname'] = $quote->getCustomerLastname();
        $params['customer_cookies_accepted'] = 'false';

        if ($this->gatewayConfig->getUseIframe()) {
            $params[self::USE_IFRAME] = "1";
            $params['use_iframe'] = 1;
        }

        $params[static::KEY_SID] = $this->encryptor->encrypt($this->checkoutSession->getSessionId());

        $params[static::KEY_STORE_ID] = $this->checkoutSession->getStore()
            ? $this->checkoutSession->getStore()->getId()
            : $this->checkoutSession->getQuote()->getStoreId();


        $params = $this->filterParams($params);
        $params['signed_field_names'] = $this->getSignedFields($params);
        $params['signature'] = $this->sign($params, $this->gatewayConfig->getAuthSecretKey());

        return $params;
    }

    /**
     * @param Quote $quote
     * @param Quote\Address $billingAddress
     * @return array
     */
    private function buildBasePaymentData(Quote $quote, Quote\Address $billingAddress)
    {
        $shippingAddress = $quote->getShippingAddress();

        $params = [
            'access_key' => $this->gatewayConfig->getAuthAccessKey(),
            'profile_id' => $this->gatewayConfig->getAuthProfileId(),
            'ignore_avs' => $this->gatewayConfig->getIgnoreAvs() ? 'true' : 'false',
            'ignore_cvn' => $this->gatewayConfig->getIgnoreCvn() ? 'true' : 'false',
            'transaction_uuid' => uniqid(),
            'payment_method' => 'card',
            'unsigned_field_names' => '',
            'signed_date_time' => gmdate("Y-m-d\\TH:i:s\\Z"),
            'locale' => $this->locale,
            'transaction_type' => $this->getTransactionType(),
            'reference_number' => $quote->getReservedOrderId(),
            'amount' => $this->formatAmount($quote->getBaseGrandTotal()),
            'currency' => $quote->getCurrency()->getData('base_currency_code'),
            'request_url' => $this->requestUrl,
            'override_custom_receipt_page' => $this->urlBuilder->getUrl('cybersource/index/placeorder'),
            'override_custom_cancel_page' => $this->urlBuilder->getUrl('cybersource/index/cancel'),
            'tax_amount' => $this->formatAmount($shippingAddress->getBaseTaxAmount()),
            'partner_solution_id' => self::PARTNER_SOLUTION_ID
        ];

        if ($this->gatewayConfig->getAuthIndicator() != AuthIndicator::UNDEFINED) {
            $params['auth_indicator'] = $this->gatewayConfig->getAuthIndicator();
        }

        $params[static::KEY_QUOTE_ID] = $quote->getId();

        $params = array_merge($params, $this->buildBillingAddress($billingAddress, $quote->getCustomerEmail()));
        $params = array_merge($params, $this->buildShippingAddress($shippingAddress, $quote->getCustomerEmail(), false, $quote->isVirtual()));
        $params = $this->buildDecisionManagerFieldsForSA($quote, $params);
        $params = array_merge($params, $this->buildOrderItems($quote->getAllVisibleItems()));

        if ($shippingAddress->getBaseShippingAmount() > 0 ) {
            $params = array_merge($params, $this->getShippingOrderLineItem($shippingAddress->getBaseShippingAmount(), $params['line_item_count']));
            $params['line_item_count'] = $params['line_item_count'] + 1;
        }

        //Admin initiated transaction
        if (empty($quote->getRemoteIp())) {
            $params['e_commerce_indicator'] = 'moto';
        }


        return $params;
    }

    private function getShippingOrderLineItem($shippingAmount, $id)
    {
        $toReturn['item_' . $id . '_name'] = 'shipping';
        $toReturn['item_' . $id . '_sku'] = 'shipping_and_handling';
        $toReturn['item_' . $id . '_quantity'] = 1;
        $toReturn['item_' . $id . '_unit_price'] = $this->formatAmount($shippingAmount);
        $toReturn['item_' . $id . '_code'] = 'shipping_and_handling';

        return $toReturn;
    }


    /**
     *
     * @param Quote $quote
     * @return array
     */
    public function buildDecisionManagerFieldsForSA(Quote $quote, $params)
    {
        $data = [];
        $data['merchant_defined_data1'] = (int)$this->customerSession->isLoggedIn();// Registered or Guest Account

        if ($this->customerSession->isLoggedIn()) {
            $customer = $this->customerModel->load($this->customerSession->getCustomerId());
            $data['merchant_defined_data2'] = $customer->getData('created_at'); // Account Creation Date

            $orders = $this->orderCollectionFactory->create()
                ->addFieldToFilter('customer_id', $this->customerSession->getCustomerId())
                ->setOrder('created_at', 'desc');

            $data['merchant_defined_data3'] = count($orders); // Purchase History Count

            if ($orders->getSize() > 0) {
                $lastOrder = $orders->getFirstItem();
                $data['merchant_defined_data4'] = $lastOrder->getData('created_at'); // Last Order Date
            }

            $data['merchant_defined_data5'] = round((time() - strtotime($customer->getData('created_at') ?? '')) / (3600 * 24));// Member Account Age (Days)
        }

        $orders = $this->orderGridCollectionFactory->create()
            ->addFieldToFilter('customer_email', $quote->getCustomerEmail())
        ;
        $orders->getSelect()->limit(1);

        $data['merchant_defined_data6'] = (int)(count($orders) > 0); // Repeat Customer
        $data['merchant_defined_data20'] = $quote->getCouponCode(); //Coupon Code
        $data['merchant_defined_data21'] = ($quote->getBaseSubtotal() - $quote->getBaseSubtotalWithDiscount()); // Discount

        $message = $this->giftMessage->load($quote->getGiftMessageId());
        $data['merchant_defined_data22'] = ($message) ? $message->getMessage() : ''; // Gift Message
        $data['merchant_defined_data23'] = ($this->auth->isLoggedIn()) ? 'call center' : 'web'; //order source

        $data['consumer_id'] = $this->customerSession->getCustomerId();
        $data['customer_ip_address'] = $this->_remoteAddress->getRemoteAddress();

        if (!$quote->getIsVirtual()) {
            if ($shippingAddress = $quote->getShippingAddress()) {
                $data['merchant_defined_data31'] = $quote->getShippingAddress()->getShippingMethod();
                $data['merchant_defined_data32'] = $quote->getShippingAddress()->getShippingDescription();
            }
        }

        /**
         *  Remove invalid values before send it to cybersource, otherwise it will trigger a 403 without any error
         */
        $filteredData = [];
        foreach ($data as $key => $value) {
            if ($value !== null && !empty($value) && $value !== "" && $value !== 'null') {
                $filteredData[$key] = $value;
            }
        }

        $params = array_merge($params, $filteredData);

        return $params;
    }

    /**
     * @param $code
     * @param bool $isMagentoType
     * @return mixed
     */
    public function getCardType($code, $isMagentoType = false)
    {
        $types = [
            'VI' => '001',
            'MC' => '002',
            'AE' => '003',
            'DI' => '004',
            'DN' => '005',
            'JCB' => '007',
            'MI' => '042',
        ];

        if ($isMagentoType) {
            $types = array_flip($types);
        }

        return (!empty($types[$code])) ? $types[$code] : $code;
    }

    /**
     * @param $params
     * @param $secretKey
     * @return string
     */
    public function sign($params, $secretKey)
    {
        return $this->signatureManagement->sign($params, $secretKey);
    }

    /**
     * @param $response
     * @param $key
     *
     * @return bool
     */
    public function validateSignature($response, $key)
    {
        return $this->signatureManagement->validateSignature($response, $key);
    }

    /**
     * @param $billingAddress
     * @param $customerEmail
     * @param bool $isSilent
     * @return array
     */
    private function buildBillingAddress($billingAddress, $customerEmail, $isSilent = false)
    {
        $data = [
            'bill_to_forename' => $billingAddress->getData('firstname'),
            'bill_to_surname' => $billingAddress->getData('lastname'),
            'bill_to_email' => $customerEmail,
            'bill_to_company_name' => $billingAddress->getCompany(),
            'bill_to_phone' => $billingAddress->getTelephone(),
            'bill_to_address_line1' => $billingAddress->getStreetLine(1),
            'bill_to_address_line2' => $billingAddress->getStreetLine(2),
            'bill_to_address_city' => $billingAddress->getData('city'),
            'bill_to_address_postal_code' => $billingAddress->getData('postcode'),
            'bill_to_address_country' => $billingAddress->getData('country_id'),
        ];

        if ($billingAddress->getRegionCode() != null) {
            $data['bill_to_address_state'] = $billingAddress->getRegionCode();
        }

        if (!$isSilent) {
            $data['bill_address1'] = $billingAddress->getStreetLine(1);
            $data['bill_address2'] = $billingAddress->getStreetLine(2);
            $data['bill_city'] = $billingAddress->getData('city');
            $data['bill_country'] = $billingAddress->getData('country_id');
        }

        return $data;
    }

    /**
     * @param $shippingAddress
     * @param $customerEmail
     * @param bool $isSilent
     * @param bool $isVirtual
     * @return array
     */
    private function buildShippingAddress(
        $shippingAddress,
        $customerEmail,
        $isSilent = false,
        $isVirtual = false
    ) {

        /**
         * If quote is virtual it will not have shipping address
         */
        if ($isVirtual) {
            return [];
        }

        $data = [
            'ship_to_forename' => $shippingAddress->getData('firstname'),
            'ship_to_surname' => $shippingAddress->getData('lastname'),
            'ship_to_email' => $customerEmail,
            'ship_to_company_name' => $shippingAddress->getCompany(),
            'ship_to_address_line1' => $shippingAddress->getStreetLine(1),
            'ship_to_address_line2' => $shippingAddress->getStreetLine(2),
            'ship_to_phone' => $shippingAddress->getTelephone(),
            'ship_to_address_city' => $shippingAddress->getData('city'),
            'ship_to_address_postal_code' => $shippingAddress->getData('postcode'),
            'ship_to_address_country' => $shippingAddress->getData('country_id')
        ];

        if ($isSilent) {
            $data['ship_to_country'] = $shippingAddress->getData('country_id');
            if ($shippingAddress->getRegionCode() != null) {
                $data['ship_to_state'] = $shippingAddress->getRegionCode();
            }
        }

        if ($shippingAddress->getRegionCode() != null) {
            $data['ship_to_address_state'] = $shippingAddress->getRegionCode();
        }

        return $data;
    }

    /**
     * @return mixed
     */
    private function buildOrderItems($orderItems)
    {
        $i = 0;
        $j = 0;
        /** @var \Magento\Quote\Model\Quote\Item $item */
        $totalDiscountArray = $this->getCartItemsDiscount(); // Get discount for all items

        foreach ($orderItems as $item) {

            $qty = $item->getQty();
            if (empty($qty)) {
                $qty = 1;
            }
            $type = $item->getProductType() ?: $item->getOrderItem()->getProductType();

            if ($item->getProductType() === \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE) {

                //$amount = ($item->getPrice() - ($item->getDiscountAmount() / $qty)) * $qty;
                $params['item_' . $i . '_name'] = $this->filter->filter($item->getName());
                $params['item_' . $i . '_sku'] = $item->getSku();
                $params['item_' . $i . '_quantity'] = $qty;
                //$params['item_' . $i . '_unit_price'] = $this->formatAmount($amount);
                $params['item_' . $i . '_tax_amount'] = $this->formatAmount($item->getTaxAmount());

                $j = $i + 1;
                $discountPrice = 0;
                foreach ($item->getQtyOptions() as $option) {
                    $product = $option->getProduct();
                    $sku = $product->getSku();
                    $discountAmount = (!empty($totalDiscountArray) && isset($totalDiscountArray[$sku])) ? $totalDiscountArray[$sku] : 0;
                    $discountPrice = $discountPrice + $discountAmount; // calculate discount per item
                    $params['item_' . $j . '_name'] = $product->getName();
                    $params['item_' . $j . '_sku'] = $product->getSku();
                    $params['item_' . $j . '_quantity'] = $option->getValue();
                    $params['item_' . $j . '_unit_price'] = $this->formatAmount(0);
                    $params['item_' . $j . '_tax_amount'] = $this->formatAmount(0);
                    $params['item_' . $j . '_code'] = $type;


                    $j++;
                }
                // Get item amount after discount
                $amount = ($item->getPrice() - ($discountPrice / $qty)) * $qty;

                $params['item_' . $i . '_unit_price'] = $this->formatAmount($amount);
            } else {

                $amount = (
                    ($this->taxConfig->priceIncludesTax($item->getStoreId())
                        ? $item->getPriceInclTax()
                        : $item->getPrice())
                    - ($item->getDiscountAmount() / $qty));

                $params['item_' . $i . '_name'] = $this->filter->filter($item->getName());
                $params['item_' . $i . '_sku'] = $item->getSku();
                $params['item_' . $i . '_quantity'] = $qty;
                $params['item_' . $i . '_unit_price'] = $this->formatAmount($amount);
                $params['item_' . $i . '_tax_amount'] = $this->formatAmount($item->getTaxAmount());
                $params['item_' . $i . '_code'] = $type;
            }
            $i++;
        }

        $params['line_item_count'] = ($j > 0) ? $j : $i;
        return $params;
    }


    /**
     * @return string
     */
    private function getTransactionType()
    {
        $type = self::TYPE_SALE;
        $paymentAction = $this->gatewayConfig->getPaymentAction();
        $isTestMode = $this->gatewayConfig->isTestMode();

        $this->requestUrl = $this->requestUrls[self::PAY_URL];

        if ($isTestMode) {
            $this->requestUrl = $this->requestUrls[self::PAY_TEST_URL];
        }

        if ('authorize' === $paymentAction) {
            $type = self::TYPE_AUTHORIZATION;
        }

        if ($this->vaultHelper->getVaultEnabled()) {
            $type .= ',' . self::TYPE_CREATE_TOKEN;
        }
        return $type;
    }

    /**
     * @param $params
     * @return array|string
     */
    public function getSignedFields($params)
    {
        $signedFieldNames = [];
        foreach ($params as $key => $value) {
            if ($key !== 'card_number' &&
                $key !== 'request_url'
            ) {
                $signedFieldNames[] = $key;
            }
        }

        $signedFieldNames = implode(",", $signedFieldNames);

        return $signedFieldNames . ",signed_field_names";
    }

    /**
     * @param $params
     * @return string
     */
    public function getUnsignedFields($params)
    {
        $signedFieldNames = explode(",", $params["signed_field_names"] ?? '');
        $unsignedFieldNames = [];
        foreach ($params as $key => $field) {
            if (!in_array($key, $signedFieldNames)) {
                $unsignedFieldNames[] = $key;
            }
        }
        return implode(",", $unsignedFieldNames);
    }

    /**
     * Get checkout method
     *
     * @param Quote $quote
     * @return string
     */
    public function getCheckoutMethod(Quote $quote)
    {
        if ($this->customerSession->isLoggedIn()) {
            return Onepage::METHOD_CUSTOMER;
        }
        if (!$quote->getCheckoutMethod()) {
            if ($this->checkoutHelper->isAllowedGuestCheckout($quote)) {
                $quote->setCheckoutMethod(Onepage::METHOD_GUEST);
            } else {
                $quote->setCheckoutMethod(Onepage::METHOD_REGISTER);
            }
        }

        return $quote->getCheckoutMethod();
    }

    /**
     * Prepare quote for guest checkout order submit
     *
     * @param Quote $quote
     * @return void
     */
    public function prepareGuestQuote(Quote $quote)
    {
        $quote->setCustomerId(null);
        $quote->setCustomerEmail($quote->getBillingAddress()->getEmail());
        $quote->setCustomerIsGuest(true);
        $quote->setCustomerGroupId(Group::NOT_LOGGED_IN_ID);
    }

    /**
     * @param PaymentTokenInterface $paymentToken
     * @return \stdClass
     */
    public function buildDeleteTokenRequest(PaymentTokenInterface $paymentToken)
    {
        $tokenDetails = json_decode($paymentToken->getTokenDetails());

        $request = new \stdClass();
        $request->merchantID = $this->gatewayConfig->getMerchantId();
        $request->merchantReferenceCode = $tokenDetails->incrementId;

        $recurringSubscriptionInfo = new \stdClass();
        $recurringSubscriptionInfo->subscriptionID = $paymentToken->getGatewayToken();

        $paySubscriptionDeleteService = new \stdClass();
        $paySubscriptionDeleteService->run = 'true';

        $request->recurringSubscriptionInfo = $recurringSubscriptionInfo;
        $request->paySubscriptionDeleteService = $paySubscriptionDeleteService;

        return $request;
    }

    public function buildCreateTokenRequest()
    {

        if ($this->gatewayConfig->isTestMode()) {
            $configServiceUrl = $this->gatewayConfig->getSopServiceUrlTest();
        } else {
            $configServiceUrl = $this->gatewayConfig->getSopServiceUrl();
        }

        $quote = $this->checkoutSession->getQuote();

        $data = [];
        $data['access_key'] = $this->gatewayConfig->getSopAccessKey();
        $data['profile_id'] = $this->gatewayConfig->getSopProfileId();
        $data['transaction_uuid'] = uniqid();
        $data['unsigned_field_names'] = 'card_type,card_number,card_expiry_date,card_cvn';
        $data['signed_date_time'] = gmdate("Y-m-d\\TH:i:s\\Z");
        $data['locale'] = $this->getLocale();
        $data['transaction_type'] = (!empty($token)) ? 'update_payment_token' : 'create_payment_token';
        $data['reference_number'] = $quote->getReservedOrderId() ?? ('token_request_' . $quote->getId());
        $data['amount'] = '0';
        $data['currency'] = $quote->getCurrency()->getData('store_currency_code');
        $data['payment_method'] = 'card';
        $data['override_custom_receipt_page'] = $this->urlBuilder->getUrl('cybersource/index/createtoken');

        $data['bill_to_forename'] = $quote->getBillingAddress()->getFirstname();
        $data['bill_to_surname'] = $quote->getBillingAddress()->getLastname();
        $data['bill_to_email'] = $quote->getBillingAddress()->getEmail();
        $data['bill_to_phone'] = $quote->getBillingAddress()->getTelephone();
        $data['bill_to_address_country'] = $quote->getBillingAddress()->getCountryId();
        $data['bill_to_address_city'] = $quote->getBillingAddress()->getCity();
        $data['bill_to_address_state'] = $quote->getBillingAddress()->getRegionCode();
        $data['bill_to_address_line1'] = $quote->getBillingAddress()->getStreetLine(1);
        $data['bill_to_address_postal_code'] = $quote->getBillingAddress()->getPostcode();
        $data['skip_decision_manager'] = 'true';
        $data['skip_auto_auth'] = 'true';

        $data['signed_field_names'] = $this->getSignedFields($data);

        $data['signature'] = $this->sign($data, $this->gatewayConfig->getSopSecretKey());
        $data['action_url'] = $configServiceUrl . '/silent/embedded/token/create';

        return $data;
    }

    public function buildCreateTokenRequestSoap($cardType, $cardNumber, $cardExpiryDate)
    {
        $quote = $this->checkoutSession->getQuote();

        $request = new \stdClass();
        $request->merchantID = $this->gatewayConfig->getMerchantId();
        $request->merchantReferenceCode = $quote->getReservedOrderId();

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $quote->getCurrency()->getData('store_currency_code');

        $request->purchaseTotals = $purchaseTotals;

        $card = new \stdClass();
        $card->accountNumber = $cardNumber;
        $expireDate = explode("-", $cardExpiryDate ?? '');
        $card->expirationMonth = $expireDate[1];
        $card->expirationYear = $expireDate[0];
        $card->cardType = $cardType;

        $request->card = $card;

        $recurringSubscriptionInfo = new \stdClass();
        $recurringSubscriptionInfo->frequency = "on-demand";

        $request->recurringSubscriptionInfo = $recurringSubscriptionInfo;

        $subscription = new \stdClass();
        $subscription->paymentMethod = "credit card";

        $request->subscription = $subscription;

        $paySubscriptionCreateService = new \stdClass();
        $paySubscriptionCreateService->run = "true";

        $request->paySubscriptionCreateService = $paySubscriptionCreateService;

        $billTo = new \stdClass();
        $billTo->firstName = $quote->getBillingAddress()->getFirstname();
        $billTo->lastName = $quote->getBillingAddress()->getLastname();
        $billTo->email = $quote->getBillingAddress()->getEmail();
        $billTo->country = $quote->getBillingAddress()->getCountryId();
        $billTo->city = $quote->getBillingAddress()->getCity();
        $billTo->state = $quote->getBillingAddress()->getRegionCode();
        $billTo->street1 = $quote->getBillingAddress()->getStreetLine(1);
        $billTo->postalCode = $quote->getBillingAddress()->getPostcode();
        $billTo->phoneNumber = $quote->getBillingAddress()->getTelephone();

        $request->billTo = $billTo;

        return $request;
    }


    public function buildCaptureRequest(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $request = new \stdClass();
        $request->partnerSolutionID = self::PARTNER_SOLUTION_ID;
        $request->storeId = $payment->getOrder()->getStoreId();
        $request->merchantID = $this->gatewayConfig->getValue(
            Config::KEY_MERCHANT_ID,
            $payment->getOrder()->getStoreId()
        );
        $developerId = $this->gatewayConfig->getDeveloperId();
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $request->merchantReferenceCode = $payment->getOrder()->getIncrementId();

        $ccCaptureService = new \stdClass();
        $ccCaptureService->run = "true";
        $ccCaptureService->authRequestID = $payment->getParentTransactionId();

        $this->buildCaptureSequence($payment, $ccCaptureService, $amount);

        $invoice = $payment->getInvoice();
        if (!$invoice) {
            $invoice = $payment->getCreatedInvoice();
        }

        $invoicedItems = [];
        if ($invoice) {
            /** @var \Magento\Sales\Model\Order\Invoice\Item $invoiceItem */
            foreach ($invoice->getAllItems() as $invoiceItem) {
                if ($invoiceItem->getQty() >= 1) {
                    $invoicedItems[] = $invoiceItem;
                }
            }
        }
        $request = $this->buildSoapOrderItems($invoicedItems, $request);

        $request->ccCaptureService = $ccCaptureService;

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $payment->getOrder()->getBaseCurrencyCode();
        $purchaseTotals->grandTotalAmount = $this->formatAmount($amount);
        $request->purchaseTotals = $purchaseTotals;

        return $request;
    }

    public function buildRefundRequest(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $request = new \stdClass();
        $request->partnerSolutionID = self::PARTNER_SOLUTION_ID;
        $request->storeId = $payment->getOrder()->getStoreId();
        $request->merchantID = $this->gatewayConfig->getValue(
            Config::KEY_MERCHANT_ID,
            $payment->getOrder()->getStoreId()
        );
        $developerId = $this->gatewayConfig->getDeveloperId($payment->getOrder()->getStoreId());
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $request->merchantReferenceCode = $payment->getOrder()->getIncrementId();

        $ccCreditService = new \stdClass();
        $ccCreditService->run = "true";
        $ccCreditService->captureRequestID = $payment->getParentTransactionId() ? $payment->getParentTransactionId() : $payment->getRefundTransactionId();
        $request->ccCreditService = $ccCreditService;

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $payment->getOrder()->getBaseCurrencyCode();
        $purchaseTotals->grandTotalAmount = $this->formatAmount($amount);
        $request->purchaseTotals = $purchaseTotals;

        $creditmemo = $payment->getCreditmemo();

        $creditmemoItems = [];
        if ($creditmemo) {
            /** @var \Magento\Sales\Model\Order\Creditmemo\Item $creditmemoItem */
            foreach ($creditmemo->getAllItems() as $creditmemoItem) {
                if ($creditmemoItem->getQty() >= 1) {
                    $creditmemoItems[] = $creditmemoItem;
                }
            }
        }
        $request = $this->buildSoapOrderItems($creditmemoItems, $request);

        return $request;
    }

    public function buildCancelRequest(\Magento\Payment\Model\InfoInterface $payment)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        $request = new \stdClass();
        $request->partnerSolutionID = self::PARTNER_SOLUTION_ID;
        $request->storeId = $payment->getOrder()->getStoreId();
        $request->merchantID = $this->gatewayConfig->getValue(
            Config::KEY_MERCHANT_ID,
            $payment->getOrder()->getStoreId()
        );
        $developerId = $this->gatewayConfig->getDeveloperId($payment->getOrder()->getStoreId());
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $request->merchantReferenceCode = $order->getIncrementId();

        $ccAuthReversalService = new \stdClass();
        $ccAuthReversalService->run = "true";
        $ccAuthReversalService->authRequestID = $payment->getParentTransactionId();
        $request->ccAuthReversalService = $ccAuthReversalService;

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $order->getOrderBaseCurrencyCode();
        $purchaseTotals->grandTotalAmount = $this->formatAmount($order->getBaseGrandTotal());
        $request->purchaseTotals = $purchaseTotals;

        return $request;
    }

    public function buildVoidRequest(\Magento\Payment\Model\InfoInterface $payment)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        $request = new \stdClass();
        $request->partnerSolutionID = self::PARTNER_SOLUTION_ID;
        $request->storeId = $payment->getOrder()->getStoreId();
        $request->merchantID = $this->gatewayConfig->getValue(
            Config::KEY_MERCHANT_ID,
            $payment->getOrder()->getStoreId()
        );
        $developerId = $this->gatewayConfig->getDeveloperId();
        if (!empty($developerId) || $developerId !== null) {
            $request->developerId = $developerId;
        }
        $request->merchantReferenceCode = $order->getIncrementId();

        $voidService = new \stdClass();
        $voidService->run = "true";
        $voidService->voidRequestID = $payment->getParentTransactionId();
        $request->voidService = $voidService;

        $purchaseTotals = new \stdClass();
        $purchaseTotals->currency = $order->getOrderBaseCurrencyCode();
        $purchaseTotals->grandTotalAmount = $this->formatAmount($order->getBaseGrandTotal());
        $request->purchaseTotals = $purchaseTotals;

        return $request;
    }

    private function buildSoapOrderItems($items, \stdClass $request)
    {

        if (empty($items)) {
            return $request;
        }

        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($items as $i => $item) {
            $requestItem = new \stdClass();
            $requestItem->id = $i;
            $requestItem->productName = $item->getName();
            $requestItem->productSKU = $item->getSku();
            $requestItem->quantity = (int)$item->getQty();
            $requestItem->productCode = 'default';
            $requestItem->unitPrice = $this->formatAmount($item->getBasePrice());
            $requestItem->taxAmount = $this->formatAmount($item->getBaseTaxAmount());
            $request->item[] = $requestItem;
        }

        foreach ($request->item as $key => $item) {
            if ($item->unitPrice == 0) {
                unset($request->item[$key]);
            }
        }

        $request->item = array_values($request->item);

        return $request;
    }

    /**
     * Clears all empty values and converts to strings
     *
     * @param array $params
     * @return array
     */
    public function filterParams($params)
    {
        return array_map(
            function ($value) {
                return (string)$value;
            },
            array_filter(
                $params,
                function ($value) {
                    return !is_null($value) && $value !== '';
                }
            )
        );
    }

    public function getLocale()
    {
        return str_replace('_', '-', strtolower($this->locale ?? ''));
    }

    public function getCartItemsDiscount()
    {
        // get discount for all items
        $cartItemDiscount = [];
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cart = $objectManager->get('\Magento\Checkout\Model\Cart');
        $items = $cart->getQuote()->getAllItems();
        if (!empty($items)) {
            foreach ($items as $item) {
                $cartItemDiscount[$item->getSku()] = $item->getDiscountAmount();
            }
        }
        return $cartItemDiscount;
    }
}
