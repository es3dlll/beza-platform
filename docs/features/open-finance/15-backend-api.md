# Open Finance Backend API Specification

## Endpoint: Register Developer
```http
POST /api/v1/of/register
Content-Type: application/json

{
  "email": "rami@payfast.co",
  "company_name": "PayFast Syria",
  "company_website": "https://payfast.co",
  "phone": "+963912345678",
  "password": "SecurePass123!",
  "accept_terms": true
}
```

### Response: 201 Created
```json
{
  "status": "success",
  "data": {
    "developer_id": 42,
    "email": "rami@payfast.co",
    "company_name": "PayFast Syria",
    "tier": "free",
    "sandbox_key": "sk_test_abc123def456...xyz",
    "created_at": "2026-06-01T10:00:00Z"
  }
}
```

### Error Responses
```json
// 409 — Email already registered
{
  "status": "error",
  "error": {
    "code": "EMAIL_EXISTS",
    "message": "هذا البريد الإلكتروني مسجل بالفعل",
    "message_en": "This email is already registered",
    "details": { "email": ["already_taken"] },
    "request_id": "req_abc"
  }
}
```

## Endpoint: OAuth Token
```http
POST /api/v1/of/oauth/token
Content-Type: application/json

{
  "grant_type": "client_credentials",
  "client_id": "beza_client_abc123",
  "client_secret": "cs_live_xyz789...",
  "scope": "payments.write accounts.read"
}
```

### Response: 200 OK
```json
{
  "access_token": "beza_oat_abc123...xyz",
  "token_type": "Bearer",
  "expires_in": 7200,
  "scope": "payments.write accounts.read",
  "developer_id": 42
}
```

## Endpoint: Create API Key
```http
POST /api/v1/of/console/api-keys
Authorization: Bearer {token}
Content-Type: application/json

{
  "label": "Production Shop API",
  "environment": "production",
  "scopes": ["payments.write", "accounts.read", "webhooks.read"]
}
```

### Response: 201 Created
```json
{
  "status": "success",
  "data": {
    "id": "key_abc123",
    "label": "Production Shop API",
    "key": "sk_live_abc123def456...xyz",
    "key_preview": "sk_live_abc123...",
    "environment": "production",
    "scopes": ["payments.write", "accounts.read", "webhooks.read"],
    "expires_at": "2027-06-01T10:00:00Z",
    "created_at": "2026-06-01T10:00:00Z"
  }
}
```

## Endpoint: Console Dashboard
```http
GET /api/v1/of/console/dashboard
Authorization: Bearer {token}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "daily_requests": 15234,
    "error_rate": 2.3,
    "p99_latency_ms": 98,
    "active_apps": 3,
    "time_series": [
      {"hour": "00:00", "requests": 120, "errors": 2},
      {"hour": "01:00", "requests": 85, "errors": 1}
    ],
    "recent_requests": [
      {
        "method": "POST",
        "path": "/v1/of/payments",
        "status": 200,
        "latency_ms": 45,
        "timestamp": "2026-06-01T09:59:00Z"
      }
    ],
    "service_status": {
      "payments": {"status": "operational", "latency_ms": 45},
      "accounts": {"status": "operational", "latency_ms": 32},
      "webhooks": {"status": "operational", "latency_ms": 12}
    },
    "current_tier": "business",
    "usage_this_month": 45234,
    "usage_limit": 100000
  }
}
```

## Endpoint: Payment Initiation
```http
POST /api/v1/of/payments
Authorization: Bearer {token}
Idempotency-Key: 7c9e8f8a-3b2d-4e5f-8a7b-6c5d4e3f2a1b
Content-Type: application/json

{
  "amount": 25000,
  "currency": "SYP",
  "recipient": {
    "type": "wallet",
    "phone": "+963912345678"
  },
  "description": "Order #12345 from ShopSyria",
  "metadata": {
    "order_id": "ORD-12345",
    "customer_email": "ahmad@example.com"
  }
}
```

### Response: 201 Created
```json
{
  "status": "success",
  "data": {
    "payment_id": "pay_abc123",
    "status": "completed",
    "amount": 25000,
    "fee": 50,
    "currency": "SYP",
    "recipient": {
      "type": "wallet",
      "phone": "+963912345678",
      "name": "Ahmad Khaled"
    },
    "description": "Order #12345 from ShopSyria",
    "reference": "TXN-ABC123XYZ",
    "metadata": {
      "order_id": "ORD-12345"
    },
    "created_at": "2026-06-01T10:00:00Z"
  }
}
```

## Endpoint: Create Webhook
```http
POST /api/v1/of/console/webhooks
Authorization: Bearer {token}
Content-Type: application/json

{
  "url": "https://myshop.com/beza-webhook",
  "events": ["payment.completed", "payment.failed", "transfer.completed"],
  "description": "Order status updates"
}
```

### Response: 201 Created
```json
{
  "status": "success",
  "data": {
    "id": "wh_abc123",
    "url": "https://myshop.com/beza-webhook",
    "signing_secret": "whsec_abc123def456...",
    "events": ["payment.completed", "payment.failed", "transfer.completed"],
    "status": "active",
    "created_at": "2026-06-01T10:00:00Z"
  }
}
```

## Endpoint: Sandbox Reset
```http
POST /api/v1/of/sandbox/reset
Authorization: Bearer {token}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "message": "تم إعادة تعيين بيئة الاختبار",
    "message_en": "Sandbox environment has been reset",
    "accounts": [
      {"phone": "+963900000001", "balance_syp": 1000000, "balance_usd": 10000}
    ]
  }
}
```

## Endpoint: Account Information
```http
GET /api/v1/of/accounts/balance
Authorization: Bearer {token}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "balances": [
      {
        "currency": "SYP",
        "available": 125000,
        "held": 25000,
        "total": 150000,
        "last_updated": "2026-06-01T10:00:00Z"
      },
      {
        "currency": "USD",
        "available": 250,
        "held": 0,
        "total": 250,
        "last_updated": "2026-06-01T09:30:00Z"
      }
    ]
  }
}
```

## Standard Error Format
```json
{
  "status": "error",
  "error": {
    "code": "ERROR_CODE",
    "message": "رسالة الخطأ بالعربية",
    "message_en": "Error message in English",
    "details": {
      "field": ["وصف المشكلة"]
    },
    "request_id": "req_abc123",
    "documentation_url": "https://developers.beza.com/docs/errors#ERROR_CODE"
  }
}
```

### Common Error Codes
| Code | HTTP Status | Description |
|------|------------|-------------|
| INVALID_API_KEY | 401 | API key is invalid or expired |
| RATE_LIMIT_EXCEEDED | 429 | Rate limit exceeded for your tier |
| INSUFFICIENT_BALANCE | 402 | Source account has insufficient funds |
| IDEMPOTENCY_CONFLICT | 409 | Duplicate idempotency key with different params |
| INVALID_AMOUNT | 400 | Amount outside allowed range (min 1,000, max 10M) |
| INVALID_RECIPIENT | 400 | Recipient phone number not found or invalid |
| SANDBOX_ONLY | 400 | Operation only available in sandbox |
| UNSUPPORTED_VERSION | 400 | API version is deprecated or unknown |
