# Bill Payment Backend API Specification

## Endpoint: Fetch Bill

```http
POST /api/v1/bills/fetch
Authorization: Bearer {token}
Content-Type: application/json

{
  "customer_id": "123456789012345678901234",
  "biller_type": "peed",
  "idempotency_key": "uuid"
}
```

### Customer ID Formats by Biller
| biller_type | Format | Example |
|------------|--------|---------|
| peed | 24 numeric digits | 123456789012345678901234 |
| damascus_water | 10 numeric digits | 1234567890 |
| syriatel | 10 digits (mobile) | 0933123456 |
| mtn | 10 digits (mobile) | 0954123456 |
| syria_telecom | 7 digits (landline) | 1123456 |
| aya_internet | 8 digits | 12345678 |
| saman_internet | 8 digits | 12345678 |
| government_fees | 16 digits (national ID) | 1234567890123456 |
| university_fees | 12 digits (student ID) | 123456789012 |

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "customer_id": "123456789012345678901234",
    "customer_name": "أحمد خالد",
    "customer_address": "دمشق, المزة, شارع النصر, بناء 15",
    "biller_type": "peed",
    "biller_name": "الشركة العامة للكهرباء",
    "invoice_number": "PE-2026-789012",
    "billing_period": "مايو 2026",
    "amount": 42500,
    "late_fee": 2125,
    "total_due": 44625,
    "vat": null,
    "due_date": "2026-06-15",
    "breakdown": [
      {"label": "قيمة الاستهلاك (850 ك.و.س)", "amount": 40000},
      {"label": "ضريبة 5%", "amount": 2500}
    ],
    "biller_reference": "PE1234567890",
    "is_paid": false,
    "payment_date": null,
    "fetched_at": "2026-06-10T09:30:00Z"
  }
}
```

### Error Responses
```json
// 400 — Invalid Customer ID Format
{
  "status": "error",
  "error": {
    "code": "INVALID_CUSTOMER_ID",
    "message": "رقم المشترك غير صحيح",
    "details": {
      "expected_format": "24 رقماً (XXXX-XXXX-XXXX-XXXX-XXXX)",
      "received": "12345678901234567890123",
      "received_length": 23
    }
  }
}

// 404 — Bill Not Found
{
  "status": "error",
  "error": {
    "code": "BILL_NOT_FOUND",
    "message": "لم يتم العثور على فاتورة لهذا الرقم",
    "details": {
      "customer_id": "123456789012345678901234",
      "biller_type": "peed"
    }
  }
}

// 409 — Bill Already Paid
{
  "status": "error",
  "error": {
    "code": "BILL_ALREADY_PAID",
    "message": "هذه الفاتورة مدفوعة مسبقاً",
    "details": {
      "biller_reference": "PE1234567890",
      "paid_at": "2026-06-05T14:00:00Z"
    }
  }
}

// 502 — Biller API Down
{
  "status": "error",
  "error": {
    "code": "BILLER_UNAVAILABLE",
    "message": "خدمة مزود الفاتورة غير متوفرة حالياً، يرجى المحاولة لاحقاً",
    "details": {
      "biller_type": "peed",
      "retry_after_seconds": 120
    }
  }
}
```

## Endpoint: Pay Bill

```http
POST /api/v1/bills/pay
Authorization: Bearer {token}
Idempotency-Key: {uuid}
Content-Type: application/json

{
  "customer_id": "123456789012345678901234",
  "biller_type": "peed",
  "invoice_number": "PE-2026-789012",
  "total_due": 44625,
  "pin": "hashed_pin_value"
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "transaction_id": "bptxn_abc123",
    "status": "paid",
    "beza_reference": "BILL-PEED-20260610-ABCDEFGHIJ",
    "biller_reference": "PE1234567890-CONFIRM",
    "biller_type": "peed",
    "biller_name": "الشركة العامة للكهرباء",
    "customer_id": "123456789012345678901234",
    "customer_name": "أحمد خالد",
    "invoice_number": "PE-2026-789012",
    "billing_period": "مايو 2026",
    "amount": 42500,
    "late_fee": 2125,
    "total_due": 44625,
    "fee": 224,
    "fee_breakdown": {
      "commission": 224,
      "vat": 0
    },
    "total_charged": 44849,
    "wallet_balance_before": 124849,
    "wallet_balance_after": 80000,
    "paid_at": "2026-06-10T09:30:00Z",
    "receipt_url": "https://cdn.beza.com/receipts/bptxn_abc123.pdf"
  }
}
```

### Error Responses
```json
// 402 — Insufficient Balance
{
  "status": "error",
  "error": {
    "code": "INSUFFICIENT_BALANCE",
    "message": "الرصيد غير كافٍ",
    "details": {
      "required": 44849,
      "available": 30000,
      "shortfall": 14849
    }
  }
}

// 409 — Bill Already Paid
{
  "status": "error",
  "error": {
    "code": "BILL_ALREADY_PAID",
    "message": "تم دفع هذه الفاتورة مسبقاً",
    "details": {
      "biller_reference": "PE1234567890",
      "beza_reference": "BILL-PEED-20260605-XXXXXXXXXX",
      "paid_at": "2026-06-05T14:00:00Z"
    }
  }
}

// 502 — Biller Confirmation Failed
{
  "status": "error",
  "error": {
    "code": "BILLER_CONFIRMATION_FAILED",
    "message": "فشل تأكيد الدفع لدى مزود الخدمة",
    "details": {
      "biller_type": "peed",
      "biller_error": "INVALID_ACCOUNT_STATUS",
      "beza_reference": "BILL-PEED-20260610-ABCDEFGHIJ",
      "action": "تم إرجاع المبلغ إلى محفظتك. اتصل بالدعم الفني."
    }
  }
}
```

## Endpoint: Get Bill History

```http
GET /api/v1/bills/history?page=1&per_page=20&biller_type=all&from=2026-01-01&to=2026-06-01&status=all&search=كهرباء
Authorization: Bearer {token}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "transactions": [
      {
        "id": "bptxn_abc123",
        "biller_type": "peed",
        "biller_name": "الشركة العامة للكهرباء",
        "biller_logo_url": "https://cdn.beza.com/billers/peed.png",
        "customer_id": "1234-5678-9012-3456-7890",
        "customer_name": "أحمد خالد",
        "invoice_number": "PE-2026-789012",
        "billing_period": "مايو 2026",
        "amount": 44625,
        "fee": 224,
        "total": 44849,
        "status": "paid",
        "beza_reference": "BILL-PEED-20260610-ABCDEFGHIJ",
        "biller_reference": "PE1234567890-CONFIRM",
        "paid_at": "2026-06-10T09:30:00Z",
        "receipt_url": "https://cdn.beza.com/receipts/bptxn_abc123.pdf"
      },
      {
        "id": "bptxn_def456",
        "biller_type": "syriatel",
        "biller_name": "سيريتل",
        "biller_logo_url": "https://cdn.beza.com/billers/syriatel.png",
        "customer_id": "0933-123-456",
        "customer_name": "أحمد خالد",
        "invoice_number": "SYR-2026-345678",
        "billing_period": "يونيو 2026",
        "amount": 33000,
        "fee": 430,
        "total": 33430,
        "status": "paid",
        "beza_reference": "BILL-SYR-20260605-XXXXXXXXXX",
        "biller_reference": "SYR-CONFIRM-123456",
        "paid_at": "2026-06-05T08:15:00Z",
        "receipt_url": "https://cdn.beza.com/receipts/bptxn_def456.pdf"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 47,
      "last_page": 3,
      "has_more": true
    },
    "summary": {
      "total_paid": 4458000,
      "total_fees": 22300,
      "total_transactions": 47,
      "period": "2026-01-01 to 2026-06-01"
    }
  }
}
```

## Endpoint: Get Scheduled/Reminder Bills

```http
GET /api/v1/bills/scheduled
Authorization: Bearer {token}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "scheduled": [
      {
        "id": 1,
        "biller_type": "peed",
        "biller_name": "الشركة العامة للكهرباء",
        "customer_id": "1234-5678-9012-3456-7890",
        "amount": null,
        "schedule_type": "monthly",
        "reminder_days": 3,
        "next_due": "2026-07-15",
        "auto_pay_enabled": true,
        "auto_pay_status": "active",
        "status": "active",
        "created_at": "2026-06-01T10:00:00Z"
      },
      {
        "id": 2,
        "biller_type": "syriatel",
        "biller_name": "سيريتل",
        "customer_id": "0933-123-456",
        "amount": 33000,
        "schedule_type": "monthly",
        "reminder_days": 1,
        "next_due": "2026-07-12",
        "auto_pay_enabled": false,
        "auto_pay_status": null,
        "status": "active",
        "created_at": "2026-06-05T08:00:00Z"
      }
    ],
    "overdue": [
      {
        "id": 3,
        "biller_type": "damascus_water",
        "biller_name": "مؤسسة مياه دمشق",
        "customer_id": "1234567890",
        "amount": 8500,
        "schedule_type": "once",
        "reminder_days": 3,
        "next_due": "2026-05-20",
        "auto_pay_enabled": false,
        "auto_pay_status": null,
        "status": "overdue",
        "days_overdue": 21,
        "late_fee": 425,
        "created_at": "2026-05-01T10:00:00Z"
      }
    ]
  }
}
```

## Endpoint: Set Bill Reminder / Schedule

```http
POST /api/v1/bills/reminder/set
Authorization: Bearer {token}
Content-Type: application/json

{
  "biller_type": "peed",
  "customer_id": "123456789012345678901234",
  "schedule_type": "monthly",
  "reminder_days": 3,
  "next_due": "2026-07-15",
  "auto_pay": true
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "biller_type": "peed",
    "customer_id": "1234-5678-9012-3456-7890",
    "schedule_type": "monthly",
    "reminder_days": 3,
    "next_due": "2026-07-15",
    "auto_pay_enabled": true,
    "message": "تم تعيين التذكير لفاتورة الكهرباء. سيتم إشعارك قبل 3 أيام من تاريخ الاستحقاق."
  }
}
```

## Endpoint: Cancel Schedule

```http
POST /api/v1/bills/reminder/cancel
Authorization: Bearer {token}
Content-Type: application/json

{
  "schedule_id": 1
}
```

### Response
```json
{
  "status": "success",
  "data": {
    "message": "تم إلغاء التذكير"
  }
}
```

## Webhook: Biller Notification (Incoming)

```http
POST /api/v1/bills/webhook/{biller_type}
Content-Type: application/json
X-Webhook-Secret: {shared_secret}

// PEED payment confirmation webhook
{
  "event": "payment_confirmed",
  "biller_reference": "PE1234567890",
  "beza_reference": "BILL-PEED-20260610-ABCDEFGHIJ",
  "status": "completed",
  "confirmed_at": "2026-06-10T09:31:00Z",
  "signature": "sha256_hmac_signature"
}
```

### Response
```json
{
  "status": "received"
}
```
