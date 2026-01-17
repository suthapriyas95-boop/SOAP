# SOAP Admin Order Creation - File List

This document lists all file names required for admin order creation in the SOAP build (module-secure-acceptance).

## Controllers (4 files)
- Controller/Adminhtml/Microform/FlexPlaceOrder.php
- Controller/Adminhtml/Microform/TokenRequest.php
- Controller/Adminhtml/Transparent/RequestSilentData.php
- Controller/Adminhtml/Transparent/Response.php

## Blocks (4 files)
- Block/Adminhtml/Transparent/Form.php
- Block/Adminhtml/Transparent/Form/SaveForLater.php
- Block/Adminhtml/Microform/Flex.php
- Block/Vault/Form.php

## Services (1 file)
- Service/Adminhtml/TokenService.php

## Plugins (5 files)
- Plugin/Helper/RequestDataBuilderPlugin.php
- Plugin/Model/Quote/Payment/ToOrderPaymentPlugin.php
- Plugin/Model/Method/VaultPlugin.php
- Plugin/Controller/Index/PlaceOrderPlugin.php
- Plugin/Controller/Cards/DeleteTokenPlugin.php

## Observers (3 files)
- Observer/DataAssignObserver.php
- Observer/SecureTokenObserver.php
- Observer/PrepareCapture.php

## Helpers (2 files)
- Helper/MethodForm.php
- Helper/RequestDataBuilder.php

## Gateway Configurations (7 files)
- Gateway/Config/Config.php
- Gateway/Config/PlaceOrderUrlHandler.php
- Gateway/Config/CgiUrlHandler.php
- Gateway/Config/CcTypesHandler.php
- Gateway/Config/CanVoidHandler.php
- Gateway/Config/CanInitializeHandler.php
- Gateway/Config/SaConfigProvider.php

## Templates (8 files)
- view/adminhtml/templates/payment/microform.phtml
- view/adminhtml/templates/payment/sop.phtml
- view/adminhtml/templates/payment/save_for_later.phtml
- view/adminhtml/templates/flexjs.phtml
- view/adminhtml/templates/transparent/iframe.phtml
- view/adminhtml/templates/vault/renderer.phtml
- view/adminhtml/templates/vault/cvn.phtml
- view/adminhtml/templates/payment/wm.phtml

## JavaScript (2 files)
- view/adminhtml/web/js/sop.js
- view/adminhtml/web/js/microform.js

## CSS (1 file)
- view/adminhtml/web/css/styles.css

## XML Configurations (3 files)
- etc/adminhtml/system.xml
- etc/adminhtml/di.xml
- etc/adminhtml/routes.xml

## Model Files (2 files)
- Model/PaymentTokenManagement.php
- Model/Ui/Adminhtml/TokenUiComponentProvider.php

Total: 37+ files</content>
<parameter name="filePath">/Applications/MAMP/htdocs/code/SOAP/payment/Admin_Order_SOAP_15_01_26.md