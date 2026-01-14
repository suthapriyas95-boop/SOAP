# Admin Order Code Documentation - module-secure-acceptance

This document provides a comprehensive overview of all Admin Order-related code in the `module-secure-acceptance` payment module.

---

## Overview

The Admin Order functionality in this module allows administrators to create orders directly from the Magento backend using the CyberSource Secure Acceptance payment gateway. The module supports two main payment methods:

1. **Flex Microform** - Modern payment form using tokens
2. **Transparent/SOP (Silent Order Post)** - Legacy payment method with transparent redirect

---

## Core Admin Order Files

### 1. **Controller/Adminhtml/Microform/FlexPlaceOrder.php**
**Purpose**: Handles order placement from admin dashboard using Flex Microform payment method.

**Key Features**:
- Extends `\Magento\Sales\Controller\Adminhtml\Order`
- Processes flex token data and creates orders
- Handles JWT token processing for card data
- Validates form keys and request methods
- Manages quote to order conversion

**Key Methods**:
- `execute()` - Main entry point that processes flex payment and creates order
- `getErrorResponse()` - Returns error JSON response

**Constants**:
```php
const KEY_FLEX_TOKEN = 'token';
const KEY_CARD_TYPE = 'ccType';
const KEY_EXP_DATE = 'expDate';
const KEY_FLEX_MASKED_PAN = 'maskedPan';
```

**Key Dependencies**:
- `Magento\Payment\Gateway\Command\CommandManagerInterface` - Command execution
- `Magento\Quote\Model\QuoteRepository` - Quote management
- `Magento\Backend\Model\Session\Quote` - Admin session management
- `Magento\Sales\Api\OrderRepositoryInterface` - Order persistence
- `CyberSource\SecureAcceptance\Model\Jwt\JwtProcessorInterface` - JWT token processing

---

### 2. **Controller/Adminhtml/Microform/TokenRequest.php**
**Purpose**: Generates Flex Microform tokens for admin payment processing.

**Key Features**:
- Implements token generation command
- Works with backend quote session
- Validates form keys
- Returns JSON response with token and place order URL

**Key Methods**:
- `execute()` - Generates token for flex payment
- `getErrorResponse()` - Returns error JSON

**Flow**:
```
POST Request -> Quote retrieval -> Token generation -> Quote save -> JSON Response
```

---

### 3. **Controller/Adminhtml/Transparent/RequestSilentData.php**
**Purpose**: Builds and returns Silent Order Post (SOP) request data for transparent payment processing.

**Key Features**:
- Handles legacy SOP payment requests from admin
- Manages vault options (save card for future use)
- Builds request data for CyberSource
- Validates form keys and quotes

**Key Methods**:
- `execute()` - Main entry point for building SOP request

**Key Dependencies**:
- `CyberSource\SecureAcceptance\Helper\RequestDataBuilder` - Builds request data
- `Magento\Backend\Model\Session\Quote` - Admin quote session
- `CyberSource\SecureAcceptance\Helper\Vault` - Vault management

---

### 4. **Controller/Adminhtml/Transparent/Response.php**
**Purpose**: Processes CyberSource SOP response and creates the order after payment authorization.

**Key Features**:
- Validates CyberSource response signature
- Converts response data to Magento order
- Handles gift messages
- Manages order creation via AdminOrder\Create model
- Supports both redirect and iframe responses

**Key Methods**:
- `execute()` - Main entry point for processing payment response
- `isResponseRedirect()` - Determines response type
- `isValidSignature()` - Validates CyberSource signature
- `substitutePostWithOrderData()` - Maps CyberSource response to Magento order

**Critical Code Flow**:
```
1. Validate signature
2. Substitute POST data with stored order data
3. Get admin order create model
4. Set payment method and data
5. Process gift messages
6. Recollect cart and save quote
7. Create order with validation
8. Return success or error response
```

---

## Admin Blocks

### 1. **Block/Adminhtml/Transparent/Form.php**
**Purpose**: Renders the payment form block in admin order creation.

**Key Features**:
- Extends `\Magento\Payment\Block\Adminhtml\Transparent\Form`
- Prepares layout with "Save for Later" child block
- Injects payment method configuration

**Code**:
```php
namespace CyberSource\SecureAcceptance\Block\Adminhtml\Transparent;

class Form extends \Magento\Payment\Block\Adminhtml\Transparent\Form
{
    private $config;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Payment\Model\Config $paymentConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \CyberSource\SecureAcceptance\Gateway\Config\Config $config,
        array $data = []
    ) {
        $this->config = $config;
        parent::__construct($context, $paymentConfig, $checkoutSession, $data);
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->addSaveForLaterChild();
        return $this;
    }

    public function setMethod(\Magento\Payment\Model\MethodInterface $method)
    {
        if ($saveForLaterBlock = $this->getChildBlock('field_save_for_later')) {
            $saveForLaterBlock->setMethod($method);
        }
        return parent::setMethod($method);
    }

    private function addSaveForLaterChild()
    {
        $this->addChild(
            'field_save_for_later',
            \CyberSource\SecureAcceptance\Block\Adminhtml\Transparent\Form\SaveForLater::class,
            [
                'template' => 'CyberSource_SecureAcceptance::payment/save_for_later.phtml',
            ]
        );
    }
}
```

---

### 2. **Block/Adminhtml/Transparent/Form/SaveForLater.php**
**Purpose**: Manages vault (save for later) option display in admin payment form.

**Key Features**:
- Extends admin transparent form
- Checks admin vault configuration
- Controls visibility of "Save for Later" field

**Code**:
```php
namespace CyberSource\SecureAcceptance\Block\Adminhtml\Transparent\Form;

class SaveForLater extends \Magento\Payment\Block\Adminhtml\Transparent\Form
{
    private $config;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Payment\Model\Config $paymentConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \CyberSource\SecureAcceptance\Gateway\Config\Config $config,
        array $data = []
    ) {
        parent::__construct($context, $paymentConfig, $checkoutSession, $data);
        $this->config = $config;
    }

    public function isAdminVaultEnabled($storeId = null)
    {
        return $this->config->isVaultEnabledConfiguredOption($storeId) && 
               $this->config->isVaultEnabledAdmin($storeId);
    }
}
```

---

### 3. **Block/Adminhtml/Microform/Flex.php**
**Purpose**: Manages Flex Microform client library integration in admin orders.

**Key Features**:
- Retrieves and renders client library URLs
- Manages integrity hashes for client library security
- Works with backend quote sessions
- Handles sandbox/production environments

**Code**:
```php
namespace CyberSource\SecureAcceptance\Block\Adminhtml\Microform;

use Magento\Framework\View\Element\Template;
use CyberSource\SecureAcceptance\Gateway\Config\Config;
use CyberSource\SecureAcceptance\Service\Adminhtml\TokenService;
use Magento\Backend\Model\Session\Quote as BackendQuoteSession;
use CyberSource\Core\Model\LoggerInterface;

class Flex extends Template
{
    private $config;
    private $tokenService;
    private $backendQuoteSession;
    private $logger;

    public function __construct(
        Template\Context $context,
        Config $config,
        TokenService $tokenService,
        BackendQuoteSession $backendQuoteSession,      
        LoggerInterface $logger,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->tokenService = $tokenService;
        $this->backendQuoteSession = $backendQuoteSession;
        $this->_logger = $logger;
    }

    public function isSandbox()
    {
        return $this->config->isTestMode();
    }

    public function getClientIntegrity()
    {
        $quote = $this->backendQuoteSession->getQuote();
        if (!$quote || !$quote->getId()) {
            return null;
        }
        $this->tokenService->generateToken();
        $extension = $quote->getExtensionAttributes();
        $clientLibraryIntegrity = $extension ? $extension->getClientLibraryIntegrity() : null;
        $this->_logger->info('Retrieved clientLibraryIntegrity: ' . ($clientLibraryIntegrity ?? 'NULL'));
        return $clientLibraryIntegrity ?? '';
    }

    public function getClientLibrary()
    {
        $quote = $this->backendQuoteSession->getQuote();
        if (!$quote || !$quote->getId()) {
            return null;
        }
        $this->tokenService->generateToken();
        $extension = $quote->getExtensionAttributes();
        $clientLibrary = $extension ? $extension->getClientLibrary() : null;
        $this->_logger->info('Retrieved clientLibrary: ' . ($clientLibrary ?? 'NULL'));
        return $clientLibrary ?? '';
    }

    public function getProductionClientLibrary()
    {
        $quote = $this->backendQuoteSession->getQuote();
        if (!$quote || !$quote->getId()) {
            return null;
        }
        $this->tokenService->generateToken();
        $extension = $quote->getExtensionAttributes();
        $clientLibraryProd = $extension ? $extension->getClientLibrary() : null;
        $this->_logger->info('Retrieved production clientLibrary: ' . ($clientLibraryProd ?? 'NULL'));
        return $clientLibraryProd ?? '';
    }
}
```

---

## Admin Services

### 1. **Service/Adminhtml/TokenService.php**
**Purpose**: Generates Flex Microform tokens for admin payment processing.

**Key Features**:
- Executes token generation commands
- Manages quote extensions with client library data
- Handles JWT decode operations
- Supports both sandbox and production

**Code**:
```php
namespace CyberSource\SecureAcceptance\Service\Adminhtml;

use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Framework\Url\DecoderInterface;
use Magento\Backend\Model\Session\Quote as BackendQuoteSession;
use Magento\Quote\Model\QuoteRepository;
use CyberSource\Core\Model\LoggerInterface;
use CyberSource\SecureAcceptance\Gateway\Config\Config;

class TokenService
{
    const COMMAND_CODE = 'generate_flex_key';

    private $commandManager;
    private $urlDecoder;
    private $backendQuoteSession;
    private $quoteRepository;
    private $logger;
    private $config;

    public function __construct(
        CommandManagerInterface $commandManager,
        DecoderInterface $urlDecoder,
        BackendQuoteSession $backendQuoteSession,
        QuoteRepository $quoteRepository,
        LoggerInterface $logger,
        Config $config
    ) {
        $this->commandManager = $commandManager;
        $this->urlDecoder = $urlDecoder;
        $this->backendQuoteSession = $backendQuoteSession;
        $this->quoteRepository = $quoteRepository;
        $this->logger = $logger;
        $this->config = $config;
    }

    public function generateToken()
    {
        $quote = $this->backendQuoteSession->getQuote();

        if (!$quote || !$quote->getId()) {
            $this->logger->warning('Cart is empty or unable to load cart data.');
            return;
        }

        if ($this->config->isMicroform()) {
            $commandResult = $this->commandManager->executeByCode(
                self::COMMAND_CODE,
                $quote->getPayment()
            );

            if (is_object($commandResult) && method_exists($commandResult, 'get')) {
                $commandData = $commandResult->get();
            } else {
                $commandData = $commandResult;
            }
            $this->quoteRepository->save($quote);

            if (!isset($commandData['response'])) {
                return ['error' => __('Invalid response from token generation.')];
            }

            $captureContextValue = $commandData['response'];
            $decodedCaptureResponse = json_decode($this->urlDecoder->decode(explode('.', $captureContextValue)[1]));
            $ctxData = $decodedCaptureResponse->ctx[0]->data ?? null;

            if ($ctxData) {
                $quoteExtension = $quote->getExtensionAttributes();
                if (!$quoteExtension) {
                    $quoteExtension = $this->quoteRepository->create();
                }
                
                if (!$quoteExtension->getClientLibraryIntegrity()) {
                    $quoteExtension->setClientLibraryIntegrity($ctxData->clientLibraryIntegrity ?? null);
                }
                
                if (!$quoteExtension->getClientLibrary()) {
                    $quoteExtension->setClientLibrary($ctxData->clientLibrary ?? null);
                }

                $quote->setExtensionAttributes($quoteExtension);
                $this->quoteRepository->save($quote);
            }
        } else {
            return true;
        }
    }
}
```

---

## Configuration Classes

### 1. **Gateway/Config/Config.php** (Relevant Admin Methods)

**Key Admin-Related Methods**:

```php
/**
 * Check if vault is enabled for admin
 */
public function isVaultEnabledAdmin($storeId = null)
{
    $this->setMethodCode(ConfigProvider::CC_VAULT_CODE);
    $value = $this->getValue(self::KEY_VAULT_ADMIN_ENABLE, $storeId);
    $this->setMethodCode(ConfigProvider::CODE);
    return $value;
}

/**
 * Determine if in legacy mode for admin orders
 */
public function getIsLegacyMode($storeId = null)
{
    $flowType = $this->getValue(self::KEY_FLOW_MODE, $storeId);
    if ($flowType == self::SA_FLOW || $this->isAdmin()) {
        return true;
    }
    return false;
}

/**
 * Check if currently in admin area
 */
public function isAdmin()
{
    try {
        return $this->appState->getAreaCode() === \Magento\Framework\App\Area::AREA_ADMINHTML;
    } catch (\Exception $e) {
        // Returns false if area code not set
    }
    return false;
}

/**
 * Check if using microform
 */
public function isMicroform($storeId = null)
{
    return $this->getSaType($storeId) == \CyberSource\Core\Model\Source\SecureAcceptance\Type::SA_FLEX_MICROFORM;
}
```

**Admin Configuration Constants**:
```php
const KEY_VAULT_ADMIN_ENABLE = 'active_admin';
const KEY_VAULT_ADMIN_ENABLE_CVV = 'enable_admin_cvv';
```

---

### 2. **Gateway/Config/PlaceOrderUrlHandler.php**
**Purpose**: Determines the correct place order URL for admin vs. frontend.

**Key Features**:
- Checks if order is being placed from admin
- Returns admin-specific or frontend URL

**Code**:
```php
namespace CyberSource\SecureAcceptance\Gateway\Config;

class PlaceOrderUrlHandler implements \Magento\Payment\Gateway\Config\ValueHandlerInterface
{
    private $isAdmin;

    public function __construct(
        bool $isAdmin = false
    ) {
        $this->isAdmin = $isAdmin;
    }

    public function handle(array $subject, $storeId = null)
    {
        if ($this->isAdmin) {
            return 'chcybersource/microform/flexPlaceOrder';
        }
        // return frontend URL
    }
}
```

---

### 3. **Gateway/Config/CgiUrlHandler.php**
**Purpose**: Determines CGI URL based on admin/frontend context.

**Key Code**:
```php
public function handle(array $subject, $storeId = null)
{
    if (!$this->config->getIsLegacyMode() && !$this->isAdmin) {
        // Use Checkout API for frontend
    }
    // Use SOP for admin
}
```

---

## Admin Plugins

### 1. **Plugin/Controller/Index/PlaceOrderPlugin.php**
**Purpose**: Plugin for handling failed order placement state in admin.

**Key Features**:
- Hooks into `PlaceOrder` controller's `afterUpdateFailedState`
- Converts failed orders back to quotes
- Preserves cart items in case of payment failure

**Code**:
```php
namespace CyberSource\SecureAcceptance\Plugin\Controller\Index;

class PlaceOrderPlugin
{
    private $cartRepository;
    private $orderToQuote;
    private $backpostDetector;
    private $messageManager;
    private $checkoutSession;

    public function afterUpdateFailedState(
        \CyberSource\SecureAcceptance\Controller\Index\PlaceOrder $subject,
        $result,
        $order
    ) {
        try {
            $quote = $this->checkoutSession->getQuote();

            if ($quote->hasItems() || $this->backpostDetector->isBackpost()) {
                return;
            }

            $quote = $this->orderToQuote->convertOrderToQuote($order->getId(), $quote);
            $this->cartRepository->save($quote);
            $this->checkoutSession->setQuoteId($quote->getId());
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
    }
}
```

---

### 2. **Plugin/Controller/Cards/DeleteTokenPlugin.php**
**Purpose**: Plugin for deleting payment tokens from CyberSource when deleted from admin vault.

**Key Features**:
- Intercepts vault card deletion
- Calls CyberSource SOAP API to delete token
- Validates deletion response

**Code**:
```php
namespace CyberSource\SecureAcceptance\Plugin\Controller\Cards;

class DeleteTokenPlugin
{
    private $paymentTokenManagement;
    private $customerSession;
    private $requestDataBuilder;
    private $cyberSourceSoapApi;
    private $redirect;
    private $messageManager;
    private $response;

    public function aroundExecute(\Magento\Vault\Controller\Cards\DeleteAction $subject, \Closure $proceed)
    {
        $request = $subject->getRequest();
        $paymentToken = $this->getPaymentToken($request);

        if ($paymentToken !== null && !empty($paymentToken->getData())) {
            if ($paymentToken->getPaymentMethodCode() != \CyberSource\SecureAcceptance\Model\Ui\ConfigProvider::CODE) {
                return $proceed();
            }

            $requestData = $this->requestDataBuilder->buildDeleteTokenRequest($paymentToken);
            $result = $this->cyberSourceSoapApi->run($requestData);

            if ($result && $result->reasonCode !== 100) {
                $this->messageManager->addErrorMessage(__('Deletion failure. Please try again. Error: '. $result->reasonCode));
                $this->redirect->redirect($subject->getResponse(), 'vault/cards/listaction');
            } else {
                return $proceed();
            }
        }
    }

    private function getPaymentToken(Http $request)
    {
        $publicHash = $request->getPostValue(PaymentTokenInterface::PUBLIC_HASH);

        if ($publicHash === null) {
            return null;
        }

        return $this->paymentTokenManagement->getByPublicHash(
            $publicHash,
            $this->customerSession->getCustomerId()
        );
    }
}
```

---

## Admin Configuration (XML)

### **etc/adminhtml/system.xml** (Payment Settings)

The system configuration defines admin order payment settings:

```xml
<field id="active" translate="label" type="select" sortOrder="1" showInDefault="1" showInWebsite="0" showInStore="1">
    <label>Enabled</label>
    <source_model>Magento\Config\Model\Config\Source\Yesno</source_model>
    <config_path>payment/chcybersource/active</config_path>
</field>

<field id="active_admin" translate="label" type="select" sortOrder="1" showInDefault="1" showInWebsite="0" showInStore="1">
    <label>Enable for Admin Orders</label>
    <source_model>Magento\Config\Model\Config\Source\Yesno</source_model>
    <config_path>payment/chcybersource_vault/active_admin</config_path>
</field>

<field id="enable_admin_cvv" translate="label" type="select" sortOrder="2" showInDefault="1" showInWebsite="0" showInStore="1">
    <label>Enable CVV for Admin Orders</label>
    <source_model>Magento\Config\Model\Config\Source\Yesno</source_model>
    <config_path>payment/chcybersource_vault/enable_admin_cvv</config_path>
</field>
```

---

### **etc/adminhtml/di.xml** (Dependency Injection)

Key admin-specific DI configuration:

```xml
<!-- Flex Microform Admin Controller -->
<type name="CyberSource\SecureAcceptance\Controller\Adminhtml\Microform\FlexPlaceOrder">
    <arguments>
        <argument name="sessionManager" xsi:type="object">Magento\Checkout\Model\Session</argument>
        <argument name="paymentMethod" xsi:type="object">CyberSourceSAGatewayFacade</argument>
    </arguments>
</type>

<!-- SOP Admin Controller -->
<type name="CyberSource\SecureAcceptance\Controller\Adminhtml\Transparent\RequestSilentData">
    <arguments>
        <argument name="sessionManager" xsi:type="object">Magento\Backend\Model\Session\Quote</argument>
        <argument name="paymentMethod" xsi:type="object">CyberSourceSAGatewayFacade</argument>
    </arguments>
</type>

<!-- SOP Response Controller -->
<type name="CyberSource\SecureAcceptance\Controller\Adminhtml\Transparent\Response">
    <arguments>
        <argument name="sessionManager" xsi:type="object">Magento\Backend\Model\Session\Quote</argument>
    </arguments>
</type>

<!-- Vault for Admin -->
<type name="CyberSource\SecureAcceptance\Gateway\Request\VaultAuthorizationRequest">
    <arguments>
        <argument name="isAdmin" xsi:type="boolean">true</argument>
    </arguments>
</type>

<!-- Place Order URL Handler -->
<type name="CyberSource\SecureAcceptance\Gateway\Config\PlaceOrderUrlHandler">
    <arguments>
        <argument name="isAdmin" xsi:type="boolean">true</argument>
    </arguments>
</type>
```

---

## Admin Order Flow Diagrams

### **Flex Microform Admin Order Flow**:

```
1. Admin accesses "Create Order" page
2. Flex block (Block/Adminhtml/Microform/Flex.php) loads
3. TokenService generates flex token (Service/Adminhtml/TokenService.php)
4. Token stored in quote extension attributes
5. Admin submits payment form
6. FlexPlaceOrder controller receives request
7. JWT processor extracts card data from token
8. Quote converted to order via QuoteManagement
9. Order saved and success response returned
```

### **SOP (Transparent) Admin Order Flow**:

```
1. Admin accesses "Create Order" page
2. Form block (Block/Adminhtml/Transparent/Form.php) loads
3. Admin selects card type and submits form
4. RequestSilentData controller builds SOP request
5. Request data stored in session
6. Admin redirected to CyberSource SOP form
7. Admin completes payment on CyberSource
8. Response controller (Response.php) receives postback
9. Signature validated
10. Order data substituted with stored session data
11. AdminOrder/Create model creates order
12. Order saved and success message displayed
```

---

## Key Admin Features

| Feature | Implementation |
|---------|-----------------|
| **Admin Vault Support** | `Config::isVaultEnabledAdmin()` checks `active_admin` setting |
| **CVV for Admin** | `Config::KEY_VAULT_ADMIN_ENABLE_CVV` configuration |
| **Microform in Admin** | `Block/Adminhtml/Microform/Flex.php` with `TokenService` |
| **SOP in Admin** | `Block/Adminhtml/Transparent/Form.php` with `RequestSilentData` |
| **Token Management** | `Plugin/Controller/Cards/DeleteTokenPlugin.php` |
| **Admin Area Detection** | `Config::isAdmin()` checks `AREA_ADMINHTML` |
| **Order Creation** | Uses Magento native `AdminOrder\Create` model |

---

## Additional Critical Admin Components

### **Plugin/Model/Quote/Payment/ToOrderPaymentPlugin.php**
**Purpose**: Converts quote payment data to order payment during admin order creation.

**Key Features**:
- Implements `afterConvert` hook
- Removes sensitive CVV data before order creation
- Preserves other payment attributes

**Code**:
```php
namespace CyberSource\SecureAcceptance\Plugin\Model\Quote\Payment;

class ToOrderPaymentPlugin
{
    /**
     * Plugin method that converts CcaResponse extension attribute 
     * from Quote Payment to Order Payment model
     *
     * @param \Magento\Quote\Model\Quote\Payment\ToOrderPayment $subject
     * @param \Magento\Sales\Api\Data\OrderPaymentInterface $result
     * @param \Magento\Quote\Model\Quote\Payment $quotePayment
     * @param array $data
     *
     * @return \Magento\Sales\Api\Data\OrderPaymentInterface
     */
    public function afterConvert(
        \Magento\Quote\Model\Quote\Payment\ToOrderPayment $subject,
        \Magento\Sales\Api\Data\OrderPaymentInterface $result,
        \Magento\Quote\Model\Quote\Payment $quotePayment,
        $data = []
    ) {
        $quotePayment->unsAdditionalInformation('cvv');
        return $result;
    }
}
```

---

### **Plugin/Model/Method/VaultPlugin.php**
**Purpose**: Controls vault payment method availability in admin orders.

**Key Features**:
- Checks if customer has saved tokens
- Controls form block type for vault payments
- Validates token availability

**Code**:
```php
namespace CyberSource\SecureAcceptance\Plugin\Model\Method;

use Magento\Backend\Model\Session\Quote;
use Magento\Vault\Model\Method\Vault;
use CyberSource\SecureAcceptance\Model\PaymentTokenManagement;
use CyberSource\SecureAcceptance\Model\Ui\ConfigProvider;

class VaultPlugin
{
    private $quote;
    private $tokenManagement;

    public function __construct(
        Quote $quote,
        PaymentTokenManagement $tokenManagement
    ) {
        $this->quote = $quote;
        $this->tokenManagement = $tokenManagement;
    }

    /**
     * Check if vault payment is available
     */
    public function afterIsAvailable(
        Vault $subject,
        $result
    ) {
        if (!$result) {
            return $result;
        }
        if ($subject->getCode() != ConfigProvider::CC_VAULT_CODE) {
            return $result;
        }

        if (!$customerId = $this->quote->getCustomerId()) {
            return false; // no vault for a blank customer
        }

        $tokens = $this->tokenManagement->getAvailableTokens($customerId, ConfigProvider::CODE);
        if (empty($tokens)) {
            return false;
        }

        return $result;
    }

    /**
     * Set vault form block type for admin
     */
    public function afterGetFormBlockType(Vault $subject, $result)
    {
        if ($subject->getCode() != ConfigProvider::CC_VAULT_CODE) {
            return $result;
        }

        return \CyberSource\SecureAcceptance\Block\Vault\Form::class;
    }
}
```

---

### **Plugin/Helper/RequestDataBuilderPlugin.php**
**Purpose**: Modifies request data for admin payment processing (most critical for admin orders).

**Key Features**:
- Overrides receipt page URL for admin orders
- Modifies signature and access keys for admin context
- Removes device fingerprint and customer IP for admin
- Sets proper scope to "adminhtml"

**Critical Code**:
```php
namespace CyberSource\SecureAcceptance\Plugin\Helper;

class RequestDataBuilderPlugin
{
    private $request;
    private $urlBuilder;
    private $configProvider;
    private $config;
    private $checkoutSession;
    private $encryptor;

    // ... constructor ...

    /**
     * Plugin for buildSilentRequestData - used for SOP in admin
     * Appends override_custom_receipt_page and re-signs the request
     */
    public function afterBuildSilentRequestData(
        \CyberSource\SecureAcceptance\Helper\RequestDataBuilder $subject,
        $result
    ) {
        // Set custom receipt page URL for admin
        $result['override_custom_receipt_page'] = $this->getCustomReceiptPageUrl();

        // Remove fields not needed for admin
        unset($result['device_fingerprint_id']);
        unset($result['customer_ip_address']);
        unset($result['signed_field_names']);
        unset($result['signature']);
        
        // Set scope to adminhtml
        $result[\CyberSource\SecureAcceptance\Helper\RequestDataBuilder::KEY_SCOPE] 
            = \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE;
        
        // Get store ID and apply admin credentials
        $storeId = $result[\CyberSource\SecureAcceptance\Helper\RequestDataBuilder::KEY_STORE_ID] ?? null;
        $result['access_key'] = $this->configProvider->getAccessKey($storeId);
        $result['profile_id'] = $this->configProvider->getProfileId($storeId);
        
        // Re-sign the request with admin credentials
        $result['signed_field_names'] = $subject->getSignedFields($result);
        $result['signature'] = $subject->sign($result, $this->configProvider->getSecretKey($storeId));

        return $result;
    }

    /**
     * Plugin for buildRequestData - used for Flex in admin
     * Similar to above but for Flex Microform
     */
    public function afterBuildRequestData(
        \CyberSource\SecureAcceptance\Helper\RequestDataBuilder $subject,
        $result
    ) {
        $result['override_custom_receipt_page'] = $this->getCustomReceiptPageUrl();

        unset($result['signed_field_names']);
        unset($result['signature']);
        $result[\CyberSource\SecureAcceptance\Helper\RequestDataBuilder::KEY_SCOPE] 
            = \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE;
        
        $storeId = $result[\CyberSource\SecureAcceptance\Helper\RequestDataBuilder::KEY_STORE_ID] ?? null;
        $result['access_key'] = $this->configProvider->getAccessKey($storeId);
        $result['profile_id'] = $this->configProvider->getProfileId($storeId);
        $result['signed_field_names'] = $subject->getSignedFields($result);
        $result['signature'] = $subject->sign($result, $this->configProvider->getSecretKey($storeId));

        return $result;
    }

    private function getCustomReceiptPageUrl()
    {
        return $this->urlBuilder->getUrl('chcybersource/transparent/response',
            ['_secure' => $this->request->isSecure()]);
    }
}
```

---

### **Gateway/Request/VaultAuthorizationRequest.php** (Admin-Specific Logic)
**Purpose**: Builds authorization request for vault tokens with admin-specific handling.

**Admin-Specific Methods**:
```php
public function build(array $buildSubject)
{
    // ... code ...
    
    if ($vaultPaymentToken !== null && !$vaultPaymentToken->isEmpty()) {

        /**
         * When order is placed with multishipping we need to build requestData 
         * from order not from quote
         */
        $isMultiShipping = false;
        if ($payment->getOrder()->getQuote() !== null) {
            $isMultiShipping = $payment->getOrder()->getQuote()->getIsMultiShipping();
        }

        if ($isMultiShipping) {
            $requestData = $this->requestDataBuilder->buildSilentRequestData(
                null,
                $vaultPaymentToken->getGatewayToken(),
                null,
                $payment->getOrder()
            );
        } elseif ($this->isAdmin) {
            // ADMIN ORDER: Include email and additional metadata
            $requestData = $this->requestDataBuilder->buildSilentRequestData(
                $paymentDO->getOrder()->getBillingAddress()->getEmail(),
                $vaultPaymentToken->getGatewayToken(),
                null,
                $payment->getOrder(),
                $paymentDO->getOrder()->getCurrencyCode(),
                ['merchant_defined_data24' => 'token_payment']
            );
        } else {
            // FRONTEND: Standard vault token payment
            $requestData = $this->requestDataBuilder->buildSilentRequestData(
                null,
                $vaultPaymentToken->getGatewayToken(),
                null,
                null,
                null,
                ['merchant_defined_data24' => 'token_payment']
            );
        }
    }
}
```

---

### **Observer/DataAssignObserver.php**
**Purpose**: Assigns payment data from frontend/admin form to quote/order.

**Key Methods**:
- `assignMicroformData()` - Processes flex token data
- `assignCardType()` - Extracts and assigns card type
- `assignCvv()` - Handles CVV from form
- `assignCardExpirationDate()` - Sets expiration date

**Code**:
```php
namespace CyberSource\SecureAcceptance\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;

class DataAssignObserver extends AbstractDataAssignObserver
{
    const KEY_FLEX_SIGNED_FIELDS = 'signedFields';
    const KEY_FLEX_SIGNATURE = 'signature';
    const KEY_FLEX_TOKEN = 'token';
    const KEY_CARD_TYPE = 'ccType';
    const KEY_EXP_DATE = 'expDate';
    const KEY_FLEX_MASKED_PAN = 'maskedPan';

    protected $session;
    private $config;
    private $paymentTokenManagement;
    private $jwtProcessor;

    public function __construct(
        \Magento\Framework\Session\SessionManagerInterface $session,
        \CyberSource\SecureAcceptance\Model\Jwt\JwtProcessorInterface $jwtProcessor,
        \CyberSource\SecureAcceptance\Model\PaymentTokenManagement $paymentTokenManagement,
        \CyberSource\SecureAcceptance\Gateway\Config\Config $config
    ) {
        $this->session = $session;
        $this->config = $config;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->jwtProcessor = $jwtProcessor;
    }

    /**
     * Main entry point - assigns all payment data
     */
    public function execute(Observer $observer)
    {
        $this->assignMicroformData($observer);
        $this->assignCardType($observer);
        $this->assignCvv($observer);
        $this->assignCardExpirationDate($observer);
    }

    private function assignMicroformData($observer)
    {
        if (!$this->config->isMicroform()) {
            return;
        }
        // ... implementation ...
    }
}
```

---

## Complete Admin Order Request/Response Flow

### **1. Admin SOP (Transparent) Flow**:

```
REQUEST PHASE:
1. Admin clicks "Create Order" → RequestSilentData controller
2. RequestSilentData::execute()
   - Gets quote from session
   - Checks vault option
   - Calls RequestDataBuilder->buildSilentRequestData()
   - RequestDataBuilderPlugin->afterBuildSilentRequestData() intercepts:
     * Sets override_custom_receipt_page to 'chcybersource/transparent/response'
     * Removes device_fingerprint_id and customer_ip_address
     * Sets scope to 'adminhtml'
     * Applies admin profile_id and access_key
     * Re-signs request with admin secret_key
3. Returns signed request JSON
4. Frontend submits to CyberSource SOP form

RESPONSE PHASE:
1. CyberSource posts back to 'chcybersource/transparent/response'
2. Response controller receives postback
3. Validates signature with admin secret_key
4. Calls substitutePostWithOrderData()
   - Gets stored order_data from session
   - Maps CyberSource response fields to Magento order fields
5. Gets AdminOrder\Create model
6. Sets payment method and data
7. Calls orderCreateModel->createOrder()
   - Quote->Payment->ToOrderPayment plugin fires
   - ToOrderPaymentPlugin->afterConvert():
     * Removes CVV from quote payment
8. Order saved and success response returned
```

### **2. Admin Flex Microform Flow**:

```
TOKEN GENERATION PHASE:
1. Admin accesses order creation page
2. Block/Adminhtml/Microform/Flex loads
3. Calls TokenService->generateToken()
4. TokenService:
   - Gets quote from BackendQuoteSession
   - Executes 'generate_flex_key' command
   - Receives captureContext token
   - Decodes token to extract clientLibrary and integrity
   - Stores in quote extension attributes
5. Block renders with client library loaded

PAYMENT SUBMISSION PHASE:
1. Admin selects card type and submits form
2. Data flows through DataAssignObserver->execute()
   - Extracts JWT token
   - Processes card data from token
   - Assigns cardType, expDate, maskedPan
3. FlexPlaceOrder controller receives request
4. FlexPlaceOrder::execute():
   - Validates form key
   - Gets quote from session
   - Extracts token and card data
   - JwtProcessor->getFlexPaymentToken() extracts transientToken
   - Sets additional information on payment:
     * flexJwt = JWT token
     * transientToken = extracted token
     * cardType = extracted card type
     * expDate = submitted exp date
     * maskedPan = masked card number
   - Calls QuoteManagement->submit($quote)
     * Triggers Payment->ToOrderPayment plugin
     * Order created with payment data
   - Saves order and returns success JSON
```

---

## Summary

The admin order functionality in `module-secure-acceptance` provides a complete payment processing solution for backend order creation with:

1. **Two payment methods**: Flex Microform and Secure Order Post (SOP)
2. **Vault support** for saving customer payment methods
3. **Form blocks** for secure payment input
4. **Controllers** handling token generation and payment response
5. **Services** managing token generation and quote/order operations
6. **Plugins** for:
   - Request data modification (RequestDataBuilderPlugin - most critical)
   - Payment data conversion (ToOrderPaymentPlugin)
   - Vault method availability (VaultPlugin)
   - Token deletion (DeleteTokenPlugin)
   - Failed order handling (PlaceOrderPlugin)
7. **Observers** for assigning payment data to quote
8. **Configuration** options for enabling/disabling admin features

All components work together to provide a seamless admin order creation experience with CyberSource payment processing. **The RequestDataBuilderPlugin is the most critical component** as it handles all request modifications specific to admin orders.
