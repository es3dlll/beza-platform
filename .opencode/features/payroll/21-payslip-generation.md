# 21 — Payslip Generation

---

## PDF Specification

| Property | Detail |
|----------|--------|
| Format | A4 portrait |
| Language | Arabic (right-to-left) |
| Font | Noto Naskh Arabic (embedded) |
| Colour | Company branding colours + Beza watermark |
| Encryption | AES-256 (PDF user password = employee national ID; owner password = Beza master) |
| Digital signature | Beza seal image + QR code for online verification |

## Payslip Content

```
┌─────────────────────────────────────────────┐
│                                             │
│            [Company Logo]                   │
│     شركة الشام للصناعات الحديدية              │
│                                             │
│            كشف الراتب                        │
│            PAYSLIP                          │
│                                             │
│  الشهر: مايو 2026                           │
│  May 2026                                   │
│                                             │
│─────────────────────────────────────────────│
│                                             │
│  اسم الموظف: أحمد علي                        │
│  Employee: Ahmad Ali                        │
│                                             │
│  رقم الموظف: EMP-012                        │
│  القسم: إنتاج                               │
│  المسمى: ملحّم                              │
│                                             │
│─────────────────────────────────────────────│
│                                             │
│  البيان                |    المبلغ           │
│  ──────────────────────|──────────────────── │
│  الراتب الأساسي        |  950,000 ل.س        │
│  بدل نقل              |  150,000 ل.س         │
│  بدل إنتاج            |  200,000 ل.س         │
│  ──────────────────────|──────────────────── │
│  الإجمالي             |  1,300,000 ل.س       │
│  ──────────────────────|──────────────────── │
│  الخصومات:                                  │
│  ضريبة الدخل          |   50,000 ل.س         │
│  التأمينات            |   50,000 ل.س         │
│  ──────────────────────|──────────────────── │
│  الصافي               |  1,200,000 ل.س       │
│                                             │
│─────────────────────────────────────────────│
│                                             │
│  تاريخ الدفع: 29 مايو 2026                  │
│  Payment Date: 2026-05-29                   │
│                                             │
│  مرجع الدفعة: B-2026-05-001                 │
│  Batch Reference                            │
│                                             │
│  [QR Code] ← مسح للتحقق من صحة الكشف        │
│  Scan to verify authenticity                │
│                                             │
│  [Beza Digital Seal]                        │
│                                             │
└─────────────────────────────────────────────┘
```

## QR Code Payload

```json
{
  "type": "payslip_verification",
  "employee": "uuid",
  "batch": "B-2026-05-001",
  "amount": 1200000,
  "paid_at": "2026-05-29T10:05:01Z"
}
```

Signed with Beza's private key. Verification endpoint: `GET /payroll/v1/payslip/verify?data=<signed_payload>`

## Generation Pipeline

```
trigger: batch completed
    ↓
select all successful transactions
    ↓
for each transaction (parallel):
    ↓
    load employee + company data
    ↓
    render HTML template (Handlebars)
    ↓
    convert HTML → PDF (Puppeteer)
    ↓
    apply encryption (AES-256)
    ↓
    upload to S3: payslips/{company}/{batch}/{employee_id}.pdf
    ↓
    store S3 key in payroll_transactions
    ↓
after all complete:
    ↓
    update batch.payslip_generated_at
    ↓
    generate batch ZIP archive
    ↓
    upload ZIP to S3 with pre-signed URL (expires 48h)
```

## Storage & Access

| File | Path | Retention |
|------|------|-----------|
| Per-employee PDF | `payslips/{company_id}/{batch_id}/{employee_id}.pdf` | 7 years |
| Batch ZIP | `payslips/{company_id}/{batch_id}/batch-payslips.zip` | 48 hours (regeneratable) |
| Archive | `archive/payslips/{year}/{month}/...` | After 12 months, moved to cold storage |
