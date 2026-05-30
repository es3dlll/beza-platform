# Telecom Integration

## Overview

Direct REST API integration with Syrian telecom operators for mobile top-up and data bundle purchases. Supports Syriatel (093x, 094x) and MTN (095x, 096x) networks.

## Architecture

```
Beza Marketplace
    │
    ├── TelecomIntegrationService
    │       │
    │       ├── SyriatelProvider
    │       │       ├── topUp(phone, amount, requestId) -> POST /api/v1/topup
    │       │       ├── checkBalance(phone) -> GET /api/v1/balance/{phone}
    │       │       ├── getDataPlans() -> GET /api/v1/plans
    │       │       ├── purchaseDataPlan(phone, planId, requestId) -> POST /api/v1/datapurchase
    │       │       └── getTransactionStatus(requestId) -> GET /api/v1/status/{requestId}
    │       │
    │       └── MTNProvider
    │               ├── topUp(phone, amount, requestId) -> POST /api/topup
    │               ├── checkBalance(phone) -> GET /api/balance/{phone}
    │               ├── getDataPlans() -> GET /api/databundles
    │               ├── purchaseDataPlan(phone, planId, requestId) -> POST /api/databundles
    │               └── getTransactionStatus(requestId) -> GET /api/status/{requestId}
    │
    └── SmsFallbackProvider
            └── sendSmsTopUp(phone, amount) -> SMPP/USSD gateway
```

## Syriatel Provider

### Base URL
- Production: `https://api.syriatel.sy/topup/v2`
- Sandbox: `https://sandbox.api.syriatel.sy/topup/v2`

### Authentication
- API Key in header: `X-API-Key: ${SYRIATEL_API_KEY}`
- HMAC-SHA256 signature: `X-Signature: HMAC(request_body + timestamp + secret)`
- IP whitelist required
- Rate limit: 100 req/min

### Top-Up Endpoint
```
POST /api/v1/top-up
Content-Type: application/json
X-API-Key: <api_key>
X-Signature: <hmac>
X-Timestamp: 2026-05-29T10:30:00Z

{
  "subscriberNumber": "963933456789",
  "amount": 10000,
  "currency": "SYP",
  "requestId": "txn_syr_a7f3k2",
  "callbackUrl": "https://api.beza.sy/marketplace/v1/telecom/callback/syriatel",
  "timestamp": "2026-05-29T10:30:00Z"
}
```

### Response
```json
{
  "status": "ACCEPTED",
  "transactionId": "SYR-TXN-123456",
  "requestId": "txn_syr_a7f3k2",
  "estimatedCompletionTime": "2026-05-29T10:30:05Z"
}
```

### Status Polling
```
GET /api/v1/status/{transactionId}
Response:
{
  "status": "COMPLETED",  // PENDING | PROCESSING | COMPLETED | FAILED
  "transactionId": "SYR-TXN-123456",
  "completedAt": "2026-05-29T10:30:05Z",
  "referenceNumber": "SYR-REF-789012"
}
```

## MTN Provider

### Base URL
- Production: `https://api.mtn.com.sy/topup/v1`
- Sandbox: `https://sandbox.api.mtn.com.sy/topup/v1`

### Authentication
- OAuth 2.0 Client Credentials Grant
- Token endpoint: `POST /oauth/v2/token`
- Scope: `topup:write balance:read`
- Token TTL: 3600s
- Rate limit: 200 req/min

### Top-Up Endpoint
```
POST /api/topup
Authorization: Bearer <access_token>
Content-Type: application/json

{
  "msisdn": "963955123456",
  "amount": 5000,
  "currency": "SYP",
  "externalId": "txn_mtn_b4c5d6",
  "callbackUrl": "https://api.beza.sy/marketplace/v1/telecom/callback/mtn"
}
```

## Fallback Strategy

When the primary API is unavailable:

| Level | Method | Latency | Reliability |
|---|---|---|---|
| 1 (Primary) | REST API | ~3s | 99.5% |
| 2 (Fallback) | SMS Gateway (SMPP) | ~30s | 95% |
| 3 (Manual) | USSD code sent to user | User-initiated | 99% |

## Reconciliation

Daily reconciliation between Beza records and telecom operator records:

```sql
-- Reconciliation query
SELECT 
    DATE(created_at) as date,
    network,
    COUNT(*) as transactions,
    SUM(amount) as total_amount,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
FROM marketplace_topups
WHERE created_at >= NOW() - INTERVAL '48 hours'
GROUP BY DATE(created_at), network;
```

Weekly settlement file generation (CSV format) for finance team to process payments with telecom operators.
