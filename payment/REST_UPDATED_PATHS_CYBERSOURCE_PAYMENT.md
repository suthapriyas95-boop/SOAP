# REST Admin Order - COMPLETE FILE PATHS & NAMESPACES (UPDATED)

Updated directory structure and file paths for REST implementation using `Cybersource\Payment` namespace.

---

## **NEW MODULE DIRECTORY STRUCTURE**

```
Cybersource/Payment/ (module root)
├── Api/
│   ├── Admin/
│   │   ├── TokenGeneratorInterface.php
│   │   ├── SopRequestDataBuilderInterface.php
│   │   ├── SopResponseHandlerInterface.php
│   │   ├── FlexOrderCreatorInterface.php
│   │   └── VaultTokenManagementInterface.php
│   └── Vault/
│       └── TokenManagementInterface.php
│
├── Block/
│   └── Adminhtml/
│       ├── Transparent/
│       │   ├── Form.php
│       │   └── Form/SaveForLater.php
│       ├── Microform/
│       │   └── Flex.php
│       └── Rest/
│           ├── FlexTokenDisplay.php
│           ├── SopFormDisplay.php
│           └── PaymentMethodInfo.php
│
├── Controller/
│   ├── Adminhtml/
│   │   ├── Microform/
│   │   │   ├── FlexPlaceOrder.php
│   │   │   └── TokenRequest.php
│   │   └── Transparent/
│   │       ├── RequestSilentData.php
│   │       └── Response.php
│   └── Rest/
│       └── Admin/
│           ├── TokenGenerator.php
│           ├── SopRequestData.php
│           ├── SopResponse.php
│           ├── FlexPlaceOrder.php
│           └── VaultTokenDelete.php
│
├── Helper/
│   ├── RequestDataBuilder.php
│   ├── MethodForm.php
│   └── Rest/
│       ├── RequestValidator.php
│       ├── ResponseFormatter.php
│       └── TokenDataExtractor.php
│
├── Model/
│   ├── Api/
│   │   ├── AdminTokenGenerator.php
│   │   ├── AdminSopRequestDataBuilder.php
│   │   ├── AdminSopResponseHandler.php
│   │   ├── AdminFlexOrderCreator.php
│   │   └── AdminVaultTokenManagement.php
│   ├── Rest/
│   │   ├── Request/
│   │   │   ├── TokenRequest.php
│   │   │   ├── SopRequestDataRequest.php
│   │   │   ├── SopResponseRequest.php
│   │   │   ├── FlexPlaceOrderRequest.php
│   │   │   └── VaultTokenDeleteRequest.php
│   │   └── Response/
│   │       ├── TokenResponse.php
│   │       ├── SopRequestDataResponse.php
│   │       ├── OrderResponse.php
│   │       └── SuccessResponse.php
│   └── (other existing models)
│
├── Observer/
│   ├── DataAssignObserver.php
│   └── Rest/
│       ├── RestDataAssignObserver.php
│       └── RestTokenObserver.php
│
├── Plugin/
│   ├── Helper/
│   │   └── RequestDataBuilderPlugin.php
│   └── Rest/
│       ├── RequestDataBuilderPlugin.php
│       ├── TokenValidatorPlugin.php
│       └── ResponseSignaturePlugin.php
│
├── Service/
│   ├── Adminhtml/
│   │   └── TokenService.php
│   └── Rest/
│       ├── RequestValidator.php
│       ├── ResponseFormatter.php
│       ├── OrderDataProcessor.php
│       └── ErrorHandler.php
│
├── view/
│   ├── adminhtml/
│   │   ├── layout/
│   │   │   ├── sales_order_create_index.xml
│   │   │   ├── sales_order_create_load_block_billing_method.xml
│   │   │   └── cybersource_rest_admin_payment.xml
│   │   ├── templates/
│   │   │   ├── payment/
│   │   │   │   ├── microform.phtml
│   │   │   │   ├── sop.phtml
│   │   │   │   ├── flexjs.phtml
│   │   │   │   └── rest/
│   │   │   │       ├── flex_token.phtml
│   │   │   │       ├── sop_form.phtml
│   │   │   │       └── payment_info.phtml
│   │   │   └── rest/
│   │   │       ├── token_response.phtml
│   │   │       ├── form_fields.phtml
│   │   │       └── error_message.phtml
│   │   └── web/
│   │       ├── js/
│   │       │   ├── sop.js
│   │       │   ├── microform.js
│   │       │   └── rest/
│   │       │       ├── rest-client.js
│   │       │       ├── token-generator.js
│   │       │       ├── sop-request.js
│   │       │       ├── flex-order.js
│   │       │       ├── form-validator.js
│   │       │       └── response-handler.js
│   │       └── css/
│   │           └── rest/
│   │               ├── admin-payment.css
│   │               └── form-display.css
│
├── etc/
│   ├── webapi.xml
│   ├── di.xml
│   ├── routes.xml
│   ├── extension_attributes.xml
│   ├── system.xml
│   ├── config.xml
│   ├── acl.xml
│   └── (other config files)
│
├── composer.json
└── registration.php
```

---

## **UPDATED NAMESPACE REFERENCES**

### **PHP Namespaces - Change All From:**
```
CyberSource\SecureAcceptance
```

### **To:**
```
Cybersource\Payment
```

---

## **EXAMPLES OF UPDATED PATHS & NAMESPACES**

### **API Interfaces**
```php
// OLD
namespace CyberSource\SecureAcceptance\Api\Admin;

// NEW
namespace Cybersource\Payment\Api\Admin;
```

### **Service Implementations**
```php
// OLD
namespace CyberSource\SecureAcceptance\Model\Api;

// NEW
namespace Cybersource\Payment\Model\Api;
```

### **REST Controllers**
```php
// OLD
namespace CyberSource\SecureAcceptance\Controller\Rest\Admin;

// NEW
namespace Cybersource\Payment\Controller\Rest\Admin;
```

### **REST Helpers**
```php
// OLD
namespace CyberSource\SecureAcceptance\Helper\Rest;

// NEW
namespace Cybersource\Payment\Helper\Rest;
```

### **REST Services**
```php
// OLD
namespace CyberSource\SecureAcceptance\Service\Rest;

// NEW
namespace Cybersource\Payment\Service\Rest;
```

### **Observers**
```php
// OLD
namespace CyberSource\SecureAcceptance\Observer\Rest;

// NEW
namespace Cybersource\Payment\Observer\Rest;
```

### **Plugins**
```php
// OLD
namespace CyberSource\SecureAcceptance\Plugin\Rest;

// NEW
namespace Cybersource\Payment\Plugin\Rest;
```

### **View Blocks**
```php
// OLD
namespace CyberSource\SecureAcceptance\Block\Adminhtml\Rest;

// NEW
namespace Cybersource\Payment\Block\Adminhtml\Rest;
```

### **Data Models**
```php
// OLD
namespace CyberSource\SecureAcceptance\Model\Rest\Request;
namespace CyberSource\SecureAcceptance\Model\Rest\Response;

// NEW
namespace Cybersource\Payment\Model\Rest\Request;
namespace Cybersource\Payment\Model\Rest\Response;
```

---

## **UPDATED TEMPLATE & ASSET PATHS**

### **Template References in PHP**
```php
// OLD
'template' => 'CyberSource_SecureAcceptance::payment/rest/flex_token.phtml'

// NEW
'template' => 'Cybersource_Payment::payment/rest/flex_token.phtml'
```

### **JavaScript References in Layout**
```xml
<!-- OLD -->
<script src="CyberSource_SecureAcceptance::web/js/rest/token-generator.js"/>

<!-- NEW -->
<script src="Cybersource_Payment::web/js/rest/token-generator.js"/>
```

### **CSS References in Layout**
```xml
<!-- OLD -->
<link src="CyberSource_SecureAcceptance::web/css/rest/admin-payment.css"/>

<!-- NEW -->
<link src="Cybersource_Payment::web/css/rest/admin-payment.css"/>
```

### **JavaScript Require Statements**
```javascript
// OLD
require(['CyberSource_SecureAcceptance/rest/token-generator'])

// NEW
require(['Cybersource_Payment/rest/token-generator'])
```

---

## **UPDATED CLASS REFERENCES IN DI.XML**

### **Before:**
```xml
<preference for="CyberSource\SecureAcceptance\Api\Admin\TokenGeneratorInterface"
            type="CyberSource\SecureAcceptance\Model\Api\AdminTokenGenerator"/>
```

### **After:**
```xml
<preference for="Cybersource\Payment\Api\Admin\TokenGeneratorInterface"
            type="Cybersource\Payment\Model\Api\AdminTokenGenerator"/>
```

---

## **UPDATED CLASS REFERENCES IN WEBAPI.XML**

### **Before:**
```xml
<service class="CyberSource\SecureAcceptance\Api\Admin\TokenGeneratorInterface" 
         method="generateToken"/>
```

### **After:**
```xml
<service class="Cybersource\Payment\Api\Admin\TokenGeneratorInterface" 
         method="generateToken"/>
```

---

## **COMPLETE FILE PATH MAPPING**

### **Phase 1: API Interfaces**
| Old Path | New Path |
|----------|----------|
| `Api/Admin/TokenGeneratorInterface.php` | `Api/Admin/TokenGeneratorInterface.php` |
| Namespace: `CyberSource\SecureAcceptance\Api\Admin` | Namespace: `Cybersource\Payment\Api\Admin` |

### **Phase 2: Service Implementations**
| Old Path | New Path |
|----------|----------|
| `Model/Api/AdminTokenGenerator.php` | `Model/Api/AdminTokenGenerator.php` |
| Namespace: `CyberSource\SecureAcceptance\Model\Api` | Namespace: `Cybersource\Payment\Model\Api` |

### **Phase 3: REST Controllers**
| Old Path | New Path |
|----------|----------|
| `Controller/Rest/Admin/TokenGenerator.php` | `Controller/Rest/Admin/TokenGenerator.php` |
| Namespace: `CyberSource\SecureAcceptance\Controller\Rest\Admin` | Namespace: `Cybersource\Payment\Controller\Rest\Admin` |

### **Phase 4: Request Data Models**
| Old Path | New Path |
|----------|----------|
| `Model/Rest/Request/TokenRequest.php` | `Model/Rest/Request/TokenRequest.php` |
| Namespace: `CyberSource\SecureAcceptance\Model\Rest\Request` | Namespace: `Cybersource\Payment\Model\Rest\Request` |

### **Phase 5: Response Data Models**
| Old Path | New Path |
|----------|----------|
| `Model/Rest/Response/TokenResponse.php` | `Model/Rest/Response/TokenResponse.php` |
| Namespace: `CyberSource\SecureAcceptance\Model\Rest\Response` | Namespace: `Cybersource\Payment\Model\Rest\Response` |

### **Phase 6: REST Helpers**
| Old Path | New Path |
|----------|----------|
| `Helper/Rest/RequestValidator.php` | `Helper/Rest/RequestValidator.php` |
| Namespace: `CyberSource\SecureAcceptance\Helper\Rest` | Namespace: `Cybersource\Payment\Helper\Rest` |

### **Phase 7: REST Services**
| Old Path | New Path |
|----------|----------|
| `Service/Rest/RequestValidator.php` | `Service/Rest/RequestValidator.php` |
| Namespace: `CyberSource\SecureAcceptance\Service\Rest` | Namespace: `Cybersource\Payment\Service\Rest` |

### **Phase 8: Observers**
| Old Path | New Path |
|----------|----------|
| `Observer/Rest/RestDataAssignObserver.php` | `Observer/Rest/RestDataAssignObserver.php` |
| Namespace: `CyberSource\SecureAcceptance\Observer\Rest` | Namespace: `Cybersource\Payment\Observer\Rest` |

### **Phase 9: Plugins**
| Old Path | New Path |
|----------|----------|
| `Plugin/Rest/RequestDataBuilderPlugin.php` | `Plugin/Rest/RequestDataBuilderPlugin.php` |
| Namespace: `CyberSource\SecureAcceptance\Plugin\Rest` | Namespace: `Cybersource\Payment\Plugin\Rest` |

### **Phase 10: View Blocks**
| Old Path | New Path |
|----------|----------|
| `Block/Adminhtml/Rest/FlexTokenDisplay.php` | `Block/Adminhtml/Rest/FlexTokenDisplay.php` |
| Namespace: `CyberSource\SecureAcceptance\Block\Adminhtml\Rest` | Namespace: `Cybersource\Payment\Block\Adminhtml\Rest` |

### **Phase 11: Layout Files**
| Old Path | New Path |
|----------|----------|
| `view/adminhtml/layout/sales_order_create_index.xml` | `view/adminhtml/layout/sales_order_create_index.xml` |
| Class ref: `CyberSource\SecureAcceptance\Block` | Class ref: `Cybersource\Payment\Block` |

### **Phase 12: Template Files**
| Old Path | New Path |
|----------|----------|
| `view/adminhtml/templates/payment/rest/flex_token.phtml` | `view/adminhtml/templates/payment/rest/flex_token.phtml` |
| Require path: `CyberSource_SecureAcceptance/rest/` | Require path: `Cybersource_Payment/rest/` |

### **Phase 13: JavaScript Files**
| Old Path | New Path |
|----------|----------|
| `view/adminhtml/web/js/rest/token-generator.js` | `view/adminhtml/web/js/rest/token-generator.js` |
| Module ref: `CyberSource_SecureAcceptance` | Module ref: `Cybersource_Payment` |

### **Phase 14: CSS Files**
| Old Path | New Path |
|----------|----------|
| `view/adminhtml/web/css/rest/admin-payment.css` | `view/adminhtml/web/css/rest/admin-payment.css` |
| (CSS paths don't change) | (CSS paths don't change) |

### **Phase 15: Configuration Files**
| Old Path | New Path |
|----------|----------|
| `etc/webapi.xml` | `etc/webapi.xml` |
| Class ref: `CyberSource\SecureAcceptance` | Class ref: `Cybersource\Payment` |

---

## **QUICK REFERENCE - ALL REPLACEMENTS NEEDED**

### **In PHP Files:**
```
OLD: namespace CyberSource\SecureAcceptance\
NEW: namespace Cybersource\Payment\

OLD: use CyberSource\SecureAcceptance\
NEW: use Cybersource\Payment\
```

### **In XML Files:**
```
OLD: class="CyberSource\SecureAcceptance\
NEW: class="Cybersource\Payment\

OLD: type="CyberSource\SecureAcceptance\
NEW: type="Cybersource\Payment\
```

### **In Templates (PHTML):**
```
OLD: require(['CyberSource_SecureAcceptance/
NEW: require(['Cybersource_Payment/

OLD: 'CyberSource_SecureAcceptance::
NEW: 'Cybersource_Payment::
```

### **In Layout XML:**
```
OLD: class="CyberSource\SecureAcceptance\
NEW: class="Cybersource\Payment\

OLD: src="CyberSource_SecureAcceptance::
NEW: src="Cybersource_Payment::
```

---

## **MODULE REGISTRATION & COMPOSER**

### **registration.php**
```php
<?php
\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Cybersource_Payment',
    __DIR__
);
```

### **composer.json**
```json
{
    "name": "cybersource/module-payment",
    "description": "CyberSource Payment Gateway Integration",
    "require": {
        "magento/framework": "*",
        "magento/module-sales": "*",
        "magento/module-quote": "*",
        "magento/module-vault": "*"
    },
    "type": "magento2-module",
    "version": "1.0.0",
    "license": [
        "proprietary"
    ],
    "autoload": {
        "psr-4": {
            "Cybersource\\Payment\\": ""
        }
    }
}
```

---

## **INSTALLATION INSTRUCTIONS WITH NEW PATHS**

1. **Create module directory:**
   ```bash
   mkdir -p app/code/Cybersource/Payment
   ```

2. **Create all subdirectories:**
   ```bash
   mkdir -p app/code/Cybersource/Payment/Api/Admin
   mkdir -p app/code/Cybersource/Payment/Block/Adminhtml/Rest
   mkdir -p app/code/Cybersource/Payment/Controller/Rest/Admin
   mkdir -p app/code/Cybersource/Payment/Helper/Rest
   mkdir -p app/code/Cybersource/Payment/Model/Api
   mkdir -p app/code/Cybersource/Payment/Model/Rest/Request
   mkdir -p app/code/Cybersource/Payment/Model/Rest/Response
   mkdir -p app/code/Cybersource/Payment/Observer/Rest
   mkdir -p app/code/Cybersource/Payment/Plugin/Rest
   mkdir -p app/code/Cybersource/Payment/Service/Rest
   mkdir -p app/code/Cybersource/Payment/view/adminhtml/layout
   mkdir -p app/code/Cybersource/Payment/view/adminhtml/templates/payment/rest
   mkdir -p app/code/Cybersource/Payment/view/adminhtml/templates/rest
   mkdir -p app/code/Cybersource/Payment/view/adminhtml/web/js/rest
   mkdir -p app/code/Cybersource/Payment/view/adminhtml/web/css/rest
   mkdir -p app/code/Cybersource/Payment/etc
   ```

3. **Copy all PHP files** to appropriate directories with updated namespaces

4. **Copy all view files** (templates, layouts, JS, CSS) to view directories

5. **Copy configuration files** to etc directory

6. **Enable module:**
   ```bash
   php bin/magento module:enable Cybersource_Payment
   php bin/magento setup:upgrade
   php bin/magento setup:di:compile
   php bin/magento setup:static-content:deploy
   ```

---

## **FILE COUNT BY DIRECTORY**

```
Api/Admin/                           5 files
Block/Adminhtml/Rest/                3 files
Controller/Rest/Admin/               5 files
Helper/Rest/                         3 files
Model/Api/                           5 files
Model/Rest/Request/                  5 files
Model/Rest/Response/                 4 files
Observer/Rest/                       2 files
Plugin/Rest/                         3 files
Service/Rest/                        4 files
view/adminhtml/layout/               3 files (+ updates)
view/adminhtml/templates/payment/rest/  3 files
view/adminhtml/templates/rest/       3 files
view/adminhtml/web/js/rest/          6 files
view/adminhtml/web/css/rest/         2 files
etc/                                 7 files (new + updates)
─────────────────────────────────────────────
TOTAL:                              63 FILES
```

---

## **VALIDATION CHECKLIST**

- [ ] All `CyberSource\SecureAcceptance` replaced with `Cybersource\Payment`
- [ ] All `CyberSource_SecureAcceptance::` replaced with `Cybersource_Payment::`
- [ ] All `CyberSource_SecureAcceptance/` replaced with `Cybersource_Payment/`
- [ ] All file paths created in `app/code/Cybersource/Payment/`
- [ ] registration.php updated with `Cybersource_Payment`
- [ ] composer.json updated with `cybersource/module-payment`
- [ ] All class references in webapi.xml updated
- [ ] All class references in di.xml updated
- [ ] All template paths in layout XMLs updated
- [ ] All require paths in PHTML files updated
- [ ] All require paths in JavaScript files updated
- [ ] Module enables without errors
- [ ] Compilation succeeds
- [ ] REST endpoints accessible

---

## **REFERENCE TABLE - FIND & REPLACE**

| Find | Replace | Location |
|------|---------|----------|
| `CyberSource\SecureAcceptance` | `Cybersource\Payment` | All `.php` files |
| `CyberSource_SecureAcceptance::` | `Cybersource_Payment::` | All `.phtml` and `.xml` files |
| `CyberSource_SecureAcceptance/` | `Cybersource_Payment/` | All `.js` files and `.xml` files |
| `namespace CyberSource` | `namespace Cybersource` | All `.php` files |
| `use CyberSource` | `use Cybersource` | All `.php` files |
| `class="CyberSource` | `class="Cybersource` | All `.xml` files |
| `type="CyberSource` | `type="Cybersource` | All `.xml` files |

---

## **SUMMARY**

All 63 files with updated namespaces and paths:

✅ **Namespace:** `Cybersource\Payment` (instead of `CyberSource\SecureAcceptance`)
✅ **Module Code:** `Cybersource_Payment` (instead of `CyberSource_SecureAcceptance`)
✅ **Directory:** `app/code/Cybersource/Payment/` (instead of `module-secure-acceptance`)
✅ **All paths updated** across PHP, XML, PHTML, and JavaScript files
✅ **Ready to implement** in new module structure
