# CyberSource Payment Module - REST API (v25.2.0)

This module provides REST API endpoints for CyberSource payment processing in Magento 2 admin order creation.

## Features

- **Flex Microform Integration**: Secure token-based card data capture
- **SOP (Silent Order Post)**: Legacy payment processing
- **Payment Operations**: Authorize, Sale, Capture, Void, Refund
- **Vault Integration**: Save and reuse payment tokens
- **Admin Order Support**: Complete admin order creation workflow

## API Endpoints

### Token Generation
- **POST** `/rest/V1/cybersource/admin/token/generate`
- Generates Flex microform tokens for secure card data capture

### SOP Processing
- **POST** `/rest/V1/cybersource/admin/sop/request-data` - Build SOP request data
- **POST** `/rest/V1/cybersource/admin/sop/response` - Handle SOP responses

### Flex Order Creation
- **POST** `/rest/V1/cybersource/admin/flex/place-order` - Create orders with Flex tokens

### Payment Operations
- **POST** `/rest/V1/cybersource/admin/payment/authorize` - Authorize payment only
- **POST** `/rest/V1/cybersource/admin/payment/sale` - Authorize and capture
- **POST** `/rest/V1/cybersource/admin/payment/capture` - Capture authorized payment
- **POST** `/rest/V1/cybersource/admin/payment/void` - Void transaction
- **POST** `/rest/V1/cybersource/admin/payment/refund` - Process refund

## Installation

1. Copy module files to `app/code/CyberSource/Payment/`
2. Run `php bin/magento setup:upgrade`
3. Run `php bin/magento setup:di:compile`
4. Run `php bin/magento cache:flush`

## Configuration

Configure CyberSource credentials in Magento admin:
- Stores → Configuration → Sales → Payment Methods → CyberSource

## Usage Examples

### Generate Flex Token
```bash
curl -X POST "https://your-domain/rest/V1/cybersource/admin/token/generate" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"quote_id": 123, "store_id": 1}'
```

### Authorize Payment
```bash
curl -X POST "https://your-domain/rest/V1/cybersource/admin/payment/authorize" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "quote_id": 123,
    "card_data": {
      "cc_type": "001",
      "cc_number": "4111111111111111",
      "cc_exp_month": "12",
      "cc_exp_year": "2025",
      "cc_cid": "123"
    },
    "amount": 100.00,
    "currency": "USD"
  }'
```

## Architecture

- **API Layer**: Interfaces define contracts for all operations
- **Model Layer**: Implementation classes handle business logic
- **Controller Layer**: REST controllers handle HTTP requests/responses
- **Gateway Layer**: Integration with CyberSource REST APIs

## Security

- All endpoints require admin authentication (Bearer tokens)
- Card data is tokenized and never stored in Magento
- PCI compliance through CyberSource tokenization
- Request/response validation and sanitization

## Dependencies

- Magento Framework
- Magento Payment Module
- Magento Quote Module
- Magento Sales Module
- Magento Customer Module
- Magento Vault Module