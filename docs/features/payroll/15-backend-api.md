# 15 — Backend API

> **Key File** — Complete REST API specification for Payroll feature.

---

## Base URL

```
Production:  https://api.beza.sy/payroll/v1
Sandbox:     https://sandbox-api.beza.sy/payroll/v1
```

## Authentication

- Company API calls: `Authorization: Bearer <company_api_key>` (mTLS optional for direct API)
- Employee-facing calls: `Authorization: Bearer <user_jwt>`
- Admin calls: `Authorization: Bearer <admin_jwt>`

## Standard Headers

| Header | Value | Required |
|--------|-------|----------|
| `Content-Type` | `application/json` | Yes |
| `Accept-Language` | `ar` or `en` | Optional (default: `ar`) |
| `Idempotency-Key` | UUID | For POST/PUT requests |

## Standard Error Response

```json
{
  "error": {
    "code": "INSUFFICIENT_BALANCE",
    "message_ar": "رصيد الشركة غير كافٍ. المبلغ المطلوب: 10,000,000 ل.س، الرصيد المتاح: 5,000,000 ل.س",
    "message_en": "Insufficient company balance.",
    "details": { "required": 10000000, "available": 5000000 }
  }
}
```

---

## Endpoints

### POST /payroll/batch — إنشاء دفعة جديدة

Create and process (or schedule) a new payroll batch.

**Request Body:**
```json
{
  "company_id": "uuid-company",
  "currency": "SYP",
  "schedule_date": "2026-06-01",
  "employee_list": [
    { "employee_id": "uuid-emp-1", "amount": 1200000 },
    { "employee_id": "uuid-emp-2", "amount": 850000 },
    { "employee_id": "uuid-emp-3", "amount": 1500000 }
  ]
}
```

**Response 201:**
```json
{
  "batch_id": "uuid-batch",
  "batch_ref": "B-2026-05-001",
  "status": "pending",
  "total_employees": 3,
  "total_amount": 3550000,
  "total_fee": 17750,
  "currency": "SYP",
  "schedule_date": "2026-06-01",
  "created_at": "2026-05-29T10:00:00Z"
}
```

**Errors:** `INVALID_EMPLOYEE`, `INSUFFICIENT_BALANCE`, `CURRENCY_MISMATCH`, `CSV_VALIDATION_FAILED`

---

### GET /payroll/batch/{batch_id} — تفاصيل الدفعة

Get full batch details including per-employee transaction statuses.

**Response 200:**
```json
{
  "batch_id": "uuid",
  "batch_ref": "B-2026-05-001",
  "company_id": "uuid",
  "status": "partial_failure",
  "total_employees": 150,
  "total_amount": 120000000,
  "total_fee": 600000,
  "currency": "SYP",
  "processed_at": "2026-05-29T10:05:00Z",
  "failed_count": 2,
  "settled_at": null,
  "transactions": [
    {
      "employee_id": "uuid",
      "employee_name_ar": "أحمد علي",
      "amount": 1200000,
      "status": "success",
      "paid_at": "2026-05-29T10:05:01Z"
    },
    {
      "employee_id": "uuid",
      "employee_name_ar": "خالد عمر",
      "amount": 850000,
      "status": "failed",
      "failure_reason": "wallet_not_active",
      "retry_count": 2
    }
  ]
}
```

---

### GET /payroll/batches — قائمة الدفعات

List batches for a company, with optional date range.

**Query Parameters:**
| Param | Type | Description |
|-------|------|-------------|
| `company_id` | UUID | Required |
| `status` | string | Optional filter: `completed`, `failed`, ... |
| `from` | date | Start date (YYYY-MM-DD) |
| `to` | date | End date (YYYY-MM-DD) |
| `page` | int | Pagination (default 1) |
| `size` | int | Page size (default 20, max 100) |

**Response 200:**
```json
{
  "items": [
    {
      "batch_id": "uuid",
      "batch_ref": "B-2026-05-001",
      "total_employees": 150,
      "total_amount": 120000000,
      "status": "completed",
      "processed_at": "2026-05-29T10:05:00Z",
      "failed_count": 0
    }
  ],
  "total": 12,
  "page": 1,
  "size": 20
}
```

---

### GET /payroll/employee/{employee_id}/history — سجل رواتب الموظف

Employee's salary payment history (accessible by employee via JWT or by company).

**Response 200:**
```json
{
  "employee_id": "uuid",
  "employee_name_ar": "أحمد علي",
  "payments": [
    {
      "batch_ref": "B-2026-05-001",
      "amount": 1200000,
      "currency": "SYP",
      "paid_at": "2026-05-29T10:05:01Z",
      "payslip_url": "https://api.beza.sy/payroll/v1/payslip/uuid/download?token=...",
      "status": "success"
    },
    {
      "batch_ref": "B-2026-04-001",
      "amount": 1200000,
      "currency": "SYP",
      "paid_at": "2026-04-30T09:00:00Z",
      "payslip_url": "https://...",
      "status": "success"
    }
  ]
}
```

---

### POST /payroll/company/register — تسجيل شركة جديدة

Initiate company onboarding.

**Request Body:**
```json
{
  "name_ar": "شركة الشام للصناعات الحديدية",
  "name_en": "Al-Sham Steel Industries",
  "license_number": "12345",
  "license_file": "base64-encoded-pdf",
  "tax_id": "67890",
  "tax_cert_file": "base64-encoded-pdf",
  "authorized_signatory": {
    "full_name_ar": "محمد خالد",
    "national_id": "01010123456",
    "phone": "+963933123456",
    "email": "m.khalid@shamsteel.sy"
  },
  "beneficial_owners": [
    { "full_name_ar": "محمد خالد", "ownership_pct": 60.0, "national_id": "01010123456" },
    { "full_name_ar": "سامر أحمد", "ownership_pct": 40.0, "national_id": "01010987654" }
  ],
  "settlement_period": "T+0"
}
```

**Response 201:**
```json
{
  "company_id": "uuid",
  "status": "pending_review",
  "message_ar": "تم استلام طلب التسجيل. سيتم مراجعته من قبل فريق بيزة خلال 24 ساعة.",
  "message_en": "Registration received. Beza team will review within 24 hours."
}
```

---

### POST /payroll/batch/{batch_id}/retry — إعادة محاولة الدفعة

Retry all failed transactions in a batch, or specific employees.

**Request Body (optional):**
```json
{
  "employee_ids": ["uuid-emp-1", "uuid-emp-3"]
}
```
If omitted, retries all failed.

**Response 200:**
```json
{
  "batch_id": "uuid",
  "retried": 2,
  "succeeded": 1,
  "still_failed": 1,
  "details": [
    { "employee_id": "uuid", "status": "success" },
    { "employee_id": "uuid", "status": "failed", "failure_reason": "insufficient_balance" }
  ]
}
```

---

### POST /payroll/employee — إضافة/تحديث موظف

Add or update an employee in the company roster.

**Request Body:**
```json
{
  "company_id": "uuid",
  "employee_id": "EMP-001",
  "full_name_ar": "أحمد علي",
  "phone": "+963955123456",
  "department": "إنتاج",
  "role": "ملحّم",
  "salary_amount": 1200000,
  "currency": "SYP"
}
```

**Response 201:** Employee object.

---

### Webhook Callbacks

Beza will POST to company-registered webhook URL on batch state changes.

```json
{
  "event": "batch.completed",
  "batch_id": "uuid",
  "batch_ref": "B-2026-05-001",
  "status": "completed",
  "timestamp": "2026-05-29T10:05:00Z",
  "summary": {
    "total": 150,
    "succeeded": 148,
    "failed": 2,
    "total_amount": 120000000
  }
}
```

**Events:** `batch.completed`, `batch.partial_failure`, `batch.failed`, `batch.settled`
