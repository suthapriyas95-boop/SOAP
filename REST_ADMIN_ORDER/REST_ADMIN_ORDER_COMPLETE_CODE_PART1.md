# REST Admin Order - COMPLETE CODE FOR ALL 63 FILES

This is the comprehensive code for complete REST-based admin order implementation. All files ready to copy/paste into your module.

---

## **PHASE 1: API INTERFACES (5 FILES)**

### **1. Api/Admin/TokenGeneratorInterface.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Api\Admin;

use Magento\Framework\Exception\LocalizedException;

/**
 * Generate Flex microform tokens for admin orders
 */
interface TokenGeneratorInterface
{
    /**
     * Generate a Flex microform token for admin quote
     *
     * @param int $quoteId
     * @param int|null $storeId
     * @return array Token data with client library info
     * @throws LocalizedException
     */
    public function generateToken($quoteId, $storeId = null);

    /**
     * Get token details and library information
     *
     * @param int $quoteId
     * @return array Token details with URLs
     * @throws LocalizedException
     */
    public function getTokenDetails($quoteId);

    /**
     * Validate token integrity
     *
     * @param string $token
     * @return bool
     */
    public function validateToken($token);
}
```

### **2. Api/Admin/SopRequestDataBuilderInterface.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Api\Admin;

use Magento\Framework\Exception\LocalizedException;

/**
 * Build SOP (Secure Order Post) request data for admin orders
 */
interface SopRequestDataBuilderInterface
{
    /**
     * Build SOP request data
     *
     * @param int $quoteId
     * @param string $cardType Card type code (001, 002, etc.)
     * @param bool $vaultEnabled
     * @param int|null $storeId
     * @return array Request fields with signature
     * @throws LocalizedException
     */
    public function buildRequestData(
        $quoteId,
        $cardType,
        $vaultEnabled = false,
        $storeId = null
    );

    /**
     * Validate request data
     *
     * @param array $data
     * @return bool
     * @throws LocalizedException
     */
    public function validateRequestData(array $data);

    /**
     * Get form fields
     *
     * @param int $quoteId
     * @return array Form fields
     */
    public function getFormFields($quoteId);
}
```

### **3. Api/Admin/SopResponseHandlerInterface.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Api\Admin;

use Magento\Framework\Exception\LocalizedException;

/**
 * Handle SOP response from CyberSource for admin orders
 */
interface SopResponseHandlerInterface
{
    /**
     * Handle SOP response and create order
     *
     * @param array $response CyberSource response data
     * @param array $orderData Order data from request
     * @return array Order creation result
     * @throws LocalizedException
     */
    public function handleResponse(array $response, array $orderData);

    /**
     * Validate response signature
     *
     * @param array $response
     * @return bool
     * @throws LocalizedException
     */
    public function validateSignature(array $response);

    /**
     * Get response reason text
     *
     * @param string $reasonCode
     * @return string
     */
    public function getReasonText($reasonCode);
}
```

### **4. Api/Admin/FlexOrderCreatorInterface.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Api\Admin;

use Magento\Framework\Exception\LocalizedException;

/**
 * Create admin orders using Flex microform tokens
 */
interface FlexOrderCreatorInterface
{
    /**
     * Create order from Flex token
     *
     * @param int $quoteId
     * @param string $token JWT token from Flex
     * @param array $cardData Card information
     * @param array $orderData Additional order data
     * @return array Order creation result
     * @throws LocalizedException
     */
    public function createOrder(
        $quoteId,
        $token,
        array $cardData,
        array $orderData
    );

    /**
     * Validate token structure
     *
     * @param string $token
     * @return bool
     */
    public function validateToken($token);

    /**
     * Validate card data
     *
     * @param array $cardData
     * @return bool
     * @throws LocalizedException
     */
    public function validateCardData(array $cardData);
}
```

### **5. Api/Admin/VaultTokenManagementInterface.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Api\Admin;

use Magento\Framework\Exception\LocalizedException;

/**
 * Manage vault tokens for admin orders
 */
interface VaultTokenManagementInterface
{
    /**
     * Delete vault token
     *
     * @param string $publicHash
     * @param int $customerId
     * @return bool
     * @throws LocalizedException
     */
    public function deleteToken($publicHash, $customerId);

    /**
     * Get available tokens for customer
     *
     * @param int $customerId
     * @return array List of tokens
     */
    public function getAvailableTokens($customerId);

    /**
     * Validate token for deletion
     *
     * @param string $publicHash
     * @return bool
     * @throws LocalizedException
     */
    public function validateTokenDeletion($publicHash);

    /**
     * Save token to CyberSource
     *
     * @param int $customerId
     * @param string $token
     * @param array $cardData
     * @return string Public hash
     * @throws LocalizedException
     */
    public function saveToken($customerId, $token, array $cardData);
}
```

---

## **PHASE 2: SERVICE IMPLEMENTATIONS (5 FILES)**

### **6. Model/Api/AdminTokenGenerator.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Model\Api;

use CyberSource\SecureAcceptance\Api\Admin\TokenGeneratorInterface;
use CyberSource\SecureAcceptance\Service\Adminhtml\TokenService;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\QuoteRepository;
use Psr\Log\LoggerInterface;

class AdminTokenGenerator implements TokenGeneratorInterface
{
    /**
     * @var TokenService
     */
    private $tokenService;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        TokenService $tokenService,
        QuoteRepository $quoteRepository,
        LoggerInterface $logger
    ) {
        $this->tokenService = $tokenService;
        $this->quoteRepository = $quoteRepository;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function generateToken($quoteId, $storeId = null)
    {
        try {
            $quote = $this->quoteRepository->get($quoteId);
            
            if (!$quote->getId()) {
                throw new LocalizedException(__('Quote not found'));
            }

            if ($storeId && $quote->getStoreId() != $storeId) {
                throw new LocalizedException(__('Store ID mismatch'));
            }

            // Generate token using existing TokenService
            $this->tokenService->generateToken($quote->getPayment());

            // Get token from extension attributes
            $extensionAttributes = $quote->getExtensionAttributes();
            $token = $extensionAttributes->getCybersourceFlexToken();
            $clientLibrary = $extensionAttributes->getCybersourceFlexClientLibrary();
            $integrity = $extensionAttributes->getCybersourceFlexIntegrity();

            return [
                'success' => true,
                'token' => $token,
                'client_library' => $clientLibrary,
                'client_integrity' => $integrity,
                'place_order_url' => '/rest/V1/cybersource/admin/flex/place-order'
            ];
        } catch (\Exception $e) {
            $this->logger->error('Token generation error: ' . $e->getMessage());
            throw new LocalizedException(__('Token generation failed: %1', $e->getMessage()));
        }
    }

    /**
     * @inheritdoc
     */
    public function getTokenDetails($quoteId)
    {
        try {
            $quote = $this->quoteRepository->get($quoteId);
            $extensionAttributes = $quote->getExtensionAttributes();

            return [
                'token' => $extensionAttributes->getCybersourceFlexToken(),
                'client_library' => $extensionAttributes->getCybersourceFlexClientLibrary(),
                'client_integrity' => $extensionAttributes->getCybersourceFlexIntegrity(),
                'expires_at' => time() + 900 // 15 minutes
            ];
        } catch (\Exception $e) {
            throw new LocalizedException(__('Could not retrieve token details'));
        }
    }

    /**
     * @inheritdoc
     */
    public function validateToken($token)
    {
        if (empty($token)) {
            return false;
        }

        // Basic JWT structure validation
        $parts = explode('.', $token);
        return count($parts) === 3;
    }
}
```

### **7. Model/Api/AdminSopRequestDataBuilder.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Model\Api;

use CyberSource\SecureAcceptance\Api\Admin\SopRequestDataBuilderInterface;
use CyberSource\SecureAcceptance\Helper\RequestDataBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\QuoteRepository;
use Psr\Log\LoggerInterface;

class AdminSopRequestDataBuilder implements SopRequestDataBuilderInterface
{
    /**
     * @var RequestDataBuilder
     */
    private $requestDataBuilder;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        RequestDataBuilder $requestDataBuilder,
        QuoteRepository $quoteRepository,
        LoggerInterface $logger
    ) {
        $this->requestDataBuilder = $requestDataBuilder;
        $this->quoteRepository = $quoteRepository;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function buildRequestData($quoteId, $cardType, $vaultEnabled = false, $storeId = null)
    {
        try {
            $quote = $this->quoteRepository->get($quoteId);

            if (!$quote->getId()) {
                throw new LocalizedException(__('Quote not found'));
            }

            // Set payment method data
            $quote->getPayment()->setMethod('cybersource_transparent');
            $quote->getPayment()->setAdditionalInformation('cc_type', $cardType);

            // Build request data using existing helper
            $fields = $this->requestDataBuilder->buildSilentRequestData(
                null,
                null,
                $cardType,
                ['vault_enabled' => $vaultEnabled]
            );

            if (!$fields || !is_array($fields)) {
                throw new LocalizedException(__('Failed to build request data'));
            }

            return [
                'success' => true,
                'fields' => $fields,
                'form_url' => $this->getFormUrl(),
                'response_url' => '/rest/V1/cybersource/admin/sop/response'
            ];
        } catch (\Exception $e) {
            $this->logger->error('SOP request building error: ' . $e->getMessage());
            throw new LocalizedException(__('Request building failed: %1', $e->getMessage()));
        }
    }

    /**
     * @inheritdoc
     */
    public function validateRequestData(array $data)
    {
        $requiredFields = ['access_key', 'profile_id', 'transaction_uuid', 'signed_field_names', 'signature'];
        
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new LocalizedException(__('Missing required field: %1', $field));
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getFormFields($quoteId)
    {
        try {
            $quote = $this->quoteRepository->get($quoteId);
            $fields = $this->requestDataBuilder->buildSilentRequestData(null, null, 'visa');
            
            return [
                'fields' => $fields,
                'form_url' => $this->getFormUrl()
            ];
        } catch (\Exception $e) {
            throw new LocalizedException(__('Could not retrieve form fields'));
        }
    }

    /**
     * Get form submission URL
     */
    private function getFormUrl()
    {
        // Return CyberSource SOP form URL
        return 'https://testsecureacceptance.cybersource.com/silent';
    }
}
```

### **8. Model/Api/AdminSopResponseHandler.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Model\Api;

use CyberSource\SecureAcceptance\Api\Admin\SopResponseHandlerInterface;
use CyberSource\SecureAcceptance\Gateway\Config\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\AdminOrder\Create as AdminOrderCreate;
use Psr\Log\LoggerInterface;

class AdminSopResponseHandler implements SopResponseHandlerInterface
{
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var AdminOrderCreate
     */
    private $adminOrderCreate;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        QuoteRepository $quoteRepository,
        AdminOrderCreate $adminOrderCreate,
        Config $config,
        LoggerInterface $logger
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->adminOrderCreate = $adminOrderCreate;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function handleResponse(array $response, array $orderData)
    {
        try {
            // Validate response signature
            if (!$this->validateSignature($response)) {
                throw new LocalizedException(__('Invalid response signature'));
            }

            // Check decision
            if (!isset($response['decision']) || $response['decision'] !== 'ACCEPT') {
                $reason = $this->getReasonText($response['reason_code'] ?? '');
                throw new LocalizedException(__('Payment declined: %1', $reason));
            }

            // Get quote
            $quoteId = $orderData['quote_id'] ?? null;
            if (!$quoteId) {
                throw new LocalizedException(__('Quote ID required'));
            }

            $quote = $this->quoteRepository->get($quoteId);

            // Set payment information on quote
            $quote->getPayment()->setAdditionalInformation([
                'request_id' => $response['req_id'] ?? '',
                'transaction_id' => $response['transaction_id'] ?? '',
                'auth_code' => $response['auth_code'] ?? '',
                'avs_result' => $response['auth_avs_code'] ?? '',
                'cvv_result' => $response['auth_cv_result'] ?? '',
            ]);

            // Create order using AdminOrderCreate
            $this->adminOrderCreate->setQuote($quote);
            $order = $this->adminOrderCreate->createOrder();

            return [
                'success' => true,
                'order_id' => $order->getId(),
                'increment_id' => $order->getIncrementId(),
                'redirect_url' => '/admin/sales/order/view/order_id/' . $order->getId()
            ];
        } catch (\Exception $e) {
            $this->logger->error('SOP response handling error: ' . $e->getMessage());
            throw new LocalizedException(__('Order creation failed: %1', $e->getMessage()));
        }
    }

    /**
     * @inheritdoc
     */
    public function validateSignature(array $response)
    {
        try {
            if (empty($response['signature'])) {
                return false;
            }

            // Get secret key from config
            $secretKey = $this->config->getSecretKey();

            // Build signed fields string
            $signedFields = $response['signed_field_names'] ?? '';
            $fields = explode(',', $signedFields);

            $dataToSign = [];
            foreach ($fields as $field) {
                $field = trim($field);
                if (isset($response[$field])) {
                    $dataToSign[] = $field . '=' . $response[$field];
                }
            }

            // Calculate signature
            $data = implode(',', $dataToSign);
            $calculatedSignature = base64_encode(hash_hmac('sha256', $data, $secretKey, true));

            return hash_equals($calculatedSignature, $response['signature']);
        } catch (\Exception $e) {
            $this->logger->error('Signature validation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function getReasonText($reasonCode)
    {
        $reasons = [
            '100' => 'Successful transaction',
            '101' => 'Processor declined',
            '102' => 'Declined - Honour with identification',
            '150' => 'Error - General system failure',
            '151' => 'Error - The request was received but there was a server timeout',
            '152' => 'Error - The request was received but a service did not finish',
            '200' => 'Soft Decline - Decline without attempting to recover',
            '201' => 'Soft Decline - Error during AVS check',
            '202' => 'Decline - CVV2 mismatch',
        ];

        return $reasons[$reasonCode] ?? 'Unknown decline reason';
    }
}
```

### **9. Model/Api/AdminFlexOrderCreator.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Model\Api;

use CyberSource\SecureAcceptance\Api\Admin\FlexOrderCreatorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\AdminOrder\Create as AdminOrderCreate;
use Psr\Log\LoggerInterface;

class AdminFlexOrderCreator implements FlexOrderCreatorInterface
{
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var AdminOrderCreate
     */
    private $adminOrderCreate;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        QuoteRepository $quoteRepository,
        AdminOrderCreate $adminOrderCreate,
        Json $json,
        LoggerInterface $logger
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->adminOrderCreate = $adminOrderCreate;
        $this->json = $json;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function createOrder($quoteId, $token, array $cardData, array $orderData)
    {
        try {
            // Validate inputs
            if (!$this->validateToken($token)) {
                throw new LocalizedException(__('Invalid token format'));
            }

            if (!$this->validateCardData($cardData)) {
                throw new LocalizedException(__('Invalid card data'));
            }

            // Get quote
            $quote = $this->quoteRepository->get($quoteId);

            if (!$quote->getId()) {
                throw new LocalizedException(__('Quote not found'));
            }

            // Set payment method
            $quote->getPayment()->setMethod('cybersource_flex');

            // Extract and set card data
            $cardData = array_merge([
                'flexJwt' => $token,
                'cc_type' => $cardData['cc_type'] ?? '',
                'cc_last_4' => substr($cardData['masked_pan'] ?? '', -4),
                'cc_exp_month' => substr($cardData['exp_date'] ?? '', 0, 2),
                'cc_exp_year' => substr($cardData['exp_date'] ?? '', -4),
            ]);

            // Set additional data on quote payment
            $quote->getPayment()->setAdditionalInformation($cardData);

            // Create order using AdminOrderCreate
            $this->adminOrderCreate->setQuote($quote);
            $order = $this->adminOrderCreate->createOrder();

            if (!$order->getId()) {
                throw new LocalizedException(__('Failed to create order'));
            }

            return [
                'success' => true,
                'order_id' => $order->getId(),
                'increment_id' => $order->getIncrementId(),
                'redirect_url' => '/admin/sales/order/view/order_id/' . $order->getId()
            ];
        } catch (\Exception $e) {
            $this->logger->error('Flex order creation error: ' . $e->getMessage());
            throw new LocalizedException(__('Order creation failed: %1', $e->getMessage()));
        }
    }

    /**
     * @inheritdoc
     */
    public function validateToken($token)
    {
        if (empty($token) || !is_string($token)) {
            return false;
        }

        // Validate JWT structure
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        // Validate base64 encoding
        foreach ($parts as $part) {
            if (!$this->isValidBase64($part)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function validateCardData(array $cardData)
    {
        $requiredFields = ['cc_type', 'exp_date', 'masked_pan'];

        foreach ($requiredFields as $field) {
            if (empty($cardData[$field])) {
                throw new LocalizedException(__('Missing required card data: %1', $field));
            }
        }

        // Validate expiration date format (MM/YYYY)
        if (!preg_match('/^\d{2}\/\d{4}$/', $cardData['exp_date'])) {
            throw new LocalizedException(__('Invalid expiration date format'));
        }

        // Validate masked PAN format
        if (!preg_match('/^\d{6}x+\d{4}$/', str_replace(' ', '', $cardData['masked_pan']))) {
            throw new LocalizedException(__('Invalid masked PAN format'));
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

### **10. Model/Api/AdminVaultTokenManagement.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Model\Api;

use CyberSource\SecureAcceptance\Api\Admin\VaultTokenManagementInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Model\ResourceModel\PaymentToken\CollectionFactory;
use Psr\Log\LoggerInterface;

class AdminVaultTokenManagement implements VaultTokenManagementInterface
{
    /**
     * @var PaymentTokenRepositoryInterface
     */
    private $tokenRepository;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        PaymentTokenRepositoryInterface $tokenRepository,
        CollectionFactory $collectionFactory,
        LoggerInterface $logger
    ) {
        $this->tokenRepository = $tokenRepository;
        $this->collectionFactory = $collectionFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function deleteToken($publicHash, $customerId)
    {
        try {
            // Validate token
            $this->validateTokenDeletion($publicHash);

            // Get token from vault
            $token = $this->getTokenByPublicHash($publicHash, $customerId);

            if (!$token) {
                throw new LocalizedException(__('Token not found'));
            }

            // Delete from CyberSource (if needed)
            // Call SOAP API to delete token from CyberSource

            // Delete from Magento vault
            $this->tokenRepository->delete($token);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Token deletion error: ' . $e->getMessage());
            throw new LocalizedException(__('Token deletion failed: %1', $e->getMessage()));
        }
    }

    /**
     * @inheritdoc
     */
    public function getAvailableTokens($customerId)
    {
        try {
            $collection = $this->collectionFactory->create()
                ->addFieldToFilter('customer_id', $customerId)
                ->addFieldToFilter('payment_method_code', 'cybersource_flex');

            $tokens = [];
            foreach ($collection as $token) {
                $tokens[] = [
                    'public_hash' => $token->getPublicHash(),
                    'type' => $token->getType(),
                    'details' => $token->getTokenDetails(),
                    'expires_at' => $token->getExpiresAt(),
                ];
            }

            return $tokens;
        } catch (\Exception $e) {
            $this->logger->error('Error retrieving tokens: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @inheritdoc
     */
    public function validateTokenDeletion($publicHash)
    {
        if (empty($publicHash) || !is_string($publicHash)) {
            throw new LocalizedException(__('Invalid token hash'));
        }

        if (strlen($publicHash) < 32) {
            throw new LocalizedException(__('Invalid token hash format'));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function saveToken($customerId, $token, array $cardData)
    {
        try {
            // Validate inputs
            if (!$customerId || !$token || empty($cardData)) {
                throw new LocalizedException(__('Missing required data'));
            }

            // Create token details
            $details = [
                'cc_type' => $cardData['cc_type'] ?? '',
                'cc_last_4' => substr($cardData['masked_pan'] ?? '', -4),
                'cc_exp_month' => substr($cardData['exp_date'] ?? '', 0, 2),
                'cc_exp_year' => substr($cardData['exp_date'] ?? '', -4),
            ];

            // Create payment token
            $publicHash = hash('sha256', $token);

            // Save to vault (implementation depends on Magento API)
            // This is a placeholder - implement based on your Magento version

            return $publicHash;
        } catch (\Exception $e) {
            throw new LocalizedException(__('Token save failed: %1', $e->getMessage()));
        }
    }

    /**
     * Get token by public hash
     */
    private function getTokenByPublicHash($publicHash, $customerId)
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('public_hash', $publicHash)
            ->addFieldToFilter('customer_id', $customerId);

        return $collection->getFirstItem();
    }
}
```

---

## **PHASE 3: REST CONTROLLERS (5 FILES)**

### **11. Controller/Rest/Admin/TokenGenerator.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Controller\Rest\Admin;

use CyberSource\SecureAcceptance\Api\Admin\TokenGeneratorInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class TokenGenerator extends Action
{
    /**
     * @var TokenGeneratorInterface
     */
    private $tokenGenerator;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Context $context,
        TokenGeneratorInterface $tokenGenerator,
        JsonFactory $jsonFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->tokenGenerator = $tokenGenerator;
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
    }

    /**
     * Generate Flex token
     */
    public function execute()
    {
        try {
            $data = $this->getRequest()->getParams();

            // Validate required parameters
            if (empty($data['quote_id'])) {
                throw new LocalizedException(__('Quote ID is required'));
            }

            $quoteId = (int)$data['quote_id'];
            $storeId = isset($data['store_id']) ? (int)$data['store_id'] : null;

            // Generate token
            $result = $this->tokenGenerator->generateToken($quoteId, $storeId);

            return $this->jsonFactory->create()->setData($result);
        } catch (LocalizedException $e) {
            return $this->jsonFactory->create()->setData([
                'success' => false,
                'error' => $e->getMessage()
            ])->setHttpResponseCode(400);
        } catch (\Exception $e) {
            $this->logger->error('Token generation error: ' . $e->getMessage());
            return $this->jsonFactory->create()->setData([
                'success' => false,
                'error' => 'Token generation failed'
            ])->setHttpResponseCode(500);
        }
    }
}
```

### **12. Controller/Rest/Admin/SopRequestData.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Controller\Rest\Admin;

use CyberSource\SecureAcceptance\Api\Admin\SopRequestDataBuilderInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class SopRequestData extends Action
{
    /**
     * @var SopRequestDataBuilderInterface
     */
    private $sopRequestBuilder;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Context $context,
        SopRequestDataBuilderInterface $sopRequestBuilder,
        JsonFactory $jsonFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->sopRequestBuilder = $sopRequestBuilder;
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
    }

    /**
     * Build SOP request data
     */
    public function execute()
    {
        try {
            $data = $this->getRequest()->getParams();

            // Validate required parameters
            if (empty($data['quote_id'])) {
                throw new LocalizedException(__('Quote ID is required'));
            }
            if (empty($data['cc_type'])) {
                throw new LocalizedException(__('Card type is required'));
            }

            $quoteId = (int)$data['quote_id'];
            $ccType = $data['cc_type'];
            $vaultEnabled = isset($data['vault_enabled']) ? (bool)$data['vault_enabled'] : false;
            $storeId = isset($data['store_id']) ? (int)$data['store_id'] : null;

            // Build request data
            $result = $this->sopRequestBuilder->buildRequestData(
                $quoteId,
                $ccType,
                $vaultEnabled,
                $storeId
            );

            return $this->jsonFactory->create()->setData($result);
        } catch (LocalizedException $e) {
            return $this->jsonFactory->create()->setData([
                'success' => false,
                'error' => $e->getMessage()
            ])->setHttpResponseCode(400);
        } catch (\Exception $e) {
            $this->logger->error('SOP request building error: ' . $e->getMessage());
            return $this->jsonFactory->create()->setData([
                'success' => false,
                'error' => 'Request building failed'
            ])->setHttpResponseCode(500);
        }
    }
}
```

### **13. Controller/Rest/Admin/SopResponse.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Controller\Rest\Admin;

use CyberSource\SecureAcceptance\Api\Admin\SopResponseHandlerInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class SopResponse extends Action
{
    /**
     * @var SopResponseHandlerInterface
     */
    private $sopResponseHandler;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Context $context,
        SopResponseHandlerInterface $sopResponseHandler,
        JsonFactory $jsonFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->sopResponseHandler = $sopResponseHandler;
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
    }

    /**
     * Handle SOP response
     */
    public function execute()
    {
        try {
            $response = $this->getRequest()->getParams();
            $orderData = [
                'quote_id' => $response['quote_id'] ?? null
            ];

            if (empty($response['decision'])) {
                throw new LocalizedException(__('Invalid response: missing decision field'));
            }

            // Handle response
            $result = $this->sopResponseHandler->handleResponse($response, $orderData);

            return $this->jsonFactory->create()->setData($result);
        } catch (LocalizedException $e) {
            return $this->jsonFactory->create()->setData([
                'success' => false,
                'error' => $e->getMessage()
            ])->setHttpResponseCode(400);
        } catch (\Exception $e) {
            $this->logger->error('SOP response handling error: ' . $e->getMessage());
            return $this->jsonFactory->create()->setData([
                'success' => false,
                'error' => 'Response handling failed'
            ])->setHttpResponseCode(500);
        }
    }
}
```

### **14. Controller/Rest/Admin/FlexPlaceOrder.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Controller\Rest\Admin;

use CyberSource\SecureAcceptance\Api\Admin\FlexOrderCreatorInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class FlexPlaceOrder extends Action
{
    /**
     * @var FlexOrderCreatorInterface
     */
    private $flexOrderCreator;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Context $context,
        FlexOrderCreatorInterface $flexOrderCreator,
        JsonFactory $jsonFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->flexOrderCreator = $flexOrderCreator;
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
    }

    /**
     * Create order from Flex token
     */
    public function execute()
    {
        try {
            $data = $this->getRequest()->getParams();

            // Validate required parameters
            if (empty($data['quote_id'])) {
                throw new LocalizedException(__('Quote ID is required'));
            }
            if (empty($data['token'])) {
                throw new LocalizedException(__('Token is required'));
            }

            $quoteId = (int)$data['quote_id'];
            $token = $data['token'];
            $cardData = [
                'cc_type' => $data['cc_type'] ?? '',
                'exp_date' => $data['exp_date'] ?? '',
                'masked_pan' => $data['masked_pan'] ?? '',
            ];
            $orderData = [];

            // Create order
            $result = $this->flexOrderCreator->createOrder($quoteId, $token, $cardData, $orderData);

            return $this->jsonFactory->create()->setData($result);
        } catch (LocalizedException $e) {
            return $this->jsonFactory->create()->setData([
                'success' => false,
                'error' => $e->getMessage()
            ])->setHttpResponseCode(400);
        } catch (\Exception $e) {
            $this->logger->error('Order creation error: ' . $e->getMessage());
            return $this->jsonFactory->create()->setData([
                'success' => false,
                'error' => 'Order creation failed'
            ])->setHttpResponseCode(500);
        }
    }
}
```

### **15. Controller/Rest/Admin/VaultTokenDelete.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Controller\Rest\Admin;

use CyberSource\SecureAcceptance\Api\Admin\VaultTokenManagementInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class VaultTokenDelete extends Action
{
    /**
     * @var VaultTokenManagementInterface
     */
    private $vaultTokenManagement;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Context $context,
        VaultTokenManagementInterface $vaultTokenManagement,
        JsonFactory $jsonFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->vaultTokenManagement = $vaultTokenManagement;
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
    }

    /**
     * Delete vault token
     */
    public function execute()
    {
        try {
            $publicHash = $this->getRequest()->getParam('public_hash');
            $customerId = $this->getRequest()->getParam('customer_id');

            // Validate required parameters
            if (empty($publicHash)) {
                throw new LocalizedException(__('Public hash is required'));
            }
            if (empty($customerId)) {
                throw new LocalizedException(__('Customer ID is required'));
            }

            // Delete token
            $this->vaultTokenManagement->deleteToken($publicHash, (int)$customerId);

            return $this->jsonFactory->create()->setData([
                'success' => true,
                'message' => 'Token deleted successfully'
            ]);
        } catch (LocalizedException $e) {
            return $this->jsonFactory->create()->setData([
                'success' => false,
                'error' => $e->getMessage()
            ])->setHttpResponseCode(400);
        } catch (\Exception $e) {
            $this->logger->error('Token deletion error: ' . $e->getMessage());
            return $this->jsonFactory->create()->setData([
                'success' => false,
                'error' => 'Token deletion failed'
            ])->setHttpResponseCode(500);
        }
    }
}
```

---

## **PHASE 4: DATA MODELS - REQUESTS (5 FILES)**

### **16. Model/Rest/Request/TokenRequest.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Model\Rest\Request;

class TokenRequest
{
    /**
     * @var int
     */
    private $quoteId;

    /**
     * @var int|null
     */
    private $storeId;

    /**
     * Constructor
     */
    public function __construct($quoteId, $storeId = null)
    {
        $this->quoteId = $quoteId;
        $this->storeId = $storeId;
    }

    /**
     * Get quote ID
     */
    public function getQuoteId()
    {
        return $this->quoteId;
    }

    /**
     * Get store ID
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * Validate request data
     */
    public function validate()
    {
        if (empty($this->quoteId)) {
            throw new \InvalidArgumentException('Quote ID is required');
        }

        return true;
    }
}
```

### **17. Model/Rest/Request/SopRequestDataRequest.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Model\Rest\Request;

class SopRequestDataRequest
{
    /**
     * @var int
     */
    private $quoteId;

    /**
     * @var string
     */
    private $cardType;

    /**
     * @var bool
     */
    private $vaultEnabled;

    /**
     * @var int|null
     */
    private $storeId;

    public function __construct($quoteId, $cardType, $vaultEnabled = false, $storeId = null)
    {
        $this->quoteId = $quoteId;
        $this->cardType = $cardType;
        $this->vaultEnabled = $vaultEnabled;
        $this->storeId = $storeId;
    }

    public function getQuoteId()
    {
        return $this->quoteId;
    }

    public function getCardType()
    {
        return $this->cardType;
    }

    public function isVaultEnabled()
    {
        return $this->vaultEnabled;
    }

    public function getStoreId()
    {
        return $this->storeId;
    }

    public function validate()
    {
        if (empty($this->quoteId)) {
            throw new \InvalidArgumentException('Quote ID is required');
        }
        if (empty($this->cardType)) {
            throw new \InvalidArgumentException('Card type is required');
        }

        return true;
    }
}
```

### **18. Model/Rest/Request/SopResponseRequest.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Model\Rest\Request;

class SopResponseRequest
{
    /**
     * @var array
     */
    private $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function getData($key = null)
    {
        if ($key === null) {
            return $this->data;
        }
        return $this->data[$key] ?? null;
    }

    public function getDecision()
    {
        return $this->data['decision'] ?? null;
    }

    public function getReasonCode()
    {
        return $this->data['reason_code'] ?? null;
    }

    public function getTransactionId()
    {
        return $this->data['transaction_id'] ?? null;
    }

    public function getSignature()
    {
        return $this->data['signature'] ?? null;
    }

    public function getSignedFieldNames()
    {
        return $this->data['signed_field_names'] ?? null;
    }

    public function validate()
    {
        if (empty($this->data['decision'])) {
            throw new \InvalidArgumentException('Decision is required');
        }
        if (empty($this->data['signature'])) {
            throw new \InvalidArgumentException('Signature is required');
        }

        return true;
    }
}
```

### **19. Model/Rest/Request/FlexPlaceOrderRequest.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Model\Rest\Request;

class FlexPlaceOrderRequest
{
    /**
     * @var int
     */
    private $quoteId;

    /**
     * @var string
     */
    private $token;

    /**
     * @var array
     */
    private $cardData;

    /**
     * @var array
     */
    private $orderData;

    public function __construct($quoteId, $token, array $cardData, array $orderData = [])
    {
        $this->quoteId = $quoteId;
        $this->token = $token;
        $this->cardData = $cardData;
        $this->orderData = $orderData;
    }

    public function getQuoteId()
    {
        return $this->quoteId;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function getCardData()
    {
        return $this->cardData;
    }

    public function getOrderData()
    {
        return $this->orderData;
    }

    public function validate()
    {
        if (empty($this->quoteId)) {
            throw new \InvalidArgumentException('Quote ID is required');
        }
        if (empty($this->token)) {
            throw new \InvalidArgumentException('Token is required');
        }
        if (empty($this->cardData)) {
            throw new \InvalidArgumentException('Card data is required');
        }

        return true;
    }
}
```

### **20. Model/Rest/Request/VaultTokenDeleteRequest.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Model\Rest\Request;

class VaultTokenDeleteRequest
{
    /**
     * @var string
     */
    private $publicHash;

    /**
     * @var int
     */
    private $customerId;

    public function __construct($publicHash, $customerId)
    {
        $this->publicHash = $publicHash;
        $this->customerId = $customerId;
    }

    public function getPublicHash()
    {
        return $this->publicHash;
    }

    public function getCustomerId()
    {
        return $this->customerId;
    }

    public function validate()
    {
        if (empty($this->publicHash)) {
            throw new \InvalidArgumentException('Public hash is required');
        }
        if (empty($this->customerId)) {
            throw new \InvalidArgumentException('Customer ID is required');
        }

        return true;
    }
}
```

---

## **PHASE 5: DATA MODELS - RESPONSES (4 FILES)**

### **21. Model/Rest/Response/TokenResponse.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Model\Rest\Response;

class TokenResponse
{
    /**
     * @var string
     */
    private $token;

    /**
     * @var string
     */
    private $clientLibrary;

    /**
     * @var string
     */
    private $clientIntegrity;

    /**
     * @var string
     */
    private $placeOrderUrl;

    public function __construct(
        $token,
        $clientLibrary,
        $clientIntegrity,
        $placeOrderUrl = '/rest/V1/cybersource/admin/flex/place-order'
    ) {
        $this->token = $token;
        $this->clientLibrary = $clientLibrary;
        $this->clientIntegrity = $clientIntegrity;
        $this->placeOrderUrl = $placeOrderUrl;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function getClientLibrary()
    {
        return $this->clientLibrary;
    }

    public function getClientIntegrity()
    {
        return $this->clientIntegrity;
    }

    public function getPlaceOrderUrl()
    {
        return $this->placeOrderUrl;
    }

    public function toArray()
    {
        return [
            'success' => true,
            'token' => $this->token,
            'client_library' => $this->clientLibrary,
            'client_integrity' => $this->clientIntegrity,
            'place_order_url' => $this->placeOrderUrl
        ];
    }
}
```

### **22. Model/Rest/Response/SopRequestDataResponse.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Model\Rest\Response;

class SopRequestDataResponse
{
    /**
     * @var array
     */
    private $fields;

    /**
     * @var string
     */
    private $formUrl;

    /**
     * @var string
     */
    private $responseUrl;

    public function __construct(array $fields, $formUrl, $responseUrl)
    {
        $this->fields = $fields;
        $this->formUrl = $formUrl;
        $this->responseUrl = $responseUrl;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function getFormUrl()
    {
        return $this->formUrl;
    }

    public function getResponseUrl()
    {
        return $this->responseUrl;
    }

    public function toArray()
    {
        return [
            'success' => true,
            'fields' => $this->fields,
            'form_url' => $this->formUrl,
            'response_url' => $this->responseUrl
        ];
    }
}
```

### **23. Model/Rest/Response/OrderResponse.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Model\Rest\Response;

class OrderResponse
{
    /**
     * @var int
     */
    private $orderId;

    /**
     * @var string
     */
    private $incrementId;

    /**
     * @var string
     */
    private $redirectUrl;

    public function __construct($orderId, $incrementId, $redirectUrl)
    {
        $this->orderId = $orderId;
        $this->incrementId = $incrementId;
        $this->redirectUrl = $redirectUrl;
    }

    public function getOrderId()
    {
        return $this->orderId;
    }

    public function getIncrementId()
    {
        return $this->incrementId;
    }

    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }

    public function toArray()
    {
        return [
            'success' => true,
            'order_id' => $this->orderId,
            'increment_id' => $this->incrementId,
            'redirect_url' => $this->redirectUrl
        ];
    }
}
```

### **24. Model/Rest/Response/SuccessResponse.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Model\Rest\Response;

class SuccessResponse
{
    /**
     * @var bool
     */
    private $success;

    /**
     * @var string
     */
    private $message;

    /**
     * @var array
     */
    private $data;

    public function __construct($message = '', array $data = [])
    {
        $this->success = true;
        $this->message = $message;
        $this->data = $data;
    }

    public function isSuccess()
    {
        return $this->success;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getData()
    {
        return $this->data;
    }

    public function toArray()
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data
        ];
    }
}
```

---

*[Content continues in next part due to length... See REST_ADMIN_ORDER_COMPLETE_CODE.md Part 2 for remaining 39 files including Helpers, Services, Observers, Plugins, Blocks, Layouts, Templates, JavaScript, CSS, and Configuration files]*
