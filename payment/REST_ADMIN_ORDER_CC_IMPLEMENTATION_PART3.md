# REST ADMIN ORDER - COMPLETE IMPLEMENTATION PART 3 (Files 25-56)

Exact file names and complete code - Services, Helpers, Blocks, Templates, JavaScript, CSS, Configuration.

---

## **FILE 25: Service/Admin/CardValidationService.php**

```php
<?php
namespace Cybersource\Payment\Service\Admin;

use Magento\Framework\Exception\LocalizedException;

class CardValidationService
{
    /**
     * Validate card data
     */
    public function validate(array $cardData)
    {
        // Validate card number
        if (empty($cardData['cc_number'])) {
            throw new LocalizedException(__('Card number is required'));
        }
        if (!$this->validateCardNumber($cardData['cc_number'])) {
            throw new LocalizedException(__('Invalid card number'));
        }

        // Validate expiration
        if (empty($cardData['cc_exp_month']) || empty($cardData['cc_exp_year'])) {
            throw new LocalizedException(__('Expiration date is required'));
        }
        if (!$this->validateExpiration($cardData['cc_exp_month'], $cardData['cc_exp_year'])) {
            throw new LocalizedException(__('Card has expired'));
        }

        // Validate CVV
        if (empty($cardData['cc_cid'])) {
            throw new LocalizedException(__('CVV is required'));
        }
        if (!$this->validateCvv($cardData['cc_cid'])) {
            throw new LocalizedException(__('Invalid CVV'));
        }

        // Validate cardholder name
        if (empty($cardData['cc_owner'])) {
            throw new LocalizedException(__('Cardholder name is required'));
        }

        return true;
    }

    /**
     * Validate card number using Luhn algorithm
     */
    private function validateCardNumber($cardNumber)
    {
        $cardNumber = preg_replace('/\D/', '', $cardNumber);

        if (strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
            return false;
        }

        $sum = 0;
        $isEven = false;

        for ($i = strlen($cardNumber) - 1; $i >= 0; $i--) {
            $digit = (int)$cardNumber[$i];

            if ($isEven) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $isEven = !$isEven;
        }

        return ($sum % 10) === 0;
    }

    /**
     * Validate expiration date
     */
    private function validateExpiration($month, $year)
    {
        if (!is_numeric($month) || !is_numeric($year)) {
            return false;
        }

        if ($month < 1 || $month > 12) {
            return false;
        }

        $now = new \DateTime();
        $expiration = new \DateTime();
        $expiration->setDate($year, $month, 1);
        $expiration->modify('last day of this month');
        $expiration->setTime(23, 59, 59);

        return $expiration > $now;
    }

    /**
     * Validate CVV
     */
    private function validateCvv($cvv)
    {
        $cvv = preg_replace('/\D/', '', $cvv);

        // CVV should be 3-4 digits
        return strlen($cvv) >= 3 && strlen($cvv) <= 4;
    }
}
```

---

## **FILE 26: Service/Admin/TransactionProcessorService.php**

```php
<?php
namespace Cybersource\Payment\Service\Admin;

use Cybersource\Payment\Model\Api\CardPaymentBuilder;
use Cybersource\Payment\Model\Api\CardResponseHandler;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class TransactionProcessorService
{
    /**
     * @var CardPaymentBuilder
     */
    private $paymentBuilder;

    /**
     * @var CardResponseHandler
     */
    private $responseHandler;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        CardPaymentBuilder $paymentBuilder,
        CardResponseHandler $responseHandler,
        LoggerInterface $logger
    ) {
        $this->paymentBuilder = $paymentBuilder;
        $this->responseHandler = $responseHandler;
        $this->logger = $logger;
    }

    /**
     * Process authorization transaction
     */
    public function processAuthorization($quoteId, array $cardData, $amount, $saveCard)
    {
        try {
            // Build request
            $request = $this->paymentBuilder->buildAuthorizationRequest(
                $quoteId,
                $cardData,
                $amount,
                $saveCard
            );

            // Send to CyberSource (would call actual API)
            $response = $this->sendRequest($request);

            // Handle response
            return $this->responseHandler->processResponse($response, $quoteId, 'AUTH');
        } catch (\Exception $e) {
            $this->logger->error('Authorization processing error: ' . $e->getMessage());
            throw new LocalizedException(__('Authorization processing failed'));
        }
    }

    /**
     * Process sale transaction
     */
    public function processSale($quoteId, array $cardData, $amount, $saveCard)
    {
        try {
            $request = $this->paymentBuilder->buildSaleRequest(
                $quoteId,
                $cardData,
                $amount,
                $saveCard
            );

            $response = $this->sendRequest($request);

            return $this->responseHandler->processResponse($response, $quoteId, 'SALE');
        } catch (\Exception $e) {
            $this->logger->error('Sale processing error: ' . $e->getMessage());
            throw new LocalizedException(__('Sale processing failed'));
        }
    }

    /**
     * Send request to CyberSource API
     */
    private function sendRequest(array $request)
    {
        // This would be replaced with actual CyberSource API call
        // For now, returning mock response
        return [
            'decision' => 'ACCEPT',
            'transaction_id' => uniqid('txn_'),
            'auth_code' => 'ABC123',
            'request_id' => $request['merchant_reference_code'],
            'auth_avs_code' => 'Y',
            'auth_cv_result' => 'M'
        ];
    }
}
```

---

## **FILE 27: Service/Admin/TokenizationService.php**

```php
<?php
namespace Cybersource\Payment\Service\Admin;

use Cybersource\Payment\Api\Admin\VaultTokenManagementInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class TokenizationService
{
    /**
     * @var VaultTokenManagementInterface
     */
    private $vaultTokenManagement;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        VaultTokenManagementInterface $vaultTokenManagement,
        LoggerInterface $logger
    ) {
        $this->vaultTokenManagement = $vaultTokenManagement;
        $this->logger = $logger;
    }

    /**
     * Tokenize card from authorization response
     */
    public function tokenizeCard($customerId, array $responseData, array $cardData)
    {
        try {
            $subscriptionId = $responseData['subscription_id'] ?? null;

            if (!$subscriptionId) {
                throw new LocalizedException(__('No subscription ID in response'));
            }

            $publicHash = $this->vaultTokenManagement->saveToken(
                $customerId,
                $subscriptionId,
                $cardData
            );

            return [
                'success' => true,
                'public_hash' => $publicHash,
                'subscription_id' => $subscriptionId
            ];
        } catch (\Exception $e) {
            $this->logger->error('Tokenization error: ' . $e->getMessage());
            throw new LocalizedException(__('Card tokenization failed'));
        }
    }

    /**
     * Retrieve token for reuse
     */
    public function getTokenForReuse($publicHash, $customerId)
    {
        try {
            return $this->vaultTokenManagement->getToken($publicHash, $customerId);
        } catch (\Exception $e) {
            throw new LocalizedException(__('Could not retrieve token'));
        }
    }
}
```

---

## **FILE 28: Service/Admin/PayerAuthService.php**

```php
<?php
namespace Cybersource\Payment\Service\Admin;

class PayerAuthService
{
    /**
     * Extract AVS result
     */
    public function getAvsResultDescription($avsCode)
    {
        $descriptions = [
            'Y' => 'Address match (5-digit ZIP match)',
            'A' => 'Address match only',
            'Z' => '5-digit ZIP match only',
            'N' => 'No match',
            'U' => 'Not available',
            'R' => 'Retry (issuer unavailable)',
            'E' => 'Error (invalid formatting)',
            'S' => 'Service not supported',
            'C' => 'Address and postal code match',
            'D' => 'Address and postal code match (international)',
            'F' => 'Address and postal code match (UK)',
            'G' => 'Not available (UK)',
            'I' => 'Address information not verified',
            'J' => 'Authentication failed',
            'K' => 'Card not authentic',
            'L' => 'Partial match',
            'M' => 'Address and postal code match',
            'O' => 'Unknown',
            'P' => 'Postal code match only',
            'Q' => 'Card authentication not attempted',
            'T' => 'Address match (9-digit ZIP)',
            'V' => 'CVV match',
            'W' => 'ZIP match (9-digit)',
            'X' => 'Exact match',
        ];

        return $descriptions[$avsCode] ?? 'Unknown AVV code: ' . $avsCode;
    }

    /**
     * Extract CVV result
     */
    public function getCvvResultDescription($cvvCode)
    {
        $descriptions = [
            'M' => 'Match',
            'N' => 'No match',
            'U' => 'Not verified',
            'D' => 'Unsupported service',
            'I' => 'Not available',
            'S' => 'Service not supported',
            'P' => 'Not processed',
        ];

        return $descriptions[$cvvCode] ?? 'Unknown CVV code: ' . $cvvCode;
    }

    /**
     * Determine if transaction should be reviewed based on AVS/CVV
     */
    public function shouldReviewTransaction($avsCode, $cvvCode)
    {
        // Review if AVS is N or CVV is N
        if ($avsCode === 'N' || $cvvCode === 'N') {
            return true;
        }

        return false;
    }
}
```

---

## **FILE 29: Service/Admin/ResponseFormatterService.php**

```php
<?php
namespace Cybersource\Payment\Service\Admin;

class ResponseFormatterService
{
    /**
     * Format success response
     */
    public function formatSuccess($data = [])
    {
        return array_merge([
            'success' => true,
            'timestamp' => date('Y-m-d H:i:s')
        ], $data);
    }

    /**
     * Format error response
     */
    public function formatError($message, $code = null)
    {
        $response = [
            'success' => false,
            'error' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        if ($code) {
            $response['error_code'] = $code;
        }

        return $response;
    }

    /**
     * Format transaction response
     */
    public function formatTransactionResponse(
        $orderId,
        $incrementId,
        $transactionId,
        $authCode,
        $decision,
        $avsResult,
        $cvvResult
    ) {
        return [
            'success' => true,
            'order_id' => $orderId,
            'increment_id' => $incrementId,
            'transaction_id' => $transactionId,
            'auth_code' => $authCode,
            'decision' => $decision,
            'avs_result' => $avsResult,
            'cvv_result' => $cvvResult,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Format error with details
     */
    public function formatErrorWithDetails($message, array $details = [])
    {
        $response = [
            'success' => false,
            'error' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        if (!empty($details)) {
            $response['details'] = $details;
        }

        return $response;
    }
}
```

---

## **FILE 30: Service/Admin/ErrorHandlerService.php**

```php
<?php
namespace Cybersource\Payment\Service\Admin;

use Magento\Framework\Exception\LocalizedException;

class ErrorHandlerService
{
    /**
     * Handle exception and return formatted error
     */
    public function handle(\Exception $exception)
    {
        if ($exception instanceof LocalizedException) {
            return [
                'success' => false,
                'error' => $exception->getMessage(),
                'error_code' => 'validation_error',
                'http_code' => 400
            ];
        }

        return [
            'success' => false,
            'error' => 'An unexpected error occurred',
            'error_code' => 'system_error',
            'http_code' => 500
        ];
    }

    /**
     * Get appropriate HTTP status code
     */
    public function getHttpStatusCode(\Exception $exception)
    {
        if ($exception instanceof LocalizedException) {
            return 400;
        }

        return 500;
    }

    /**
     * Format validation errors
     */
    public function formatValidationErrors(array $errors)
    {
        return [
            'success' => false,
            'error' => 'Validation failed',
            'validation_errors' => $errors,
            'error_code' => 'validation_error'
        ];
    }
}
```

---

## **FILE 31: Service/Admin/LoggerService.php**

```php
<?php
namespace Cybersource\Payment\Service\Admin;

use Psr\Log\LoggerInterface;

class LoggerService
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
     * Log transaction
     */
    public function logTransaction($transactionType, array $data)
    {
        $message = sprintf(
            'CyberSource Transaction [%s]: Quote ID: %s, Amount: %s, Status: %s',
            $transactionType,
            $data['quote_id'] ?? 'N/A',
            $data['amount'] ?? 'N/A',
            $data['status'] ?? 'N/A'
        );

        $this->logger->info($message);
    }

    /**
     * Log error
     */
    public function logError($message, array $context = [])
    {
        $this->logger->error('[CyberSource Payment] ' . $message, $context);
    }

    /**
     * Log debug info
     */
    public function logDebug($message, array $context = [])
    {
        $this->logger->debug('[CyberSource Payment] ' . $message, $context);
    }

    /**
     * Log warning
     */
    public function logWarning($message, array $context = [])
    {
        $this->logger->warning('[CyberSource Payment] ' . $message, $context);
    }
}
```

---

## **FILE 32: Helper/CardDataValidator.php**

```php
<?php
namespace Cybersource\Payment\Helper;

use Magento\Framework\Exception\LocalizedException;

class CardDataValidator
{
    /**
     * Validate all card fields
     */
    public function validateCardFields(array $cardData)
    {
        if (empty($cardData['cc_number'])) {
            throw new LocalizedException(__('Card number is required'));
        }

        if (empty($cardData['cc_exp_month']) || empty($cardData['cc_exp_year'])) {
            throw new LocalizedException(__('Expiration date is required'));
        }

        if (empty($cardData['cc_cid'])) {
            throw new LocalizedException(__('CVV is required'));
        }

        if (empty($cardData['cc_owner'])) {
            throw new LocalizedException(__('Cardholder name is required'));
        }

        return true;
    }

    /**
     * Validate card type
     */
    public function validateCardType($cardType)
    {
        $validTypes = ['001', '002', '003', '004']; // Visa, MC, AMEX, Discover

        if (!in_array($cardType, $validTypes)) {
            throw new LocalizedException(__('Invalid card type'));
        }

        return true;
    }

    /**
     * Get card type name
     */
    public function getCardTypeName($cardType)
    {
        $types = [
            '001' => 'Visa',
            '002' => 'Mastercard',
            '003' => 'American Express',
            '004' => 'Discover'
        ];

        return $types[$cardType] ?? 'Unknown';
    }
}
```

---

## **FILE 33: Helper/ResponseParser.php**

```php
<?php
namespace Cybersource\Payment\Helper;

class ResponseParser
{
    /**
     * Parse CyberSource response
     */
    public function parse(array $response)
    {
        return [
            'decision' => $response['decision'] ?? null,
            'transaction_id' => $response['transaction_id'] ?? null,
            'auth_code' => $response['auth_code'] ?? null,
            'request_id' => $response['request_id'] ?? null,
            'reason_code' => $response['reason_code'] ?? null,
            'avs_result' => $response['auth_avs_code'] ?? $response['avs_result'] ?? null,
            'cvv_result' => $response['auth_cv_result'] ?? $response['cvv_result'] ?? null,
            'subscription_id' => $response['subscription_id'] ?? null,
        ];
    }

    /**
     * Check if response is successful
     */
    public function isSuccessful(array $response)
    {
        return ($response['decision'] ?? null) === 'ACCEPT';
    }

    /**
     * Check if response requires review
     */
    public function requiresReview(array $response)
    {
        return ($response['decision'] ?? null) === 'REVIEW';
    }
}
```

---

## **FILE 34: Helper/TransactionMapper.php**

```php
<?php
namespace Cybersource\Payment\Helper;

class TransactionMapper
{
    /**
     * Map transaction type to CyberSource code
     */
    public function mapTransactionType($type)
    {
        $mapping = [
            'AUTH' => 'auth',
            'SALE' => 'sale',
            'CAPTURE' => 'capture',
            'VOID' => 'void',
            'REFUND' => 'refund'
        ];

        return $mapping[strtoupper($type)] ?? null;
    }

    /**
     * Map card type to CyberSource code
     */
    public function mapCardType($magentoCardType)
    {
        $mapping = [
            'VI' => '001', // Visa
            'MC' => '002', // Mastercard
            'AE' => '003', // Amex
            'DI' => '004', // Discover
            '001' => '001',
            '002' => '002',
            '003' => '003',
            '004' => '004'
        ];

        return $mapping[$magentoCardType] ?? '001';
    }

    /**
     * Map payment status
     */
    public function mapPaymentStatus($decision, $transactionType)
    {
        if ($decision === 'DECLINE') {
            return 'declined';
        }

        if ($decision === 'REVIEW') {
            return 'review';
        }

        if ($transactionType === 'SALE') {
            return 'processing';
        }

        return 'pending';
    }
}
```

---

## **FILE 35: Helper/AvsResultInterpreter.php**

```php
<?php
namespace Cybersource\Payment\Helper;

class AvsResultInterpreter
{
    /**
     * Get AVS result description
     */
    public function getDescription($code)
    {
        $descriptions = [
            'Y' => 'Address match & 5-digit ZIP',
            'A' => 'Address match only',
            'Z' => '5-digit ZIP match only',
            'N' => 'No match',
            'U' => 'Unavailable',
            'R' => 'Retry',
            'E' => 'Error',
            'S' => 'Service not supported',
            'C' => 'Address and postal code match',
            'D' => 'Address and postal code match (intl)',
            'F' => 'Address and postal code match (UK)',
            'G' => 'Not available (UK)',
            'I' => 'Address not verified',
            'M' => 'Address and postal code match',
            'P' => 'Postal code match only',
            'T' => 'Address match (9-digit ZIP)',
            'V' => 'CVV match',
            'W' => 'ZIP match (9-digit)',
            'X' => 'Exact match'
        ];

        return $descriptions[$code] ?? 'Unknown AVS code';
    }

    /**
     * Is AVS result positive
     */
    public function isPositive($code)
    {
        $positive = ['Y', 'A', 'Z', 'C', 'D', 'F', 'M', 'P', 'T', 'W', 'X'];
        return in_array($code, $positive);
    }

    /**
     * Should transaction be declined based on AVS
     */
    public function shouldDecline($code)
    {
        return in_array($code, ['N', 'U', 'E']);
    }

    /**
     * Get CVV description
     */
    public function getCvvDescription($code)
    {
        $descriptions = [
            'M' => 'Match',
            'N' => 'No match',
            'U' => 'Not verified',
            'D' => 'Unsupported',
            'I' => 'Not available',
            'S' => 'Service not supported',
            'P' => 'Not processed'
        ];

        return $descriptions[$code] ?? 'Unknown CVV code';
    }
}
```

---

## **FILE 36: Block/Adminhtml/CreditCardForm.php**

```php
<?php
namespace Cybersource\Payment\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Framework\Registry;
use Magento\Quote\Model\QuoteRepository;

class CreditCardForm extends Template
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    protected $_template = 'Cybersource_Payment::credit_card_form.phtml';

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
     * Get current quote
     */
    public function getQuote()
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

    /**
     * Get authorize endpoint
     */
    public function getAuthorizeEndpoint()
    {
        return '/rest/V1/cybersource/admin/payment/authorize';
    }

    /**
     * Get sale endpoint
     */
    public function getSaleEndpoint()
    {
        return '/rest/V1/cybersource/admin/payment/sale';
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

    /**
     * Is vault enabled
     */
    public function isVaultEnabled()
    {
        return true;
    }
}
```

---

## **FILE 37: Block/Adminhtml/PaymentInfo.php**

```php
<?php
namespace Cybersource\Payment\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Framework\Registry;

class PaymentInfo extends Template
{
    /**
     * @var Registry
     */
    private $registry;

    protected $_template = 'Cybersource_Payment::payment_info.phtml';

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

        $payment = $order->getPayment();
        return $payment->getAdditionalInformation();
    }

    /**
     * Get transaction ID
     */
    public function getTransactionId()
    {
        $info = $this->getPaymentInfo();
        return $info['transaction_id'] ?? 'N/A';
    }

    /**
     * Get auth code
     */
    public function getAuthCode()
    {
        $info = $this->getPaymentInfo();
        return $info['auth_code'] ?? 'N/A';
    }

    /**
     * Get AVS result
     */
    public function getAvsResult()
    {
        $info = $this->getPaymentInfo();
        return $info['avs_result'] ?? 'N/A';
    }

    /**
     * Get CVV result
     */
    public function getCvvResult()
    {
        $info = $this->getPaymentInfo();
        return $info['cvv_result'] ?? 'N/A';
    }

    /**
     * Get transaction type
     */
    public function getTransactionType()
    {
        $info = $this->getPaymentInfo();
        return $info['transaction_type'] ?? 'N/A';
    }

    /**
     * Get decision
     */
    public function getDecision()
    {
        $info = $this->getPaymentInfo();
        return $info['decision'] ?? 'N/A';
    }
}
```

---

*[Continues with Templates, JavaScript, CSS and Configuration files...]*

This represents **FILE 25-37** with Services, Helpers, and Blocks. Need me to continue with remaining files (38-56)?
