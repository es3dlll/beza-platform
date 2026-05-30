# مواصفات واجهة API — Backend API Specification

## Base URL
`https://api.beza.sy/v1/financing`

## Authentication
All endpoints require `Authorization: Bearer <jwt_token>` header.

## Common Headers
| Header | Value |
|--------|-------|
| Accept-Language | ar (default), en |
| X-Idempotency-Key | UUID (for mutation endpoints) |

---

## 1. POST /financing/apply
Submit a financing application.

### Request
```json
{
  "product_type": "qard_hasan | murabaha | micro",
  "amount": 300000,
  "purpose": "medical | education | business | home_appliance | electronics | furniture | other",
  "term_days": 90,
  "currency": "SYP",
  "documents": [
    {
      "type": "national_id | income_proof | invoice | business_registration | other",
      "file_id": "uuid-of-uploaded-file"
    }
  ],
  "guarantor": {
    "phone": "+963931234567",
    "relationship": "brother | friend | colleague"
  },
  "consent": {
    "credit_check": true,
    "auto_deduct": true,
    "sharia_terms": true
  }
}
```

### Response (201)
```json
{
  "application_id": 12345,
  "status": "submitted",
  "estimated_decision_time_minutes": 120,
  "created_at": "2026-05-29T10:30:00Z",
  "_ar": "تم تقديم الطلب بنجاح. سيتم إعلامك بنتيجة التقييم خلال ١٢٠ دقيقة."
}
```

### Error Responses
| Code | Meaning |
|------|---------|
| 400 | Invalid amount/term/product |
| 401 | Unauthenticated |
| 403 | KYC not completed, max active loans reached |
| 409 | Duplicate application (same idempotency key) |
| 422 | Missing required documents |

---

## 2. GET /financing/applications
List user's applications with optional filters.

### Query Parameters
| Param | Type | Default | Description |
|-------|------|---------|-------------|
| status | string | all | Filter by status |
| product_type | string | all | Filter by product |
| page | int | 1 | Page number |
| per_page | int | 20 | Items per page |
| sort | string | created_at_desc | Sort order |

### Response (200)
```json
{
  "data": [
    {
      "id": 12345,
      "product_type": "qard_hasan",
      "amount": 300000,
      "status": "approved",
      "created_at": "2026-05-29T10:30:00Z",
      "status_updated_at": "2026-05-29T10:45:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total_pages": 3,
    "total_count": 58
  }
}
```

---

## 3. GET /financing/applications/{id}
Get detailed application information.

### Response (200)
```json
{
  "id": 12345,
  "user": {
    "id": 67890,
    "name": "ليلى أحمد",
    "phone": "+963********34"
  },
  "product": {
    "type": "qard_hasan",
    "name_ar": "قرض حسن",
    "name_en": "Qard Hasan"
  },
  "requested_amount": 300000,
  "requested_term_days": 90,
  "purpose": "medical",
  "status": "approved",
  "credit_score": 680,
  "documents": [
    {"type": "national_id", "status": "verified"}
  ],
  "guarantor": {
    "name": "أحمد أحمد",
    "phone": "+963********56",
    "status": "approved"
  },
  "offer": {
    "approved_amount": 300000,
    "approved_term_days": 90,
    "profit_rate": 0,
    "admin_fee": 3000,
    "total_repayment": 300000,
    "installment_amount": 3333,
    "installment_frequency": "daily",
    "expires_at": "2026-06-05T10:45:00Z"
  },
  "timeline": [
    {"status": "submitted", "at": "2026-05-29T10:30:00Z"},
    {"status": "underwriting", "at": "2026-05-29T10:32:00Z"},
    {"status": "approved", "at": "2026-05-29T10:45:00Z"}
  ]
}
```

---

## 4. POST /financing/applications/{id}/documents
Upload additional documents to an existing application.

### Request (multipart/form-data)
| Field | Type |
|-------|------|
| document_type | string |
| file | binary |

### Response (201)
```json
{
  "file_id": "uuid",
  "status": "pending_review"
}
```

---

## 5. POST /financing/applications/{id}/accept
Accept an approved offer and sign the contract.

### Request
```json
{
  "signature_type": "digital",
  "consent_auto_deduct": true,
  "consent_terms": true
}
```

### Response (200)
```json
{
  "contract": {
    "id": 9876,
    "contract_number": "BZ-QH-2026-00001",
    "status": "active",
    "disbursement_status": "pending"
  },
  "disbursement": {
    "method": "wallet",
    "estimated_time_minutes": 30
  }
}
```

---

## 6. GET /financing/active
List user's active financing contracts.

### Response (200)
```json
{
  "data": [
    {
      "contract_id": 9876,
      "contract_number": "BZ-QH-2026-00001",
      "product_type": "qard_hasan",
      "principal": 300000,
      "remaining_balance": 180000,
      "total_paid": 120000,
      "progress_percent": 40,
      "next_payment": {
        "installment_number": 37,
        "due_date": "2026-06-15",
        "amount": 3333,
        "status": "pending"
      },
      "status": "active",
      "overdue_days": 0
    }
  ]
}
```

---

## 7. GET /financing/{contractId}/schedule
Get repayment schedule for a contract.

### Response (200)
```json
{
  "contract": {
    "id": 9876,
    "contract_number": "BZ-QH-2026-00001",
    "principal": 300000,
    "profit": 0,
    "total": 300000,
    "installments_count": 90,
    "installment_amount": 3333
  },
  "summary": {
    "total_paid": 120000,
    "total_remaining": 180000,
    "paid_installments": 36,
    "pending_installments": 54,
    "overdue_installments": 0,
    "late_fees_accrued": 0
  },
  "installments": [
    {
      "number": 1,
      "due_date": "2026-05-10",
      "principal_part": 3333,
      "profit_part": 0,
      "total_due": 3333,
      "paid_amount": 3333,
      "paid_at": "2026-05-10T08:00:00Z",
      "status": "paid"
    }
  ]
}
```

---

## 8. POST /financing/{contractId}/pay
Make a manual repayment.

### Request
```json
{
  "amount": 3333,
  "installment_number": 37,
  "payment_method": "wallet",
  "source_wallet_id": "wallet-uuid"
}
```

### Response (200)
```json
{
  "transaction_id": "txn-uuid",
  "amount": 3333,
  "allocated_to": {
    "principal": 3333,
    "profit": 0,
    "late_fee": 0,
    "charity": 0
  },
  "remaining_balance": 176667,
  "next_due_date": "2026-06-16",
  "status": "success",
  "_ar": "تمت عملية السداد بنجاح ✓"
}
```

---

## 9. GET /financing/credit-score
Get user's credit score and factors.

### Response (200)
```json
{
  "score": 680,
  "max_score": 850,
  "min_score": 300,
  "trend": "up",
  "trend_value": 15,
  "last_updated": "2026-05-29T00:00:00Z",
  "factors": [
    {
      "name_ar": "نشاط المعاملات",
      "name_en": "Transaction Activity",
      "score": 476,
      "weight": 25,
      "details": "معدل ٣ معاملات يومياً"
    },
    {
      "name_ar": "الادخار",
      "name_en": "Savings",
      "score": 272,
      "weight": 20,
      "details": "متوسط الرصيد ٧٥,٠٠٠ ل.س"
    },
    {
      "name_ar": "دفع الفواتير",
      "name_en": "Bill Payment",
      "score": 612,
      "weight": 15,
      "details": "نسبة الالتزام ٩٥٪"
    },
    {
      "name_ar": "استخدام المحفظة",
      "name_en": "Wallet Usage",
      "score": 442,
      "weight": 10,
      "details": "عمر المحفظة ٨ أشهر"
    },
    {
      "name_ar": "تفاعل الوكلاء",
      "name_en": "Agent Interaction",
      "score": 374,
      "weight": 10,
      "details": "٥ وكلاء نشطين"
    }
  ],
  "improvement_tips": [
    "قم بالادخار شهرياً في محفظة الادخار لتحسين درجتك",
    "حافظ على رصيد يومي لا يقل عن ٥٠,٠٠٠ ل.س",
    "استخدم المحفظة لدفع الفواتير في موعدها"
  ]
}
```

---

## 10. POST /financing/{contractId}/restructure
Request contract restructuring.

### Request
```json
{
  "type": "extend | reduce | holiday",
  "additional_term_days": 45,
  "reason": "income_loss | medical | other",
  "acknowledge_fee": true
}
```

### Response (200)
```json
{
  "status": "restructure_requested",
  "proposed_changes": {
    "new_term_days": 135,
    "new_installment": 2222,
    "restructure_fee": 25000
  },
  "requires_approval": true
}
```

---

## Admin Endpoints

### 11. GET /admin/financing/applications
List all applications (admin).

### 12. POST /admin/financing/applications/{id}/review
Submit underwriting decision.

```json
{
  "decision": "approve | reject",
  "approved_amount": 300000,
  "approved_term_days": 90,
  "profit_rate": 0,
  "rejection_reason": "insufficient_history",
  "notes": "Internal notes",
  "underwriter_id": 123
}
```

### 13. GET /admin/financing/collections
Get collection queue.

### 14. POST /admin/financing/contracts/{id}/restructure/approve
Approve restructuring request.

### 15. POST /admin/financing/contracts/{id}/default
Declare contract as defaulted.
