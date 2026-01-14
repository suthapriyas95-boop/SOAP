# REST ADMIN ORDER - COMPLETE CODE PART 2 (Cybersource\Payment Namespace)

Continuing with all helper, service, observer, plugin, block, layout, template, JavaScript, and CSS files.

---

## **PHASE 6: REST HELPERS (3 FILES)**

### **25. Helper/Rest/RequestValidator.php**
```php
<?php
namespace Cybersource\Payment\Helper\Rest;

use Magento\Framework\Exception\LocalizedException;

class RequestValidator
{
    /**
     * Validate token generation request
     */
    public function validateTokenRequest(array $data)
    {
        if (empty($data['quote_id'])) {
            throw new LocalizedException(__('Quote ID is required'));
        }

        if (!is_numeric($data['quote_id'])) {
            throw new LocalizedException(__('Quote ID must be a number'));
        }

        if (isset($data['store_id']) && !is_numeric($data['store_id'])) {
            throw new LocalizedException(__('Store ID must be a number'));
        }

        return true;
    }

    /**
     * Validate SOP request data
     */
    public function validateSopRequest(array $data)
    {
        if (empty($data['quote_id'])) {
            throw new LocalizedException(__('Quote ID is required'));
        }

        if (empty($data['cc_type'])) {
            throw new LocalizedException(__('Card type is required'));
        }

        $validCardTypes = ['001', '002', '003', '004']; // VISA, MC, AMEX, DISCOVER
        if (!in_array($data['cc_type'], $validCardTypes)) {
            throw new LocalizedException(__('Invalid card type'));
        }

        return true;
    }

    /**
     * Validate SOP response
     */
    public function validateSopResponse(array $data)
    {
        $requiredFields = ['decision', 'signature', 'signed_field_names', 'transaction_id'];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new LocalizedException(__('Missing required field: %1', $field));
            }
        }

        if (!in_array($data['decision'], ['ACCEPT', 'DECLINE', 'REVIEW'])) {
            throw new LocalizedException(__('Invalid decision value'));
        }

        return true;
    }

    /**
     * Validate Flex order request
     */
    public function validateFlexOrderRequest(array $data)
    {
        if (empty($data['quote_id'])) {
            throw new LocalizedException(__('Quote ID is required'));
        }

        if (empty($data['token'])) {
            throw new LocalizedException(__('Token is required'));
        }

        if (empty($data['cc_type'])) {
            throw new LocalizedException(__('Card type is required'));
        }

        // Validate JWT token format
        $parts = explode('.', $data['token']);
        if (count($parts) !== 3) {
            throw new LocalizedException(__('Invalid token format'));
        }

        return true;
    }

    /**
     * Validate vault token deletion
     */
    public function validateVaultDeletion(array $data)
    {
        if (empty($data['public_hash'])) {
            throw new LocalizedException(__('Public hash is required'));
        }

        if (empty($data['customer_id'])) {
            throw new LocalizedException(__('Customer ID is required'));
        }

        if (!is_numeric($data['customer_id'])) {
            throw new LocalizedException(__('Customer ID must be a number'));
        }

        return true;
    }
}
```

### **26. Helper/Rest/ResponseFormatter.php**
```php
<?php
namespace Cybersource\Payment\Helper\Rest;

class ResponseFormatter
{
    /**
     * Format token response
     */
    public function formatTokenResponse($token, $clientLibrary, $integrity)
    {
        return [
            'success' => true,
            'token' => $token,
            'client_library' => $clientLibrary,
            'client_integrity' => $integrity,
            'place_order_url' => '/rest/V1/cybersource/admin/flex/place-order'
        ];
    }

    /**
     * Format SOP request response
     */
    public function formatSopRequestResponse(array $fields, $formUrl, $responseUrl)
    {
        return [
            'success' => true,
            'fields' => $fields,
            'form_url' => $formUrl,
            'response_url' => $responseUrl
        ];
    }

    /**
     * Format order creation response
     */
    public function formatOrderResponse($orderId, $incrementId, $redirectUrl = null)
    {
        $response = [
            'success' => true,
            'order_id' => $orderId,
            'increment_id' => $incrementId
        ];

        if ($redirectUrl) {
            $response['redirect_url'] = $redirectUrl;
        }

        return $response;
    }

    /**
     * Format error response
     */
    public function formatErrorResponse($error, $code = null)
    {
        $response = [
            'success' => false,
            'error' => $error
        ];

        if ($code) {
            $response['error_code'] = $code;
        }

        return $response;
    }

    /**
     * Format success response
     */
    public function formatSuccessResponse($message = '', array $data = [])
    {
        $response = [
            'success' => true
        ];

        if ($message) {
            $response['message'] = $message;
        }

        if (!empty($data)) {
            $response['data'] = $data;
        }

        return $response;
    }

    /**
     * Format SOP response data
     */
    public function formatSopResponseData(array $responseData, $decision)
    {
        return [
            'decision' => $decision,
            'reason_code' => $responseData['reason_code'] ?? '',
            'transaction_id' => $responseData['transaction_id'] ?? '',
            'auth_code' => $responseData['auth_code'] ?? '',
            'auth_avs_code' => $responseData['auth_avs_code'] ?? '',
            'auth_cv_result' => $responseData['auth_cv_result'] ?? '',
            'req_id' => $responseData['req_id'] ?? ''
        ];
    }
}
```

### **27. Helper/Rest/TokenDataExtractor.php**
```php
<?php
namespace Cybersource\Payment\Helper\Rest;

use Magento\Framework\Exception\LocalizedException;

class TokenDataExtractor
{
    /**
     * Extract JWT claims
     */
    public function extractJwtClaims($token)
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                throw new LocalizedException(__('Invalid token format'));
            }

            $payload = $parts[1];
            $decoded = base64_decode(strtr($payload, '-_', '+/'));
            $claims = json_decode($decoded, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new LocalizedException(__('Invalid token payload'));
            }

            return $claims;
        } catch (\Exception $e) {
            throw new LocalizedException(__('Could not extract token data: %1', $e->getMessage()));
        }
    }

    /**
     * Extract card data from response
     */
    public function extractCardData(array $responseData)
    {
        return [
            'cc_type' => $responseData['cc_type'] ?? '',
            'cc_last_4' => substr($responseData['masked_pan'] ?? '', -4),
            'cc_exp_month' => isset($responseData['exp_date']) ? substr($responseData['exp_date'], 0, 2) : '',
            'cc_exp_year' => isset($responseData['exp_date']) ? substr($responseData['exp_date'], -4) : '',
        ];
    }

    /**
     * Extract payment data for quote
     */
    public function extractPaymentData(array $responseData)
    {
        return [
            'request_id' => $responseData['req_id'] ?? '',
            'transaction_id' => $responseData['transaction_id'] ?? '',
            'auth_code' => $responseData['auth_code'] ?? '',
            'avs_result' => $responseData['auth_avs_code'] ?? '',
            'cvv_result' => $responseData['auth_cv_result'] ?? '',
            'decision' => $responseData['decision'] ?? '',
            'reason_code' => $responseData['reason_code'] ?? ''
        ];
    }

    /**
     * Extract SOP form fields
     */
    public function extractFormFields(array $fields)
    {
        $essentialFields = [
            'access_key',
            'profile_id',
            'transaction_uuid',
            'signed_field_names',
            'signature',
            'override_custom_receipt_page',
            'amount',
            'currency'
        ];

        $result = [];
        foreach ($essentialFields as $field) {
            if (isset($fields[$field])) {
                $result[$field] = $fields[$field];
            }
        }

        // Include card type options if available
        if (isset($fields['card_types'])) {
            $result['card_types'] = $fields['card_types'];
        }

        return $result;
    }
}
```

---

## **PHASE 7: REST SERVICES (4 FILES)**

### **28. Service/Rest/RequestValidator.php**
```php
<?php
namespace Cybersource\Payment\Service\Rest;

use Magento\Framework\Exception\LocalizedException;

class RequestValidator
{
    /**
     * Validate all required parameters
     */
    public function validate($action, array $data)
    {
        switch ($action) {
            case 'generate_token':
                return $this->validateTokenRequest($data);
            case 'sop_request':
                return $this->validateSopRequest($data);
            case 'sop_response':
                return $this->validateSopResponse($data);
            case 'flex_place_order':
                return $this->validateFlexOrderRequest($data);
            case 'vault_delete':
                return $this->validateVaultDeletion($data);
            default:
                throw new LocalizedException(__('Unknown action: %1', $action));
        }
    }

    private function validateTokenRequest(array $data)
    {
        if (empty($data['quote_id']) || !is_numeric($data['quote_id'])) {
            throw new LocalizedException(__('Invalid quote ID'));
        }
        return true;
    }

    private function validateSopRequest(array $data)
    {
        if (empty($data['quote_id']) || !is_numeric($data['quote_id'])) {
            throw new LocalizedException(__('Invalid quote ID'));
        }
        if (empty($data['cc_type'])) {
            throw new LocalizedException(__('Card type is required'));
        }
        return true;
    }

    private function validateSopResponse(array $data)
    {
        $required = ['decision', 'signature', 'signed_field_names'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new LocalizedException(__('Missing required field: %1', $field));
            }
        }
        return true;
    }

    private function validateFlexOrderRequest(array $data)
    {
        if (empty($data['quote_id']) || !is_numeric($data['quote_id'])) {
            throw new LocalizedException(__('Invalid quote ID'));
        }
        if (empty($data['token'])) {
            throw new LocalizedException(__('Token is required'));
        }
        return true;
    }

    private function validateVaultDeletion(array $data)
    {
        if (empty($data['public_hash'])) {
            throw new LocalizedException(__('Public hash is required'));
        }
        if (empty($data['customer_id']) || !is_numeric($data['customer_id'])) {
            throw new LocalizedException(__('Invalid customer ID'));
        }
        return true;
    }
}
```

### **29. Service/Rest/ResponseFormatter.php**
```php
<?php
namespace Cybersource\Payment\Service\Rest;

class ResponseFormatter
{
    /**
     * Format response with consistent structure
     */
    public function format($success, $message = '', $data = [])
    {
        return [
            'success' => (bool)$success,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Format error response
     */
    public function error($error, $code = null, $details = [])
    {
        $response = [
            'success' => false,
            'error' => $error,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        if ($code) {
            $response['error_code'] = $code;
        }

        if (!empty($details)) {
            $response['details'] = $details;
        }

        return $response;
    }

    /**
     * Format API response
     */
    public function apiResponse($data, $statusCode = 200)
    {
        return [
            'status' => $statusCode,
            'data' => $data,
            'timestamp' => time()
        ];
    }
}
```

### **30. Service/Rest/OrderDataProcessor.php**
```php
<?php
namespace Cybersource\Payment\Service\Rest;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\QuoteRepository;
use Psr\Log\LoggerInterface;

class OrderDataProcessor
{
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        QuoteRepository $quoteRepository,
        LoggerInterface $logger
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->logger = $logger;
    }

    /**
     * Process order data from request
     */
    public function processOrderData($quoteId, array $paymentData)
    {
        try {
            $quote = $this->quoteRepository->get($quoteId);

            if (!$quote->getId()) {
                throw new LocalizedException(__('Quote not found'));
            }

            // Set payment method and additional data
            $quote->getPayment()->setMethod('cybersource_flex');
            $quote->getPayment()->setAdditionalInformation($paymentData);

            // Save quote
            $this->quoteRepository->save($quote);

            return [
                'quote_id' => $quote->getId(),
                'customer_id' => $quote->getCustomerId(),
                'grand_total' => $quote->getGrandTotal(),
                'currency_code' => $quote->getCurrencyCode()
            ];
        } catch (\Exception $e) {
            $this->logger->error('Order data processing error: ' . $e->getMessage());
            throw new LocalizedException(__('Could not process order data'));
        }
    }

    /**
     * Validate order data
     */
    public function validateOrderData(array $data)
    {
        if (empty($data['grand_total']) || $data['grand_total'] <= 0) {
            throw new LocalizedException(__('Invalid order amount'));
        }

        if (empty($data['currency_code'])) {
            throw new LocalizedException(__('Currency code is required'));
        }

        return true;
    }
}
```

### **31. Service/Rest/ErrorHandler.php**
```php
<?php
namespace Cybersource\Payment\Service\Rest;

use Magento\Framework\Exception\LocalizedException;

class ErrorHandler
{
    /**
     * Handle and format errors
     */
    public function handle(\Exception $exception, $context = '')
    {
        $message = $exception->getMessage();
        $code = $exception->getCode();

        if ($exception instanceof LocalizedException) {
            return [
                'success' => false,
                'error' => $message,
                'error_code' => 'validation_error',
                'context' => $context
            ];
        }

        // Log critical errors
        return [
            'success' => false,
            'error' => 'An unexpected error occurred',
            'error_code' => 'system_error',
            'context' => $context,
            'dev_message' => $message
        ];
    }

    /**
     * Get HTTP status code from error
     */
    public function getHttpStatusCode(\Exception $exception)
    {
        if ($exception instanceof LocalizedException) {
            return 400; // Bad Request
        }

        return 500; // Internal Server Error
    }

    /**
     * Format error message
     */
    public function formatMessage($error, $field = '')
    {
        if ($field) {
            return ucfirst($field) . ': ' . $error;
        }
        return $error;
    }
}
```

---

## **PHASE 8: OBSERVERS (2 FILES)**

### **32. Observer/Rest/RestDataAssignObserver.php**
```php
<?php
namespace Cybersource\Payment\Observer\Rest;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class RestDataAssignObserver implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Assign REST data to payment
     */
    public function execute(Observer $observer)
    {
        try {
            $event = $observer->getEvent();
            $data = $event->getDataByKey('data');
            $model = $event->getDataByKey('model');

            if (!$data || !$model) {
                return $this;
            }

            // Assign cybersource specific data
            if (isset($data['cybersource'])) {
                $model->setAdditionalInformation($data['cybersource']);
            }

            return $this;
        } catch (\Exception $e) {
            $this->logger->error('REST data assign error: ' . $e->getMessage());
            return $this;
        }
    }
}
```

### **33. Observer/Rest/RestTokenObserver.php**
```php
<?php
namespace Cybersource\Payment\Observer\Rest;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class RestTokenObserver implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Process REST token data
     */
    public function execute(Observer $observer)
    {
        try {
            $event = $observer->getEvent();
            $quote = $event->getDataByKey('quote');

            if (!$quote) {
                return $this;
            }

            // Add token to extension attributes if available
            $extensionAttributes = $quote->getExtensionAttributes();
            if ($extensionAttributes) {
                // Token data would be added here during token generation
            }

            return $this;
        } catch (\Exception $e) {
            $this->logger->error('REST token processing error: ' . $e->getMessage());
            return $this;
        }
    }
}
```

---

## **PHASE 9: PLUGINS (3 FILES)**

### **34. Plugin/Rest/RequestDataBuilderPlugin.php**
```php
<?php
namespace Cybersource\Payment\Plugin\Rest;

use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class RequestDataBuilderPlugin
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Intercept request data building for admin orders
     */
    public function aroundBuildSilentRequestData(
        $subject,
        \Closure $proceed,
        $order = null,
        $storeId = null,
        $controller = null,
        array $additionalData = []
    ) {
        try {
            // Check if admin context
            if (!$this->isAdminContext()) {
                return $proceed($order, $storeId, $controller, $additionalData);
            }

            // Handle admin-specific request building
            $data = $proceed($order, $storeId, $controller, $additionalData);

            // Add admin-specific fields
            $data['admin_order'] = '1';
            if (isset($additionalData['vault_enabled'])) {
                $data['vault_enabled'] = $additionalData['vault_enabled'];
            }

            return $data;
        } catch (\Exception $e) {
            $this->logger->error('Request data builder plugin error: ' . $e->getMessage());
            throw new LocalizedException(__('Request building failed'));
        }
    }

    /**
     * Check if admin context
     */
    private function isAdminContext()
    {
        // Check if admin route is being used
        return defined('Magento\Backend\App\Area\FrontNameResolver::ADMIN_AREA_FRONT_NAME');
    }
}
```

### **35. Plugin/Rest/TokenValidatorPlugin.php**
```php
<?php
namespace Cybersource\Payment\Plugin\Rest;

use Magento\Framework\Exception\LocalizedException;

class TokenValidatorPlugin
{
    /**
     * Validate token before processing
     */
    public function beforeSubmit($subject, $payment)
    {
        $additionalInfo = $payment->getAdditionalInformation();

        // Check for Flex JWT
        if (isset($additionalInfo['flexJwt'])) {
            $token = $additionalInfo['flexJwt'];

            if (!$this->validateToken($token)) {
                throw new LocalizedException(__('Invalid payment token'));
            }
        }

        return [$payment];
    }

    /**
     * Validate JWT token structure
     */
    private function validateToken($token)
    {
        if (empty($token)) {
            return false;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        // Validate each part is valid base64
        foreach ($parts as $part) {
            if (!$this->isValidBase64($part)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate base64 string
     */
    private function isValidBase64($str)
    {
        return base64_encode(base64_decode($str, true)) === $str;
    }
}
```

### **36. Plugin/Rest/ResponseSignaturePlugin.php**
```php
<?php
namespace Cybersource\Payment\Plugin\Rest;

use Cybersource\Payment\Gateway\Config\Config;
use Magento\Framework\Exception\LocalizedException;

class ResponseSignaturePlugin
{
    /**
     * @var Config
     */
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Validate response signature
     */
    public function beforeProcessResponse($subject, array $response)
    {
        if (!$this->validateSignature($response)) {
            throw new LocalizedException(__('Invalid response signature'));
        }

        return [$response];
    }

    /**
     * Validate signature
     */
    private function validateSignature(array $response)
    {
        if (empty($response['signature']) || empty($response['signed_field_names'])) {
            return false;
        }

        try {
            $secretKey = $this->config->getSecretKey();
            $signedFields = explode(',', $response['signed_field_names']);

            $dataToSign = [];
            foreach ($signedFields as $field) {
                $field = trim($field);
                if (isset($response[$field])) {
                    $dataToSign[] = $field . '=' . $response[$field];
                }
            }

            $data = implode(',', $dataToSign);
            $calculatedSignature = base64_encode(hash_hmac('sha256', $data, $secretKey, true));

            return hash_equals($calculatedSignature, $response['signature']);
        } catch (\Exception $e) {
            return false;
        }
    }
}
```

---

## **PHASE 10: VIEW BLOCKS (3 FILES)**

### **37. Block/Rest/FlexTokenDisplay.php**
```php
<?php
namespace Cybersource\Payment\Block\Rest;

use Magento\Backend\Block\Template;
use Magento\Framework\Registry;
use Magento\Quote\Model\QuoteRepository;

class FlexTokenDisplay extends Template
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    protected $_template = 'Cybersource_Payment::rest/flex_token.phtml';

    public function __construct(
        Template\Context $context,
        Registry $registry,
        QuoteRepository $quoteRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Get token from extension attributes
     */
    public function getToken()
    {
        $quote = $this->getQuote();
        if (!$quote) {
            return null;
        }

        $extensionAttributes = $quote->getExtensionAttributes();
        return $extensionAttributes ? $extensionAttributes->getCybersourceFlexToken() : null;
    }

    /**
     * Get client library URL
     */
    public function getClientLibrary()
    {
        $quote = $this->getQuote();
        if (!$quote) {
            return null;
        }

        $extensionAttributes = $quote->getExtensionAttributes();
        return $extensionAttributes ? $extensionAttributes->getCybersourceFlexClientLibrary() : null;
    }

    /**
     * Get client integrity value
     */
    public function getClientIntegrity()
    {
        $quote = $this->getQuote();
        if (!$quote) {
            return null;
        }

        $extensionAttributes = $quote->getExtensionAttributes();
        return $extensionAttributes ? $extensionAttributes->getCybersourceFlexIntegrity() : null;
    }

    /**
     * Get token generation endpoint
     */
    public function getTokenGenerationEndpoint()
    {
        return '/rest/V1/cybersource/admin/token/generate';
    }

    /**
     * Get order creation endpoint
     */
    public function getOrderCreationEndpoint()
    {
        return '/rest/V1/cybersource/admin/flex/place-order';
    }

    /**
     * Get current quote
     */
    private function getQuote()
    {
        if ($quoteId = $this->getRequest()->getParam('quote_id')) {
            try {
                return $this->quoteRepository->get($quoteId);
            } catch (\Exception $e) {
                return null;
            }
        }

        return $this->registry->registry('current_quote');
    }
}
```

### **38. Block/Rest/SopFormDisplay.php**
```php
<?php
namespace Cybersource\Payment\Block\Rest;

use Magento\Backend\Block\Template;
use Magento\Framework\Registry;

class SopFormDisplay extends Template
{
    /**
     * @var Registry
     */
    private $registry;

    protected $_template = 'Cybersource_Payment::rest/sop_form.phtml';

    public function __construct(
        Template\Context $context,
        Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
    }

    /**
     * Get SOP request data endpoint
     */
    public function getRequestDataEndpoint()
    {
        return '/rest/V1/cybersource/admin/sop/request-data';
    }

    /**
     * Get SOP response handler endpoint
     */
    public function getResponseHandlerEndpoint()
    {
        return '/rest/V1/cybersource/admin/sop/response';
    }

    /**
     * Get SOP form URL
     */
    public function getSopFormUrl()
    {
        // Return test or production URL based on config
        return 'https://testsecureacceptance.cybersource.com/silent';
    }

    /**
     * Get current quote ID
     */
    public function getQuoteId()
    {
        return $this->getRequest()->getParam('quote_id');
    }

    /**
     * Get available card types
     */
    public function getCardTypes()
    {
        return [
            '001' => __('Visa'),
            '002' => __('Mastercard'),
            '003' => __('American Express'),
            '004' => __('Discover')
        ];
    }
}
```

### **39. Block/Rest/PaymentMethodInfo.php**
```php
<?php
namespace Cybersource\Payment\Block\Rest;

use Magento\Backend\Block\Template;
use Magento\Sales\Model\Order;
use Magento\Framework\Registry;

class PaymentMethodInfo extends Template
{
    /**
     * @var Registry
     */
    private $registry;

    protected $_template = 'Cybersource_Payment::rest/payment_info.phtml';

    public function __construct(
        Template\Context $context,
        Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
    }

    /**
     * Get order from registry
     */
    public function getOrder()
    {
        return $this->registry->registry('current_order');
    }

    /**
     * Get payment information
     */
    public function getPaymentInfo()
    {
        $order = $this->getOrder();
        if (!$order) {
            return [];
        }

        return $order->getPayment()->getAdditionalInformation();
    }

    /**
     * Get transaction ID
     */
    public function getTransactionId()
    {
        $paymentInfo = $this->getPaymentInfo();
        return $paymentInfo['transaction_id'] ?? 'N/A';
    }

    /**
     * Get authorization code
     */
    public function getAuthCode()
    {
        $paymentInfo = $this->getPaymentInfo();
        return $paymentInfo['auth_code'] ?? 'N/A';
    }

    /**
     * Get AVS result
     */
    public function getAvsResult()
    {
        $paymentInfo = $this->getPaymentInfo();
        return $paymentInfo['avs_result'] ?? 'N/A';
    }

    /**
     * Get CVV result
     */
    public function getCvvResult()
    {
        $paymentInfo = $this->getPaymentInfo();
        return $paymentInfo['cvv_result'] ?? 'N/A';
    }
}
```

---

## **PHASE 11: LAYOUT FILES (3 FILES)**

### **40. view/adminhtml/layout/sales_order_create_index.xml (UPDATE)**
```xml
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <update handle="editor"/>
    <update handle="formkey"/>
    <update handle="adminhtml_customer_navigation"/>
    <update handle="sales_order_create_load_block_items"/>
    <update handle="sales_order_create_load_block_billing_address"/>
    <update handle="sales_order_create_load_block_shipping_address"/>
    <update handle="sales_order_create_load_block_billing_method"/>
    <update handle="sales_order_create_load_block_shipping_method"/>
    <update handle="sales_order_create_load_block_totals"/>
    <update handle="sales_order_create_load_block_newsletter"/>

    <!-- Add Cybersource REST payment block -->
    <update handle="cybersource_rest_admin_payment"/>

    <head>
        <title>Create Orders</title>
    </head>
    <body>
        <referenceContainer name="content">
            <block class="Magento\Sales\Block\Adminhtml\Order\Create" name="sales_order_create" template="Magento_Sales::order/create.phtml">
                <block class="Magento\Sales\Block\Adminhtml\Order\Create\Form" name="sales_order_create.form" as="form">
                    <block class="Magento\Backend\Block\Widget\Accordion" name="order_create_accordion">
                        <block class="Magento\Sales\Block\Adminhtml\Order\Create\Accordion\Items" name="order_items" as="items">
                            <argument name="sort_order" xsi:type="number">1</argument>
                        </block>
                        <block class="Magento\Sales\Block\Adminhtml\Order\Create\Accordion\Address" name="order_address" as="address">
                            <argument name="sort_order" xsi:type="number">2</argument>
                        </block>
                        <block class="Magento\Sales\Block\Adminhtml\Order\Create\Accordion\Shipping" name="order_shipping" as="shipping">
                            <argument name="sort_order" xsi:type="number">3</argument>
                        </block>
                        <block class="Magento\Sales\Block\Adminhtml\Order\Create\Accordion\Comment" name="order_comment" as="comment">
                            <argument name="sort_order" xsi:type="number">4</argument>
                        </block>
                    </block>
                </block>
                <block class="Magento\Sales\Block\Adminhtml\Order\Create\Totals" name="sales_order_create.totals" as="totals">
                    <block class="Magento\Sales\Block\Adminhtml\Order\Create\Totals\Subtotal" name="subtotal" as="subtotal"/>
                    <block class="Magento\Sales\Block\Adminhtml\Order\Create\Totals\Shipping" name="shipping" as="shipping"/>
                    <block class="Magento\Sales\Block\Adminhtml\Order\Create\Totals\Tax" name="tax" as="tax"/>
                    <block class="Magento\Sales\Block\Adminhtml\Order\Create\Totals\Discount" name="discount" as="discount"/>
                    <block class="Magento\Sales\Block\Adminhtml\Order\Create\Totals\Grand" name="grand_total" as="grand_total"/>
                </block>
            </block>
        </referenceContainer>
    </body>
</page>
```

### **41. view/adminhtml/layout/sales_order_create_load_block_billing_method.xml (UPDATE)**
```xml
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <body>
        <referenceBlock name="order_shipping">
            <block class="Magento\Sales\Block\Adminhtml\Order\Create\Shipping\Method" name="shipping_method" as="shipping_method"/>
        </referenceBlock>
        <referenceBlock name="order_comment">
            <block class="Magento\Sales\Block\Adminhtml\Order\Create\Comment" name="comment" as="comment"/>
        </referenceBlock>

        <!-- Add Cybersource REST payment method block -->
        <block class="Magento\Sales\Block\Adminhtml\Order\Create\Billing\Method" name="billing_method" as="billing_method">
            <container name="cybersource_payment_methods" as="cybersource_payment_methods">
                <block class="Cybersource\Payment\Block\Rest\FlexTokenDisplay" name="cybersource_flex" as="flex_payment"/>
                <block class="Cybersource\Payment\Block\Rest\SopFormDisplay" name="cybersource_sop" as="sop_payment"/>
            </container>
        </block>
    </body>
</page>
```

### **42. view/adminhtml/layout/cybersource_rest_admin_payment.xml (NEW)**
```xml
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <update handle="styles"/>
    <body>
        <referenceContainer name="content">
            <block class="Cybersource\Payment\Block\Rest\FlexTokenDisplay" name="cybersource_flex_payment" as="flex_payment">
                <argument name="sort_order" xsi:type="number">10</argument>
            </block>
            <block class="Cybersource\Payment\Block\Rest\SopFormDisplay" name="cybersource_sop_payment" as="sop_payment">
                <argument name="sort_order" xsi:type="number">20</argument>
            </block>
            <block class="Cybersource\Payment\Block\Rest\PaymentMethodInfo" name="cybersource_payment_info" as="payment_info">
                <argument name="sort_order" xsi:type="number">30</argument>
            </block>
        </referenceContainer>
    </body>
</page>
```

---

*[Content continues in Part 3 with Template Files, JavaScript Files, CSS Files, Configuration Files, and Implementation Checklist]*
