# Settlement Backend API Specification

## Endpoint: Create Settlement Batch

```http
POST /api/v1/settlement/batch/create
Authorization: Bearer {token}
Content-Type: application/json

{
  "type": "eod",
  "cut_off_time": "2026-05-29T23:00:00Z",
  "entity_types": ["bank", "biller", "merchant", "agent"],
  "include_transactions_from": "2026-05-28T23:00:00Z"
}
```

### Response: 201 Created
```json
{
  "status": "success",
  "data": {
    "batch_id": "batch_stl_20260529_001",
    "batch_number": "STL-20260529-0001",
    "type": "eod",
    "status": "draft",
    "cut_off_time": "2026-05-29T23:00:00Z",
    "transaction_count": 12450,
    "total_amount": 125800000,
    "currency": "SYP",
    "items": [
      {
        "entity_type": "bank",
        "entity_id": "bemo_saudi_fransi",
        "transaction_count": 5400,
        "total_debit": 12000000,
        "total_credit": 57000000
      },
      {
        "entity_type": "biller",
        "entity_id": "syriatel",
        "transaction_count": 2800,
        "total_debit": 18500000,
        "total_credit": 6000000
      }
    ],
    "created_at": "2026-05-29T23:00:00Z"
  }
}
```

## Endpoint: Process Settlement Batch

```http
POST /api/v1/settlement/batch/{id}/process
Authorization: Bearer {token}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "batch_id": "batch_stl_20260529_001",
    "batch_number": "STL-20260529-0001",
    "status": "awaiting_confirmation",
    "processed_at": "2026-05-29T23:15:00Z",
    "netting_summary": {
      "net_amount": 45000000,
      "positions": [
        {
          "entity_type": "bank",
          "entity_id": "bemo_saudi_fransi",
          "total_debit": 12000000,
          "total_credit": 57000000,
          "net_amount": 45000000,
          "direction": "pay"
        },
        {
          "entity_type": "biller",
          "entity_id": "syriatel",
          "total_debit": 18500000,
          "total_credit": 6000000,
          "net_amount": -12500000,
          "direction": "receive"
        }
      ]
    },
    "payment_orders": [
      {
        "order_id": "po_001",
        "entity_type": "bank",
        "amount": 45000000,
        "direction": "pay",
        "status": "transmitted",
        "external_reference": "BANK-REF-8877"
      }
    ]
  }
}
```

## Endpoint: Get Batch Detail

```http
GET /api/v1/settlement/batch/{id}
Authorization: Bearer {token}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "batch_id": "batch_stl_20260529_001",
    "batch_number": "STL-20260529-0001",
    "type": "eod",
    "status": "on_hold",
    "cut_off_time": "2026-05-29T23:00:00Z",
    "transaction_count": 12450,
    "total_amount": 125800000,
    "net_amount": 45000000,
    "currency": "SYP",
    "hold_reason": "Exception EXC-001: amount mismatch",
    "held_at": "2026-05-29T23:45:00Z",
    "processed_at": "2026-05-29T23:15:00Z",
    "settled_at": null,
    "items": [
      {
        "item_id": 1,
        "entity_type": "bank",
        "entity_id": "bemo_saudi_fransi",
        "transaction_count": 5400,
        "total_debit": 12000000,
        "total_credit": 57000000,
        "net_amount": 45000000,
        "status": "unmatched",
        "external_confirmed_amount": 45005000
      }
    ],
    "payment_orders": [
      {
        "order_id": "po_001",
        "amount": 45000000,
        "status": "confirmed",
        "confirmed_amount": 45005000,
        "bank_reference": "BSF-CONF-8877",
        "confirmed_at": "2026-05-29T23:50:00Z"
      }
    ],
    "reconciliation": {
      "id": "recon_001",
      "status": "partially_matched",
      "match_rate": 99.2,
      "matched_items": 12350,
      "unmatched_items": 100,
      "completed_at": "2026-05-30T00:15:00Z"
    },
    "exceptions": [
      {
        "exception_id": "exc_001",
        "type": "amount_mismatch",
        "severity": "medium",
        "status": "open",
        "internal_amount": 45000000,
        "external_amount": 45005000,
        "difference": 5000,
        "created_at": "2026-05-29T23:45:00Z"
      }
    ],
    "created_at": "2026-05-29T23:00:00Z"
  }
}
```

## Endpoint: List Settlement Batches

```http
GET /api/v1/settlement/batches?status=all&type=eod&date_from=2026-05-01&date_to=2026-05-29&page=1&per_page=20
Authorization: Bearer {token}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "batches": [
      {
        "batch_id": "batch_stl_20260529_001",
        "batch_number": "STL-20260529-0001",
        "type": "eod",
        "status": "settled",
        "transaction_count": 12450,
        "total_amount": 125800000,
        "net_amount": 45000000,
        "match_rate": 99.84,
        "exception_count": 2,
        "created_at": "2026-05-29T23:00:00Z",
        "settled_at": "2026-05-30T00:30:00Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 45,
      "last_page": 3
    },
    "summary": {
      "total_batches": 45,
      "total_transactions": 520000,
      "total_amount": 5200000000,
      "avg_match_rate": 99.3,
      "open_exceptions": 5,
      "settled_batches": 42,
      "on_hold_batches": 1,
      "failed_batches": 2
    }
  }
}
```

## Endpoint: Generate Payment Orders

```http
POST /api/v1/settlement/payment-order/generate
Authorization: Bearer {token}
Content-Type: application/json

{
  "batch_id": "batch_stl_20260529_001"
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "batch_id": "batch_stl_20260529_001",
    "payment_orders": [
      {
        "order_id": "po_001",
        "entity_type": "bank",
        "entity_id": "bemo_saudi_fransi",
        "direction": "pay",
        "amount": 45000000,
        "currency": "SYP",
        "status": "generated",
        "file_format": "ISO_20022_CAMT_053",
        "file_content_preview": "Reference,Amount,Currency,SettlementAccount,ValueDate\npo_001,45000000,SYP,acc_bank_bsf,2026-05-29",
        "created_at": "2026-05-29T23:15:00Z"
      }
    ]
  }
}
```

## Endpoint: Run Reconciliation

```http
POST /api/v1/settlement/reconciliation/run
Authorization: Bearer {token}
Content-Type: application/json

{
  "batch_id": "batch_stl_20260529_001"
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "reconciliation_id": "recon_001",
    "batch_id": "batch_stl_20260529_001",
    "date": "2026-05-29",
    "status": "partially_matched",
    "total_items": 12450,
    "matched_items": 12350,
    "unmatched_items": 100,
    "match_rate": 99.2,
    "total_internal_amount": 125800000,
    "total_external_amount": 125805000,
    "total_difference": 5000,
    "completed_at": "2026-05-30T00:15:00Z",
    "summary_by_type": {
      "exact": 12000,
      "within_tolerance": 350,
      "amount_mismatch": 98,
      "missing_confirmation": 2,
      "duplicate": 0
    }
  }
}
```

## Endpoint: Get Reconciliation by Date

```http
GET /api/v1/settlement/reconciliation/{date}
Authorization: Bearer {token}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "date": "2026-05-29",
    "reconciliations": [
      {
        "reconciliation_id": "recon_001",
        "batch_id": "batch_stl_20260529_001",
        "batch_number": "STL-20260529-0001",
        "status": "partially_matched",
        "match_rate": 99.2,
        "matched_items": 12350,
        "unmatched_items": 100,
        "completed_at": "2026-05-30T00:15:00Z"
      }
    ],
    "daily_summary": {
      "total_batches": 4,
      "total_items": 18500,
      "avg_match_rate": 99.4,
      "total_matched": 18350,
      "total_unmatched": 150,
      "open_exceptions": 3
    }
  }
}
```

## Endpoint: List Settlement Exceptions

```http
GET /api/v1/settlement/exceptions?severity=high&status=open&batch_id=...&page=1&per_page=20
Authorization: Bearer {token}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "exceptions": [
      {
        "exception_id": "exc_001",
        "batch_id": "batch_stl_20260529_001",
        "batch_number": "STL-20260529-0001",
        "type": "amount_mismatch",
        "severity": "high",
        "status": "open",
        "internal_amount": 45000000,
        "external_amount": 45005000,
        "difference": 5000,
        "description": "Bank Bemo Saudi Fransi credited 5,000 SYP more than expected",
        "entity_type": "bank",
        "entity_id": "bemo_saudi_fransi",
        "created_at": "2026-05-29T23:45:00Z",
        "time_open": "2h 15m",
        "batch_status": "on_hold"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 5,
      "last_page": 1
    },
    "summary": {
      "total_open": 3,
      "total_investigating": 1,
      "total_resolved_today": 12,
      "by_severity": {
        "critical": 0,
        "high": 1,
        "medium": 1,
        "low": 1
      },
      "by_type": {
        "amount_mismatch": 2,
        "missing_confirmation": 1,
        "duplicate": 0,
        "rejected": 0
      }
    }
  }
}
```

## Endpoint: Resolve Exception

```http
POST /api/v1/settlement/exceptions/{id}/resolve
Authorization: Bearer {token}
Content-Type: application/json

{
  "resolution_type": "adjustment",
  "notes": "Bank deducted 5,000 SYP transfer fee before crediting. Created adjustment entry DR Bank Charges 5,000 SYP.",
  "attachment_reference": "attachment_bsf_statement_20260529.pdf"
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "exception_id": "exc_001",
    "previous_status": "open",
    "status": "resolved",
    "resolution_type": "adjustment",
    "resolution_notes": "Bank deducted 5,000 SYP transfer fee before crediting. Created adjustment entry DR Bank Charges 5,000 SYP.",
    "resolved_by": "user_layla_ops",
    "resolved_at": "2026-05-30T00:30:00Z",
    "batch_released": true,
    "batch_status": "awaiting_confirmation"
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
      "cut_off_time": ["وقت القطع مطلوب"],
      "type": ["نوع الدفعة يجب أن يكون eod أو realtime"]
    }
  }
}

// 409 — Batch Already Processed
{
  "status": "error",
  "error": {
    "code": "BATCH_ALREADY_PROCESSED",
    "message": "تمت معالجة هذه الدفعة مسبقاً",
    "details": {
      "batch_id": "batch_stl_20260529_001",
      "current_status": "settled",
      "processed_at": "2026-05-29T23:15:00Z"
    }
  }
}

// 423 — Batch On Hold
{
  "status": "error",
  "error": {
    "code": "BATCH_ON_HOLD",
    "message": "الدفعة معلقة بسبب استثناءات",
    "details": {
      "batch_id": "batch_stl_20260529_001",
      "hold_reason": "Exception EXC-001: amount mismatch",
      "held_at": "2026-05-29T23:45:00Z",
      "open_exceptions": 3
    }
  }
}

// 404 — Not Found
{
  "status": "error",
  "error": {
    "code": "BATCH_NOT_FOUND",
    "message": "الدفعة غير موجودة"
  }
}

// 404 — Exception Not Found
{
  "status": "error",
  "error": {
    "code": "EXCEPTION_NOT_FOUND",
    "message": "الاستثناء غير موجود"
  }
}

// 409 — Exception Already Resolved
{
  "status": "error",
  "error": {
    "code": "EXCEPTION_ALREADY_RESOLVED",
    "message": "تمت تسوية هذا الاستثناء مسبقاً",
    "details": {
      "exception_id": "exc_001",
      "resolved_at": "2026-05-30T00:30:00Z",
      "resolution_type": "adjustment"
    }
  }
}
```
