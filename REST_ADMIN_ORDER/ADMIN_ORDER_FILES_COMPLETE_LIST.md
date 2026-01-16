# Complete List of Admin Order Files - module-secure-acceptance

This document provides a comprehensive inventory of ALL files responsible for admin order functionality.

---

## **CONTROLLERS (4 files)**

### 1. `Controller/Adminhtml/Microform/FlexPlaceOrder.php`
**Purpose**: Handles Flex Microform order placement from admin
**Responsibility**: 
- Receives flex token and card data
- Creates order from quote
- Returns JSON response with order success/error

### 2. `Controller/Adminhtml/Microform/TokenRequest.php`
**Purpose**: Generates Flex Microform tokens for admin orders
**Responsibility**:
- Executes token generation command
- Returns capture context token
- Provides place order URL

### 3. `Controller/Adminhtml/Transparent/RequestSilentData.php`
**Purpose**: Builds Silent Order Post (SOP) request data
**Responsibility**:
- Builds CyberSource SOP request fields
- Stores order data in session
- Validates form keys and quotes
- Returns request fields JSON

### 4. `Controller/Adminhtml/Transparent/Response.php`
**Purpose**: Processes CyberSource SOP response and creates order
**Responsibility**:
- Validates CyberSource signature
- Maps response data to order fields
- Creates order using AdminOrder\Create
- Returns success/error response

---

## **BLOCKS (4 files)**

### 1. `Block/Adminhtml/Transparent/Form.php`
**Purpose**: Main payment form block for admin orders
**Responsibility**:
- Extends Magento transparent form block
- Adds "Save for Later" child block
- Renders payment form fields

### 2. `Block/Adminhtml/Transparent/Form/SaveForLater.php`
**Purpose**: Manages vault/save for later option
**Responsibility**:
- Checks admin vault configuration
- Controls visibility of save card checkbox
- Validates admin vault settings

### 3. `Block/Adminhtml/Microform/Flex.php`
**Purpose**: Manages Flex Microform client library integration
**Responsibility**:
- Retrieves client library URLs
- Generates token via TokenService
- Provides integrity hashes for script validation
- Handles sandbox/production switching

### 4. `Block/Vault/Form.php`
**Purpose**: Renders saved card vault form for admin
**Responsibility**:
- Displays list of saved payment tokens
- Allows selection of saved cards
- Optionally requests CVV for saved tokens

---

## **SERVICES (1 file)**

### 1. `Service/Adminhtml/TokenService.php`
**Purpose**: Generates and manages Flex Microform tokens
**Responsibility**:
- Executes 'generate_flex_key' command
- Decodes capture context JWT
- Stores client library URLs in quote extensions
- Handles token generation errors

---

## **GATEWAY CONFIGURATION (7 files)**

### 1. `Gateway/Config/Config.php`
**Purpose**: Central configuration class with admin-specific methods
**Key Methods**:
- `isAdmin()` - Detects admin area
- `isVaultEnabledAdmin()` - Checks admin vault setting
- `getIsLegacyMode()` - Returns true for admin orders
- `isMicroform()` - Checks if using Flex Microform

### 2. `Gateway/Config/PlaceOrderUrlHandler.php`
**Purpose**: Returns admin-specific place order URL
**Responsibility**:
- Returns 'chcybersource/microform/flexPlaceOrder' for admin

### 3. `Gateway/Config/CgiUrlHandler.php`
**Purpose**: Determines CGI URL based on context
**Responsibility**:
- Routes to appropriate payment gateway URL
- Checks admin flag to use legacy mode

### 4. `Gateway/Config/CcTypesHandler.php`
**Purpose**: Provides available credit card types

### 5. `Gateway/Config/CanVoidHandler.php`
**Purpose**: Determines if transaction can be voided

### 6. `Gateway/Config/CanInitializeHandler.php`
**Purpose**: Determines if payment can be initialized

### 7. `Gateway/Config/SaConfigProvider.php`
**Purpose**: Provides payment API configuration

---

## **GATEWAY REQUEST BUILDERS (2 files)**

### 1. `Gateway/Request/VaultAuthorizationRequest.php`
**Purpose**: Builds vault authorization request for admin
**Admin Logic**:
- Includes email in request for admin orders
- Sets merchant_defined_data24 = 'token_payment'
- Handles multishipping scenarios

### 2. `Gateway/Request/Flex/GenerateKeyRequest.php`
**Purpose**: Builds Flex key generation request
**Responsibility**:
- Configures encryption type
- Sets target origins (URLs)
- Returns allowed card networks

---

## **PLUGINS (5 files)**

### 1. `Plugin/Helper/RequestDataBuilderPlugin.php` **(CRITICAL)**
**Purpose**: Modifies request data for admin orders
**Responsibility**:
- **Overrides receipt page URL to admin response controller**
- Removes device fingerprint and customer IP
- **Sets scope to "adminhtml"**
- **Applies admin credentials (profile_id, access_key)**
- **Re-signs request with admin secret_key**

### 2. `Plugin/Model/Quote/Payment/ToOrderPaymentPlugin.php`
**Purpose**: Converts quote payment to order payment
**Responsibility**:
- Removes sensitive CVV before order creation
- Preserves other payment attributes

### 3. `Plugin/Model/Method/VaultPlugin.php`
**Purpose**: Controls vault payment method availability
**Responsibility**:
- Checks customer has saved tokens
- Sets form block type for vault payments
- Validates token availability

### 4. `Plugin/Controller/Index/PlaceOrderPlugin.php`
**Purpose**: Handles failed order placement
**Responsibility**:
- Converts failed orders back to quotes
- Preserves cart items on failure

### 5. `Plugin/Controller/Cards/DeleteTokenPlugin.php`
**Purpose**: Deletes tokens from CyberSource
**Responsibility**:
- Calls CyberSource SOAP API to delete token
- Validates deletion response
- Shows error/success message

---

## **OBSERVERS (3 files)**

### 1. `Observer/DataAssignObserver.php`
**Purpose**: Assigns payment form data to quote
**Key Methods**:
- `assignMicroformData()` - Processes flex token
- `assignCardType()` - Extracts card type
- `assignCvv()` - Handles CVV
- `assignCardExpirationDate()` - Sets expiration

### 2. `Observer/SecureTokenObserver.php`
**Purpose**: Handles secure token assignment

### 3. `Observer/PrepareCapture.php`
**Purpose**: Prepares order for capture

---

## **HELPERS (2 files)**

### 1. `Helper/MethodForm.php`
**Purpose**: Determines correct template for payment method
**Methods**:
- `getCCTemplateName()` - Returns template path based on payment type
  - Microform → 'payment/microform.phtml'
  - SOP → 'payment/sop.phtml'
  - iFrame → 'payment/wm-iframe.phtml'
- `getCCVaultTemplateName()` - Returns vault template path

### 2. `Helper/RequestDataBuilder.php`
**Purpose**: Builds complete SOP request data
**Responsibility**:
- Constructs request field names and values
- Generates signatures
- Validates signatures

---

## **LAYOUT FILES (3 XML files)**

### 1. `view/adminhtml/layout/sales_order_create_index.xml`
**Purpose**: Main admin order creation page layout
**Content**:
```xml
- Loads CSS styles
- Renders Flex Microform requirejs config
- References order_create_billing_form block
- Sets payment method templates:
  * chcybersource_cc_vault → uses getCCVaultTemplateName()
  * chcybersource → uses getCCTemplateName()
```

### 2. `view/adminhtml/layout/sales_order_create_load_block_billing_method.xml`
**Purpose**: Alternative billing method block loading
**Content**:
```xml
- Loads Flex requirejs config
- References order.create.billing.method.form block
- Sets same payment method templates
```

### 3. `view/adminhtml/layout/cybersource_iframe_payment_response.xml`
**Purpose**: Iframe response layout for SOP
**Content**:
```xml
- Renders transparent iframe block
- Template: transparent/iframe.phtml
```

---

## **TEMPLATE FILES (8 PHTML files)**

### 1. `view/adminhtml/templates/payment/microform.phtml`
**Purpose**: Flex Microform payment form for admin
**Fields**:
- Credit card type selector
- Flex microform card number div
- Expiration month/year selectors
- CVV/CID div (conditional)
**Scripts**: Initializes `CyberSource_SecureAcceptance/js/microform`

### 2. `view/adminhtml/templates/payment/sop.phtml`
**Purpose**: SOP (Silent Order Post) payment form for admin
**Fields**:
- Credit card type selector
- Credit card number input
- Expiration month/year selectors
- CVV/CID input
- Billing address fields
**Scripts**: Initializes `CyberSource_SecureAcceptance/js/sop`

### 3. `view/adminhtml/templates/payment/save_for_later.phtml`
**Purpose**: Save card for future use checkbox
**Content**:
- Conditional checkbox for saving card
- Visible only if admin vault enabled
- Data attribute: `vault_is_enabled`

### 4. `view/adminhtml/templates/flexjs.phtml`
**Purpose**: Loads Flex Microform client library
**Responsibility**:
- Configures require.js paths
- Dynamically loads external Flex script
- Sets integrity hash for security
- Handles sandbox/production URLs

### 5. `view/adminhtml/templates/transparent/iframe.phtml`
**Purpose**: Processes SOP response in iframe
**Responsibility**:
- Checks response for redirect URLs
- Displays error messages
- Triggers order creation via realOrder event
- Disables form inputs after submission

### 6. `view/adminhtml/templates/vault/renderer.phtml`
**Purpose**: Renders saved payment token in vault list
**Displays**: Card type, last 4 digits, expiration date

### 7. `view/adminhtml/templates/vault/cvn.phtml`
**Purpose**: CVN input for saved card payment
**Content**: CVN/CID input field for admin vault payments

### 8. `view/adminhtml/templates/payment/wm.phtml`
**Purpose**: (Optional) Alternative payment form template

---

## **JAVASCRIPT FILES (2 files)**

### 1. `view/adminhtml/web/js/sop.js`
**Purpose**: SOP payment form JavaScript widget
**Functionality**:
- Extends $.mage.transparent widget
- Handles form submission for SOP
- Sends POST request to RequestSilentData controller
- Includes order_data serialization
- Vault enabled flag handling
- Form submission via POST to gateway

### 2. `view/adminhtml/web/js/microform.js`
**Purpose**: Flex Microform JavaScript widget
**Functionality**:
- Flex card input initialization
- Token generation request
- Form validation
- Card submission handling

---

## **CSS FILES (1 file)**

### 1. `view/adminhtml/web/css/styles.css`
**Purpose**: Admin-specific payment form styling
**Styles**: Form layout, input styling, validation displays

---

## **CONFIGURATION FILES (3 XML files)**

### 1. `etc/adminhtml/system.xml`
**Purpose**: Admin payment configuration UI
**Sections**:
- Payment method enable/disable
- Vault enable for admin (active_admin)
- CVV enable for admin (enable_admin_cvv)
- Payment action (Auth, Sale)
- Auth indicator
- Order status
- AVS/CVN settings
- Microform vs SOP selection
- Flex vs SOAP flow selection

### 2. `etc/adminhtml/di.xml`
**Purpose**: Dependency injection for admin components
**Key Configurations**:
- Form block type for payment methods
- Session manager assignments:
  - RequestSilentData → Backend\Session\Quote
  - FlexPlaceOrder → Checkout\Session
  - TokenRequest → Backend\Session\Quote
  - Response → Backend\Session\Quote
- PlaceOrderUrlHandler isAdmin = true
- VaultAuthorizationRequest isAdmin = true
- RequestDataBuilder plugin registration

### 3. `etc/adminhtml/routes.xml`
**Purpose**: Admin router configuration
**Router**: chcybersource (maps to Controller/Adminhtml)
**Routes**:
- chcybersource/microform/tokenRequest
- chcybersource/microform/flexPlaceOrder
- chcybersource/transparent/requestSilentData
- chcybersource/transparent/response

---

## **MODEL FILES (2 relevant files)**

### 1. `Model/PaymentTokenManagement.php`
**Purpose**: Manages saved payment tokens
**Functionality**: Retrieves available tokens for admin vault

### 2. `Model/Ui/Adminhtml/TokenUiComponentProvider.php`
**Purpose**: Provides UI for vault tokens in admin

---

## **FILE ORGANIZATION BY RESPONSIBILITY**

### **Request Processing (Admin Order Creation)**
1. ✅ `Controller/Adminhtml/Microform/TokenRequest.php` - Token generation
2. ✅ `Controller/Adminhtml/Transparent/RequestSilentData.php` - SOP request building
3. ✅ `Service/Adminhtml/TokenService.php` - Token service
4. ✅ `Helper/RequestDataBuilder.php` - Request field building
5. ✅ `Plugin/Helper/RequestDataBuilderPlugin.php` - Request modification

### **Response Processing (Payment Response)**
1. ✅ `Controller/Adminhtml/Transparent/Response.php` - Response handler
2. ✅ `Controller/Adminhtml/Microform/FlexPlaceOrder.php` - Flex order creation

### **Form Rendering (UI)**
1. ✅ `Block/Adminhtml/Transparent/Form.php` - Form block
2. ✅ `Block/Adminhtml/Microform/Flex.php` - Flex block
3. ✅ `view/adminhtml/templates/payment/microform.phtml` - Microform template
4. ✅ `view/adminhtml/templates/payment/sop.phtml` - SOP template
5. ✅ `view/adminhtml/web/js/sop.js` - SOP JavaScript
6. ✅ `Helper/MethodForm.php` - Template selection helper

### **Data Flow (Payment Data Assignment)**
1. ✅ `Observer/DataAssignObserver.php` - Payment data observer
2. ✅ `Plugin/Model/Quote/Payment/ToOrderPaymentPlugin.php` - Quote to order conversion

### **Vault Management**
1. ✅ `Block/Adminhtml/Transparent/Form/SaveForLater.php` - Save for later UI
2. ✅ `Block/Vault/Form.php` - Vault form block
3. ✅ `Plugin/Model/Method/VaultPlugin.php` - Vault method availability
4. ✅ `Plugin/Controller/Cards/DeleteTokenPlugin.php` - Token deletion
5. ✅ `Model/PaymentTokenManagement.php` - Token management

### **Configuration**
1. ✅ `Gateway/Config/Config.php` - Main config class
2. ✅ `Gateway/Config/PlaceOrderUrlHandler.php` - URL handler
3. ✅ `Gateway/Config/CgiUrlHandler.php` - CGI URL handler
4. ✅ `etc/adminhtml/system.xml` - Admin settings
5. ✅ `etc/adminhtml/di.xml` - DI configuration
6. ✅ `etc/adminhtml/routes.xml` - Route configuration

---

## **CRITICAL FILE CHAIN FOR ADMIN ORDER FLOW**

```
SOP Flow:
RequestSilentData.php 
  → Helper/RequestDataBuilder.php
    → Plugin/Helper/RequestDataBuilderPlugin.php (CRITICAL - modifies for admin)
      → Output: Request fields
        → CyberSource Payment Gateway
          → Response
            → Controller/Adminhtml/Transparent/Response.php
              → Validate signature
              → AdminOrder\Create model
              → Order creation
              → Observer/DataAssignObserver.php (data assignment)
              → Plugin/Model/Quote/Payment/ToOrderPaymentPlugin.php (remove CVV)

Flex Flow:
Controller/Adminhtml/Microform/TokenRequest.php
  → Service/Adminhtml/TokenService.php
    → Generates token
      → Block/Adminhtml/Microform/Flex.php
        → view/adminhtml/templates/flexjs.phtml
          → Flex JavaScript
            → Admin submits form
              → Observer/DataAssignObserver.php (data assignment)
                → Controller/Adminhtml/Microform/FlexPlaceOrder.php
                  → QuoteManagement->submit()
                    → Order creation
                    → Plugin/Model/Quote/Payment/ToOrderPaymentPlugin.php (remove CVV)
```

---

## **SUMMARY**

Total files involved in admin order functionality: **37+ files**

### By Type:
- **Controllers**: 4
- **Blocks**: 4
- **Services**: 1
- **Plugins**: 5
- **Observers**: 3
- **Helpers**: 2
- **Gateways/Config**: 7
- **Models**: 2
- **Templates**: 8
- **JavaScript**: 2
- **CSS**: 1
- **XML Configuration**: 3

All these files work together to provide a complete admin order payment processing experience with CyberSource integration.
