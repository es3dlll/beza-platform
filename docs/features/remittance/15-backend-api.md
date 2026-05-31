# Remittance Backend API Specification

## Endpoint: Send Remittance (Diaspora)

```http
POST /api/v1/remittance/send
Authorization: Bearer {token}
Idempotency-Key: {uuid}
Content-Type: application/json

{
  "beneficiary_id": 42,
  "amount": 300.00,
  "source_currency": "EUR",
  "target_currency": "SYP",
  "fx_rate_lock_id": "fx_lock_abc123",
  "delivery_method": "wallet",
  "note": "مصاريف البيت - يونيو",
  "funding_source": "wallet",
  "source_of_funds": "salary",
  "pin": "hashed_pin_value",
  "biometric_verified": true,
  "device_id": "device_abc",
  "ip_country": "DE"
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "remittance_id": "rem_abc123",
    "status": "completed",
    "source": {
      "amount": 300.00,
      "currency": "EUR",
      "fee": 4.50,
      "total_debit": 304.50
    },
    "fx": {
      "rate": 13200,
      "mid_market_rate": 13420,
      "spread_percent": 1.8,
      "locked": true,
      "locked_at": "2026-06-15T10:00:02Z"
    },
    "target": {
      "amount": 3960000,
      "currency": "SYP",
      "recipient_gets": 3960000
    },
    "beneficiary": {
      "id": 42,
      "name": "أم محمد",
      "phone": "+963912345678",
      "relationship": "أمي"
    },
    "timeline": {
      "initiated_at": "2026-06-15T10:00:00Z",
      "fx_locked_at": "2026-06-15T10:00:02Z",
      "completed_at": "2026-06-15T10:00:08Z",
      "received_at": "2026-06-15T10:00:08Z"
    },
    "reference": "REM-ABC123XYZ",
    "receipt_url": "https://beza.com/receipts/rem_abc123.pdf"
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
      "amount": ["يجب أن يكون المبلغ 1 EUR على الأقل"],
      "beneficiary_id": ["المستفيد غير موجود"]
    }
  }
}

// 402 — Insufficient Balance
{
  "status": "error",
  "error": {
    "code": "INSUFFICIENT_BALANCE",
    "message": "الرصيد غير كافٍ",
    "details": {
      "available": 250.00,
      "required": 304.50,
      "currency": "EUR"
    }
  }
}

// 403 — Corridor Inactive
{
  "status": "error",
  "error": {
    "code": "CORRECTOR_INACTIVE",
    "message": "ممر التحويل غير متاح حالياً",
    "details": {
      "corridor": "EUR->SYP",
      "status": "maintenance",
      "estimated_restore": "2026-06-15T12:00:00Z"
    }
  }
}

// 429 — Daily Limit Exceeded
{
  "status": "error",
  "error": {
    "code": "DAILY_LIMIT_EXCEEDED",
    "message": "تم تجاوز الحد اليومي للتحويل",
    "details": {
      "daily_limit": 1000,
      "daily_used": 750,
      "currency": "EUR",
      "requested": 304.50
    }
  }
}

// 409 — Duplicate Request
{
  "status": "error",
  "error": {
    "code": "DUPLICATE_REQUEST",
    "message": "تمت معالجة هذا الطلب مسبقاً",
    "details": {
      "existing_remittance_id": "rem_abc123"
    }
  }
}

// 451 — Sanctions Block
{
  "status": "error",
  "error": {
    "code": "SANCTIONS_BLOCK",
    "message": "لا يمكن إتمام العملية لأسباب تنظيمية",
    "details": {
      "reason": "SANCTIONS_MATCH",
      "support_reference": "CASE-12345"
    }
  }
}
```

## Endpoint: Lock FX Rate

```http
POST /api/v1/remittance/fx/lock
Authorization: Bearer {token}
Content-Type: application/json

{
  "corridor": "EUR->SYP",
  "source_currency": "EUR",
  "target_currency": "SYP"
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "lock_id": "fx_lock_abc123",
    "rate": 13200,
    "mid_market_rate": 13420,
    "spread_percent": 1.8,
    "locked_at": "2026-06-15T10:00:00Z",
    "expires_at": "2026-06-15T10:01:00Z",
    "countdown_seconds": 58
  }
}
```

## Endpoint: Create Beneficiary

```http
POST /api/v1/remittance/beneficiaries
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "أم محمد",
  "relationship": "أمي",
  "phone": "+963912345678",
  "city": "دمشق",
  "currency_preference": "SYP",
  "notes": "والدتي - تسلم على رقمها"
}
```

### Response: 201 Created
```json
{
  "status": "success",
  "data": {
    "id": 42,
    "name": "أم محمد",
    "relationship": "أمي",
    "relationship_label": "Mother",
    "phone": "+963912345678",
    "city": "دمشق",
    "currency_preference": "SYP",
    "total_sent": 0,
    "last_sent_at": null,
    "created_at": "2026-06-15T10:00:00Z"
  }
}
```

## Endpoint: Create Recurring Transfer

```http
POST /api/v1/remittance/recurring
Authorization: Bearer {token}
Content-Type: application/json

{
  "beneficiary_id": 42,
  "amount": 200.00,
  "source_currency": "EUR",
  "target_currency": "SYP",
  "frequency": "monthly",
  "day_of_month": 1,
  "max_executions": null,
  "end_date": null,
  "pin": "hashed_pin_value"
}
```

### Response: 201 Created
```json
{
  "status": "success",
  "data": {
    "id": 7,
    "beneficiary": {
      "id": 42,
      "name": "أم محمد",
      "phone": "+963912345678"
    },
    "amount": 200.00,
    "source_currency": "EUR",
    "target_currency": "SYP",
    "frequency": "monthly",
    "day_of_month": 1,
    "status": "active",
    "next_execution": "2026-07-01T08:00:00Z",
    "total_executions": 0,
    "created_at": "2026-06-15T10:00:00Z"
  }
}
```

## Endpoint: Pending Money Requests

```http
GET /api/v1/remittance/request/pending
Authorization: Bearer {token}
```

### Response
```json
{
  "status": "success",
  "data": {
    "requests": [
      {
        "id": 15,
        "requester": {
          "id": 87,
          "name": "أحمد خالد",
          "phone": "+963912345678",
          "avatar_url": "https://cdn.beza.com/avatars/user_87.jpg"
        },
        "amount": 100.00,
        "currency": "EUR",
        "note": "مساهمة في علاج الوالدة",
        "status": "pending",
        "expires_at": "2026-06-22T10:00:00Z",
        "created_at": "2026-06-15T10:00:00Z"
      }
    ],
    "total_pending": 3
  }
}
```
