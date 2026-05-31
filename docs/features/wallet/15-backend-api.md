# Wallet Backend API Specification

## Endpoint: Send Money

```http
POST /api/v1/wallet/transfer/send
Authorization: Bearer {token}
Idempotency-Key: {uuid}
Content-Type: application/json

{
  "recipient_phone": "+963912345678",
  "amount": 25000,
  "currency": "SYP",
  "note": "Rent for June",
  "pin": "hashed_pin_value",
  "source": "main_wallet",
  "beneficiary_id": null
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "transaction_id": "txn_abc123",
    "status": "completed",
    "amount": 25000,
    "fee": 125,
    "total": 25125,
    "currency": "SYP",
    "recipient": {
      "name": "Ahmad Khaled",
      "phone": "+963912345678",
      "avatar_url": "https://cdn.beza.com/avatars/user_42.jpg"
    },
    "recipient_gets": 25000,
    "sender_balance_after": 74875,
    "timestamp": "2026-06-01T10:00:00Z",
    "reference": "TXN-ABC123XYZ",
    "receipt_url": "https://beza.com/receipts/txn_abc123.pdf"
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
      "amount": ["يجب أن يكون المبلغ 1000 ل.س على الأقل"],
      "recipient_phone": ["رقم الهاتف غير صحيح"]
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
      "available": 50000,
      "required": 75125,
      "shortfall": 25125
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
      "daily_limit": 500000,
      "daily_used": 475000,
      "remaining": 25000,
      "requested": 25125
    }
  }
}

// 409 — Duplicate Request (idempotency)
{
  "status": "error",
  "error": {
    "code": "DUPLICATE_REQUEST",
    "message": "تمت معالجة هذا الطلب مسبقاً",
    "details": {
      "existing_transaction_id": "txn_abc123"
    }
  }
}
```

## Endpoint: Get Balance

```http
GET /api/v1/wallet/balance
Authorization: Bearer {token}
```

### Response
```json
{
  "status": "success",
  "data": {
    "balances": {
      "SYP": {
        "available": 125000,
        "held": 25000,
        "total": 150000,
        "currency": "SYP",
        "last_updated": "2026-06-01T10:00:00Z"
      },
      "USD": {
        "available": 250,
        "held": 0,
        "total": 250,
        "currency": "USD",
        "last_updated": "2026-06-01T09:30:00Z"
      }
    },
    "default_currency": "SYP"
  }
}
```

## Endpoint: Transaction History

```http
GET /api/v1/wallet/transactions?page=1&per_page=20&type=all&from=2026-01-01&to=2026-06-01&search=electricity&sort=desc
Authorization: Bearer {token}
```

### Response
```json
{
  "status": "success",
  "data": {
    "transactions": [
      {
        "id": "txn_abc123",
        "type": "send",
        "status": "completed",
        "amount": -25125,
        "currency": "SYP",
        "counterparty": {
          "name": "Ahmad Khaled",
          "phone": "+963912345678"
        },
        "note": "Rent for June",
        "timestamp": "2026-06-01T10:00:00Z",
        "reference": "TXN-ABC123XYZ"
      },
      {
        "id": "txn_def456",
        "type": "receive",
        "status": "completed",
        "amount": 50000,
        "currency": "SYP",
        "counterparty": {
          "name": "Fatima Ali",
          "phone": "+963987654321"
        },
        "note": "Grocery payment",
        "timestamp": "2026-05-30T14:30:00Z",
        "reference": "TXN-DEF456UVW"
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
      "total_sent": 500000,
      "total_received": 1200000,
      "total_fees": 5000,
      "period": "2026-01-01 to 2026-06-01"
    }
  }
}
```

## Endpoint: Cash-in (Agent Funding)

```http
POST /api/v1/wallet/funding/cash-in
Content-Type: application/json
Idempotency-Key: {uuid}

{
  "agent_code": "AGENT-12345",
  "amount": 100000,
  "currency": "SYP",
  "pin": "hashed_pin",
  "device_id": "device_abc",
  "location": {
    "lat": 33.5138,
    "lng": 36.2765
  }
}
```

### Response
```json
{
  "status": "success",
  "data": {
    "transaction_id": "txn_fund_789",
    "amount": 100000,
    "fee": 0,
    "total": 100000,
    "balance_after": 225000,
    "agent_name": "Mohammad Shop",
    "receipt_url": "https://beza.com/receipts/txn_fund_789.pdf",
    "timestamp": "2026-06-01T11:00:00Z"
  }
}
```
