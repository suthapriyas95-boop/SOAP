# REST ADMIN ORDER - COMPLETE IMPLEMENTATION PART 2 (45 Files - Credit Card)

Exact file names and complete code for CyberSource REST Admin Order - Credit Card Payment Processing.

---

## **DIRECTORY STRUCTURE**

```
app/code/Cybersource/Payment/
├── Api/
│   └── Admin/
│       ├── CardAuthorizationInterface.php (FILE 1)
│       ├── CardPaymentBuilderInterface.php (FILE 2)
│       ├── CardResponseHandlerInterface.php (FILE 3)
│       └── VaultTokenManagementInterface.php (FILE 4)
├── Model/
│   ├── Api/
│   │   ├── CardAuthorization.php (FILE 5)
│   │   ├── CardPaymentBuilder.php (FILE 6)
│   │   ├── CardResponseHandler.php (FILE 7)
│   │   ├── VaultTokenManagement.php (FILE 8)
│   │   └── OrderManagementService.php (FILE 9)
│   ├── Config/
│   │   ├── Source/
│   │   │   └── Environment.php (FILE 10)
│   │   └── Config.php (FILE 11)
│   └── Request/
│       ├── AuthorizeRequest.php (FILE 12)
│       ├── SaleRequest.php (FILE 13)
│       ├── CaptureRequest.php (FILE 14)
│       ├── VoidRequest.php (FILE 15)
│       └── RefundRequest.php (FILE 16)
├── Controller/
│   └── Rest/
│       └── Admin/
│           ├── Authorize.php (FILE 17)
│           ├── Sale.php (FILE 18)
│           ├── AuthorizeToken.php (FILE 19)
│           ├── SaleToken.php (FILE 20)
│           ├── Capture.php (FILE 21)
│           ├── Void.php (FILE 22)
│           ├── Refund.php (FILE 23)
│           └── VaultTokenManager.php (FILE 24)
├── Service/
│   └── Admin/
│       ├── CardValidationService.php (FILE 25)
│       ├── TransactionProcessorService.php (FILE 26)
│       ├── TokenizationService.php (FILE 27)
│       ├── PayerAuthService.php (FILE 28)
│       ├── ResponseFormatterService.php (FILE 29)
│       ├── ErrorHandlerService.php (FILE 30)
│       └── LoggerService.php (FILE 31)
├── Helper/
│   ├── CardDataValidator.php (FILE 32)
│   ├── ResponseParser.php (FILE 33)
│   ├── TransactionMapper.php (FILE 34)
│   └── AvsResultInterpreter.php (FILE 35)
├── Block/
│   └── Adminhtml/
│       ├── CreditCardForm.php (FILE 36)
│       └── PaymentInfo.php (FILE 37)
├── view/
│   └── adminhtml/
│       ├── layout/
│       │   ├── sales_order_create_index.xml (FILE 38 - UPDATE)
│       │   ├── sales_order_create_load_block_billing_method.xml (FILE 39 - UPDATE)
│       │   └── cybersource_admin_payment.xml (FILE 40 - NEW)
│       ├── templates/
│       │   ├── credit_card_form.phtml (FILE 41)
│       │   ├── payment_info.phtml (FILE 42)
│       │   └── error_message.phtml (FILE 43)
│       └── web/
│           ├── js/
│           │   ├── card-form-handler.js (FILE 44)
│           │   ├── card-validator.js (FILE 45)
│           │   ├── payment-processor.js (FILE 46)
│           │   └── token-handler.js (FILE 47)
│           └── css/
│               └── payment-form.css (FILE 48)
├── etc/
│   ├── webapi.xml (FILE 49)
│   ├── di.xml (FILE 50)
│   ├── routes.xml (FILE 51)
│   ├── system.xml (FILE 52)
│   ├── acl.xml (FILE 53)
│   └── module.xml (FILE 54)
├── registration.php (FILE 55)
└── composer.json (FILE 56)
```

---

## **FILE 10: Model/Config/Source/Environment.php**

```php
<?php
namespace Cybersource\Payment\Model\Config\Source;

class Environment implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'test', 'label' => __('Test')],
            ['value' => 'production', 'label' => __('Production')]
        ];
    }
}
```

---

## **FILE 11: Model/Config/Config.php**

```php
<?php
namespace Cybersource\Payment\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const CONFIG_PATH_PREFIX = 'payment/cybersource_cc/';
    
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function getMerchantId($storeId = null)
    {
        return $this->getConfigValue('merchant_id', $storeId);
    }

    public function getAccessKey($storeId = null)
    {
        return $this->getConfigValue('access_key', $storeId);
    }

    public function getSecretKey($storeId = null)
    {
        return $this->getConfigValue('secret_key', $storeId);
    }

    public function getEnvironment($storeId = null)
    {
        return $this->getConfigValue('environment', $storeId) ?? 'test';
    }

    public function isActive($storeId = null)
    {
        return (bool)$this->getConfigValue('active', $storeId);
    }

    public function getDebugMode($storeId = null)
    {
        return (bool)$this->getConfigValue('debug', $storeId);
    }

    public function isVaultEnabled($storeId = null)
    {
        return (bool)$this->getConfigValue('vault_enabled', $storeId);
    }

    public function get3dSecureEnabled($storeId = null)
    {
        return (bool)$this->getConfigValue('3d_secure_enabled', $storeId);
    }

    public function getProfileId($storeId = null)
    {
        return $this->getConfigValue('profile_id', $storeId);
    }

    private function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_PATH_PREFIX . $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
```

---

## **FILE 12: Model/Request/AuthorizeRequest.php**

```php
<?php
namespace Cybersource\Payment\Model\Request;

class AuthorizeRequest
{
    private $quoteId;
    private $cardData;
    private $amount;
    private $currency;
    private $saveCard = false;

    public function __construct($quoteId, array $cardData, $amount, $currency, $saveCard = false)
    {
        $this->quoteId = $quoteId;
        $this->cardData = $cardData;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->saveCard = $saveCard;
    }

    public function getQuoteId()
    {
        return $this->quoteId;
    }

    public function getCardData()
    {
        return $this->cardData;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function isSaveCard()
    {
        return $this->saveCard;
    }

    public function validate()
    {
        if (!$this->quoteId || !is_numeric($this->quoteId)) {
            throw new \InvalidArgumentException('Quote ID is required and must be numeric');
        }
        if (empty($this->cardData)) {
            throw new \InvalidArgumentException('Card data is required');
        }
        if (!$this->amount || $this->amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than 0');
        }
        if (empty($this->currency)) {
            throw new \InvalidArgumentException('Currency is required');
        }
        return true;
    }
}
```

---

## **FILE 13: Model/Request/SaleRequest.php**

```php
<?php
namespace Cybersource\Payment\Model\Request;

class SaleRequest
{
    private $quoteId;
    private $cardData;
    private $amount;
    private $currency;
    private $saveCard = false;

    public function __construct($quoteId, array $cardData, $amount, $currency, $saveCard = false)
    {
        $this->quoteId = $quoteId;
        $this->cardData = $cardData;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->saveCard = $saveCard;
    }

    public function getQuoteId()
    {
        return $this->quoteId;
    }

    public function getCardData()
    {
        return $this->cardData;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function isSaveCard()
    {
        return $this->saveCard;
    }

    public function validate()
    {
        if (!$this->quoteId) {
            throw new \InvalidArgumentException('Quote ID is required');
        }
        if (empty($this->cardData)) {
            throw new \InvalidArgumentException('Card data is required');
        }
        if (!$this->amount || $this->amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than 0');
        }
        return true;
    }
}
```

---

## **FILE 14: Model/Request/CaptureRequest.php**

```php
<?php
namespace Cybersource\Payment\Model\Request;

class CaptureRequest
{
    private $orderId;
    private $transactionId;
    private $amount;

    public function __construct($orderId, $transactionId, $amount = null)
    {
        $this->orderId = $orderId;
        $this->transactionId = $transactionId;
        $this->amount = $amount;
    }

    public function getOrderId()
    {
        return $this->orderId;
    }

    public function getTransactionId()
    {
        return $this->transactionId;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function validate()
    {
        if (!$this->orderId) {
            throw new \InvalidArgumentException('Order ID is required');
        }
        if (!$this->transactionId) {
            throw new \InvalidArgumentException('Transaction ID is required');
        }
        return true;
    }
}
```

---

## **FILE 15: Model/Request/VoidRequest.php**

```php
<?php
namespace Cybersource\Payment\Model\Request;

class VoidRequest
{
    private $orderId;
    private $transactionId;

    public function __construct($orderId, $transactionId)
    {
        $this->orderId = $orderId;
        $this->transactionId = $transactionId;
    }

    public function getOrderId()
    {
        return $this->orderId;
    }

    public function getTransactionId()
    {
        return $this->transactionId;
    }

    public function validate()
    {
        if (!$this->orderId) {
            throw new \InvalidArgumentException('Order ID is required');
        }
        if (!$this->transactionId) {
            throw new \InvalidArgumentException('Transaction ID is required');
        }
        return true;
    }
}
```

---

## **FILE 16: Model/Request/RefundRequest.php**

```php
<?php
namespace Cybersource\Payment\Model\Request;

class RefundRequest
{
    private $orderId;
    private $transactionId;
    private $amount;

    public function __construct($orderId, $transactionId, $amount = null)
    {
        $this->orderId = $orderId;
        $this->transactionId = $transactionId;
        $this->amount = $amount;
    }

    public function getOrderId()
    {
        return $this->orderId;
    }

    public function getTransactionId()
    {
        return $this->transactionId;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function validate()
    {
        if (!$this->orderId) {
            throw new \InvalidArgumentException('Order ID is required');
        }
        if (!$this->transactionId) {
            throw new \InvalidArgumentException('Transaction ID is required');
        }
        return true;
    }
}
```

---

## **FILE 17: Controller/Rest/Admin/Authorize.php**

```php
<?php
namespace Cybersource\Payment\Controller\Rest\Admin;

use Cybersource\Payment\Api\Admin\CardAuthorizationInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class Authorize extends Action
{
    /**
     * @var CardAuthorizationInterface
     */
    private $cardAuthorization;

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
        CardAuthorizationInterface $cardAuthorization,
        JsonFactory $jsonFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->cardAuthorization = $cardAuthorization;
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
    }

    public function execute()
    {
        try {
            $data = $this->getRequest()->getParams();

            if (empty($data['quote_id'])) {
                throw new LocalizedException(__('Quote ID is required'));
            }

            $cardData = $data['card_data'] ?? [];
            $amount = (float)($data['amount'] ?? 0);
            $saveCard = isset($data['save_card']) ? (bool)$data['save_card'] : false;

            $result = $this->cardAuthorization->authorize(
                $data['quote_id'],
                $cardData,
                $amount,
                $saveCard
            );

            return $this->jsonFactory->create()->setData($result);
        } catch (LocalizedException $e) {
            return $this->jsonFactory->create()->setData([
                'success' => false,
                'error' => $e->getMessage()
            ])->setHttpResponseCode(400);
        } catch (\Exception $e) {
            $this->logger->error('Authorization error: ' . $e->getMessage());
            return $this->jsonFactory->create()->setData([
                'success' => false,
                'error' => 'Authorization failed'
            ])->setHttpResponseCode(500);
        }
    }
}
```

---

## **FILE 18: Controller/Rest/Admin/Sale.php**

```php
<?php
namespace Cybersource\Payment\Controller\Rest\Admin;

use Cybersource\Payment\Api\Admin\CardAuthorizationInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class Sale extends Action
{
    /**
     * @var CardAuthorizationInterface
     */
    private $cardAuthorization;

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
        CardAuthorizationInterface $cardAuthorization,
        JsonFactory $jsonFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->cardAuthorization = $cardAuthorization;
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
    }

    public function execute()
    {
        try {
            $data = $this->getRequest()->getParams();

            if (empty($data['quote_id'])) {
                throw new LocalizedException(__('Quote ID is required'));
            }

            $cardData = $data['card_data'] ?? [];
            $amount = (float)($data['amount'] ?? 0);
            $saveCard = isset($data['save_card']) ? (bool)$data['save_card'] : false;

            // For sale, we authorize and set transaction type to SALE
            $result = $this->cardAuthorization->authorize(
                $data['quote_id'],
                $cardData,
                $amount,
                $saveCard
            );

            // Override transaction type to SALE
            $result['transaction_type'] = 'SALE';

            return $this->jsonFactory->create()->setData($result);
        } catch (LocalizedException $e) {
            return $this->jsonFactory->create()->setData([
                'success' => false,
                'error' => $e->getMessage()
            ])->setHttpResponseCode(400);
        } catch (\Exception $e) {
            $this->logger->error('Sale error: ' . $e->getMessage());
            return $this->jsonFactory->create()->setData([
                'success' => false,
                'error' => 'Sale transaction failed'
            ])->setHttpResponseCode(500);
        }
    }
}
```

---

## **FILE 19: Controller/Rest/Admin/AuthorizeToken.php**

```php
<?php
namespace Cybersource\Payment\Controller\Rest\Admin;

use Cybersource\Payment\Api\Admin\CardAuthorizationInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class AuthorizeToken extends Action
{
    /**
     * @var CardAuthorizationInterface
     */
    private $cardAuthorization;

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
        CardAuthorizationInterface $cardAuthorization,
        JsonFactory $jsonFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->cardAuthorization = $cardAuthorization;
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
    }

    public function execute()
    {
        try {
            $data = $this->getRequest()->getParams();

            if (empty($data['quote_id'])) {
                throw new LocalizedException(__('Quote ID is required'));
            }
            if (empty($data['public_hash'])) {
                throw new LocalizedException(__('Token is required'));
            }
            if (empty($data['customer_id'])) {
                throw new LocalizedException(__('Customer ID is required'));
            }

            $result = $this->cardAuthorization->authorizeWithToken(
                $data['quote_id'],
                $data['public_hash'],
                $data['customer_id'],
                (float)($data['amount'] ?? 0)
            );

            return $this->jsonFactory->create()->setData($result);
        } catch (LocalizedException $e) {
            return $this->jsonFactory->create()->setData([
                'success' => false,
                'error' => $e->getMessage()
            ])->setHttpResponseCode(400);
        } catch (\Exception $e) {
            $this->logger->error('Token authorization error: ' . $e->getMessage());
            return $this->jsonFactory->create()->setData([
                'success' => false,
                'error' => 'Token authorization failed'
            ])->setHttpResponseCode(500);
        }
    }
}
```

---

## **FILE 20: Controller/Rest/Admin/SaleToken.php**

```php
<?php
namespace Cybersource\Payment\Controller\Rest\Admin;

use Cybersource\Payment\Api\Admin\CardAuthorizationInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class SaleToken extends Action
{
    /**
     * @var CardAuthorizationInterface
     */
    private $cardAuthorization;

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
        CardAuthorizationInterface $cardAuthorization,
        JsonFactory $jsonFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->cardAuthorization = $cardAuthorization;
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
    }

    public function execute()
    {
        try {
            $data = $this->getRequest()->getParams();

            if (empty($data['quote_id'])) {
                throw new LocalizedException(__('Quote ID is required'));
            }
            if (empty($data['public_hash'])) {
                throw new LocalizedException(__('Token is required'));
            }

            $result = $this->cardAuthorization->authorizeWithToken(
                $data['quote_id'],
                $data['public_hash'],
                $data['customer_id'] ?? 0,
                (float)($data['amount'] ?? 0)
            );

            $result['transaction_type'] = 'SALE';

            return $this->jsonFactory->create()->setData($result);
        } catch (LocalizedException $e) {
            return $this->jsonFactory->create()->setData([
                'success' => false,
                'error' => $e->getMessage()
            ])->setHttpResponseCode(400);
        } catch (\Exception $e) {
            $this->logger->error('Sale token error: ' . $e->getMessage());
            return $this->jsonFactory->create()->setData([
                'success' => false,
                'error' => 'Sale with token failed'
            ])->setHttpResponseCode(500);
        }
    }
}
```

---

## **FILE 21: Controller/Rest/Admin/Capture.php**

```php
<?php
namespace Cybersource\Payment\Controller\Rest\Admin;

use Cybersource\Payment\Model\Api\OrderManagementService;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\OrderRepository;
use Psr\Log\LoggerInterface;

class Capture extends Action
{
    /**
     * @var OrderManagementService
     */
    private $orderManagementService;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

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
        OrderManagementService $orderManagementService,
        OrderRepository $orderRepository,
        JsonFactory $jsonFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->orderManagementService = $orderManagementService;
        $this->orderRepository = $orderRepository;
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
    }

    public function execute()
    {
        try {
            $data = $this->getRequest()->getParams();

            if (empty($data['order_id'])) {
                throw new LocalizedException(__('Order ID is required'));
            }
            if (empty($data['transaction_id'])) {
                throw new LocalizedException(__('Transaction ID is required'));
            }

            $order = $this->orderRepository->get($data['order_id']);
            $amount = isset($data['amount']) ? (float)$data['amount'] : null;

            $result = $this->orderManagementService->capture(
                $order,
                $data['transaction_id'],
                $amount
            );

            return $this->jsonFactory->create()->setData($result);
        } catch (LocalizedException $e) {
            return $this->jsonFactory->create()->setData([
                'success' => false,
                'error' => $e->getMessage()
            ])->setHttpResponseCode(400);
        } catch (\Exception $e) {
            $this->logger->error('Capture error: ' . $e->getMessage());
            return $this->jsonFactory->create()->setData([
                'success' => false,
                'error' => 'Capture failed'
            ])->setHttpResponseCode(500);
        }
    }
}
```

---

## **FILE 22: Controller/Rest/Admin/Void.php**

```php
<?php
namespace Cybersource\Payment\Controller\Rest\Admin;

use Cybersource\Payment\Model\Api\OrderManagementService;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\OrderRepository;
use Psr\Log\LoggerInterface;

class Void extends Action
{
    /**
     * @var OrderManagementService
     */
    private $orderManagementService;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

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
        OrderManagementService $orderManagementService,
        OrderRepository $orderRepository,
        JsonFactory $jsonFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->orderManagementService = $orderManagementService;
        $this->orderRepository = $orderRepository;
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
    }

    public function execute()
    {
        try {
            $data = $this->getRequest()->getParams();

            if (empty($data['order_id'])) {
                throw new LocalizedException(__('Order ID is required'));
            }
            if (empty($data['transaction_id'])) {
                throw new LocalizedException(__('Transaction ID is required'));
            }

            $order = $this->orderRepository->get($data['order_id']);

            $result = $this->orderManagementService->void(
                $order,
                $data['transaction_id']
            );

            return $this->jsonFactory->create()->setData($result);
        } catch (LocalizedException $e) {
            return $this->jsonFactory->create()->setData([
                'success' => false,
                'error' => $e->getMessage()
            ])->setHttpResponseCode(400);
        } catch (\Exception $e) {
            $this->logger->error('Void error: ' . $e->getMessage());
            return $this->jsonFactory->create()->setData([
                'success' => false,
                'error' => 'Void failed'
            ])->setHttpResponseCode(500);
        }
    }
}
```

---

## **FILE 23: Controller/Rest/Admin/Refund.php**

```php
<?php
namespace Cybersource\Payment\Controller\Rest\Admin;

use Cybersource\Payment\Model\Api\OrderManagementService;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\OrderRepository;
use Psr\Log\LoggerInterface;

class Refund extends Action
{
    /**
     * @var OrderManagementService
     */
    private $orderManagementService;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

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
        OrderManagementService $orderManagementService,
        OrderRepository $orderRepository,
        JsonFactory $jsonFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->orderManagementService = $orderManagementService;
        $this->orderRepository = $orderRepository;
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
    }

    public function execute()
    {
        try {
            $data = $this->getRequest()->getParams();

            if (empty($data['order_id'])) {
                throw new LocalizedException(__('Order ID is required'));
            }
            if (empty($data['transaction_id'])) {
                throw new LocalizedException(__('Transaction ID is required'));
            }

            $order = $this->orderRepository->get($data['order_id']);
            $amount = isset($data['amount']) ? (float)$data['amount'] : null;

            $result = $this->orderManagementService->refund(
                $order,
                $data['transaction_id'],
                $amount
            );

            return $this->jsonFactory->create()->setData($result);
        } catch (LocalizedException $e) {
            return $this->jsonFactory->create()->setData([
                'success' => false,
                'error' => $e->getMessage()
            ])->setHttpResponseCode(400);
        } catch (\Exception $e) {
            $this->logger->error('Refund error: ' . $e->getMessage());
            return $this->jsonFactory->create()->setData([
                'success' => false,
                'error' => 'Refund failed'
            ])->setHttpResponseCode(500);
        }
    }
}
```

---

## **FILE 24: Controller/Rest/Admin/VaultTokenManager.php**

```php
<?php
namespace Cybersource\Payment\Controller\Rest\Admin;

use Cybersource\Payment\Api\Admin\VaultTokenManagementInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class VaultTokenManager extends Action
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

    public function execute()
    {
        try {
            $method = $this->getRequest()->getMethod();
            $action = $this->getRequest()->getParam('action');

            if ($method === 'GET' && $action === 'list') {
                $customerId = $this->getRequest()->getParam('customer_id');
                if (!$customerId) {
                    throw new LocalizedException(__('Customer ID is required'));
                }

                $tokens = $this->vaultTokenManagement->listTokens($customerId);
                return $this->jsonFactory->create()->setData([
                    'success' => true,
                    'tokens' => $tokens
                ]);
            }

            if ($method === 'GET' && $action === 'get') {
                $publicHash = $this->getRequest()->getParam('public_hash');
                $customerId = $this->getRequest()->getParam('customer_id');

                $token = $this->vaultTokenManagement->getToken($publicHash, $customerId);
                return $this->jsonFactory->create()->setData([
                    'success' => true,
                    'token' => $token
                ]);
            }

            if ($method === 'DELETE' && $action === 'delete') {
                $publicHash = $this->getRequest()->getParam('public_hash');
                $customerId = $this->getRequest()->getParam('customer_id');

                $this->vaultTokenManagement->deleteToken($publicHash, $customerId);
                return $this->jsonFactory->create()->setData([
                    'success' => true,
                    'message' => 'Token deleted successfully'
                ]);
            }

            throw new LocalizedException(__('Invalid action'));
        } catch (LocalizedException $e) {
            return $this->jsonFactory->create()->setData([
                'success' => false,
                'error' => $e->getMessage()
            ])->setHttpResponseCode(400);
        } catch (\Exception $e) {
            $this->logger->error('Vault token manager error: ' . $e->getMessage());
            return $this->jsonFactory->create()->setData([
                'success' => false,
                'error' => 'Operation failed'
            ])->setHttpResponseCode(500);
        }
    }
}
```

---

*[Continues with Services, Helpers, Blocks, Templates, JavaScript, CSS and Configuration files in Part 3]*
