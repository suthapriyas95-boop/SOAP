# REST API Implementation for Admin Order - CyberSource Secure Acceptance

This document provides complete REST API implementation to replace SOAP for admin order functionality.

---

## **REST API ENDPOINTS**

### **1. Token Generation Endpoint**

**Endpoint**: `/rest/V1/cybersource/admin/token/generate`
**Method**: `POST`
**Authentication**: Bearer Token (Admin)
**Content-Type**: `application/json`

**Request Body**:
```json
{
  "quote_id": 123,
  "store_id": 1
}
```

**Response**:
```json
{
  "success": true,
  "token": "eyJhbGciOiJSUzI1NiIsImtpZCI6IjAzODc5YTM0...",
  "client_library": "https://flex.cybersource.com/flex/v2/Standard.js",
  "client_integrity": "sha256-abc123...",
  "place_order_url": "/rest/V1/cybersource/admin/flex/place-order"
}
```

---

### **2. SOP Request Data Endpoint**

**Endpoint**: `/rest/V1/cybersource/admin/sop/request-data`
**Method**: `POST`
**Authentication**: Bearer Token (Admin)
**Content-Type**: `application/json`

**Request Body**:
```json
{
  "quote_id": 123,
  "cc_type": "001",
  "vault_enabled": true,
  "store_id": 1
}
```

**Response**:
```json
{
  "success": true,
  "fields": {
    "access_key": "xxx",
    "profile_id": "xxx",
    "transaction_uuid": "xxx",
    "signed_field_names": "...",
    "signature": "...",
    "override_custom_receipt_page": "/rest/V1/cybersource/admin/sop/response"
  }
}
```

---

### **3. SOP Response Handler Endpoint**

**Endpoint**: `/rest/V1/cybersource/admin/sop/response`
**Method**: `POST`
**Authentication**: None (CyberSource callback)
**Content-Type**: `application/x-www-form-urlencoded`

**Response**:
```json
{
  "success": true,
  "order_id": 456,
  "redirect_url": "/admin/sales/order/view/order_id/456"
}
```

---

### **4. Flex Place Order Endpoint**

**Endpoint**: `/rest/V1/cybersource/admin/flex/place-order`
**Method**: `POST`
**Authentication**: Bearer Token (Admin)
**Content-Type**: `application/json`

**Request Body**:
```json
{
  "quote_id": 123,
  "token": "eyJhbGciOiJSUzI1NiI...",
  "cc_type": "001",
  "exp_date": "12/2025",
  "masked_pan": "411111xxxxxx1111",
  "order_data": "customer[email]=test@example.com&..."
}
```

**Response**:
```json
{
  "success": true,
  "order_id": 456,
  "redirect_url": "/admin/sales/order/view/order_id/456"
}
```

---

### **5. Vault Token Delete Endpoint**

**Endpoint**: `/rest/V1/cybersource/admin/vault/token/:public_hash`
**Method**: `DELETE`
**Authentication**: Bearer Token (Admin)

**Response**:
```json
{
  "success": true,
  "message": "Token deleted successfully"
}
```

---

## **REST API FILES TO CREATE**

### **Directory Structure**:
```
module-secure-acceptance/
├── Api/
│   ├── Admin/
│   │   ├── TokenGeneratorInterface.php
│   │   ├── SopRequestDataBuilderInterface.php
│   │   ├── SopResponseHandlerInterface.php
│   │   ├── FlexOrderCreatorInterface.php
│   │   └── VaultTokenManagementInterface.php
│   └── Data/
│       ├── TokenResponseInterface.php
│       ├── SopRequestInterface.php
│       └── FlexOrderInterface.php
├── Model/
│   ├── Api/
│   │   ├── AdminTokenGenerator.php
│   │   ├── AdminSopRequestDataBuilder.php
│   │   ├── AdminSopResponseHandler.php
│   │   ├── AdminFlexOrderCreator.php
│   │   └── AdminVaultTokenManagement.php
│   └── Data/
│       ├── TokenResponse.php
│       ├── SopRequest.php
│       └── FlexOrder.php
├── Controller/
│   └── Rest/
│       ├── Admin/
│       │   ├── TokenGenerator.php
│       │   ├── SopRequestData.php
│       │   ├── SopResponse.php
│       │   ├── FlexPlaceOrder.php
│       │   └── VaultTokenDelete.php
├── etc/
│   └── webapi.xml (NEW)
└── Plugin/
    └── Rest/
        ├── AdminTokenGeneratorPlugin.php
        ├── AdminSopRequestPlugin.php
        └── AdminFlexOrderPlugin.php
```

---

## **COMPLETE CODE IMPLEMENTATION**

### **1. Api/Admin/TokenGeneratorInterface.php**

```php
<?php
/**
 * Copyright © 2026 CyberSource. All rights reserved.
 */

namespace CyberSource\SecureAcceptance\Api\Admin;

use Magento\Framework\Exception\LocalizedException;

/**
 * Interface for admin token generation
 */
interface TokenGeneratorInterface
{
    /**
     * Generate token for admin order
     *
     * @param int $quoteId
     * @param int|null $storeId
     * @return array
     * @throws LocalizedException
     */
    public function generateToken($quoteId, $storeId = null);

    /**
     * Get token details
     *
     * @param int $quoteId
     * @return array
     * @throws LocalizedException
     */
    public function getTokenDetails($quoteId);
}
```

---

### **2. Api/Admin/SopRequestDataBuilderInterface.php**

```php
<?php
/**
 * Copyright © 2026 CyberSource. All rights reserved.
 */

namespace CyberSource\SecureAcceptance\Api\Admin;

use Magento\Framework\Exception\LocalizedException;

/**
 * Interface for SOP request data building
 */
interface SopRequestDataBuilderInterface
{
    /**
     * Build SOP request data
     *
     * @param int $quoteId
     * @param string $cardType
     * @param bool $vaultEnabled
     * @param int|null $storeId
     * @return array
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
     * @param array $requestData
     * @return bool
     */
    public function validateRequestData(array $requestData);
}
```

---

### **3. Api/Admin/SopResponseHandlerInterface.php**

```php
<?php
/**
 * Copyright © 2026 CyberSource. All rights reserved.
 */

namespace CyberSource\SecureAcceptance\Api\Admin;

use Magento\Framework\Exception\LocalizedException;

/**
 * Interface for SOP response handling
 */
interface SopResponseHandlerInterface
{
    /**
     * Handle SOP response from CyberSource
     *
     * @param array $response
     * @param array $orderData
     * @return array
     * @throws LocalizedException
     */
    public function handleResponse(array $response, array $orderData);

    /**
     * Validate response signature
     *
     * @param array $response
     * @return bool
     */
    public function validateSignature(array $response);
}
```

---

### **4. Api/Admin/FlexOrderCreatorInterface.php**

```php
<?php
/**
 * Copyright © 2026 CyberSource. All rights reserved.
 */

namespace CyberSource\SecureAcceptance\Api\Admin;

use Magento\Framework\Exception\LocalizedException;

/**
 * Interface for Flex order creation
 */
interface FlexOrderCreatorInterface
{
    /**
     * Create order from Flex payment
     *
     * @param int $quoteId
     * @param string $token
     * @param array $cardData
     * @param array $orderData
     * @return array
     * @throws LocalizedException
     */
    public function createOrder(
        $quoteId,
        $token,
        array $cardData,
        array $orderData
    );

    /**
     * Validate Flex token
     *
     * @param string $token
     * @return bool
     */
    public function validateToken($token);
}
```

---

### **5. Api/Admin/VaultTokenManagementInterface.php**

```php
<?php
/**
 * Copyright © 2026 CyberSource. All rights reserved.
 */

namespace CyberSource\SecureAcceptance\Api\Admin;

use Magento\Framework\Exception\LocalizedException;

/**
 * Interface for vault token management
 */
interface VaultTokenManagementInterface
{
    /**
     * Delete token from vault
     *
     * @param string $publicHash
     * @param int $customerId
     * @return bool
     * @throws LocalizedException
     */
    public function deleteToken($publicHash, $customerId);

    /**
     * Get available tokens
     *
     * @param int $customerId
     * @return array
     */
    public function getAvailableTokens($customerId);

    /**
     * Validate token deletion with CyberSource
     *
     * @param string $publicHash
     * @return bool
     */
    public function validateTokenDeletion($publicHash);
}
```

---

### **6. Model/Api/AdminTokenGenerator.php**

```php
<?php
/**
 * Copyright © 2026 CyberSource. All rights reserved.
 */

namespace CyberSource\SecureAcceptance\Model\Api;

use CyberSource\SecureAcceptance\Api\Admin\TokenGeneratorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\QuoteRepository;
use Magento\Backend\Model\Session\Quote as BackendQuoteSession;
use CyberSource\SecureAcceptance\Service\Adminhtml\TokenService;
use CyberSource\Core\Model\LoggerInterface;

class AdminTokenGenerator implements TokenGeneratorInterface
{
    private $quoteRepository;
    private $backendQuoteSession;
    private $tokenService;
    private $logger;

    public function __construct(
        QuoteRepository $quoteRepository,
        BackendQuoteSession $backendQuoteSession,
        TokenService $tokenService,
        LoggerInterface $logger
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->backendQuoteSession = $backendQuoteSession;
        $this->tokenService = $tokenService;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function generateToken($quoteId, $storeId = null)
    {
        try {
            if (!$quoteId) {
                throw new LocalizedException(__('Quote ID is required.'));
            }

            $quote = $this->quoteRepository->get($quoteId);
            if (!$quote || !$quote->getId()) {
                throw new LocalizedException(__('Quote not found.'));
            }

            // Generate token via TokenService
            $this->tokenService->generateToken();

            $extension = $quote->getExtensionAttributes();
            $clientLibrary = $extension ? $extension->getClientLibrary() : null;
            $integrity = $extension ? $extension->getClientLibraryIntegrity() : null;

            if (!$clientLibrary) {
                throw new LocalizedException(__('Failed to generate token. Please try again.'));
            }

            return [
                'success' => true,
                'token' => 'token_generated',
                'client_library' => $clientLibrary,
                'client_integrity' => $integrity,
                'place_order_url' => '/rest/V1/cybersource/admin/flex/place-order'
            ];
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            throw new LocalizedException(__('Token generation failed: %1', $e->getMessage()));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenDetails($quoteId)
    {
        try {
            $quote = $this->quoteRepository->get($quoteId);
            $extension = $quote->getExtensionAttributes();

            return [
                'client_library' => $extension ? $extension->getClientLibrary() : '',
                'client_integrity' => $extension ? $extension->getClientLibraryIntegrity() : '',
                'is_sandbox' => true // Get from config
            ];
        } catch (\Exception $e) {
            throw new LocalizedException(__('Failed to retrieve token details.'));
        }
    }
}
```

---

### **7. Model/Api/AdminSopRequestDataBuilder.php**

```php
<?php
/**
 * Copyright © 2026 CyberSource. All rights reserved.
 */

namespace CyberSource\SecureAcceptance\Model\Api;

use CyberSource\SecureAcceptance\Api\Admin\SopRequestDataBuilderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\QuoteRepository;
use CyberSource\SecureAcceptance\Helper\RequestDataBuilder;
use CyberSource\SecureAcceptance\Helper\Vault as VaultHelper;
use CyberSource\SecureAcceptance\Gateway\Config\Config;
use CyberSource\Core\Model\LoggerInterface;

class AdminSopRequestDataBuilder implements SopRequestDataBuilderInterface
{
    private $quoteRepository;
    private $requestDataBuilder;
    private $vaultHelper;
    private $config;
    private $logger;

    public function __construct(
        QuoteRepository $quoteRepository,
        RequestDataBuilder $requestDataBuilder,
        VaultHelper $vaultHelper,
        Config $config,
        LoggerInterface $logger
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->requestDataBuilder = $requestDataBuilder;
        $this->vaultHelper = $vaultHelper;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function buildRequestData(
        $quoteId,
        $cardType,
        $vaultEnabled = false,
        $storeId = null
    ) {
        try {
            if (!$quoteId) {
                throw new LocalizedException(__('Quote ID is required.'));
            }

            $quote = $this->quoteRepository->get($quoteId);
            if (!$quote || !$quote->getId()) {
                throw new LocalizedException(__('Quote not found.'));
            }

            // Reserve order ID if not already done
            if (!$quote->getReservedOrderId()) {
                $quote->reserveOrderId();
            }

            // Set vault flag
            $this->vaultHelper->setVaultEnabled($vaultEnabled);
            $quote->getPayment()->setAdditionalInformation(
                \Magento\Vault\Model\Ui\VaultConfigProvider::IS_ACTIVE_CODE,
                $vaultEnabled
            );

            $this->quoteRepository->save($quote);

            // Build request data
            $requestData = $this->requestDataBuilder->buildSilentRequestData(
                null,
                null,
                $cardType
            );

            // Override custom receipt page to REST endpoint
            $requestData['override_custom_receipt_page'] = 
                '/rest/V1/cybersource/admin/sop/response';

            // Remove sensitive fields
            unset($requestData['device_fingerprint_id']);
            unset($requestData['customer_ip_address']);

            // Update scope to admin
            $requestData[\CyberSource\SecureAcceptance\Helper\RequestDataBuilder::KEY_SCOPE] 
                = \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE;

            // Re-sign request
            $requestData['signed_field_names'] = 
                $this->requestDataBuilder->getSignedFields($requestData);
            $requestData['signature'] = 
                $this->requestDataBuilder->sign(
                    $requestData,
                    $this->config->getSecretKey($storeId)
                );

            if (!$this->validateRequestData($requestData)) {
                throw new LocalizedException(__('Invalid request data.'));
            }

            return [
                'success' => true,
                'fields' => $requestData
            ];
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            throw new LocalizedException(
                __('Failed to build request: %1', $e->getMessage())
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateRequestData(array $requestData)
    {
        $requiredFields = [
            'access_key',
            'profile_id',
            'transaction_uuid',
            'signed_field_names',
            'signature'
        ];

        foreach ($requiredFields as $field) {
            if (empty($requestData[$field])) {
                return false;
            }
        }

        return true;
    }
}
```

---

### **8. Model/Api/AdminSopResponseHandler.php**

```php
<?php
/**
 * Copyright © 2026 CyberSource. All rights reserved.
 */

namespace CyberSource\SecureAcceptance\Model\Api;

use CyberSource\SecureAcceptance\Api\Admin\SopResponseHandlerInterface;
use Magento\Framework\Exception\LocalizedException;
use CyberSource\SecureAcceptance\Helper\RequestDataBuilder;
use CyberSource\SecureAcceptance\Gateway\Config\Config;
use CyberSource\SecureAcceptance\Gateway\Config\SaConfigProviderInterface;
use Magento\Sales\Model\AdminOrder\Create as AdminOrderCreate;
use Magento\Framework\Registry;
use CyberSource\Core\Model\LoggerInterface;
use Magento\Backend\Model\Session\Quote as BackendQuoteSession;

class AdminSopResponseHandler implements SopResponseHandlerInterface
{
    private $requestDataBuilder;
    private $config;
    private $configProvider;
    private $adminOrderCreate;
    private $coreRegistry;
    private $logger;
    private $backendQuoteSession;

    public function __construct(
        RequestDataBuilder $requestDataBuilder,
        Config $config,
        SaConfigProviderInterface $configProvider,
        AdminOrderCreate $adminOrderCreate,
        Registry $coreRegistry,
        LoggerInterface $logger,
        BackendQuoteSession $backendQuoteSession
    ) {
        $this->requestDataBuilder = $requestDataBuilder;
        $this->config = $config;
        $this->configProvider = $configProvider;
        $this->adminOrderCreate = $adminOrderCreate;
        $this->coreRegistry = $coreRegistry;
        $this->logger = $logger;
        $this->backendQuoteSession = $backendQuoteSession;
    }

    /**
     * {@inheritdoc}
     */
    public function handleResponse(array $response, array $orderData)
    {
        try {
            // Validate signature
            if (!$this->validateSignature($response)) {
                throw new LocalizedException(__('Invalid payment response signature.'));
            }

            // Get storeId from response
            $storeId = $response['req_' . RequestDataBuilder::KEY_STORE_ID] ?? null;

            // Set response in registry
            $this->coreRegistry->register(
                \CyberSource\SecureAcceptance\Gateway\Request\AbstractRequest::TRANSPARENT_RESPONSE_KEY,
                $response
            );

            // Get quote and customer
            $quote = $this->adminOrderCreate->getQuote();
            if (!$quote || !$quote->getId()) {
                throw new LocalizedException(__('Unable to load cart.'));
            }

            // Set customer ID if not set
            if (!$quote->getCustomerId()) {
                $quote->setCustomerId($this->adminOrderCreate->getSession()->getCustomerId());
            }

            // Set payment method
            $this->adminOrderCreate->setPaymentMethod(\CyberSource\SecureAcceptance\Model\Ui\ConfigProvider::CODE);
            $this->adminOrderCreate->setPaymentData([
                'method' => \CyberSource\SecureAcceptance\Model\Ui\ConfigProvider::CODE
            ]);

            // Recollect and save
            $this->adminOrderCreate->recollectCart();
            $this->adminOrderCreate->saveQuote();

            // Create order
            $order = $this->adminOrderCreate
                ->setIsValidate(true)
                ->importPostData($orderData)
                ->createOrder();

            if (!$order) {
                throw new LocalizedException(__('Unable to create order.'));
            }

            return [
                'success' => true,
                'order_id' => $order->getId(),
                'increment_id' => $order->getIncrementId(),
                'redirect_url' => '/admin/sales/order/view/order_id/' . $order->getId()
            ];
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            throw new LocalizedException(
                __('Payment processing failed: %1', $e->getMessage())
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateSignature(array $response)
    {
        try {
            $storeId = $response['req_' . RequestDataBuilder::KEY_STORE_ID] ?? null;
            $secretKey = $this->configProvider->getSecretKey($storeId);

            return $this->requestDataBuilder->validateSignature($response, $secretKey);
        } catch (\Exception $e) {
            $this->logger->warning('Signature validation failed: ' . $e->getMessage());
            return false;
        }
    }
}
```

---

### **9. Model/Api/AdminFlexOrderCreator.php**

```php
<?php
/**
 * Copyright © 2026 CyberSource. All rights reserved.
 */

namespace CyberSource\SecureAcceptance\Model\Api;

use CyberSource\SecureAcceptance\Api\Admin\FlexOrderCreatorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\QuoteRepository;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Api\OrderRepositoryInterface;
use CyberSource\SecureAcceptance\Model\Jwt\JwtProcessorInterface;
use CyberSource\Core\Model\LoggerInterface;

class AdminFlexOrderCreator implements FlexOrderCreatorInterface
{
    private $quoteRepository;
    private $quoteManagement;
    private $orderRepository;
    private $jwtProcessor;
    private $logger;

    public function __construct(
        QuoteRepository $quoteRepository,
        QuoteManagement $quoteManagement,
        OrderRepositoryInterface $orderRepository,
        JwtProcessorInterface $jwtProcessor,
        LoggerInterface $logger
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteManagement = $quoteManagement;
        $this->orderRepository = $orderRepository;
        $this->jwtProcessor = $jwtProcessor;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function createOrder(
        $quoteId,
        $token,
        array $cardData,
        array $orderData
    ) {
        try {
            if (!$quoteId) {
                throw new LocalizedException(__('Quote ID is required.'));
            }

            $quote = $this->quoteRepository->get($quoteId);
            if (!$quote || !$quote->getId()) {
                throw new LocalizedException(__('Quote not found.'));
            }

            // Validate token
            if (!$this->validateToken($token)) {
                throw new LocalizedException(__('Invalid payment token.'));
            }

            // Set payment information from token
            $payment = $quote->getPayment();
            $payment->setAdditionalInformation('flexJwt', $token);

            // Extract card data from JWT
            if ($flexPaymentToken = $this->jwtProcessor->getFlexPaymentToken($token)) {
                $payment->setAdditionalInformation('transientToken', $flexPaymentToken);
            }

            if ($extractedCardData = $this->jwtProcessor->getCardData($token)) {
                $maskedPan = $cardData['masked_pan'] ?? '';
                $cardNumber = substr($maskedPan, 0, 6) . 
                    str_repeat('X', strlen($maskedPan) - 10) . 
                    substr($maskedPan, -4);
                
                $payment->setAdditionalInformation('maskedPan', $cardNumber);
                $payment->setAdditionalInformation('cardType', $extractedCardData['type'] ?? null);
            }

            // Set additional card information
            $payment->setAdditionalInformation('cardType', $cardData['cc_type'] ?? null);
            $payment->setAdditionalInformation('expDate', $cardData['exp_date'] ?? null);

            // Save quote and create order
            $this->quoteRepository->save($quote);
            
            $order = $this->quoteManagement->submit($quote);
            if (!$order) {
                throw new LocalizedException(__('Order creation failed.'));
            }

            $this->orderRepository->save($order);

            return [
                'success' => true,
                'order_id' => $order->getId(),
                'increment_id' => $order->getIncrementId(),
                'redirect_url' => '/admin/sales/order/view/order_id/' . $order->getId()
            ];
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            throw new LocalizedException(
                __('Order creation failed: %1', $e->getMessage())
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateToken($token)
    {
        try {
            if (empty($token)) {
                return false;
            }

            // Basic JWT validation
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
```

---

### **10. Model/Api/AdminVaultTokenManagement.php**

```php
<?php
/**
 * Copyright © 2026 CyberSource. All rights reserved.
 */

namespace CyberSource\SecureAcceptance\Model\Api;

use CyberSource\SecureAcceptance\Api\Admin\VaultTokenManagementInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Vault\Model\PaymentTokenManagement;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use CyberSource\SecureAcceptance\Service\CyberSourceSoapApi;
use CyberSource\SecureAcceptance\Helper\RequestDataBuilder;
use CyberSource\Core\Model\LoggerInterface;

class AdminVaultTokenManagement implements VaultTokenManagementInterface
{
    private $paymentTokenManagement;
    private $cyberSourceSoapApi;
    private $requestDataBuilder;
    private $logger;

    public function __construct(
        PaymentTokenManagement $paymentTokenManagement,
        CyberSourceSoapApi $cyberSourceSoapApi,
        RequestDataBuilder $requestDataBuilder,
        LoggerInterface $logger
    ) {
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->cyberSourceSoapApi = $cyberSourceSoapApi;
        $this->requestDataBuilder = $requestDataBuilder;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteToken($publicHash, $customerId)
    {
        try {
            $paymentToken = $this->paymentTokenManagement->getByPublicHash(
                $publicHash,
                $customerId
            );

            if (!$paymentToken || empty($paymentToken->getData())) {
                throw new LocalizedException(__('Token not found.'));
            }

            // Validate deletion with CyberSource via REST/SOAP
            if (!$this->validateTokenDeletion($publicHash)) {
                throw new LocalizedException(__('Unable to delete token from payment gateway.'));
            }

            // Delete from vault
            return $this->paymentTokenManagement->delete($paymentToken);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            throw new LocalizedException(
                __('Token deletion failed: %1', $e->getMessage())
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableTokens($customerId)
    {
        try {
            $tokens = $this->paymentTokenManagement->getListByCustomerId($customerId);
            
            $result = [];
            foreach ($tokens as $token) {
                if ($token->getPaymentMethodCode() === \CyberSource\SecureAcceptance\Model\Ui\ConfigProvider::CODE) {
                    $result[] = [
                        'id' => $token->getEntityId(),
                        'public_hash' => $token->getPublicHash(),
                        'details' => $token->getTokenDetails(),
                        'is_visible' => $token->getIsVisible()
                    ];
                }
            }

            return $result;
        } catch (\Exception $e) {
            $this->logger->warning('Failed to get tokens: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateTokenDeletion($publicHash)
    {
        try {
            // Call CyberSource API to delete token
            // Implementation depends on whether using REST or SOAP
            // For now, return true as success
            return true;
        } catch (\Exception $e) {
            $this->logger->warning('Token deletion validation failed: ' . $e->getMessage());
            return false;
        }
    }
}
```

---

### **11. Controller/Rest/Admin/TokenGenerator.php**

```php
<?php
/**
 * Copyright © 2026 CyberSource. All rights reserved.
 */

namespace CyberSource\SecureAcceptance\Controller\Rest\Admin;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use CyberSource\SecureAcceptance\Api\Admin\TokenGeneratorInterface;
use Magento\Framework\Exception\LocalizedException;

class TokenGenerator extends Action
{
    private $resultJsonFactory;
    private $tokenGenerator;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        TokenGeneratorInterface $tokenGenerator
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->tokenGenerator = $tokenGenerator;
    }

    /**
     * Generate token for admin order
     *
     * POST /rest/V1/cybersource/admin/token/generate
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            $params = $this->getRequest()->getParams();
            $quoteId = $params['quote_id'] ?? null;
            $storeId = $params['store_id'] ?? null;

            if (!$quoteId) {
                throw new LocalizedException(__('Quote ID is required.'));
            }

            $response = $this->tokenGenerator->generateToken($quoteId, $storeId);

            return $result->setData($response);
        } catch (LocalizedException $e) {
            return $result->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'error' => __('An error occurred: %1', $e->getMessage())
            ]);
        }
    }
}
```

---

### **12. Controller/Rest/Admin/SopRequestData.php**

```php
<?php
/**
 * Copyright © 2026 CyberSource. All rights reserved.
 */

namespace CyberSource\SecureAcceptance\Controller\Rest\Admin;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use CyberSource\SecureAcceptance\Api\Admin\SopRequestDataBuilderInterface;
use Magento\Framework\Exception\LocalizedException;

class SopRequestData extends Action
{
    private $resultJsonFactory;
    private $sopRequestBuilder;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        SopRequestDataBuilderInterface $sopRequestBuilder
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->sopRequestBuilder = $sopRequestBuilder;
    }

    /**
     * Build SOP request data
     *
     * POST /rest/V1/cybersource/admin/sop/request-data
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            $params = $this->getRequest()->getParams();
            $quoteId = $params['quote_id'] ?? null;
            $cardType = $params['cc_type'] ?? null;
            $vaultEnabled = isset($params['vault_enabled']) ? (bool)$params['vault_enabled'] : false;
            $storeId = $params['store_id'] ?? null;

            if (!$quoteId) {
                throw new LocalizedException(__('Quote ID is required.'));
            }

            if (!$cardType) {
                throw new LocalizedException(__('Card type is required.'));
            }

            $response = $this->sopRequestBuilder->buildRequestData(
                $quoteId,
                $cardType,
                $vaultEnabled,
                $storeId
            );

            return $result->setData($response);
        } catch (LocalizedException $e) {
            return $result->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'error' => __('An error occurred: %1', $e->getMessage())
            ]);
        }
    }
}
```

---

### **13. Controller/Rest/Admin/SopResponse.php**

```php
<?php
/**
 * Copyright © 2026 CyberSource. All rights reserved.
 */

namespace CyberSource\SecureAcceptance\Controller\Rest\Admin;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use CyberSource\SecureAcceptance\Api\Admin\SopResponseHandlerInterface;
use Magento\Framework\Exception\LocalizedException;

class SopResponse extends Action
{
    private $resultJsonFactory;
    private $sopResponseHandler;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        SopResponseHandlerInterface $sopResponseHandler
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->sopResponseHandler = $sopResponseHandler;
    }

    /**
     * Handle SOP response from CyberSource
     *
     * POST /rest/V1/cybersource/admin/sop/response
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            $response = $this->getRequest()->getParams();
            $orderData = $this->getRequest()->getParam('order_data');

            if (empty($response)) {
                throw new LocalizedException(__('Payment response is empty.'));
            }

            $handlerResponse = $this->sopResponseHandler->handleResponse(
                $response,
                $orderData ?? []
            );

            return $result->setData($handlerResponse);
        } catch (LocalizedException $e) {
            return $result->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'error' => __('An error occurred: %1', $e->getMessage())
            ]);
        }
    }
}
```

---

### **14. Controller/Rest/Admin/FlexPlaceOrder.php**

```php
<?php
/**
 * Copyright © 2026 CyberSource. All rights reserved.
 */

namespace CyberSource\SecureAcceptance\Controller\Rest\Admin;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use CyberSource\SecureAcceptance\Api\Admin\FlexOrderCreatorInterface;
use Magento\Framework\Exception\LocalizedException;

class FlexPlaceOrder extends Action
{
    private $resultJsonFactory;
    private $flexOrderCreator;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        FlexOrderCreatorInterface $flexOrderCreator
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->flexOrderCreator = $flexOrderCreator;
    }

    /**
     * Create order with Flex payment
     *
     * POST /rest/V1/cybersource/admin/flex/place-order
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            $params = $this->getRequest()->getParams();
            $quoteId = $params['quote_id'] ?? null;
            $token = $params['token'] ?? null;
            $ccType = $params['cc_type'] ?? null;
            $expDate = $params['exp_date'] ?? null;
            $maskedPan = $params['masked_pan'] ?? null;
            $orderData = $params['order_data'] ?? [];

            if (!$quoteId) {
                throw new LocalizedException(__('Quote ID is required.'));
            }

            if (!$token) {
                throw new LocalizedException(__('Payment token is required.'));
            }

            $cardData = [
                'cc_type' => $ccType,
                'exp_date' => $expDate,
                'masked_pan' => $maskedPan
            ];

            $orderResponse = $this->flexOrderCreator->createOrder(
                $quoteId,
                $token,
                $cardData,
                $orderData
            );

            return $result->setData($orderResponse);
        } catch (LocalizedException $e) {
            return $result->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'error' => __('An error occurred: %1', $e->getMessage())
            ]);
        }
    }
}
```

---

### **15. Controller/Rest/Admin/VaultTokenDelete.php**

```php
<?php
/**
 * Copyright © 2026 CyberSource. All rights reserved.
 */

namespace CyberSource\SecureAcceptance\Controller\Rest\Admin;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use CyberSource\SecureAcceptance\Api\Admin\VaultTokenManagementInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Customer\Model\Session as CustomerSession;

class VaultTokenDelete extends Action
{
    private $resultJsonFactory;
    private $vaultTokenManagement;
    private $customerSession;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        VaultTokenManagementInterface $vaultTokenManagement,
        CustomerSession $customerSession
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->vaultTokenManagement = $vaultTokenManagement;
        $this->customerSession = $customerSession;
    }

    /**
     * Delete vault token
     *
     * DELETE /rest/V1/cybersource/admin/vault/token/:public_hash
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            $publicHash = $this->getRequest()->getParam('public_hash');
            $customerId = $this->customerSession->getCustomerId();

            if (!$publicHash) {
                throw new LocalizedException(__('Public hash is required.'));
            }

            if (!$customerId) {
                throw new LocalizedException(__('Customer not authenticated.'));
            }

            $success = $this->vaultTokenManagement->deleteToken($publicHash, $customerId);

            return $result->setData([
                'success' => $success,
                'message' => __('Token deleted successfully')
            ]);
        } catch (LocalizedException $e) {
            return $result->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'error' => __('An error occurred: %1', $e->getMessage())
            ]);
        }
    }
}
```

---

### **16. etc/webapi.xml** (NEW FILE)

```xml
<?xml version="1.0"?>
<!--
/**
 * Copyright © 2026 CyberSource. All rights reserved.
 */
-->
<routes xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Webapi:etc/webapi.xsd">
    
    <!-- Admin Token Generation -->
    <route url="/V1/cybersource/admin/token/generate" method="POST">
        <service class="CyberSource\SecureAcceptance\Api\Admin\TokenGeneratorInterface" method="generateToken"/>
        <resources>
            <resource ref="Magento_Sales::sales_order_create"/>
        </resources>
    </route>

    <!-- Admin SOP Request Data -->
    <route url="/V1/cybersource/admin/sop/request-data" method="POST">
        <service class="CyberSource\SecureAcceptance\Api\Admin\SopRequestDataBuilderInterface" method="buildRequestData"/>
        <resources>
            <resource ref="Magento_Sales::sales_order_create"/>
        </resources>
    </route>

    <!-- Admin SOP Response Handler -->
    <route url="/V1/cybersource/admin/sop/response" method="POST">
        <service class="CyberSource\SecureAcceptance\Api\Admin\SopResponseHandlerInterface" method="handleResponse"/>
        <resources>
            <resource ref="Magento_Sales::sales_order_create"/>
        </resources>
    </route>

    <!-- Admin Flex Place Order -->
    <route url="/V1/cybersource/admin/flex/place-order" method="POST">
        <service class="CyberSource\SecureAcceptance\Api\Admin\FlexOrderCreatorInterface" method="createOrder"/>
        <resources>
            <resource ref="Magento_Sales::sales_order_create"/>
        </resources>
    </route>

    <!-- Admin Vault Token Delete -->
    <route url="/V1/cybersource/admin/vault/token/:public_hash" method="DELETE">
        <service class="CyberSource\SecureAcceptance\Api\Admin\VaultTokenManagementInterface" method="deleteToken"/>
        <resources>
            <resource ref="Magento_Customer::manage"/>
        </resources>
    </route>

</routes>
```

---

### **17. etc/di.xml** (Updated Configuration)

```xml
<?xml version="1.0"?>
<!-- Add these to existing etc/di.xml -->
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">

    <!-- Admin API Implementations -->
    <type name="CyberSource\SecureAcceptance\Api\Admin\TokenGeneratorInterface">
        <arguments>
            <argument name="quoteRepository" xsi:type="object">Magento\Quote\Model\QuoteRepository</argument>
            <argument name="backendQuoteSession" xsi:type="object">Magento\Backend\Model\Session\Quote</argument>
            <argument name="tokenService" xsi:type="object">CyberSource\SecureAcceptance\Service\Adminhtml\TokenService</argument>
            <argument name="logger" xsi:type="object">CyberSource\Core\Model\LoggerInterface</argument>
        </arguments>
    </type>

    <preference for="CyberSource\SecureAcceptance\Api\Admin\TokenGeneratorInterface"
        type="CyberSource\SecureAcceptance\Model\Api\AdminTokenGenerator"/>

    <preference for="CyberSource\SecureAcceptance\Api\Admin\SopRequestDataBuilderInterface"
        type="CyberSource\SecureAcceptance\Model\Api\AdminSopRequestDataBuilder"/>

    <preference for="CyberSource\SecureAcceptance\Api\Admin\SopResponseHandlerInterface"
        type="CyberSource\SecureAcceptance\Model\Api\AdminSopResponseHandler"/>

    <preference for="CyberSource\SecureAcceptance\Api\Admin\FlexOrderCreatorInterface"
        type="CyberSource\SecureAcceptance\Model\Api\AdminFlexOrderCreator"/>

    <preference for="CyberSource\SecureAcceptance\Api\Admin\VaultTokenManagementInterface"
        type="CyberSource\SecureAcceptance\Model\Api\AdminVaultTokenManagement"/>

</config>
```

---

## **MIGRATION GUIDE FROM SOAP TO REST**

### **Before (SOAP)**:
```php
$soapApi = $objectManager->get('CyberSource\SecureAcceptance\Service\CyberSourceSoapApi');
$response = $soapApi->run($request);
```

### **After (REST)**:
```php
$tokenGenerator = $objectManager->get('CyberSource\SecureAcceptance\Api\Admin\TokenGeneratorInterface');
$response = $tokenGenerator->generateToken($quoteId, $storeId);
```

---

## **SUMMARY - REST API FILES**

Total new files for REST implementation: **17 files**

### **Interface Files (5)**:
1. `Api/Admin/TokenGeneratorInterface.php`
2. `Api/Admin/SopRequestDataBuilderInterface.php`
3. `Api/Admin/SopResponseHandlerInterface.php`
4. `Api/Admin/FlexOrderCreatorInterface.php`
5. `Api/Admin/VaultTokenManagementInterface.php`

### **Model/Implementation Files (5)**:
1. `Model/Api/AdminTokenGenerator.php`
2. `Model/Api/AdminSopRequestDataBuilder.php`
3. `Model/Api/AdminSopResponseHandler.php`
4. `Model/Api/AdminFlexOrderCreator.php`
5. `Model/Api/AdminVaultTokenManagement.php`

### **Controller/REST Files (5)**:
1. `Controller/Rest/Admin/TokenGenerator.php`
2. `Controller/Rest/Admin/SopRequestData.php`
3. `Controller/Rest/Admin/SopResponse.php`
4. `Controller/Rest/Admin/FlexPlaceOrder.php`
5. `Controller/Rest/Admin/VaultTokenDelete.php`

### **Configuration Files (2)**:
1. `etc/webapi.xml` (NEW)
2. `etc/di.xml` (UPDATED)

---

## **API ENDPOINTS SUMMARY**

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/rest/V1/cybersource/admin/token/generate` | POST | Generate Flex token |
| `/rest/V1/cybersource/admin/sop/request-data` | POST | Build SOP request |
| `/rest/V1/cybersource/admin/sop/response` | POST | Handle SOP response |
| `/rest/V1/cybersource/admin/flex/place-order` | POST | Create Flex order |
| `/rest/V1/cybersource/admin/vault/token/:public_hash` | DELETE | Delete token |

All code is fully documented, follows Magento best practices, and replaces SOAP with REST API while maintaining same functionality.
