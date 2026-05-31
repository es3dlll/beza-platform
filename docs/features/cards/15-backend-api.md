# Cards Backend API Specification

## Endpoint: Create Card

```http
POST /api/v1/cards/create
Authorization: Bearer {token}
Idempotency-Key: {uuid}
Content-Type: application/json

{
  "type": "virtual",
  "currency": "SYP",
  "nickname": "بطاقة التسوق",
  "limits": {
    "online": 500000,
    "pos": 200000,
    "atm": 0,
    "international": 0
  },
  "card_program": "beza_standard_syp"
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "bin": "639123",
    "last_four": "4567",
    "expiry": "12/28",
    "type": "virtual",
    "card_network": "mastercard",
    "status": "active",
    "limits": {
      "online": 500000,
      "pos": 200000,
      "atm": 0,
      "international": 0
    },
    "fee": 5000,
    "created_at": "2026-06-01T10:00:00Z",
    "issued_at": "2026-06-01T10:00:00Z",
    "pan_hint": "639123••••••4567",
    "cvv_hint": "•••",
    "apple_pay_eligible": true,
    "google_pay_eligible": true
  }
}
```

### Error Responses
```json
// 400 — Validation Error
{
  "status": "error",
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "بيانات الإدخال غير صحيحة",
    "details": {
      "type": ["نوع البطاقة غير صحيح: virtual أو physical"],
      "currency": ["العملة غير مدعومة: SYP أو USD"],
      "limits.online": ["يجب أن يكون الحد بين 50,000 و 5,000,000 ل.س"]
    }
  }
}

// 403 — KYC Insufficient
{
  "status": "error",
  "error": {
    "code": "KYC_INSUFFICIENT",
    "message": "تحتاج إلى توثيق الحساب (المستوى 2) على الأقل لإصدار بطاقة",
    "current_level": 1,
    "required_level": 2
  }
}
```

## Endpoint: List Cards

```http
GET /api/v1/cards
Authorization: Bearer {token}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "bin": "639123",
      "last_four": "4567",
      "expiry": "12/28",
      "type": "virtual",
      "card_network": "mastercard",
      "status": "active",
      "nickname": "بطاقة التسوق",
      "currency": "SYP",
      "spent_today": 125000,
      "limit_today": 500000,
      "issued_at": "2026-06-01T10:00:00Z"
    },
    {
      "id": 2,
      "bin": "639124",
      "last_four": "8901",
      "expiry": "12/28",
      "type": "physical",
      "card_network": "mastercard",
      "status": "frozen",
      "nickname": "البطاقة البلاستيكية",
      "currency": "SYP",
      "spent_today": 0,
      "limit_today": 200000,
      "issued_at": "2026-06-10T14:00:00Z"
    }
  ],
  "meta": {
    "total": 2,
    "active": 1,
    "frozen": 1
  }
}
```

## Endpoint: Get Card Detail

```http
GET /api/v1/cards/{id}
Authorization: Bearer {token}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "user_id": 42,
    "tenant_id": 1,
    "bin": "639123",
    "last_four": "4567",
    "pan": "639123xxxxxx4567",
    "expiry": "12/28",
    "type": "virtual",
    "card_network": "mastercard",
    "card_program": "beza_standard_syp",
    "issuer_id": "beza_syria",
    "status": "active",
    "limits": {
      "online": {"daily": 500000, "used": 125000, "remaining": 375000, "max": 5000000},
      "pos": {"daily": 200000, "used": 0, "remaining": 200000, "max": 2000000},
      "atm": {"daily": 0, "used": 0, "remaining": 0, "max": 1000000},
      "international": {"daily": 0, "used": 0, "remaining": 0, "max": 2000000}
    },
    "tokens": [
      {"wallet_type": "apple_pay", "status": "active", "device_id": "device_abc"},
      {"wallet_type": "google_pay", "status": "active", "device_id": "device_xyz"}
    ],
    "spending": {
      "total_spent": 1250000,
      "total_transactions": 15,
      "last_transaction_at": "2026-06-15T18:30:00Z"
    },
    "issued_at": "2026-06-01T10:00:00Z",
    "activated_at": "2026-06-01T10:00:00Z"
  }
}
```

## Endpoint: Freeze Card

```http
POST /api/v1/cards/{id}/freeze
Authorization: Bearer {token}
Content-Type: application/json

{
  "pin": "hashed_pin_value",
  "reason": "suspicious_charge"
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "card_id": 1,
    "status": "frozen",
    "frozen_at": "2026-06-15T19:00:00Z",
    "reason": "suspicious_charge",
    "message": "تم تجميد البطاقة بنجاح"
  }
}
```

## Endpoint: Unfreeze Card

```http
POST /api/v1/cards/{id}/unfreeze
Authorization: Bearer {token}
Content-Type: application/json

{
  "pin": "hashed_pin_value"
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "card_id": 1,
    "status": "active",
    "unfrozen_at": "2026-06-16T08:00:00Z",
    "message": "تم إلغاء تجميد البطاقة بنجاح"
  }
}
```

## Endpoint: Change PIN

```http
POST /api/v1/cards/{id}/pin
Authorization: Bearer {token}
Content-Type: application/json

{
  "current_pin": "hashed_current_pin",
  "new_pin": "hashed_new_pin"
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "message": "تم تغيير رقم PIN بنجاح"
  }
}
```

### Error: PIN Blocked
```json
{
  "status": "error",
  "error": {
    "code": "PIN_BLOCKED",
    "message": "رقم PIN محجوب بسبب 3 محاولات خاطئة. حاول بعد 24 ساعة",
    "blocked_until": "2026-06-17T19:00:00Z"
  }
}
```

## Endpoint: Update Limits

```http
PUT /api/v1/cards/{id}/limits
Authorization: Bearer {token}
Content-Type: application/json

{
  "limits": {
    "online": 750000,
    "pos": 300000,
    "atm": 100000,
    "international": 50000
  }
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "card_id": 1,
    "limits": {
      "online": {"daily": 750000, "max": 5000000},
      "pos": {"daily": 300000, "max": 2000000},
      "atm": {"daily": 100000, "max": 1000000},
      "international": {"daily": 50000, "max": 2000000}
    },
    "message": "تم تحديث حدود البطاقة بنجاح"
  }
}
```

## Endpoint: Get Card Transactions

```http
GET /api/v1/cards/{id}/transactions?page=1&per_page=20&from=2026-06-01&to=2026-06-30&type=purchase&status=settled
Authorization: Bearer {token}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "card_id": 1,
      "type": "purchase",
      "amount": 125000,
      "currency": "SYP",
      "merchant_name": "AliExpress",
      "merchant_category": "ecommerce",
      "merchant_country": "CN",
      "status": "settled",
      "auth_code": "AUTH-ABC123",
      "rrn": "RRN-987654",
      "stan": "123456",
      "authorized_at": "2026-06-15T18:30:00Z",
      "settled_at": "2026-06-15T23:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 45,
    "last_page": 3
  }
}
```

## Endpoint: Report Lost Card

```http
POST /api/v1/cards/{id}/report-lost
Authorization: Bearer {token}
Content-Type: application/json

{
  "pin": "hashed_pin_value",
  "reason": "stolen"
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "card_id": 1,
    "status": "lost",
    "reported_at": "2026-06-16T10:00:00Z",
    "message": "تم الإبلاغ عن فقدان البطاقة. سيتم إصدار بطاقة بديلة"
  }
}
```

## Endpoint: Replace Card

```http
POST /api/v1/cards/{id}/replace
Authorization: Bearer {token}
Content-Type: application/json

{
  "pin": "hashed_pin_value",
  "reason": "damaged",
  "delivery_method": "agent"
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "old_card_id": 1,
    "new_card_id": 3,
    "bin": "639123",
    "last_four": "4567",
    "expiry": "06/30",
    "status": "pending_activation",
    "fee": 10000,
    "estimated_delivery": "2026-06-23",
    "message": "تم طلب بطاقة بديلة. نفس الرقم ولكن تاريخ انتهاء جديد"
  }
}
```

## Endpoint: One-Time Card

```http
POST /api/v1/cards/one-time
Authorization: Bearer {token}
Content-Type: application/json

{
  "amount": 75000,
  "currency": "SYP",
  "merchant_type": "unknown_online_store"
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "card_id": 4,
    "pan": "6391234567890123",
    "cvv": "123",
    "expiry": "12/28",
    "amount": 75000,
    "expires_at": "2026-06-17T10:00:00Z",
    "auto_destroy": true,
    "message": "البطاقة صالحة لمدة 24 ساعة فقط"
  }
}
```
