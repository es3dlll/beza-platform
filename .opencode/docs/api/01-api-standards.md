# API Standards — Beza Platform

> **Status:** Approved  
> **Last updated:** 2025-12-01  
> **Owner:** Platform Architecture

---

## 1. Base URL

```
Production:    https://api.beza.sy/api/v1/{module}/{resource}
Sandbox:       https://sandbox-api.beza.sy/api/v1/{module}/{resource}
Local dev:     https://api.beza.test/api/v1/{module}/{resource}
```

### Examples

```
GET  /api/v1/wallet/transactions
POST /api/v1/identity/users
POST /api/v1/fx/quotes
GET  /api/v1/agent/cash-ins
```

**Module names are singular** in the URL path, matching the module directory convention.

---

## 2. Authentication

### Header

```
Authorization: Bearer {jwt}
```

### JWT Structure

| Claim | Type | Description | Example |
|-------|------|-------------|---------|
| `sub` | UUID | User ID | `719c5e4c-7b3f-4b1c-9e2a-3f8e1c4d7a0b` |
| `role` | String | User role | `customer`, `agent`, `admin` |
| `permissions` | String[] | Scope permissions | `["wallet:read","wallet:write"]` |
| `device_id` | UUID | Bound device identifier | `a1b2c3d4-e5f6-7890-abcd-ef1234567890` |
| `session_id` | UUID | Current session | `f4e3d2c1-b0a9-8765-4321-0fedcba98765` |
| `iat` | Unix ts | Issued at | `1733040000` |
| `exp` | Unix ts | Expires | `1733040900` |

### Token Lifecycle

| Token | Expiry | Storage | Purpose |
|-------|--------|---------|---------|
| **Access token** | 15 minutes | Memory (mobile) / HttpOnly cookie (web) | API authorization |
| **Refresh token** | 7 days | Secure storage (Keychain/Keystore) | Issue new access tokens |
| **Device token** | Permanent (revocable) | Device secure enclave | Device binding |

### Refresh Flow

```
POST /api/v1/identity/auth/refresh
Authorization: Bearer {refresh_token}

Response:
{
  "access_token": "eyJhbGciOiJSUzI1NiIs...",
  "expires_in": 900,
  "refresh_token": "eyJhbGciOiJSUzI1NiIs..."
}
```

---

## 3. Request ID

### Standard

Every request **MUST** include:

```
X-Request-Id: req_{uuid_v4}
```

Example: `X-Request-Id: req_719c5e4c-7b3f-4b1c-9e2a-3f8e1c4d7a0b`

### Rules

- **Client generates** the Request ID (do not trust server-generated IDs for idempotency correlation)
- **Server MUST reject** duplicate Request IDs within 5-minute sliding window (returns 409 Conflict)
- **Passed downstream** via HTTP headers to all internal and external calls
- **Included in all log lines** — every log entry MUST include `request_id`
- **Included in error responses** — clients use this for support tickets

---

## 4. Idempotency

### Header

```
Idempotency-Key: {uuid_v4}
```

### Scope and Rules

| Property | Value |
|----------|-------|
| **Required on** | All POST, PUT, PATCH mutating endpoints |
| **Key format** | UUID v4 (client-generated) |
| **Cache duration** | 24 hours from first request |
| **Cache scope** | Per resource type (`wallet/transfers`, `identity/users`, etc.) |
| **Same key, same request** | → Return cached response (same status code + body) |
| **Same key, different request** | → 409 Conflict |
| **Key reuse after expiry** | → Allowed (treated as new request) |

### Example

```
POST /api/v1/wallet/transfers
Idempotency-Key: 7c9e5b3a-1f2d-4e6c-8a7b-0d1e2f3a4b5c

First call  → 201 Created
Second call (same payload)  → 200 OK (cached response)
Third call (different amount)  → 409 Conflict
```

### Response for Duplicate Key

```json
{
  "success": false,
  "error": {
    "code": "IDEMPOTENCY_KEY_CONFLICT",
    "message": "Idempotency key already used with different payload",
    "message_ar": "مفتاح التكرار مستخدم مع بيانات مختلفة",
    "request_id": "req_abc123",
    "timestamp": "2025-12-01T10:00:00Z"
  }
}
```

---

## 5. Pagination

### Cursor-Based (Real-Time Data)

Used for: transactions, events, notifications, audit logs.

```
GET /api/v1/wallet/transactions?cursor=eyJpZCI6IjEyMyJ9&per_page=20
```

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `cursor` | String (base64) | null | Opaque cursor from previous response |
| `per_page` | Int | 20 | Items per page (max: 100) |

Response:

```json
{
  "data": [],
  "pagination": {
    "next_cursor": "eyJpZCI6IjI0NSJ9",
    "has_more": true,
    "total": 1042
  }
}
```

### Offset-Based (Admin / List Data)

Used for: users, agents, merchants, billers (stable datasets).

```
GET /api/v1/identity/users?page=2&per_page=20
```

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | Int | 1 | Page number (1-indexed) |
| `per_page` | Int | 20 | Items per page (max: 100) |
| `sort` | String | `-created_at` | Sort field with direction prefix |

Response:

```json
{
  "data": [],
  "pagination": {
    "page": 2,
    "per_page": 20,
    "total": 1042,
    "total_pages": 53,
    "has_more": true
  }
}
```

### Filtering

```
GET /api/v1/wallet/transactions?status=completed&currency=SYP&created_at[gte]=2025-01-01
```

| Operator | Meaning | Example |
|----------|---------|---------|
| `eq` | Equals | `status=completed` |
| `gte` | Greater than or equal | `created_at[gte]=2025-01-01` |
| `lte` | Less than or equal | `amount[lte]=100000` |
| `in` | In list | `status[in]=pending,completed` |
| `between` | Range | `amount[between]=1000,50000` |

---

## 6. Response Envelope

### Success Response

```json
{
  "success": true,
  "data": {},
  "pagination": {},
  "meta": {
    "request_id": "req_abc123",
    "timestamp": "2025-12-01T10:00:00Z",
    "version": "1.0"
  }
}
```

### List Response

```json
{
  "success": true,
  "data": [
    { "id": "1", "amount": 50000, "currency": "SYP" },
    { "id": "2", "amount": 25000, "currency": "SYP" }
  ],
  "pagination": {
    "next_cursor": "eyJpZCI6IjIifQ==",
    "has_more": true,
    "total": 42
  },
  "meta": {
    "request_id": "req_def456",
    "timestamp": "2025-12-01T10:00:01Z",
    "version": "1.0"
  }
}
```

### Empty Response (204)

```
HTTP/1.1 204 No Content
X-Request-Id: req_abc123
```

---

## 7. Error Responses

### Error Envelope

```json
{
  "success": false,
  "error": {
    "code": "WALLET_INSUFFICIENT_BALANCE",
    "message": "Insufficient balance",
    "message_ar": "الرصيد غير كافٍ",
    "details": {
      "available": 5000,
      "required": 10000,
      "currency": "SYP"
    },
    "request_id": "req_abc123",
    "timestamp": "2025-12-01T10:00:00Z"
  }
}
```

### Error Code Convention

```
{MODULE}_{ERROR_REASON}
```

| Module | Example Error Codes |
|--------|-------------------|
| `IDENTITY` | `IDENTITY_USER_NOT_FOUND`, `IDENTITY_INVALID_CREDENTIALS` |
| `WALLET` | `WALLET_INSUFFICIENT_BALANCE`, `WALLET_LIMIT_EXCEEDED` |
| `FX` | `FX_RATE_EXPIRED`, `FX_CORRIDOR_UNAVAILABLE` |
| `AGENT` | `AGENT_FLOAT_INSUFFICIENT`, `AGENT_INVALID_PIN` |
| `COMPLIANCE` | `COMPLIANCE_SCREENING_FAILED`, `COMPLIANCE_SANCTIONED_ENTITY` |
| `LEDGER` | `LEDGER_IMBALANCE`, `LEDGER_ACCOUNT_FROZEN` |

### Validation Error

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Validation failed",
    "message_ar": "فشل التحقق من صحة البيانات",
    "details": {
      "fields": {
        "amount": ["Amount must be between 100 and 10,000,000 SYP"],
        "beneficiary_id": ["Beneficiary not found"]
      }
    },
    "request_id": "req_abc123",
    "timestamp": "2025-12-01T10:00:00Z"
  }
}
```

---

## 8. HTTP Status Codes

| Code | Name | Usage | When |
|------|------|-------|------|
| 200 | OK | Successful GET, PUT, PATCH | Resource retrieved or updated |
| 201 | Created | Successful POST | Resource created |
| 204 | No Content | Successful DELETE | Resource deleted |
| 400 | Bad Request | Validation error, malformed payload | Client sent invalid data |
| 401 | Unauthorized | Missing or invalid authentication | No token / expired token |
| 403 | Forbidden | Authenticated but wrong role/permissions | Insufficient scope |
| 404 | Not Found | Resource does not exist | Wrong ID, wrong endpoint |
| 409 | Conflict | Duplicate resource, idempotency mismatch | Retry with different key |
| 422 | Unprocessable Entity | Business rule violation | Transfer exceeds limit, insufficient balance |
| 429 | Too Many Requests | Rate limit exceeded | Slow down |
| 500 | Internal Server Error | Unexpected server error | Retry with idempotency key |
| 502 | Bad Gateway | Upstream failure (CBS, bank, SMS provider) | Circuit breaker open |
| 503 | Service Unavailable | Maintenance or overload | Retry after Retry-After |

### When to Use 422 vs 400

| Scenario | Code |
|----------|------|
| Missing required field | 400 |
| Invalid field format (email, UUID) | 400 |
| Business rule violation (insufficient balance, limit exceeded) | 422 |
| Duplicate resource (email already registered) | 409 |

---

## 9. Rate Limiting

### Headers

```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 87
X-RateLimit-Reset: 1733040900
Retry-After: 45
```

### Tiers

| Tier | Limit | Window | Burst (5s) | Use Case |
|------|-------|--------|------------|----------|
| Anonymous | 10 requests | 1 minute | 20 | Unauthenticated endpoints (login, register) |
| Authenticated | 100 requests | 1 minute | 200 | Standard mobile app user |
| Agent | 300 requests | 1 minute | 600 | Agent POS device (high-frequency cash in/out) |
| Merchant | 300 requests | 1 minute | 600 | Merchant payment terminal polling |
| Admin | 1000 requests | 1 minute | 2000 | Dashboard / admin panel |
| Internal Service | 5000 requests | 1 minute | 10000 | Server-to-server (no header rate limiting) |

### Rate Limit Exceeded Response

```
HTTP/1.1 429 Too Many Requests
Retry-After: 45
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1733040900

{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Too many requests. Retry after 45 seconds",
    "message_ar": "طلبات كثيرة جداً. حاول مرة أخرى بعد 45 ثانية",
    "request_id": "req_abc123",
    "timestamp": "2025-12-01T10:00:00Z"
  }
}
```

---

## 10. Naming Conventions

### URL Structure

| Element | Convention | Example |
|---------|------------|---------|
| Resources | Plural nouns | `/transactions`, `/users`, `/quotes` |
| Nested resources | `/parent/{parent_id}/child` | `/identity/users/{user_id}/devices` |
| Actions (rare) | Verb only when HTTP can't express | `/wallet/bulk-transfer` (exception) |
| Query params | snake_case | `?per_page=20&sort=-created_at` |

### Field Names

| Scope | Convention | Example |
|-------|------------|---------|
| Request body | snake_case | `{ "amount": 50000, "currency": "SYP" }` |
| Response body | snake_case | `{ "transaction_id": "txn_abc", "created_at": "..." }` |
| Query parameters | snake_case | `?sort=-created_at&status=completed` |

### Sorting

```
GET /api/v1/wallet/transactions?sort=-created_at,+amount
```

- **Prefix `-`** = descending (most recent first)
- **Prefix `+`** or no prefix = ascending
- **Multiple sorts**: comma-separated

---

## 11. Versioning

### Strategy

URL-path versioning with integer versions:

```
/api/v1/{module}/{resource}
/api/v2/{module}/{resource}
```

### Lifecycle

| Phase | Duration | Behavior |
|-------|----------|----------|
| **Active** | Until superseded | Latest version, fully supported |
| **Deprecated** | 6 months from vN+1 release | Still functional, `Sunset` header added |
| **Sunset** | After 6-month deprecation | Returns 410 Gone |

### Sunset Header

```
Sunset: Sat, 01 Jun 2026 00:00:00 GMT
Deprecation: true
```

### Changelog

| Version | Changes | Deprecation Date |
|---------|---------|-----------------|
| v1 | Initial release (2025-12-01) | TBD |

---

## 12. Retry Policy (Client Guidance)

| Status Code | Retry? | Strategy |
|-------------|--------|----------|
| 200, 201, 204 | No | Success |
| 400, 422 | **No** | Client error — fix payload |
| 401 | **No** | Refresh token, then retry |
| 403 | **No** | Permission error — contact support |
| 404 | **No** | Wrong endpoint/resource |
| 409 | **No** | Change idempotency key |
| 429 | **Yes** | Retry after `Retry-After` seconds |
| 5xx (500, 502, 503) | **Yes** | Max 3 retries, exponential backoff |

### Exponential Backoff Schedule

```
Attempt 1: wait 1 second
Attempt 2: wait 2 seconds
Attempt 3: wait 4 seconds
Total max: ~7 seconds (not counting network time)
```

**Important:** Reuse the same `Idempotency-Key` across retries to ensure exactly-once semantics.

---

## 13. Binary Data

### Upload

```
POST /api/v1/identity/kyc/documents
Content-Type: multipart/form-data

Fields:
  - document_type: "national_id" | "passport" | "selfie"
  - file: binary (PDF, JPEG, PNG)
```

### Limits

| Document Type | Max Size | Accepted Formats |
|--------------|----------|------------------|
| National ID (KYC) | 10 MB | PDF, JPEG, PNG |
| Passport | 10 MB | PDF, JPEG, PNG |
| Selfie | 5 MB | JPEG, PNG |
| Avatar | 5 MB | JPEG, PNG |
| Agent receipt | 10 MB | JPEG, PNG |
| Merchant contract | 10 MB | PDF |

### Download

```
GET /api/v1/identity/kyc/documents/{document_id}/download
Response: 200 OK
Content-Type: application/pdf
Content-Disposition: attachment; filename="kyc_national_id_abc123.pdf"
Content-Length: 245678
```

---

## 14. API Changelog

| Date | Change | Author |
|------|--------|--------|
| 2025-12-01 | Initial API standards document | Platform Architecture |

---

## Appendix A: Standard Headers Summary

| Header | Direction | Required | Description |
|--------|-----------|----------|-------------|
| `Authorization` | Request | Yes (except public endpoints) | `Bearer {jwt}` |
| `X-Request-Id` | Request | Yes | `req_{uuid_v4}` |
| `Idempotency-Key` | Request | POST/PUT/PATCH | `{uuid_v4}` |
| `Content-Type` | Request | Yes | `application/json` or `multipart/form-data` |
| `Accept-Language` | Request | No | `ar`, `en` (defaults to `ar`) |
| `X-Request-Id` | Response | Yes | Echo of request ID |
| `X-RateLimit-*` | Response | Yes | Rate limit headers |
| `Retry-After` | Response | 429, 503 | Seconds to wait |
| `Sunset` | Response | Deprecated endpoints | Sunset date |

---

## Appendix B: Supported Currencies

| Code | Name | Minor Units | Notes |
|------|------|-------------|-------|
| SYP | Syrian Pound | 1 (no subunit) | Primary settlement currency; no piastre in practice |
| EUR | Euro | 2 (cents) | Remittance source currency |
| USD | US Dollar | 2 (cents) | Reporting only; no USD transactions in V1 |

All amounts in API requests/responses are in the **minor unit** (e.g., `50000` = 50,000 SYP, `10099` = 100.99 EUR).
