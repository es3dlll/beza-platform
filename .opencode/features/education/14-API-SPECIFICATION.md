# 14 — API Specification

## 14.1 Base URLs

- **Production**: `https://api.beza.sy/education/v1`
- **Sandbox**: `https://api.sandbox.beza.sy/education/v1`
- **Headers**: `Content-Type: application/json`, `Authorization: Bearer {jwt}`, `Accept-Language: ar`

## 14.2 Parent Endpoints

### POST /payments/create
Create a payment for a school fee invoice.

```json
{
  "invoice_id": "uuid",
  "amount": 995000,
  "currency": "SYP",
  "payment_method": "beza_wallet",
  "idempotency_key": "client-generated-uuid",
  "pin": "hashed-on-device"
}
```

**Response 200**:
```json
{
  "payment_id": "uuid",
  "status": "completed",
  "receipt": {
    "receipt_number": "RCT-2026-00042",
    "pdf_url": "https://api.beza.sy/education/v1/receipts/RCT-2026-00042.pdf",
    "qr_code": "beza://receipt/RCT-2026-00042"
  },
  "timestamp": "2026-05-15T09:30:00Z"
}
```

### GET /students
List parent's registered students.

### GET /students/{id}/invoices
List all invoices for a student, with payment status.

### GET /invoices/{id}
Detailed invoice with line items, payments, balance.

### GET /payments/history
Paginated payment history with filters (school, date range, status).

### POST /auto-pay/schedule
Schedule automatic payment for an invoice on a future date.

### POST /payments/share-link
Generate a payment link that another Beza user can open and pay.

## 14.3 School Endpoints

### POST /schools/register
Merchant onboarding — creates a school record (requires admin token).

### GET /schools/{id}/dashboard
Summary dashboard data: totals, collection rate, trend data.

### GET /schools/{id}/students
Paginated list with payment status, search, filters.

### POST /schools/{id}/fee-templates
Create a fee template with line items.

### GET /schools/{id}/fee-templates
List all templates.

### POST /schools/{id}/invoices/generate
Generate invoices for all students under a given template.

### GET /schools/{id}/reports/collection
Generate collection report. Query params: `term`, `grade`, `format` (csv/pdf).

### POST /schools/{id}/communications/remind
Send bulk reminders. Body:
```json
{
  "student_ids": ["uuid", "uuid"],
  "channel": "whatsapp",
  "template_vars": {
    "custom_message": "Optional custom text"
  }
}
```

### GET /schools/{id}/settlements
List of daily/monthly settlement reports.

## 14.4 Admin/Platform Endpoints

### POST /admin/schools/approve
Approve a school after KYC.

### GET /admin/reports/platform
Platform-wide education metrics: total TPV, schools, active users.

### GET /admin/schools/pending
List schools pending KYC approval.

## 14.5 Webhook Endpoints

### POST /webhooks/payment-completed
Notify school when a payment is made. School configures target URL.

**Payload**:
```json
{
  "event": "payment.completed",
  "school_id": "uuid",
  "student_id": "uuid",
  "invoice_id": "uuid",
  "amount": 995000,
  "receipt_number": "RCT-2026-00042",
  "timestamp": "2026-05-15T09:30:00Z"
}
```

### POST /webhooks/settlement-completed
Daily settlement to school's bank account.

## 14.6 Error Codes

| Code | Meaning |
|---|---|
| EDU-001 | Invoice already paid |
| EDU-002 | Insufficient wallet balance |
| EDU-003 | Invalid invoice — expired or cancelled |
| EDU-004 | School not active |
| EDU-005 | Duplicate idempotency key |
| EDU-006 | Maximum students exceeded (tier limit) |
| EDU-007 | Payment method not supported |
| EDU-008 | Invoice generation failed — no students |
