# REST Admin Order - COMPLETE CODE FOR ALL 63 FILES

Complete implementation for CyberSource REST API admin order creation (v25.2.0), excluding Flex microform.

---

## **PHASE 1: API INTERFACES (5 FILES)**

### **1. Api/Admin/TokenGeneratorInterface.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Api\Admin;

/**
 * Generate Flex tokens for admin orders
 */
interface TokenGeneratorInterface
{
    /**
     * Generate token for quote
     *
     * @param int $quoteId
     * @param int|null $storeId
     * @return \CyberSource\SecureAcceptance\Api\Admin\Data\TokenResponseInterface
     */
    public function generateToken($quoteId, $storeId = null);
}
```

### **2. Api/Admin/SopRequestDataBuilderInterface.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Api\Admin;

/**
 * Build SOP request data for admin orders
 */
interface SopRequestDataBuilderInterface
{
    /**
     * Build request data for SOP
     *
     * @param int $quoteId
     * @param string $cardType
     * @param bool $vaultEnabled
     * @param int|null $storeId
     * @return \CyberSource\SecureAcceptance\Api\Admin\Data\SopRequestDataResponseInterface
     */
    public function buildRequestData($quoteId, $cardType, $vaultEnabled = false, $storeId = null);
}
```

### **3. Api/Admin/SopResponseHandlerInterface.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Api\Admin;

/**
 * Handle SOP responses for admin orders
 */
interface SopResponseHandlerInterface
{
    /**
     * Handle SOP response
     *
     * @param array $response
     * @param array $orderData
     * @return \CyberSource\SecureAcceptance\Api\Admin\Data\OrderResponseInterface
     */
    public function handleResponse(array $response, array $orderData = []);
}
```

### **4. Api/Admin/FlexOrderCreatorInterface.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Api\Admin;

/**
 * Create orders using Flex tokens
 */
interface FlexOrderCreatorInterface
{
    /**
     * Create order with Flex token
     *
     * @param int $quoteId
     * @param string $token
     * @param array $cardData
     * @param array $orderData
     * @return \CyberSource\SecureAcceptance\Api\Admin\Data\OrderResponseInterface
     */
    public function createOrder($quoteId, $token, array $cardData, array $orderData = []);
}
```

### **5. Api/Admin/VaultTokenManagementInterface.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Api\Admin;

/**
 * Manage vault tokens for admin orders
 */
interface VaultTokenManagementInterface
{
    /**
     * Get available tokens for customer
     *
     * @param int $customerId
     * @return \CyberSource\SecureAcceptance\Api\Admin\Data\TokenListResponseInterface
     */
    public function getAvailableTokens($customerId);

    /**
     * Delete token
     *
     * @param string $publicHash
     * @param int $customerId
     * @return bool
     */
    public function deleteToken($publicHash, $customerId);
}
```

---

## **PHASE 2: SERVICE IMPLEMENTATIONS (5 FILES)**

### **6. Model/Api/AdminTokenGenerator.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Model\Api\Admin;

use CyberSource\SecureAcceptance\Api\Admin\TokenGeneratorInterface;
use CyberSource\SecureAcceptance\Api\Admin\Data\TokenResponseInterface;
use CyberSource\SecureAcceptance\Api\Admin\Data\TokenResponseInterfaceFactory;
use CyberSource\SecureAcceptance\Service\Adminhtml\TokenService;
use Magento\Quote\Model\QuoteRepository;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Admin token generator implementation
 */
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
     * @var TokenResponseInterfaceFactory
     */
    private $tokenResponseFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        TokenService $tokenService,
        QuoteRepository $quoteRepository,
        TokenResponseInterfaceFactory $tokenResponseFactory,
        LoggerInterface $logger
    ) {
        $this->tokenService = $tokenService;
        $this->quoteRepository = $quoteRepository;
        $this->tokenResponseFactory = $tokenResponseFactory;
        $this->logger = $logger;
    }

    /**
     * Generate token for quote
     */
    public function generateToken($quoteId, $storeId = null)
    {
        try {
            $quote = $this->quoteRepository->get($quoteId);

            if (!$quote->getId()) {
                throw new LocalizedException(__('Quote not found'));
            }

            $tokenData = $this->tokenService->generateToken($quote, $storeId);

            /** @var TokenResponseInterface $response */
            $response = $this->tokenResponseFactory->create();
            $response->setToken($tokenData['token']);
            $response->setClientLibrary($tokenData['client_library']);
            $response->setClientIntegrity($tokenData['client_integrity']);
            $response->setExpiresAt($tokenData['expires_at']);

            return $response;
        } catch (\Exception $e) {
            $this->logger->error('Token generation failed: ' . $e->getMessage());
            throw new LocalizedException(__('Token generation failed: %1', $e->getMessage()));
        }
    }
}
```

### **7. Model/Api/AdminSopRequestDataBuilder.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Model\Api\Admin;

use CyberSource\SecureAcceptance\Api\Admin\SopRequestDataBuilderInterface;
use CyberSource\SecureAcceptance\Api\Admin\Data\SopRequestDataResponseInterface;
use CyberSource\SecureAcceptance\Api\Admin\Data\SopRequestDataResponseInterfaceFactory;
use CyberSource\SecureAcceptance\Helper\RequestDataBuilder;
use Magento\Quote\Model\QuoteRepository;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Admin SOP request data builder implementation
 */
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
     * @var SopRequestDataResponseInterfaceFactory
     */
    private $sopResponseFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        RequestDataBuilder $requestDataBuilder,
        QuoteRepository $quoteRepository,
        SopRequestDataResponseInterfaceFactory $sopResponseFactory,
        LoggerInterface $logger
    ) {
        $this->requestDataBuilder = $requestDataBuilder;
        $this->quoteRepository = $quoteRepository;
        $this->sopResponseFactory = $sopResponseFactory;
        $this->logger = $logger;
    }

    /**
     * Build request data for SOP
     */
    public function buildRequestData($quoteId, $cardType, $vaultEnabled = false, $storeId = null)
    {
        try {
            $quote = $this->quoteRepository->get($quoteId);

            if (!$quote->getId()) {
                throw new LocalizedException(__('Quote not found'));
            }

            $options = [
                'rest_mode' => true,
                'vault_enabled' => $vaultEnabled,
                'response_endpoint' => '/rest/V1/cybersource/admin/sop/response'
            ];

            $requestData = $this->requestDataBuilder->buildSilentRequestData(
                $quote->getPayment()->getId(),
                $storeId,
                $cardType,
                $options
            );

            /** @var SopRequestDataResponseInterface $response */
            $response = $this->sopResponseFactory->create();
            $response->setFields($requestData);
            $response->setFormUrl('https://testsecureacceptance.cybersource.com/silent');

            return $response;
        } catch (\Exception $e) {
            $this->logger->error('SOP request data build failed: ' . $e->getMessage());
            throw new LocalizedException(__('SOP request data build failed: %1', $e->getMessage()));
        }
    }
}
```

### **8. Model/Api/AdminSopResponseHandler.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Model\Api\Admin;

use CyberSource\SecureAcceptance\Api\Admin\SopResponseHandlerInterface;
use CyberSource\SecureAcceptance\Api\Admin\Data\OrderResponseInterface;
use CyberSource\SecureAcceptance\Api\Admin\Data\OrderResponseInterfaceFactory;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\AdminOrder\Create;
use CyberSource\SecureAcceptance\Gateway\Config\Config;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Admin SOP response handler implementation
 */
class AdminSopResponseHandler implements SopResponseHandlerInterface
{
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var Create
     */
    private $adminOrderCreate;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var OrderResponseInterfaceFactory
     */
    private $orderResponseFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        QuoteRepository $quoteRepository,
        Create $adminOrderCreate,
        Config $config,
        OrderResponseInterfaceFactory $orderResponseFactory,
        LoggerInterface $logger
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->adminOrderCreate = $adminOrderCreate;
        $this->config = $config;
        $this->orderResponseFactory = $orderResponseFactory;
        $this->logger = $logger;
    }

    /**
     * Handle SOP response
     */
    public function handleResponse(array $response, array $orderData = [])
    {
        try {
            // Validate response
            if (!isset($response['decision']) || $response['decision'] !== 'ACCEPT') {
                throw new LocalizedException(__('Payment was declined'));
            }

            // Get quote from response data
            $quoteId = $response['req_reference_number'] ?? null;
            if (!$quoteId) {
                throw new LocalizedException(__('Quote ID not found in response'));
            }

            $quote = $this->quoteRepository->get($quoteId);

            // Set order data
            $this->adminOrderCreate->setQuote($quote);

            if (!empty($orderData['billing_address'])) {
                $this->adminOrderCreate->setBillingAddress($orderData['billing_address']);
            }

            if (!empty($orderData['shipping_address'])) {
                $this->adminOrderCreate->setShippingAddress($orderData['shipping_address']);
            }

            if (!empty($orderData['customer'])) {
                $this->adminOrderCreate->setCustomerData($orderData['customer']);
            }

            // Create order
            $order = $this->adminOrderCreate->createOrder();

            /** @var OrderResponseInterface $response */
            $result = $this->orderResponseFactory->create();
            $result->setOrderId($order->getId());
            $result->setIncrementId($order->getIncrementId());
            $result->setRedirectUrl('/admin/sales/order/view/order_id/' . $order->getId());

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('SOP response handling failed: ' . $e->getMessage());
            throw new LocalizedException(__('Order creation failed: %1', $e->getMessage()));
        }
    }
}
```

### **9. Model/Api/AdminFlexOrderCreator.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Model\Api\Admin;

use CyberSource\SecureAcceptance\Api\Admin\FlexOrderCreatorInterface;
use CyberSource\SecureAcceptance\Api\Admin\Data\OrderResponseInterface;
use CyberSource\SecureAcceptance\Api\Admin\Data\OrderResponseInterfaceFactory;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\AdminOrder\Create;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Admin Flex order creator implementation
 */
class AdminFlexOrderCreator implements FlexOrderCreatorInterface
{
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var Create
     */
    private $adminOrderCreate;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var OrderResponseInterfaceFactory
     */
    private $orderResponseFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        QuoteRepository $quoteRepository,
        Create $adminOrderCreate,
        Json $json,
        OrderResponseInterfaceFactory $orderResponseFactory,
        LoggerInterface $logger
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->adminOrderCreate = $adminOrderCreate;
        $this->json = $json;
        $this->orderResponseFactory = $orderResponseFactory;
        $this->logger = $logger;
    }

    /**
     * Create order with Flex token
     */
    public function createOrder($quoteId, $token, array $cardData, array $orderData = [])
    {
        try {
            $quote = $this->quoteRepository->get($quoteId);

            if (!$quote->getId()) {
                throw new LocalizedException(__('Quote not found'));
            }

            // Decode token to get card data
            $tokenData = $this->json->unserialize(base64_decode(str_replace('_', '/', str_replace('-','+',explode('.', $token)[1]))));

            // Set payment data
            $payment = $quote->getPayment();
            $payment->setMethod('cybersource_flex');
            $payment->setAdditionalInformation('flexJwt', $token);
            $payment->setAdditionalInformation('cc_type', $cardData['cc_type'] ?? $tokenData['cardType']);
            $payment->setAdditionalInformation('cc_last_4', substr($tokenData['cardNumber'], -4));
            $payment->setAdditionalInformation('cc_exp_month', $tokenData['expirationMonth']);
            $payment->setAdditionalInformation('cc_exp_year', $tokenData['expirationYear']);

            // Set order data
            $this->adminOrderCreate->setQuote($quote);

            if (!empty($orderData['billing_address'])) {
                $this->adminOrderCreate->setBillingAddress($orderData['billing_address']);
            }

            if (!empty($orderData['shipping_address'])) {
                $this->adminOrderCreate->setShippingAddress($orderData['shipping_address']);
            }

            if (!empty($orderData['customer'])) {
                $this->adminOrderCreate->setCustomerData($orderData['customer']);
            }

            // Create order
            $order = $this->adminOrderCreate->createOrder();

            /** @var OrderResponseInterface $response */
            $response = $this->orderResponseFactory->create();
            $response->setOrderId($order->getId());
            $response->setIncrementId($order->getIncrementId());
            $response->setRedirectUrl('/admin/sales/order/view/order_id/' . $order->getId());

            return $response;
        } catch (\Exception $e) {
            $this->logger->error('Flex order creation failed: ' . $e->getMessage());
            throw new LocalizedException(__('Order creation failed: %1', $e->getMessage()));
        }
    }
}
```

### **10. Model/Api/AdminVaultTokenManagement.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Model\Api\Admin;

use CyberSource\SecureAcceptance\Api\Admin\VaultTokenManagementInterface;
use CyberSource\SecureAcceptance\Api\Admin\Data\TokenListResponseInterface;
use CyberSource\SecureAcceptance\Api\Admin\Data\TokenListResponseInterfaceFactory;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Magento\Vault\Model\ResourceModel\PaymentToken\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Admin vault token management implementation
 */
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
     * @var TokenListResponseInterfaceFactory
     */
    private $tokenListResponseFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        PaymentTokenRepositoryInterface $tokenRepository,
        CollectionFactory $collectionFactory,
        TokenListResponseInterfaceFactory $tokenListResponseFactory,
        LoggerInterface $logger
    ) {
        $this->tokenRepository = $tokenRepository;
        $this->collectionFactory = $collectionFactory;
        $this->tokenListResponseFactory = $tokenListResponseFactory;
        $this->logger = $logger;
    }

    /**
     * Get available tokens for customer
     */
    public function getAvailableTokens($customerId)
    {
        try {
            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('customer_id', $customerId);
            $collection->addFieldToFilter('is_active', 1);

            $tokens = [];
            foreach ($collection as $token) {
                $tokens[] = [
                    'public_hash' => $token->getPublicHash(),
                    'type' => $token->getType(),
                    'details' => $token->getTokenDetails(),
                    'created_at' => $token->getCreatedAt(),
                    'expires_at' => $token->getExpiresAt()
                ];
            }

            /** @var TokenListResponseInterface $response */
            $response = $this->tokenListResponseFactory->create();
            $response->setTokens($tokens);
            $response->setCount(count($tokens));

            return $response;
        } catch (\Exception $e) {
            $this->logger->error('Token list retrieval failed: ' . $e->getMessage());
            throw new LocalizedException(__('Token list retrieval failed: %1', $e->getMessage()));
        }
    }

    /**
     * Delete token
     */
    public function deleteToken($publicHash, $customerId)
    {
        try {
            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('public_hash', $publicHash);
            $collection->addFieldToFilter('customer_id', $customerId);

            $token = $collection->getFirstItem();
            if (!$token->getId()) {
                throw new LocalizedException(__('Token not found'));
            }

            $this->tokenRepository->delete($token);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Token deletion failed: ' . $e->getMessage());
            throw new LocalizedException(__('Token deletion failed: %1', $e->getMessage()));
        }
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
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * REST controller for token generation
 */
class TokenGenerator extends \Magento\Backend\App\Action
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
        \Magento\Backend\App\Action\Context $context,
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
     * Execute token generation
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $data = json_decode($this->getRequest()->getContent(), true);

            if (!isset($data['quote_id'])) {
                throw new LocalizedException(__('Quote ID is required'));
            }

            $storeId = $data['store_id'] ?? null;
            $tokenResponse = $this->tokenGenerator->generateToken($data['quote_id'], $storeId);

            $result->setData([
                'success' => true,
                'token' => $tokenResponse->getToken(),
                'client_library' => $tokenResponse->getClientLibrary(),
                'client_integrity' => $tokenResponse->getClientIntegrity(),
                'place_order_url' => '/rest/V1/cybersource/admin/flex/place-order',
                'expires_at' => $tokenResponse->getExpiresAt()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Token generation error: ' . $e->getMessage());
            $result->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }

        return $result;
    }
}
```

### **12. Controller/Rest/Admin/SopRequestData.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Controller\Rest\Admin;

use CyberSource\SecureAcceptance\Api\Admin\SopRequestDataBuilderInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * REST controller for SOP request data
 */
class SopRequestData extends \Magento\Backend\App\Action
{
    /**
     * @var SopRequestDataBuilderInterface
     */
    private $sopBuilder;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        SopRequestDataBuilderInterface $sopBuilder,
        JsonFactory $jsonFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->sopBuilder = $sopBuilder;
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
    }

    /**
     * Execute SOP request data build
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $data = json_decode($this->getRequest()->getContent(), true);

            if (!isset($data['quote_id']) || !isset($data['cc_type'])) {
                throw new LocalizedException(__('Quote ID and card type are required'));
            }

            $vaultEnabled = $data['vault_enabled'] ?? false;
            $storeId = $data['store_id'] ?? null;

            $sopResponse = $this->sopBuilder->buildRequestData(
                $data['quote_id'],
                $data['cc_type'],
                $vaultEnabled,
                $storeId
            );

            $result->setData([
                'success' => true,
                'fields' => $sopResponse->getFields(),
                'form_url' => $sopResponse->getFormUrl()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('SOP request data error: ' . $e->getMessage());
            $result->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }

        return $result;
    }
}
```

### **13. Controller/Rest/Admin/SopResponse.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Controller\Rest\Admin;

use CyberSource\SecureAcceptance\Api\Admin\SopResponseHandlerInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * REST controller for SOP response handling
 */
class SopResponse extends \Magento\Backend\App\Action
{
    /**
     * @var SopResponseHandlerInterface
     */
    private $sopHandler;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        SopResponseHandlerInterface $sopHandler,
        JsonFactory $jsonFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->sopHandler = $sopHandler;
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
    }

    /**
     * Execute SOP response handling
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $data = json_decode($this->getRequest()->getContent(), true);

            if (!isset($data['response'])) {
                throw new LocalizedException(__('Response data is required'));
            }

            $orderData = $data['order_data'] ?? [];
            $orderResponse = $this->sopHandler->handleResponse($data['response'], $orderData);

            $result->setData([
                'success' => true,
                'order_id' => $orderResponse->getOrderId(),
                'increment_id' => $orderResponse->getIncrementId(),
                'redirect_url' => $orderResponse->getRedirectUrl()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('SOP response handling error: ' . $e->getMessage());
            $result->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }

        return $result;
    }
}
```

### **14. Controller/Rest/Admin/FlexPlaceOrder.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Controller\Rest\Admin;

use CyberSource\SecureAcceptance\Api\Admin\FlexOrderCreatorInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * REST controller for Flex order placement
 */
class FlexPlaceOrder extends \Magento\Backend\App\Action
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
        \Magento\Backend\App\Action\Context $context,
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
     * Execute Flex order placement
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $data = json_decode($this->getRequest()->getContent(), true);

            $required = ['quote_id', 'token', 'cc_type', 'exp_date', 'masked_pan'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    throw new LocalizedException(__('%1 is required', $field));
                }
            }

            $orderData = $data['order_data'] ?? [];
            $orderResponse = $this->flexOrderCreator->createOrder(
                $data['quote_id'],
                $data['token'],
                [
                    'cc_type' => $data['cc_type'],
                    'exp_date' => $data['exp_date'],
                    'masked_pan' => $data['masked_pan']
                ],
                $orderData
            );

            $result->setData([
                'success' => true,
                'order_id' => $orderResponse->getOrderId(),
                'increment_id' => $orderResponse->getIncrementId(),
                'redirect_url' => $orderResponse->getRedirectUrl()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Flex order placement error: ' . $e->getMessage());
            $result->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }

        return $result;
    }
}
```

### **15. Controller/Rest/Admin/VaultTokenDelete.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Controller\Rest\Admin;

use CyberSource\SecureAcceptance\Api\Admin\VaultTokenManagementInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * REST controller for vault token deletion
 */
class VaultTokenDelete extends \Magento\Backend\App\Action
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
        \Magento\Backend\App\Action\Context $context,
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
     * Execute vault token deletion
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $publicHash = $this->getRequest()->getParam('publicHash');
            $data = json_decode($this->getRequest()->getContent(), true);

            if (!$publicHash || !isset($data['customer_id'])) {
                throw new LocalizedException(__('Public hash and customer ID are required'));
            }

            $deleted = $this->vaultTokenManagement->deleteToken($publicHash, $data['customer_id']);

            $result->setData([
                'success' => $deleted,
                'message' => $deleted ? 'Token deleted successfully' : 'Token deletion failed'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Vault token deletion error: ' . $e->getMessage());
            $result->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }

        return $result;
    }
}
```

---

## **PHASE 4: REQUEST DATA MODELS (5 FILES)**

### **16. Model/Rest/Request/TokenRequest.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Model\Rest\Request;

use Magento\Framework\Model\AbstractModel;

/**
 * Token request data model
 */
class TokenRequest extends AbstractModel
{
    /**
     * Get quote ID
     */
    public function getQuoteId()
    {
        return $this->getData('quote_id');
    }

    /**
     * Set quote ID
     */
    public function setQuoteId($quoteId)
    {
        return $this->setData('quote_id', $quoteId);
    }

    /**
     * Get store ID
     */
    public function getStoreId()
    {
        return $this->getData('store_id');
    }

    /**
     * Set store ID
     */
    public function setStoreId($storeId)
    {
        return $this->setData('store_id', $storeId);
    }
}
```

### **17. Model/Rest/Request/SopRequestDataRequest.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Model\Rest\Request;

use Magento\Framework\Model\AbstractModel;

/**
 * SOP request data request model
 */
class SopRequestDataRequest extends AbstractModel
{
    /**
     * Get quote ID
     */
    public function getQuoteId()
    {
        return $this->getData('quote_id');
    }

    /**
     * Set quote ID
     */
    public function setQuoteId($quoteId)
    {
        return $this->setData('quote_id', $quoteId);
    }

    /**
     * Get card type
     */
    public function getCardType()
    {
        return $this->getData('cc_type');
    }

    /**
     * Set card type
     */
    public function setCardType($cardType)
    {
        return $this->setData('cc_type', $cardType);
    }

    /**
     * Get vault enabled
     */
    public function getVaultEnabled()
    {
        return $this->getData('vault_enabled');
    }

    /**
     * Set vault enabled
     */
    public function setVaultEnabled($vaultEnabled)
    {
        return $this->setData('vault_enabled', $vaultEnabled);
    }

    /**
     * Get store ID
     */
    public function getStoreId()
    {
        return $this->getData('store_id');
    }

    /**
     * Set store ID
     */
    public function setStoreId($storeId)
    {
        return $this->setData('store_id', $storeId);
    }
}
```

### **18. Model/Rest/Request/SopResponseRequest.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Model\Rest\Request;

use Magento\Framework\Model\AbstractModel;

/**
 * SOP response request model
 */
class SopResponseRequest extends AbstractModel
{
    /**
     * Get response data
     */
    public function getResponse()
    {
        return $this->getData('response');
    }

    /**
     * Set response data
     */
    public function setResponse($response)
    {
        return $this->setData('response', $response);
    }

    /**
     * Get order data
     */
    public function getOrderData()
    {
        return $this->getData('order_data');
    }

    /**
     * Set order data
     */
    public function setOrderData($orderData)
    {
        return $this->setData('order_data', $orderData);
    }
}
```

### **19. Model/Rest/Request/FlexPlaceOrderRequest.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Model\Rest\Request;

use Magento\Framework\Model\AbstractModel;

/**
 * Flex place order request model
 */
class FlexPlaceOrderRequest extends AbstractModel
{
    /**
     * Get quote ID
     */
    public function getQuoteId()
    {
        return $this->getData('quote_id');
    }

    /**
     * Set quote ID
     */
    public function setQuoteId($quoteId)
    {
        return $this->setData('quote_id', $quoteId);
    }

    /**
     * Get token
     */
    public function getToken()
    {
        return $this->getData('token');
    }

    /**
     * Set token
     */
    public function setToken($token)
    {
        return $this->setData('token', $token);
    }

    /**
     * Get card data
     */
    public function getCardData()
    {
        return $this->getData('card_data');
    }

    /**
     * Set card data
     */
    public function setCardData($cardData)
    {
        return $this->setData('card_data', $cardData);
    }

    /**
     * Get order data
     */
    public function getOrderData()
    {
        return $this->getData('order_data');
    }

    /**
     * Set order data
     */
    public function setOrderData($orderData)
    {
        return $this->setData('order_data', $orderData);
    }
}
```

### **20. Model/Rest/Request/VaultTokenDeleteRequest.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Model\Rest\Request;

use Magento\Framework\Model\AbstractModel;

/**
 * Vault token delete request model
 */
class VaultTokenDeleteRequest extends AbstractModel
{
    /**
     * Get public hash
     */
    public function getPublicHash()
    {
        return $this->getData('public_hash');
    }

    /**
     * Set public hash
     */
    public function setPublicHash($publicHash)
    {
        return $this->setData('public_hash', $publicHash);
    }

    /**
     * Get customer ID
     */
    public function getCustomerId()
    {
        return $this->getData('customer_id');
    }

    /**
     * Set customer ID
     */
    public function setCustomerId($customerId)
    {
        return $this->setData('customer_id', $customerId);
    }
}
```

---

## **PHASE 5: RESPONSE DATA MODELS (4 FILES)**

### **21. Model/Rest/Response/TokenResponse.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Model\Rest\Response;

use Magento\Framework\Model\AbstractModel;

/**
 * Token response data model
 */
class TokenResponse extends AbstractModel
{
    /**
     * Get token
     */
    public function getToken()
    {
        return $this->getData('token');
    }

    /**
     * Set token
     */
    public function setToken($token)
    {
        return $this->setData('token', $token);
    }

    /**
     * Get client library
     */
    public function getClientLibrary()
    {
        return $this->getData('client_library');
    }

    /**
     * Set client library
     */
    public function setClientLibrary($clientLibrary)
    {
        return $this->setData('client_library', $clientLibrary);
    }

    /**
     * Get client integrity
     */
    public function getClientIntegrity()
    {
        return $this->getData('client_integrity');
    }

    /**
     * Set client integrity
     */
    public function setClientIntegrity($clientIntegrity)
    {
        return $this->setData('client_integrity', $clientIntegrity);
    }

    /**
     * Get expires at
     */
    public function getExpiresAt()
    {
        return $this->getData('expires_at');
    }

    /**
     * Set expires at
     */
    public function setExpiresAt($expiresAt)
    {
        return $this->setData('expires_at', $expiresAt);
    }
}
```

### **22. Model/Rest/Response/SopRequestDataResponse.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Model\Rest\Response;

use Magento\Framework\Model\AbstractModel;

/**
 * SOP request data response model
 */
class SopRequestDataResponse extends AbstractModel
{
    /**
     * Get fields
     */
    public function getFields()
    {
        return $this->getData('fields');
    }

    /**
     * Set fields
     */
    public function setFields($fields)
    {
        return $this->setData('fields', $fields);
    }

    /**
     * Get form URL
     */
    public function getFormUrl()
    {
        return $this->getData('form_url');
    }

    /**
     * Set form URL
     */
    public function setFormUrl($formUrl)
    {
        return $this->setData('form_url', $formUrl);
    }
}
```

### **23. Model/Rest/Response/OrderResponse.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Model\Rest\Response;

use Magento\Framework\Model\AbstractModel;

/**
 * Order response data model
 */
class OrderResponse extends AbstractModel
{
    /**
     * Get order ID
     */
    public function getOrderId()
    {
        return $this->getData('order_id');
    }

    /**
     * Set order ID
     */
    public function setOrderId($orderId)
    {
        return $this->setData('order_id', $orderId);
    }

    /**
     * Get increment ID
     */
    public function getIncrementId()
    {
        return $this->getData('increment_id');
    }

    /**
     * Set increment ID
     */
    public function setIncrementId($incrementId)
    {
        return $this->setData('increment_id', $incrementId);
    }

    /**
     * Get redirect URL
     */
    public function getRedirectUrl()
    {
        return $this->getData('redirect_url');
    }

    /**
     * Set redirect URL
     */
    public function setRedirectUrl($redirectUrl)
    {
        return $this->setData('redirect_url', $redirectUrl);
    }
}
```

### **24. Model/Rest/Response/SuccessResponse.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Model\Rest\Response;

use Magento\Framework\Model\AbstractModel;

/**
 * Success response data model
 */
class SuccessResponse extends AbstractModel
{
    /**
     * Get success
     */
    public function getSuccess()
    {
        return $this->getData('success');
    }

    /**
     * Set success
     */
    public function setSuccess($success)
    {
        return $this->setData('success', $success);
    }

    /**
     * Get message
     */
    public function getMessage()
    {
        return $this->getData('message');
    }

    /**
     * Set message
     */
    public function setMessage($message)
    {
        return $this->setData('message', $message);
    }

    /**
     * Get data
     */
    public function getData()
    {
        return $this->getData('data');
    }

    /**
     * Set data
     */
    public function setData($data)
    {
        return $this->setData('data', $data);
    }

    /**
     * Get timestamp
     */
    public function getTimestamp()
    {
        return $this->getData('timestamp');
    }

    /**
     * Set timestamp
     */
    public function setTimestamp($timestamp)
    {
        return $this->setData('timestamp', $timestamp);
    }
}
```

---

## **PHASE 6: REST HELPERS (3 FILES)**

### **25. Helper/Rest/RequestValidator.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Helper\Rest;

use Magento\Framework\Exception\LocalizedException;

/**
 * Validate REST API requests
 */
class RequestValidator
{
    /**
     * Validate token request
     */
    public function validateTokenRequest(array $data)
    {
        if (empty($data['quote_id'])) {
            throw new LocalizedException(__('Quote ID is required'));
        }

        $quoteId = (int)$data['quote_id'];
        if ($quoteId <= 0) {
            throw new LocalizedException(__('Invalid Quote ID'));
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

        $quoteId = (int)$data['quote_id'];
        if ($quoteId <= 0) {
            throw new LocalizedException(__('Invalid Quote ID'));
        }

        // Validate card type format
        if (!preg_match('/^\d{3}$/', $data['cc_type'])) {
            throw new LocalizedException(__('Invalid card type format'));
        }

        return true;
    }

    /**
     * Validate SOP response
     */
    public function validateSopResponse(array $data)
    {
        if (empty($data['decision'])) {
            throw new LocalizedException(__('Decision is required'));
        }

        if (empty($data['signature'])) {
            throw new LocalizedException(__('Signature is required'));
        }

        if (empty($data['signed_field_names'])) {
            throw new LocalizedException(__('Signed field names is required'));
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

        if (empty($data['exp_date'])) {
            throw new LocalizedException(__('Expiration date is required'));
        }

        if (empty($data['masked_pan'])) {
            throw new LocalizedException(__('Masked PAN is required'));
        }

        return true;
    }

    /**
     * Validate token deletion request
     */
    public function validateTokenDeletionRequest(array $data)
    {
        if (empty($data['public_hash'])) {
            throw new LocalizedException(__('Public hash is required'));
        }

        if (empty($data['customer_id'])) {
            throw new LocalizedException(__('Customer ID is required'));
        }

        return true;
    }
}
```

### **26. Helper/Rest/ResponseFormatter.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Helper\Rest;

/**
 * Format REST API responses
 */
class ResponseFormatter
{
    /**
     * Format success response
     */
    public function formatSuccess($message = '', $data = [])
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ];
    }

    /**
     * Format error response
     */
    public function formatError($message, $errorCode = null)
    {
        $response = [
            'success' => false,
            'error' => $message,
            'timestamp' => time()
        ];

        if ($errorCode) {
            $response['error_code'] = $errorCode;
        }

        return $response;
    }

    /**
     * Format token response
     */
    public function formatTokenResponse($token, $clientLibrary, $clientIntegrity)
    {
        return [
            'success' => true,
            'token' => $token,
            'client_library' => $clientLibrary,
            'client_integrity' => $clientIntegrity,
            'place_order_url' => '/rest/V1/cybersource/admin/flex/place-order',
            'expires_at' => time() + 900
        ];
    }

    /**
     * Format form fields response
     */
    public function formatFormFieldsResponse(array $fields)
    {
        return [
            'success' => true,
            'fields' => $fields,
            'timestamp' => time()
        ];
    }

    /**
     * Format order response
     */
    public function formatOrderResponse($orderId, $incrementId, $redirectUrl = '')
    {
        return [
            'success' => true,
            'order_id' => $orderId,
            'increment_id' => $incrementId,
            'redirect_url' => $redirectUrl,
            'timestamp' => time()
        ];
    }

    /**
     * Format vault tokens response
     */
    public function formatTokensListResponse(array $tokens)
    {
        return [
            'success' => true,
            'tokens' => $tokens,
            'count' => count($tokens),
            'timestamp' => time()
        ];
    }
}
```

### **27. Helper/Rest/TokenDataExtractor.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Helper\Rest;

/**
 * Extract token data from various sources
 */
class TokenDataExtractor
{
    /**
     * Extract token data from JWT
     */
    public function extractFromJwt($token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        $payload = json_decode(base64_decode($parts[1]), true);
        return $payload;
    }

    /**
     * Extract card data from token
     */
    public function extractCardData($token)
    {
        $payload = $this->extractFromJwt($token);
        if (!$payload) {
            return null;
        }

        return [
            'cc_type' => $payload['cardType'] ?? '',
            'cc_last_4' => $payload['cardNumber'] ?? '',
            'cc_exp_month' => $payload['expirationMonth'] ?? '',
            'cc_exp_year' => $payload['expirationYear'] ?? ''
        ];
    }

    /**
     * Extract expiration from token
     */
    public function extractExpiration($token)
    {
        $payload = $this->extractFromJwt($token);
        if (!$payload) {
            return null;
        }

        return [
            'month' => $payload['expirationMonth'] ?? '',
            'year' => $payload['expirationYear'] ?? ''
        ];
    }

    /**
     * Extract masked PAN from token
     */
    public function extractMaskedPan($token)
    {
        $payload = $this->extractFromJwt($token);
        if (!$payload) {
            return null;
        }

        return $payload['cardNumber'] ?? null;
    }
}
```

---

## **PHASE 7: REST SERVICES (4 FILES)**

### **28. Service/Rest/RequestValidator.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Service\Rest;

use Magento\Framework\Exception\LocalizedException;

/**
 * Comprehensive request validation service
 */
class RequestValidator
{
    /**
     * Validate request parameters
     */
    public function validate($operation, array $data)
    {
        switch ($operation) {
            case 'generate_token':
                return $this->validateTokenRequest($data);
            case 'build_sop_request':
                return $this->validateSopRequest($data);
            case 'handle_sop_response':
                return $this->validateSopResponse($data);
            case 'create_flex_order':
                return $this->validateFlexOrder($data);
            case 'delete_vault_token':
                return $this->validateTokenDeletion($data);
            default:
                throw new LocalizedException(__('Unknown operation: %1', $operation));
        }
    }

    private function validateTokenRequest(array $data)
    {
        if (empty($data['quote_id'])) {
            throw new LocalizedException(__('Quote ID is required'));
        }
        return true;
    }

    private function validateSopRequest(array $data)
    {
        if (empty($data['quote_id']) || empty($data['cc_type'])) {
            throw new LocalizedException(__('Quote ID and card type are required'));
        }
        return true;
    }

    private function validateSopResponse(array $data)
    {
        if (empty($data['decision']) || empty($data['signature'])) {
            throw new LocalizedException(__('Decision and signature are required'));
        }
        return true;
    }

    private function validateFlexOrder(array $data)
    {
        $required = ['quote_id', 'token', 'cc_type', 'exp_date', 'masked_pan'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new LocalizedException(__('%1 is required', $field));
            }
        }
        return true;
    }

    private function validateTokenDeletion(array $data)
    {
        if (empty($data['public_hash']) || empty($data['customer_id'])) {
            throw new LocalizedException(__('Public hash and customer ID are required'));
        }
        return true;
    }
}
```

### **29. Service/Rest/ResponseFormatter.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Service\Rest;

/**
 * Format all REST API responses uniformly
 */
class ResponseFormatter
{
    private $timestamp;

    public function __construct()
    {
        $this->timestamp = time();
    }

    public function formatSuccess($data = [], $message = 'Success')
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => $this->timestamp
        ];
    }

    public function formatError($error = 'An error occurred', $code = null)
    {
        $response = [
            'success' => false,
            'error' => $error,
            'timestamp' => $this->timestamp
        ];

        if ($code) {
            $response['error_code'] = $code;
        }

        return $response;
    }

    public function formatValidationError(array $errors)
    {
        return [
            'success' => false,
            'error' => 'Validation failed',
            'errors' => $errors,
            'timestamp' => $this->timestamp
        ];
    }

    public function formatPaginatedResponse(array $items, $total, $page = 1, $pageSize = 20)
    {
        return [
            'success' => true,
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'total_pages' => ceil($total / $pageSize),
            'timestamp' => $this->timestamp
        ];
    }
}
```

### **30. Service/Rest/OrderDataProcessor.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Service\Rest;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\QuoteRepository;

/**
 * Process order data from REST requests
 */
class OrderDataProcessor
{
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    public function __construct(QuoteRepository $quoteRepository)
    {
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Process order data
     */
    public function processOrderData($quoteId, array $orderData)
    {
        try {
            $quote = $this->quoteRepository->get($quoteId);

            // Set shipping method
            if (!empty($orderData['shipping_method'])) {
                $quote->setShippingMethod($orderData['shipping_method']);
            }

            // Set billing address
            if (!empty($orderData['billing_address'])) {
                $this->setBillingAddress($quote, $orderData['billing_address']);
            }

            // Set shipping address
            if (!empty($orderData['shipping_address'])) {
                $this->setShippingAddress($quote, $orderData['shipping_address']);
            }

            // Set customer info
            if (!empty($orderData['customer'])) {
                $this->setCustomerInfo($quote, $orderData['customer']);
            }

            // Collect totals
            $quote->collectTotals();

            return true;
        } catch (\Exception $e) {
            throw new LocalizedException(__('Error processing order data: %1', $e->getMessage()));
        }
    }

    private function setBillingAddress($quote, $addressData)
    {
        $billingAddress = $quote->getBillingAddress();
        foreach ($addressData as $key => $value) {
            $billingAddress->setData($key, $value);
        }
    }

    private function setShippingAddress($quote, $addressData)
    {
        $shippingAddress = $quote->getShippingAddress();
        foreach ($addressData as $key => $value) {
            $shippingAddress->setData($key, $value);
        }
    }

    private function setCustomerInfo($quote, $customerData)
    {
        if (!empty($customerData['email'])) {
            $quote->setCustomerEmail($customerData['email']);
        }
        if (!empty($customerData['name'])) {
            $quote->setCustomerFirstname($customerData['name']);
        }
    }
}
```

### **31. Service/Rest/ErrorHandler.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Service\Rest;

use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * Handle and format errors for REST API
 */
class ErrorHandler
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    private $errorMapping = [
        'quote_not_found' => [
            'code' => 'QUOTE_NOT_FOUND',
            'message' => 'Quote not found',
            'http_status' => 404
        ],
        'invalid_token' => [
            'code' => 'INVALID_TOKEN',
            'message' => 'Invalid or expired token',
            'http_status' => 400
        ],
        'payment_declined' => [
            'code' => 'PAYMENT_DECLINED',
            'message' => 'Payment was declined',
            'http_status' => 400
        ],
        'order_creation_failed' => [
            'code' => 'ORDER_CREATION_FAILED',
            'message' => 'Failed to create order',
            'http_status' => 500
        ],
        'validation_error' => [
            'code' => 'VALIDATION_ERROR',
            'message' => 'Validation failed',
            'http_status' => 422
        ]
    ];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Handle exception
     */
    public function handleException(\Exception $e, $errorKey = 'order_creation_failed')
    {
        $this->logger->error('REST API Error: ' . $e->getMessage());

        $errorInfo = $this->errorMapping[$errorKey] ?? $this->errorMapping['order_creation_failed'];

        return [
            'success' => false,
            'error' => $e->getMessage() ?: $errorInfo['message'],
            'error_code' => $errorInfo['code'],
            'http_status' => $errorInfo['http_status']
        ];
    }

    /**
     * Get HTTP status code
     */
    public function getHttpStatusCode($errorKey)
    {
        return $this->errorMapping[$errorKey]['http_status'] ?? 500;
    }
}
```

---

## **PHASE 8: OBSERVERS (2 FILES)**

### **32. Observer/Rest/RestDataAssignObserver.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Observer\Rest;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;

/**
 * Handle data assignment for REST payments
 */
class RestDataAssignObserver implements ObserverInterface
{
    /**
     * Execute observer
     */
    public function execute(Observer $observer)
    {
        try {
            $data = $observer->getEvent()->getData('data');
            $payment = $observer->getEvent()->getData('payment');

            if (!$data || !$payment) {
                return $this;
            }

            // Handle Flex token
            if (!empty($data['flexJwt'])) {
                $payment->setAdditionalInformation('flexJwt', $data['flexJwt']);
            }

            // Handle card data
            if (!empty($data['cc_type'])) {
                $payment->setAdditionalInformation('cc_type', $data['cc_type']);
            }

            if (!empty($data['cc_last_4'])) {
                $payment->setAdditionalInformation('cc_last_4', $data['cc_last_4']);
            }

            if (!empty($data['cc_exp_month'])) {
                $payment->setAdditionalInformation('cc_exp_month', $data['cc_exp_month']);
            }

            if (!empty($data['cc_exp_year'])) {
                $payment->setAdditionalInformation('cc_exp_year', $data['cc_exp_year']);
            }

            // Handle vault token
            if (!empty($data['public_hash'])) {
                $payment->setAdditionalInformation('public_hash', $data['public_hash']);
            }

            return $this;
        } catch (\Exception $e) {
            // Log error but don't fail
            return $this;
        }
    }
}
```

### **33. Observer/Rest/RestTokenObserver.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Observer\Rest;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Psr\Log\LoggerInterface;

/**
 * Handle REST token events
 */
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
     * Execute observer
     */
    public function execute(Observer $observer)
    {
        try {
            $token = $observer->getEvent()->getData('token');
            $quoteId = $observer->getEvent()->getData('quote_id');

            if ($token && $quoteId) {
                $this->logger->info("Token generated for quote: {$quoteId}");
            }

            return $this;
        } catch (\Exception $e) {
            $this->logger->error('Token observer error: ' . $e->getMessage());
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
namespace CyberSource\SecureAcceptance\Plugin\Rest;

use CyberSource\SecureAcceptance\Helper\RequestDataBuilder;
use Magento\Framework\Exception\LocalizedException;

/**
 * Modify request data for REST context
 */
class RequestDataBuilderPlugin
{
    /**
     * Around plugin for buildSilentRequestData
     */
    public function aroundBuildSilentRequestData(
        RequestDataBuilder $subject,
        \Closure $proceed,
        $orderPaymentId = null,
        $storeId = null,
        $cardType = null,
        $options = []
    ) {
        // Call original method
        $result = $proceed($orderPaymentId, $storeId, $cardType, $options);

        // Add REST-specific modifications
        if (isset($options['rest_mode']) && $options['rest_mode']) {
            $result['rest_mode'] = true;
            $result['api_version'] = 'V1';
            
            // Add REST response endpoint
            if (isset($options['response_endpoint'])) {
                $result['override_custom_receipt_page'] = $options['response_endpoint'];
            }
        }

        return $result;
    }
}
```

### **35. Plugin/Rest/TokenValidatorPlugin.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Plugin\Rest;

use CyberSource\SecureAcceptance\Service\Adminhtml\TokenService;
use Magento\Framework\Exception\LocalizedException;

/**
 * Validate tokens in REST calls
 */
class TokenValidatorPlugin
{
    /**
     * After plugin for generateToken
     */
    public function afterGenerateToken(TokenService $subject, $token)
    {
        if (!$this->validateToken($token)) {
            throw new LocalizedException(__('Generated token is invalid'));
        }

        return $token;
    }

    private function validateToken($token)
    {
        if (empty($token)) {
            return false;
        }

        // Validate JWT structure
        $parts = explode('.', $token);
        return count($parts) === 3;
    }
}
```

### **36. Plugin/Rest/ResponseSignaturePlugin.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Plugin\Rest;

/**
 * Sign REST responses
 */
class ResponseSignaturePlugin
{
    /**
     * Around plugin to add signature to response
     */
    public function aroundToArray($subject, \Closure $proceed)
    {
        $response = $proceed();

        // Add signature if success response
        if (isset($response['success']) && $response['success']) {
            $response['signature'] = $this->generateSignature($response);
        }

        return $response;
    }

    private function generateSignature($data)
    {
        // Generate HMAC signature for response integrity
        $payload = json_encode($data);
        return hash('sha256', $payload);
    }
}
```

---

## **PHASE 10: VIEW BLOCKS (3 FILES)**

### **37. Block/Adminhtml/Rest/FlexTokenDisplay.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Block\Adminhtml\Rest;

use Magento\Backend\Block\Template;
use CyberSource\SecureAcceptance\Helper\Rest\TokenDataExtractor;

/**
 * Display Flex token information
 */
class FlexTokenDisplay extends Template
{
    /**
     * @var TokenDataExtractor
     */
    private $tokenExtractor;

    public function __construct(
        Template\Context $context,
        TokenDataExtractor $tokenExtractor,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->tokenExtractor = $tokenExtractor;
    }

    /**
     * Get token data
     */
    public function getTokenData()
    {
        $token = $this->getRequest()->getParam('token');
        if (!$token) {
            return null;
        }

        return $this->tokenExtractor->extractCardData($token);
    }

    /**
     * Get token expiration
     */
    public function getTokenExpiration()
    {
        $token = $this->getRequest()->getParam('token');
        if (!$token) {
            return null;
        }

        return $this->tokenExtractor->extractExpiration($token);
    }

    protected function _toHtml()
    {
        return $this->_template ? parent::_toHtml() : '';
    }
}
```

### **38. Block/Adminhtml/Rest/SopFormDisplay.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Block\Adminhtml\Rest;

use Magento\Backend\Block\Template;
use CyberSource\SecureAcceptance\Api\Admin\SopRequestDataBuilderInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Display SOP form fields for REST
 */
class SopFormDisplay extends Template
{
    /**
     * @var SopRequestDataBuilderInterface
     */
    private $sopBuilder;

    public function __construct(
        Template\Context $context,
        SopRequestDataBuilderInterface $sopBuilder,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->sopBuilder = $sopBuilder;
    }

    /**
     * Get form fields
     */
    public function getFormFields()
    {
        try {
            $quoteId = (int)$this->getRequest()->getParam('quote_id');
            $ccType = $this->getRequest()->getParam('cc_type', 'visa');

            if (!$quoteId) {
                return [];
            }

            $result = $this->sopBuilder->buildRequestData($quoteId, $ccType);
            return $result['fields'] ?? [];
        } catch (LocalizedException $e) {
            return [];
        }
    }

    /**
     * Get form URL
     */
    public function getFormUrl()
    {
        return 'https://testsecureacceptance.cybersource.com/silent';
    }

    protected function _toHtml()
    {
        return $this->_template ? parent::_toHtml() : '';
    }
}
```

### **39. Block/Adminhtml/Rest/PaymentMethodInfo.php**
```php
<?php
namespace CyberSource\SecureAcceptance\Block\Adminhtml\Rest;

use Magento\Backend\Block\Template;

/**
 * Display payment method information for REST
 */
class PaymentMethodInfo extends Template
{
    /**
     * Get payment method label
     */
    public function getPaymentMethodLabel()
    {
        return 'CyberSource Secure Acceptance (REST)';
    }

    /**
     * Get API version
     */
    public function getApiVersion()
    {
        return 'V1';
    }

    /**
     * Get available endpoints
     */
    public function getAvailableEndpoints()
    {
        return [
            [
                'name' => 'Token Generation',
                'endpoint' => '/rest/V1/cybersource/admin/token/generate',
                'method' => 'POST'
            ],
            [
                'name' => 'SOP Request Data',
                'endpoint' => '/rest/V1/cybersource/admin/sop/request-data',
                'method' => 'POST'
            ],
            [
                'name' => 'Flex Place Order',
                'endpoint' => '/rest/V1/cybersource/admin/flex/place-order',
                'method' => 'POST'
            ],
            [
                'name' => 'Vault Token Delete',
                'endpoint' => '/rest/V1/cybersource/admin/vault/token/:id',
                'method' => 'DELETE'
            ]
        ];
    }

    protected function _toHtml()
    {
        return $this->_template ? parent::_toHtml() : '';
    }
}
```

---

## **PHASE 11: LAYOUT FILES (3 FILES)**

### **40. view/adminhtml/layout/sales_order_create_index.xml** (UPDATED)
```xml
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <body>
        <referenceBlock name="sales_order_create_billing_method">
            <block class="CyberSource\SecureAcceptance\Block\Adminhtml\Rest\FlexTokenDisplay"
                   name="cybersource_flex_token_display"
                   as="flex_token_display"
                   template="CyberSource_SecureAcceptance::payment/rest/flex_token.phtml"/>
            
            <block class="CyberSource\SecureAcceptance\Block\Adminhtml\Rest\SopFormDisplay"
                   name="cybersource_sop_form_display"
                   as="sop_form_display"
                   template="CyberSource_SecureAcceptance::payment/rest/sop_form.phtml"/>
            
            <block class="CyberSource\SecureAcceptance\Block\Adminhtml\Rest\PaymentMethodInfo"
                   name="cybersource_payment_method_info"
                   as="payment_method_info"
                   template="CyberSource_SecureAcceptance::payment/rest/payment_info.phtml"/>
        </referenceBlock>

        <!-- REST API Scripts -->
        <referenceBlock name="head">
            <block class="Magento\Framework\View\Element\Js\Components"
                   name="cybersource_rest_js_components"/>
        </referenceBlock>
    </body>
</page>
```

### **41. view/adminhtml/layout/sales_order_create_load_block_billing_method.xml** (UPDATED)
```xml
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <body>
        <referenceBlock name="sales_order_create_billing_method">
            <!-- REST payment blocks -->
            <block class="CyberSource\SecureAcceptance\Block\Adminhtml\Transparent\Form"
                   name="cybersource_transparent_form"
                   template="CyberSource_SecureAcceptance::payment/rest/sop_form.phtml"/>
            
            <block class="CyberSource\SecureAcceptance\Block\Adminhtml\Microform\Flex"
                   name="cybersource_flex_form"
                   template="CyberSource_SecureAcceptance::payment/rest/flex_token.phtml"/>
        </referenceBlock>
    </body>
</page>
```

### **42. view/adminhtml/layout/cybersource_rest_admin_payment.xml** (NEW)
```xml
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <body>
        <referenceBlock name="content">
            <block class="CyberSource\SecureAcceptance\Block\Adminhtml\Rest\PaymentMethodInfo"
                   name="cybersource_rest_payment_info"
                   template="CyberSource_SecureAcceptance::rest/payment_info.phtml"/>
        </referenceBlock>

        <!-- REST API Configuration -->
        <head>
            <link src="CyberSource_SecureAcceptance::web/css/rest/admin-payment.css"/>
            <script src="CyberSource_SecureAcceptance::web/js/rest/rest-client.js"/>
            <script src="CyberSource_SecureAcceptance::web/js/rest/token-generator.js"/>
            <script src="CyberSource_SecureAcceptance::web/js/rest/sop-request.js"/>
            <script src="CyberSource_SecureAcceptance::web/js/rest/flex-order.js"/>
            <script src="CyberSource_SecureAcceptance::web/js/rest/form-validator.js"/>
            <script src="CyberSource_SecureAcceptance::web/js/rest/response-handler.js"/>
        </head>
    </body>
</page>
```

---

## **PHASE 12: TEMPLATE FILES (6 FILES)**

### **43. view/adminhtml/templates/payment/rest/flex_token.phtml**
```php
<?php
/**
 * Flex Token Display Template (REST)
 */
?>
<div class="cybersource-flex-token-container">
    <h3><?php echo __('Flex Payment Token'); ?></h3>
    
    <div id="flex-token-info" class="flex-token-info">
        <div class="token-field">
            <label><?php echo __('Token:'); ?></label>
            <input type="hidden" id="flex-jwt-token" name="payment[flexJwt]" />
            <p id="token-status"><?php echo __('Generating token...'); ?></p>
        </div>
        
        <div class="card-info">
            <div class="field">
                <label><?php echo __('Card Type:'); ?></label>
                <select id="flex-cc-type" name="payment[cc_type]" class="required-entry">
                    <option value="">-- Please Select --</option>
                    <option value="001">Visa</option>
                    <option value="002">MasterCard</option>
                    <option value="003">American Express</option>
                    <option value="004">Discover</option>
                </select>
            </div>
            
            <div class="field">
                <label><?php echo __('Expiration Date:'); ?></label>
                <input type="text" id="flex-exp-date" name="payment[exp_date]" 
                       placeholder="MM/YYYY" class="required-entry validate-exp-date"/>
            </div>
        </div>
        
        <button type="button" id="generate-flex-token" class="action-primary">
            <?php echo __('Generate Token'); ?>
        </button>
    </div>
</div>

<script>
    require(['jquery', 'CyberSource_SecureAcceptance/rest/token-generator', 
             'CyberSource_SecureAcceptance/rest/form-validator'],
        function($, TokenGenerator, FormValidator) {
            var quoteId = <?php echo json_encode($block->getQuoteId() ?? 0); ?>;
            var tokenGenerator = new TokenGenerator({
                quoteId: quoteId,
                endpoint: '/rest/V1/cybersource/admin/token/generate'
            });
            
            $('#generate-flex-token').on('click', function() {
                tokenGenerator.generateToken();
            });
        }
    );
</script>
```

### **44. view/adminhtml/templates/payment/rest/sop_form.phtml**
```php
<?php
/**
 * SOP Form Display Template (REST)
 */
?>
<div class="cybersource-sop-form-container">
    <h3><?php echo __('Secure Order Post (SOP) Payment'); ?></h3>
    
    <form id="sop-payment-form" method="post" class="sop-form">
        <fieldset class="fieldset payment-form-content">
            <legend class="legend">
                <span><?php echo __('Payment Information'); ?></span>
            </legend>
            
            <div class="field required">
                <label for="sop-cc-type"><?php echo __('Card Type'); ?></label>
                <select id="sop-cc-type" name="cc_type" class="required-entry">
                    <option value="">-- Please Select --</option>
                    <option value="001">Visa</option>
                    <option value="002">MasterCard</option>
                    <option value="003">American Express</option>
                    <option value="004">Discover</option>
                </select>
            </div>
            
            <div id="sop-form-fields"></div>
            
            <div class="field">
                <label>
                    <input type="checkbox" id="sop-vault-enabled" name="vault_enabled" />
                    <span><?php echo __('Save for later use'); ?></span>
                </label>
            </div>
            
            <div class="actions-toolbar">
                <button type="button" id="generate-sop-request" class="action-primary">
                    <?php echo __('Generate SOP Request'); ?>
                </button>
            </div>
        </fieldset>
    </form>
</div>

<script>
    require(['jquery', 'CyberSource_SecureAcceptance/rest/sop-request'],
        function($, SopRequest) {
            var quoteId = <?php echo json_encode($block->getQuoteId() ?? 0); ?>;
            var sopRequest = new SopRequest({
                quoteId: quoteId,
                endpoint: '/rest/V1/cybersource/admin/sop/request-data'
            });
            
            $('#generate-sop-request').on('click', function() {
                sopRequest.buildRequestData();
            });
        }
    );
</script>
```

### **45. view/adminhtml/templates/payment/rest/payment_info.phtml**
```php
<?php
/**
 * Payment Method Info Template (REST)
 */
?>
<div class="cybersource-payment-info">
    <h4><?php echo __('Payment Method Information'); ?></h4>
    
    <div class="info-content">
        <p>
            <strong><?php echo __('Method:'); ?></strong>
            <?php echo __('CyberSource Secure Acceptance'); ?>
        </p>
        
        <p>
            <strong><?php echo __('API Version:'); ?></strong>
            REST V1
        </p>
        
        <p>
            <strong><?php echo __('Payment Options:'); ?></strong>
        </p>
        <ul>
            <li><?php echo __('Flex Microform (Token-based)'); ?></li>
            <li><?php echo __('Secure Order Post (SOP)'); ?></li>
            <li><?php echo __('Vault Token Management'); ?></li>
        </ul>
    </div>
</div>
```

### **46. view/adminhtml/templates/rest/token_response.phtml**
```php
<?php
/**
 * Token Response Template
 */
?>
<div id="token-response-container" class="token-response hidden">
    <div class="message message-success">
        <div class="message-content">
            <h4><?php echo __('Token Generated Successfully'); ?></h4>
            <p id="token-response-message"></p>
            <div id="token-details"></div>
        </div>
    </div>
</div>
```

### **47. view/adminhtml/templates/rest/form_fields.phtml**
```php
<?php
/**
 * Form Fields Template
 */
?>
<div id="form-fields-container" class="form-fields">
    <div id="hidden-fields">
        <!-- CyberSource SOP form fields will be injected here -->
    </div>
</div>
```

### **48. view/adminhtml/templates/rest/error_message.phtml**
```php
<?php
/**
 * Error Message Template
 */
?>
<div id="error-message-container" class="error-message hidden">
    <div class="message message-error">
        <div class="message-content">
            <strong><?php echo __('Error'); ?></strong>
            <p id="error-message-text"></p>
            <div id="error-details" class="error-details"></div>
        </div>
    </div>
</div>
```

---

## **PHASE 13: JAVASCRIPT FILES (6 FILES)**

### **49. view/adminhtml/web/js/rest/rest-client.js**
```javascript
/**
 * REST API Client Library
 */
define(['jquery'], function($) {
    'use strict';

    var RestClient = function(options) {
        this.options = options || {};
        this.baseUrl = this.options.baseUrl || '/rest/V1/cybersource/admin';
        this.timeout = this.options.timeout || 30000;
    };

    RestClient.prototype.post = function(endpoint, data) {
        return $.ajax({
            url: this.baseUrl + endpoint,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data),
            timeout: this.timeout,
            headers: {
                'Authorization': 'Bearer ' + (this.options.token || '')
            }
        });
    };

    RestClient.prototype.delete = function(endpoint, data) {
        return $.ajax({
            url: this.baseUrl + endpoint,
            type: 'DELETE',
            contentType: 'application/json',
            data: data ? JSON.stringify(data) : null,
            timeout: this.timeout
        });
    };

    RestClient.prototype.get = function(endpoint) {
        return $.ajax({
            url: this.baseUrl + endpoint,
            type: 'GET',
            timeout: this.timeout
        });
    };

    RestClient.prototype.handleError = function(xhr, status, error) {
        var errorMsg = 'An error occurred';
        
        if (xhr.responseJSON && xhr.responseJSON.error) {
            errorMsg = xhr.responseJSON.error;
        } else if (error) {
            errorMsg = error;
        }

        return {
            status: xhr.status,
            message: errorMsg,
            details: xhr.responseJSON
        };
    };

    return RestClient;
});
```

### **50. view/adminhtml/web/js/rest/token-generator.js**
```javascript
/**
 * Flex Token Generator
 */
define(['jquery', 'CyberSource_SecureAcceptance/rest/rest-client'], 
    function($, RestClient) {
    'use strict';

    var TokenGenerator = function(options) {
        this.options = options || {};
        this.quoteId = options.quoteId;
        this.endpoint = options.endpoint || '/token/generate';
        this.client = new RestClient(options);
    };

    TokenGenerator.prototype.generateToken = function() {
        var self = this;
        
        $('#token-status').text('Generating token...');

        this.client.post(this.endpoint, {
            quote_id: this.quoteId,
            store_id: this.options.storeId || null
        }).done(function(response) {
            if (response.success) {
                $('#flex-jwt-token').val(response.token);
                $('#token-status').text('Token generated successfully');
                $('#token-status').attr('class', 'success');
                
                self.showTokenDetails(response);
            } else {
                $('#token-status').text('Token generation failed: ' + response.error);
                $('#token-status').attr('class', 'error');
            }
        }).fail(function(xhr) {
            var error = self.client.handleError(xhr);
            $('#token-status').text('Error: ' + error.message);
            $('#token-status').attr('class', 'error');
        });
    };

    TokenGenerator.prototype.showTokenDetails = function(response) {
        var html = '<div class="token-details">' +
                   '<p><strong>Client Library:</strong> ' + response.client_library + '</p>' +
                   '<p><strong>Integrity Hash:</strong> ' + response.client_integrity + '</p>' +
                   '<p><strong>Expires In:</strong> 15 minutes</p>' +
                   '</div>';
        
        $('#flex-token-info').append(html);
    };

    return TokenGenerator;
});
```

### **51. view/adminhtml/web/js/rest/sop-request.js**
```javascript
/**
 * SOP Request Builder
 */
define(['jquery', 'CyberSource_SecureAcceptance/rest/rest-client'], 
    function($, RestClient) {
    'use strict';

    var SopRequest = function(options) {
        this.options = options || {};
        this.quoteId = options.quoteId;
        this.endpoint = options.endpoint || '/sop/request-data';
        this.client = new RestClient(options);
    };

    SopRequest.prototype.buildRequestData = function() {
        var self = this;
        var ccType = $('#sop-cc-type').val();
        var vaultEnabled = $('#sop-vault-enabled').is(':checked');

        if (!ccType) {
            alert('Please select a card type');
            return;
        }

        this.client.post(this.endpoint, {
            quote_id: this.quoteId,
            cc_type: ccType,
            vault_enabled: vaultEnabled,
            store_id: this.options.storeId || null
        }).done(function(response) {
            if (response.success) {
                self.displayFormFields(response.fields);
            } else {
                alert('Error: ' + response.error);
            }
        }).fail(function(xhr) {
            var error = self.client.handleError(xhr);
            alert('Error: ' + error.message);
        });
    };

    SopRequest.prototype.displayFormFields = function(fields) {
        var html = '';
        
        for (var key in fields) {
            if (fields.hasOwnProperty(key)) {
                html += '<input type="hidden" name="' + key + '" value="' + 
                        escapeHtml(fields[key]) + '" />';
            }
        }

        $('#sop-form-fields').html(html);
    };

    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    return SopRequest;
});
```

### **52. view/adminhtml/web/js/rest/flex-order.js**
```javascript
/**
 * Flex Order Placement
 */
define(['jquery', 'CyberSource_SecureAcceptance/rest/rest-client'], 
    function($, RestClient) {
    'use strict';

    var FlexOrder = function(options) {
        this.options = options || {};
        this.quoteId = options.quoteId;
        this.endpoint = options.endpoint || '/flex/place-order';
        this.client = new RestClient(options);
    };

    FlexOrder.prototype.placeOrder = function(token, cardData) {
        var self = this;

        this.client.post(this.endpoint, {
            quote_id: this.quoteId,
            token: token,
            cc_type: cardData.ccType,
            exp_date: cardData.expDate,
            masked_pan: cardData.maskedPan,
            order_data: this.options.orderData || {}
        }).done(function(response) {
            if (response.success) {
                self.onSuccess(response);
            } else {
                self.onError(response.error);
            }
        }).fail(function(xhr) {
            var error = self.client.handleError(xhr);
            self.onError(error.message);
        });
    };

    FlexOrder.prototype.onSuccess = function(response) {
        if (this.options.onSuccess) {
            this.options.onSuccess(response);
        }
        
        // Redirect to order view
        if (response.redirect_url) {
            window.location.href = response.redirect_url;
        }
    };

    FlexOrder.prototype.onError = function(message) {
        if (this.options.onError) {
            this.options.onError(message);
        }

        alert('Error: ' + message);
    };

    return FlexOrder;
});
```

### **53. view/adminhtml/web/js/rest/form-validator.js**
```javascript
/**
 * Form Validation for REST
 */
define(['jquery'], function($) {
    'use strict';

    var FormValidator = function(options) {
        this.options = options || {};
        this.errors = [];
    };

    FormValidator.prototype.validateCardData = function(cardData) {
        this.errors = [];

        if (!cardData.ccType || cardData.ccType === '') {
            this.errors.push('Card type is required');
        }

        if (!cardData.expDate || cardData.expDate === '') {
            this.errors.push('Expiration date is required');
        } else if (!this.isValidExpDate(cardData.expDate)) {
            this.errors.push('Expiration date format is invalid (MM/YYYY)');
        }

        if (!cardData.maskedPan || cardData.maskedPan === '') {
            this.errors.push('Card number is required');
        }

        return this.errors.length === 0;
    };

    FormValidator.prototype.isValidExpDate = function(expDate) {
        var pattern = /^\d{2}\/\d{4}$/;
        return pattern.test(expDate);
    };

    FormValidator.prototype.validateToken = function(token) {
        if (!token || token === '') {
            this.errors.push('Token is required');
            return false;
        }

        // Check JWT structure
        var parts = token.split('.');
        if (parts.length !== 3) {
            this.errors.push('Invalid token format');
            return false;
        }

        return true;
    };

    FormValidator.prototype.getErrors = function() {
        return this.errors;
    };

    FormValidator.prototype.displayErrors = function(container) {
        var html = '<ul>';
        this.errors.forEach(function(error) {
            html += '<li>' + error + '</li>';
        });
        html += '</ul>';

        $(container).html(html).show();
    };

    return FormValidator;
});
```

### **54. view/adminhtml/web/js/rest/response-handler.js**
```javascript
/**
 * Response Handler for REST
 */
define(['jquery'], function($) {
    'use strict';

    var ResponseHandler = function(options) {
        this.options = options || {};
    };

    ResponseHandler.prototype.handleSuccess = function(response, callback) {
        console.log('Success response:', response);

        if (response.order_id) {
            // Order created successfully
            this.showSuccessMessage(
                'Order created successfully. Order ID: ' + response.increment_id
            );

            if (callback) {
                callback(response);
            }

            // Redirect after delay
            if (response.redirect_url) {
                setTimeout(function() {
                    window.location.href = response.redirect_url;
                }, 2000);
            }
        }
    };

    ResponseHandler.prototype.handleError = function(error, callback) {
        console.error('Error response:', error);

        this.showErrorMessage(error.message || error);

        if (callback) {
            callback(error);
        }
    };

    ResponseHandler.prototype.showSuccessMessage = function(message) {
        var html = '<div class="message message-success">' +
                   '<div>' + message + '</div>' +
                   '</div>';

        $('#messages').html(html);
    };

    ResponseHandler.prototype.showErrorMessage = function(message) {
        var html = '<div class="message message-error">' +
                   '<div>' + message + '</div>' +
                   '</div>';

        $('#messages').html(html);
    };

    return ResponseHandler;
});
```

---

## **PHASE 14: CSS FILES (2 FILES)**

### **55. view/adminhtml/web/css/rest/admin-payment.css**
```css
/**
 * CyberSource REST Admin Payment Styling
 */

.cybersource-flex-token-container {
    background: #f5f5f5;
    border: 1px solid #ddd;
    padding: 15px;
    margin: 15px 0;
    border-radius: 3px;
}

.cybersource-flex-token-container h3 {
    margin-top: 0;
    color: #333;
    font-size: 16px;
    font-weight: 600;
}

.flex-token-info {
    margin-top: 15px;
}

.token-field {
    margin-bottom: 15px;
}

.token-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.token-field input[type="hidden"] {
    display: none;
}

#token-status {
    font-size: 13px;
    color: #666;
    margin: 5px 0;
}

#token-status.success {
    color: #00a000;
    font-weight: 600;
}

#token-status.error {
    color: #dd0000;
    font-weight: 600;
}

.card-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin: 15px 0;
}

.field {
    display: flex;
    flex-direction: column;
}

.field label {
    margin-bottom: 5px;
    font-weight: 500;
}

.field input,
.field select {
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 3px;
    font-size: 13px;
}

.field input:focus,
.field select:focus {
    outline: none;
    border-color: #2b86c4;
    box-shadow: 0 0 5px rgba(43, 134, 196, 0.2);
}

#generate-flex-token,
#generate-sop-request {
    background-color: #1f7e79;
    color: white;
    border: none;
    padding: 10px 20px;
    cursor: pointer;
    border-radius: 3px;
    font-size: 13px;
    font-weight: 600;
    margin-top: 15px;
}

#generate-flex-token:hover,
#generate-sop-request:hover {
    background-color: #1a6b66;
}

.cybersource-sop-form-container {
    background: #f5f5f5;
    border: 1px solid #ddd;
    padding: 15px;
    margin: 15px 0;
    border-radius: 3px;
}

.cybersource-sop-form-container h3 {
    margin-top: 0;
    color: #333;
    font-size: 16px;
    font-weight: 600;
}

.sop-form {
    margin-top: 15px;
}

.fieldset {
    border: none;
    padding: 0;
    margin: 0;
}

.legend {
    font-size: 14px;
    font-weight: 600;
    padding: 0;
    margin-bottom: 15px;
}

.token-details {
    background: #e8f4f8;
    border-left: 4px solid #2b86c4;
    padding: 10px 15px;
    margin-top: 10px;
    border-radius: 3px;
}

.token-details p {
    margin: 5px 0;
    font-size: 13px;
}

.error-message {
    background: #fef3cd;
    border: 1px solid #ffc107;
    color: #856404;
    padding: 12px;
    margin: 10px 0;
    border-radius: 3px;
}

.error-message.hidden {
    display: none;
}

.message-success {
    background: #d4edda;
    border: 1px solid #28a745;
    color: #155724;
    padding: 12px;
    margin: 10px 0;
    border-radius: 3px;
}

.cybersource-payment-info {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    padding: 15px;
    margin: 10px 0;
    border-radius: 3px;
}

.cybersource-payment-info h4 {
    margin-top: 0;
    color: #333;
    font-size: 14px;
}

.info-content p {
    margin: 8px 0;
    font-size: 13px;
}

.info-content ul {
    margin: 10px 0 10px 20px;
    padding: 0;
}

.info-content li {
    margin: 4px 0;
    font-size: 13px;
}
```

### **56. view/adminhtml/web/css/rest/form-display.css**
```css
/**
 * REST Form Display Styles
 */

#sop-form-fields {
    display: none;
}

#sop-form-fields.active {
    display: block;
}

.payment-form-content {
    margin: 15px 0;
}

.payment-form-content .field {
    margin-bottom: 15px;
}

.payment-form-content label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
}

.payment-form-content input[type="text"],
.payment-form-content input[type="email"],
.payment-form-content input[type="password"],
.payment-form-content select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 3px;
    font-size: 13px;
    box-sizing: border-box;
}

.payment-form-content input[type="text"]:focus,
.payment-form-content select:focus {
    outline: none;
    border-color: #2b86c4;
    box-shadow: 0 0 5px rgba(43, 134, 196, 0.2);
}

.payment-form-content input[type="checkbox"] {
    margin-right: 5px;
}

.payment-form-content .required::after {
    content: ' *';
    color: #dd0000;
}

.actions-toolbar {
    margin-top: 15px;
    display: flex;
    gap: 10px;
}

.actions-toolbar button {
    padding: 10px 20px;
    border-radius: 3px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    border: none;
}

.action-primary {
    background-color: #1f7e79;
    color: white;
}

.action-primary:hover {
    background-color: #1a6b66;
}

.action-secondary {
    background-color: #6c757d;
    color: white;
}

.action-secondary:hover {
    background-color: #5a6268;
}

.hidden-fields {
    display: none;
}

.token-response.hidden {
    display: none;
}

.error-message.hidden {
    display: none;
}
```

---

## **PHASE 15: CONFIGURATION FILES (7 FILES)**

### **57. etc/webapi.xml** (NEW)
```xml
<?xml version="1.0"?>
<routes xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Webapi:etc/webapi.xsd">
    
    <!-- Token Generation Endpoint -->
    <route url="/V1/cybersource/admin/token/generate" method="POST">
        <service class="CyberSource\SecureAcceptance\Api\Admin\TokenGeneratorInterface" 
                 method="generateToken"/>
        <resources>
            <resource ref="Magento_Sales::create"/>
            <resource ref="Magento_Sales::actions_edit"/>
        </resources>
        <data>
            <parameter name="store_id" force="true">%store_id%