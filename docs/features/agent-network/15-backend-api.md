# Agent Network Backend API Specification

## Endpoint: Agent Registration

```http
POST /api/v1/agent/register
Content-Type: multipart/form-data

{
  "full_name": "محمد أحمد علي",
  "phone": "+963933123456",
  "shop_name": "بقالة أبو محمد",
  "shop_type": "grocery",
  "address": "المزة، شارع الحمرا، بناء 12",
  "city": "دمشق",
  "district": "المزة",
  "location": {
    "lat": 33.5138,
    "lng": 36.2765
  },
  "national_id": "0101023456789",
  "national_id_image": (file upload),
  "shop_registration": (file upload),
  "proof_of_address": (file upload),
  "photo": (file upload),
  "shop_front_image": (file upload),
  "shop_interior_image": (file upload),
  "preferred_language": "ar",
  "operating_hours": {
    "saturday": "08:00-22:00",
    "sunday": "08:00-22:00",
    "monday": "08:00-22:00",
    "tuesday": "08:00-22:00",
    "wednesday": "08:00-22:00",
    "thursday": "08:00-22:00",
    "friday": "10:00-20:00"
  }
}
```

### Response: 201 Created
```json
{
  "status": "success",
  "data": {
    "agent_id": 10234,
    "agent_code": "BZ-10234",
    "status": "pending",
    "message": "تم استلام طلب التسجيل بنجاح. سيتم مراجعة المستندات والرد خلال 24 ساعة"
  }
}
```

### Error Responses
```json
// 422 — Validation Error
{
  "status": "error",
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "بيانات الإدخال غير صحيحة",
    "details": {
      "phone": ["رقم الهاتف مستخدم مسبقاً"],
      "national_id": ["رقم الهوية غير صحيح — يجب أن يكون 11 رقم"],
      "location": ["الموقع الجغرافي مطلوب"]
    }
  }
}

// 409 — Duplicate Application
{
  "status": "error",
  "error": {
    "code": "DUPLICATE_APPLICATION",
    "message": "يوجد طلب تسجيل قيد المراجعة لهذا الرقم",
    "details": {
      "existing_application_id": 10234,
      "status": "pending",
      "submitted_at": "2026-05-28T10:00:00Z"
    }
  }
}

// 403 — Location Too Close
{
  "status": "error",
  "error": {
    "code": "AGENT_TOO_CLOSE",
    "message": "يوجد وكيل آخر على بعد أقل من 500 متر من موقعك",
    "details": {
      "nearest_agent": {
        "name": "بقالة الخالد",
        "distance_meters": 320,
        "address": "المزة، شارع الحمرا"
      }
    }
  }
}
```

## Endpoint: Agent Login

```http
POST /api/v1/agent/login
Content-Type: application/json

{
  "phone": "+963933123456",
  "pin": "123456",
  "device_id": "DEVICE-SN-ABC123",
  "device_certificate": "base64_encoded_cert"
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "token": "1|abc123...",
    "agent": {
      "id": 10234,
      "code": "BZ-10234",
      "name": "محمد أحمد",
      "shop_name": "بقالة أبو محمد",
      "tier": "bronze",
      "status": "active",
      "device_id": "DEVICE-SN-ABC123"
    },
    "session": {
      "expires_at": "2026-06-01T10:30:00Z",
      "timeout_seconds": 300
    }
  }
}
```

### Error Responses
```json
// 401 — Invalid Credentials
{
  "status": "error",
  "error": {
    "code": "INVALID_CREDENTIALS",
    "message": "رقم الهاتف أو الرقم السري غير صحيح",
    "details": {
      "attempts_remaining": 2
    }
  }
}

// 423 — Account Locked
{
  "status": "error",
  "error": {
    "code": "ACCOUNT_LOCKED",
    "message": "تم حظر الحساب مؤقتاً — الرجاء المحاولة بعد 30 دقيقة",
    "details": {
      "locked_until": "2026-06-01T10:30:00Z"
    }
  }
}

// 403 — Device Not Bound
{
  "status": "error",
  "error": {
    "code": "DEVICE_NOT_BOUND",
    "message": "هذا الجهاز غير معرّف لحساب الوكيل — الرجاء الاتصال بالدعم",
    "details": {
      "device_id": "DEVICE-SN-ABC123",
      "support_phone": "+963800123456"
    }
  }
}
```

## Endpoint: Customer Verification (Cash-in/Cash-out)

```http
POST /api/v1/agent/verify-customer
Authorization: Bearer {agent_token}
Content-Type: application/json

{
  "customer_phone": "+963912345678"
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "verification_id": "ver_abc123",
    "verification_method": "sms",
    "masked_phone": "+963***4567",
    "code_sent": true,
    "expires_at": "2026-06-01T10:05:00Z"
  }
}
```

## Endpoint: Cash-in

```http
POST /api/v1/agent/cash-in
Authorization: Bearer {agent_token}
Idempotency-Key: {uuid}
Content-Type: application/json

{
  "verification_id": "ver_abc123",
  "verification_code": "4821",
  "customer_phone": "+963912345678",
  "amount": 100000,
  "currency": "SYP",
  "location": {
    "lat": 33.5138,
    "lng": 36.2765
  }
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "transaction_id": "CI-20260601-87142",
    "type": "cash_in",
    "amount": 100000,
    "currency": "SYP",
    "fee": 0,
    "commission_earned": 500,
    "agent_float_after": 900000,
    "customer_name": "أم خالد",
    "customer_balance_after": 250000,
    "timestamp": "2026-06-01T10:30:00Z",
    "reference": "CI-87142",
    "receipt_url": "https://beza.com/receipts/CI-20260601-87142.pdf"
  }
}
```

### Error Responses
```json
// 400 — Insufficient Float
{
  "status": "error",
  "error": {
    "code": "INSUFFICIENT_FLOAT",
    "message": "رصيد الصندوق غير كافٍ لإتمام العملية",
    "details": {
      "current_float": 850000,
      "required": 100000,
      "available_for_cash_in": 850000,
      "max_cash_in_amount": 850000
    }
  }
}

// 400 — Invalid Verification Code
{
  "status": "error",
  "error": {
    "code": "INVALID_VERIFICATION_CODE",
    "message": "رمز التحقق غير صحيح",
    "details": {
      "attempts_remaining": 2
    }
  }
}

// 400 — Daily Limit Exceeded
{
  "status": "error",
  "error": {
    "code": "DAILY_LIMIT_EXCEEDED",
    "message": "تم تجاوز حد الإيداع اليومي للوكيل",
    "details": {
      "daily_limit": 5000000,
      "daily_used": 4800000,
      "remaining": 200000,
      "requested": 1000000
    }
  }
}
```

## Endpoint: Cash-out

```http
POST /api/v1/agent/cash-out
Authorization: Bearer {agent_token}
Idempotency-Key: {uuid}
Content-Type: application/json

{
  "verification_id": "ver_def456",
  "verification_code": "7392",
  "customer_phone": "+963912345678",
  "amount": 50000,
  "currency": "SYP",
  "customer_pin": "1234",
  "biometric_verified": false,
  "location": {
    "lat": 33.5138,
    "lng": 36.2765
  }
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "transaction_id": "CO-20260601-45231",
    "type": "cash_out",
    "amount": 50000,
    "fee": 750,
    "total_deducted": 50750,
    "currency": "SYP",
    "commission_earned": 375,
    "agent_float_after": 950000,
    "customer_balance_after": 199250,
    "timestamp": "2026-06-01T11:00:00Z",
    "reference": "CO-45231",
    "receipt_url": "https://beza.com/receipts/CO-20260601-45231.pdf"
  }
}
```

## Endpoint: Get Float Balance

```http
GET /api/v1/agent/float
Authorization: Bearer {agent_token}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "current_balance": 1250000,
    "currency": "SYP",
    "daily_cash_in_total": 1500000,
    "daily_cash_out_total": 850000,
    "daily_cash_in_count": 12,
    "daily_cash_out_count": 8,
    "daily_commission_earned": 12500,
    "last_updated": "2026-06-01T11:00:00Z",
    "status": "ok",
    "alerts": {
      "low_float_threshold": 100000,
      "is_low": false,
      "recommended_top_up": null
    }
  }
}
```

## Endpoint: Get Agent Transactions

```http
GET /api/v1/agent/transactions?page=1&per_page=20&type=all&date_from=2026-06-01&date_to=2026-06-01&search=096&sort=desc
Authorization: Bearer {agent_token}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "transactions": [
      {
        "id": "CI-20260601-87142",
        "type": "cash_in",
        "status": "completed",
        "amount": 100000,
        "currency": "SYP",
        "commission": 500,
        "customer_phone": "+963***4567",
        "customer_name": "أم خالد",
        "timestamp": "2026-06-01T10:30:00Z",
        "reference": "CI-87142",
        "synced": true
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 156,
      "last_page": 8,
      "has_more": true
    },
    "summary": {
      "total_cash_in": 1500000,
      "total_cash_out": 850000,
      "total_commission": 12500,
      "period": "2026-06-01 to 2026-06-01"
    }
  }
}
```

## Endpoint: Offline Transaction Sync (Batch)

```http
POST /api/v1/agent/sync
Authorization: Bearer {agent_token}
Content-Type: application/json

{
  "transactions": [
    {
      "idempotency_key": "uuid-1",
      "type": "cash_in",
      "customer_phone": "+963912345678",
      "amount": 100000,
      "currency": "SYP",
      "verification_code": "4821",
      "location": {"lat": 33.5138, "lng": 36.2765},
      "performed_at": "2026-06-01T10:30:00Z",
      "device_timestamp": "2026-06-01T10:30:05Z"
    },
    {
      "idempotency_key": "uuid-2",
      "type": "cash_out",
      "customer_phone": "+963987654321",
      "amount": 50000,
      "currency": "SYP",
      "customer_pin_hash": "hash_value",
      "location": {"lat": 33.5140, "lng": 36.2770},
      "performed_at": "2026-06-01T10:45:00Z",
      "device_timestamp": "2026-06-01T10:45:10Z"
    }
  ]
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "processed": 2,
    "failed": 0,
    "results": [
      {
        "idempotency_key": "uuid-1",
        "status": "completed",
        "transaction_id": "CI-20260601-87142"
      },
      {
        "idempotency_key": "uuid-2",
        "status": "completed",
        "transaction_id": "CO-20260601-45231"
      }
    ]
  }
}
```

## Endpoint: Get Commission Summary

```http
GET /api/v1/agent/commissions/summary
Authorization: Bearer {agent_token}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "today": {
      "earned": 12500,
      "transaction_count": 20
    },
    "this_month": {
      "earned": 325000,
      "settled": 280000,
      "pending_settlement": 45000
    },
    "last_settlement": {
      "date": "2026-05-31",
      "amount": 42500,
      "status": "completed"
    },
    "next_settlement": "2026-06-02T03:00:00Z"
  }
}
```
