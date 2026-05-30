# Government Collections Backend API Specification

## Endpoint: Tax Query

```http
POST /api/v1/government/tax/query
Authorization: Bearer {token}  # Optional for guest
Content-Type: application/json

{
  "tax_id": "2536894751",
  "tax_type": "income"  // income | property | both
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "taxpayer_name": "أحمد محمد",  // Masked for privacy
    "tax_id": "2536894751",
    "obligations": [
      {
        "obligation_id": "TAX-2025-001",
        "type": "income",
        "year": 2025,
        "base_amount": 250000,
        "penalty": 12500,
        "total_due": 262500,
        "due_date": "2025-12-31",
        "status": "overdue",
        "days_overdue": 45
      }
    ],
    "total_due": 262500,
    "currency": "SYP",
    "queried_at": "2025-08-15T10:23:00Z"
  }
}
```

## Endpoint: Tax Payment

```http
POST /api/v1/government/tax/pay
Authorization: Bearer {token}
Idempotency-Key: {uuid}
Content-Type: application/json

{
  "tax_id": "2536894751",
  "obligation_ids": ["TAX-2025-001"],
  "total_amount": 262500,
  "pin": "hashed_pin_value",
  "save_payer": true
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "transaction_id": "gov_txn_abc123",
    "receipt_ref": "GOV-2025-0815-7823",
    "amount_paid": 263812,
    "base_amount": 262500,
    "beza_fee": 1312,
    "currency": "SYP",
    "biller": {
      "code": "MOF",
      "name": "وزارة المالية",
      "name_en": "Ministry of Finance"
    },
    "payment_status": "completed",
    "ministry_confirmed_at": "2025-08-15T10:23:45Z",
    "receipt_url": "https://api.beza.sy/receipts/GOV-2025-0815-7823",
    "qr_data": "beza://verify?ref=GOV-2025-0815-7823&hash=a1b2c3..."
  }
}
```

## Endpoint: Fine Query

```http
POST /api/v1/government/fine/query
Authorization: Bearer {token}
Content-Type: application/json

{
  "query_type": "traffic",  // traffic | court
  "identifier": {
    "license_plate": "۱۲۳٤٥٦",  // For traffic
    "driver_license": null
  }
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "fines": [
      {
        "fine_id": "TRAF-2025-7890",
        "type": "traffic",
        "description": "تجاوز السرعة القصوى",
        "location": "دمشق — أوتستراد المزة",
        "date": "2025-06-20",
        "amount": 15000,
        "discount_if_early": 7500,
        "discount_deadline": "2025-09-20",
        "status": "unpaid"
      }
    ],
    "total_due": 15000,
    "early_payment_discount_available": true,
    "discounted_total": 7500
  }
}
```

## Endpoint: Fine Payment

```http
POST /api/v1/government/fine/pay
Authorization: Bearer {token}
Idempotency-Key: {uuid}
Content-Type: application/json

{
  "fine_ids": ["TRAF-2025-7890"],
  "use_early_discount": true,
  "total_amount": 7500,
  "pin": "hashed_pin_value"
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "transaction_id": "gov_txn_fine_456",
    "receipt_ref": "GOV-2025-0815-7824",
    "amount_paid": 7537,
    "base_amount": 7500,
    "beza_fee": 37,
    "original_fine_amount": 15000,
    "early_discount_applied": 7500,
    "biller": {
      "code": "TRAF",
      "name": "مديرية المرور",
      "name_en": "Traffic Directorate"
    },
    "payment_status": "completed",
    "receipt_url": "https://api.beza.sy/receipts/GOV-2025-0815-7824"
  }
}
```

## Endpoint: Passport Query

```http
POST /api/v1/government/passport/query
Authorization: Bearer {token}
Content-Type: application/json

{
  "application_number": "PPR-2025-7890123",
  "passport_type": "renewal"  // renewal | new
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "applicant_name": "سامر أحمد",
    "application_number": "PPR-2025-7890123",
    "application_status": "approved",
    "fee_options": [
      {
        "type": "standard",
        "pages": 6,
        "amount": 75000,
        "processing_time": "10 business days"
      },
      {
        "type": "urgent",
        "pages": 6,
        "amount": 125000,
        "processing_time": "2 business days"
      }
    ],
    "currency": "SYP",
    "valid_until": "2025-12-31"
  }
}
```

## Endpoint: Passport Payment

```http
POST /api/v1/government/passport/pay
Authorization: Bearer {token}
Idempotency-Key: {uuid}
Content-Type: application/json

{
  "application_number": "PPR-2025-7890123",
  "fee_type": "standard",
  "total_amount": 75000,
  "pin": "hashed_pin_value"
}
```

## Endpoint: Tuition Query

```http
POST /api/v1/government/tuition/query
Authorization: Bearer {token}
Content-Type: application/json

{
  "student_id": "2024123456",
  "university_code": "DAMASCUS"
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "student_name": "ليلى أحمد",
    "student_id": "2024123456",
    "university": "جامعة دمشق",
    "faculty": "كلية الهندسة",
    "semester": "خريفي 2025-2026",
    "fee_breakdown": {
      "tuition": 200000,
      "registration": 25000,
      "faculty_fee": 15000
    },
    "total_due": 240000,
    "deadline": "2025-09-10",
    "days_remaining": 25,
    "status": "unpaid"
  }
}
```

## Endpoint: Tuition Payment

```http
POST /api/v1/government/tuition/pay
Authorization: Bearer {token}
Idempotency-Key: {uuid}
Content-Type: application/json

{
  "student_id": "2024123456",
  "university_code": "DAMASCUS",
  "semester_code": "F2025",
  "total_amount": 240000,
  "pin": "hashed_pin_value"
}
```

## Endpoint: Payment History

```http
GET /api/v1/government/history?page=1&per_page=20&service=tax&from=2025-01-01&to=2025-12-31
Authorization: Bearer {token}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "transactions": [
      {
        "transaction_id": "gov_txn_abc123",
        "service_type": "tax",
        "biller": "وزارة المالية",
        "reference_id": "2536894751",
        "amount": 263812,
        "base_amount": 262500,
        "fee": 1312,
        "currency": "SYP",
        "status": "completed",
        "receipt_ref": "GOV-2025-0815-7823",
        "paid_at": "2025-08-15T10:23:45Z",
        "settled_at": "2025-08-15T15:00:00Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 15,
      "last_page": 1
    },
    "summary": {
      "total_paid": 1500000,
      "total_fees": 7500,
      "by_service": {
        "tax": 525000,
        "fine": 15000,
        "passport": 75000,
        "tuition": 240000
      }
    }
  }
}
```

## Error Responses

```json
// 400 — Validation Error
{
  "status": "error",
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "بيانات الإدخال غير صحيحة",
    "details": {
      "tax_id": ["الرقم الضريبي يجب أن يكون 10 أرقام"]
    }
  }
}

// 402 — Ministry Query Failed
{
  "status": "error",
  "error": {
    "code": "MINISTRY_QUERY_FAILED",
    "message": "تعذر الاستعلام من وزارة المالية",
    "details": {
      "ministry": "MOF",
      "retry_after_seconds": 30
    }
  }
}

// 404 — Tax ID Not Found
{
  "status": "error",
  "error": {
    "code": "TAX_ID_NOT_FOUND",
    "message": "الرقم الضريبي غير موجود في سجلات وزارة المالية"
  }
}

// 409 — Duplicate Payment
{
  "status": "error",
  "error": {
    "code": "DUPLICATE_PAYMENT",
    "message": "تمت معالجة هذا الدفع مسبقاً",
    "details": {
      "existing_transaction_id": "gov_txn_abc123",
      "receipt_ref": "GOV-2025-0815-7823"
    }
  }
}

// 503 — Ministry Unavailable
{
  "status": "error",
  "error": {
    "code": "MINISTRY_UNAVAILABLE",
    "message": "نظام وزارة المالية غير متاح حالياً. يرجى المحاولة لاحقاً",
    "details": {
      "estimated_recovery_time": "2025-08-15T11:00:00Z"
    }
  }
}
```
