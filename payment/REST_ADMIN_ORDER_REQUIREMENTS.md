# REST ADMIN ORDER - REQUIREMENTS SPECIFICATION

Updated requirements for CyberSource REST Admin Order Implementation (Cybersource\Payment namespace).

---

## **1. PAYMENT METHOD: CREDIT CARD**

### **Supported Credit Card Types**
- Visa (001)
- Mastercard (002)
- American Express (003)
- Discover (004)

### **Card Data Collection**
- Card Number (masked)
- Expiration Date (MM/YYYY)
- CVV (3-4 digits)
- Cardholder Name
- Billing Address
- Optional: Save for future use

---

## **2. TRANSACTION TYPES**

### **2.1 Authorization Only (AUTH)**
- Request payment authorization without immediate capture
- Hold amount on cardholder account
- Requires separate capture/settlement
- Endpoint: POST `/rest/V1/cybersource/admin/payment/authorize`

**Request:**
```json
{
    "quote_id": 1,
    "card_data": {
        "cc_type": "001",
        "cc_number": "4111111111111111",
        "cc_exp_month": "12",
        "cc_exp_year": "2025",
        "cc_cid": "123",
        "cc_owner": "John Doe"
    },
    "amount": 100.00,
    "currency": "USD",
    "save_card": true
}
```

**Response:**
```json
{
    "success": true,
    "transaction_id": "5123456789012345678901",
    "auth_code": "ABC123",
    "request_id": "5123456789012345678901",
    "amount": 100.00,
    "decision": "ACCEPT",
    "avs_result": "Y",
    "cvv_result": "M",
    "order_id": 123,
    "increment_id": "000000123"
}
```

### **2.2 Sale (AUTH + CAPTURE)**
- Request payment authorization and immediate capture
- Charge amount immediately
- No separate capture step needed
- Endpoint: POST `/rest/V1/cybersource/admin/payment/sale`

**Request:**
```json
{
    "quote_id": 1,
    "card_data": {
        "cc_type": "001",
        "cc_number": "4111111111111111",
        "cc_exp_month": "12",
        "cc_exp_year": "2025",
        "cc_cid": "123",
        "cc_owner": "John Doe"
    },
    "amount": 100.00,
    "currency": "USD",
    "save_card": true
}
```

**Response:**
```json
{
    "success": true,
    "transaction_id": "5123456789012345678901",
    "auth_code": "ABC123",
    "request_id": "5123456789012345678901",
    "capture_request_id": "5123456789012345678902",
    "amount": 100.00,
    "decision": "ACCEPT",
    "avs_result": "Y",
    "cvv_result": "M",
    "order_id": 123,
    "increment_id": "000000123",
    "capture_status": "COMPLETED"
}
```

---

## **3. TOKENIZATION**

### **3.1 Save Card Token**
- Automatically save card when `save_card: true` in request
- Create vault token in Magento
- Store CyberSource subscription ID
- Enable future use for same customer
- Endpoint: Automatic during Authorization/Sale with `save_card: true`

### **3.2 Use Saved Token**
- Use previously tokenized card for payment
- No need to send card number
- Only send token reference
- Endpoint: POST `/rest/V1/cybersource/admin/payment/authorize-token` or `/rest/V1/cybersource/admin/payment/sale-token`

**Request:**
```json
{
    "quote_id": 1,
    "token_data": {
        "public_hash": "abc123def456",
        "customer_id": 5
    },
    "amount": 100.00,
    "currency": "USD"
}
```

### **3.3 Manage Tokens**
- List customer tokens: GET `/rest/V1/cybersource/admin/vault/tokens/:customerId`
- Delete token: DELETE `/rest/V1/cybersource/admin/vault/token/:publicHash`

---

## **4. ORDER MANAGEMENT (OM)**

Post-authorization operations to modify or manage existing transactions.

### **4.1 Capture**
- Convert authorization to capture
- Required after Authorization-only transaction
- Endpoint: POST `/rest/V1/cybersource/admin/payment/capture`

**Request:**
```json
{
    "order_id": 123,
    "transaction_id": "5123456789012345678901",
    "amount": 100.00
}
```

**Response:**
```json
{
    "success": true,
    "capture_request_id": "5123456789012345678902",
    "amount": 100.00,
    "decision": "ACCEPT"
}
```

### **4.2 Void**
- Cancel authorized transaction
- Prevent final settlement
- Endpoint: POST `/rest/V1/cybersource/admin/payment/void`

**Request:**
```json
{
    "order_id": 123,
    "transaction_id": "5123456789012345678901"
}
```

### **4.3 Refund**
- Refund captured/settled transaction
- Full or partial refund
- Endpoint: POST `/rest/V1/cybersource/admin/payment/refund`

**Request:**
```json
{
    "order_id": 123,
    "transaction_id": "5123456789012345678901",
    "amount": 50.00
}
```

---

## **5. PAYER AUTHORIZATION**

Customer verification for fraud prevention and regulatory compliance.

### **5.1 3D Secure / AVS / CVV**
- Automatic AVS (Address Verification System) check
- Automatic CVV verification
- Optional 3D Secure for high-risk regions
- Results returned in transaction response

### **5.2 Payer Authentication Decision**
- ACCEPT: Transaction approved
- REVIEW: Requires review before settlement
- DECLINE: Transaction rejected
- Endpoint: Automatic during Authorization/Sale

**AVS Result Codes:**
- Y: Address match & 5-digit ZIP match
- A: Address match only
- Z: 5-digit ZIP match only
- N: No match
- U: Not available
- etc.

**CVV Result Codes:**
- M: Match
- N: No match
- U: Not verified
- etc.

---

## **6. REST API ENDPOINTS (UPDATED)**

### **Core Payment Endpoints**

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/rest/V1/cybersource/admin/payment/authorize` | Authorize payment (no capture) |
| POST | `/rest/V1/cybersource/admin/payment/sale` | Authorize + Capture payment |
| POST | `/rest/V1/cybersource/admin/payment/authorize-token` | Authorize with saved token |
| POST | `/rest/V1/cybersource/admin/payment/sale-token` | Sale with saved token |

### **Order Management (OM) Endpoints**

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/rest/V1/cybersource/admin/payment/capture` | Capture authorized transaction |
| POST | `/rest/V1/cybersource/admin/payment/void` | Void authorized transaction |
| POST | `/rest/V1/cybersource/admin/payment/refund` | Refund captured transaction |

### **Vault (Tokenization) Endpoints**

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/rest/V1/cybersource/admin/vault/save-token` | Save new card token |
| GET | `/rest/V1/cybersource/admin/vault/tokens/:customerId` | List customer tokens |
| DELETE | `/rest/V1/cybersource/admin/vault/token/:publicHash` | Delete token |
| GET | `/rest/V1/cybersource/admin/vault/token/:publicHash` | Get token details |

---

## **7. MODULE FILE STRUCTURE (UPDATED - 45 FILES)**

### **API Interfaces (5)**
- TokenGeneratorInterface → CardAuthorizationInterface
- SopRequestDataBuilderInterface → CardPaymentBuilderInterface
- SopResponseHandlerInterface → CardResponseHandlerInterface
- FlexOrderCreatorInterface → OrderCreatorInterface
- VaultTokenManagementInterface (KEEP)

### **Models (15)**
- Core API implementations (5)
- Request/Response data models (9)
- Config models (1)

### **Controllers (8)**
- AuthorizePayment
- SalePayment
- AuthorizeWithToken
- SaleWithToken
- CapturePayment
- VoidPayment
- RefundPayment
- VaultTokenManager

### **Services (8)**
- CardValidationService
- TransactionProcessorService
- OmService (Order Management)
- TokenizationService
- PayerAuthService
- ResponseFormatterService
- ErrorHandlerService
- LoggerService

### **Helpers (4)**
- CardDataValidator
- ResponseParser
- TransactionMapper
- AvsResultInterpreter

### **Blocks (2)**
- CreditCardForm
- PaymentInfo

### **Templates (3)**
- credit_card_form.phtml
- payment_info.phtml
- error_message.phtml

### **JavaScript (4)**
- card-form-handler.js
- card-validator.js
- payment-processor.js
- token-handler.js

### **CSS (1)**
- payment-form.css

### **Configuration (5)**
- webapi.xml
- di.xml
- routes.xml
- system.xml
- acl.xml

---

## **8. REMOVED COMPONENTS**

The following Flex Microform-specific files are NOT needed:

- ❌ Flex token generation endpoints
- ❌ Flex microform JavaScript library integration
- ❌ Flex JWT token handling
- ❌ Flex client library URLs
- ❌ FlexTokenDisplay block
- ❌ flex_token.phtml template
- ❌ token-generator.js
- ❌ SOP (Secure Order Post) endpoints/components

---

## **9. PAYMENT PROCESSING FLOW**

### **Authorization-Only Flow**
```
1. Customer fills credit card form in admin
2. Validate card data (number, exp, CVV, name, address)
3. POST /rest/V1/cybersource/admin/payment/authorize
4. CyberSource processes authorization request
5. Return auth code + decision
6. Save transaction (PENDING status)
7. Quote converts to Order
8. Order shows as "Processing" (awaiting capture)
9. Later: Admin captures payment when ready
```

### **Sale (AUTH + CAPTURE) Flow**
```
1. Customer fills credit card form in admin
2. Validate card data
3. POST /rest/V1/cybersource/admin/payment/sale
4. CyberSource authorizes + captures
5. Return auth code + capture request ID
6. Save transaction (COMPLETED status)
7. Quote converts to Order
8. Order shows as "Processing" (payment captured)
9. Ready for shipment
```

### **Saved Card (Tokenization) Flow**
```
1. Initial transaction with save_card: true
2. CyberSource generates subscription ID
3. Magento creates vault token
4. Future transactions use token
5. POST /rest/V1/cybersource/admin/payment/authorize-token
6. No card data needed, only token reference
7. Faster checkout, no PCI data stored
```

### **Order Management (OM) Flow**
```
Authorization transaction exists:
1. Check decision = ACCEPT + auth_code exists
2. If not captured: POST /rest/V1/cybersource/admin/payment/capture
3. If capture fails: POST /rest/V1/cybersource/admin/payment/void
4. If capture successful: transaction COMPLETED

After settlement:
1. POST /rest/V1/cybersource/admin/payment/refund
2. CyberSource processes credit
3. Order shows refunded status
```

---

## **10. VALIDATION REQUIREMENTS**

### **Card Data Validation**
- Card number: Luhn algorithm + format validation
- Expiration: Not expired, valid format (MM/YYYY)
- CVV: 3-4 digits, numeric only
- Cardholder name: Required, 2+ characters
- Billing address: Address required, ZIP code required

### **Transaction Validation**
- Amount: > 0, proper decimal format
- Currency: ISO 4217 code (USD, EUR, etc.)
- Quote exists and is valid
- Customer has permission for admin order

### **Security Validation**
- SSL/HTTPS required
- Admin user authenticated + authorized
- CSRF token validation
- Input sanitization for all fields
- No card data logged to files

---

## **11. RESPONSE CODES & HANDLING**

### **Success Responses**
- 200: Transaction accepted or processed
- 201: Resource created (token, order)

### **Error Responses**
- 400: Invalid card data / validation error
- 401: Unauthorized (admin not authenticated)
- 403: Forbidden (insufficient permissions)
- 404: Quote/Order not found
- 422: Validation failed (AVS/CVV decline)
- 500: CyberSource API error / system error

### **Decision Results**
- ACCEPT: Transaction approved, proceed
- DECLINE: Transaction rejected, show error
- REVIEW: Transaction flagged for review, hold order

---

## **12. NEXT STEPS**

1. ✅ Create 45 production-ready files
2. ✅ Remove all Flex-related code
3. ✅ Implement credit card form handling
4. ✅ Implement Authorization transaction type
5. ✅ Implement Sale transaction type
6. ✅ Implement Order Management (OM)
7. ✅ Implement Tokenization
8. ✅ Implement Payer Authorization (AVS/CVV)
9. Create implementation guide
10. Create testing documentation

---

This specification provides the complete requirements for REST Admin Order implementation without Flex Microform, focusing on standard credit card processing with full transaction management capabilities.
