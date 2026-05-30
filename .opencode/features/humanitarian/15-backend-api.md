# Backend API Reference

## Base URL: `/api/v1/humanitarian`

## Authentication
All endpoints require OAuth 2.0 Bearer token with appropriate scopes.

## Endpoints

### Program Management

#### POST /programs
Create a new humanitarian aid program.

**Request:**
```json
{
  "ngo_id": "ngo_sarc_001",
  "name_ar": "برنامج المساعدة النقدية متعددة الأغراض",
  "name_en": "Multi-Purpose Cash Assistance Program",
  "program_type": "mpc",
  "description_ar": "مساعدات نقدية غير مشروطة للأسر المتضررة",
  "description_en": "Unconditional cash assistance for affected families",
  "currency": "USD",
  "budget": 5000000.00,
  "start_date": "2026-06-01",
  "end_date": "2026-12-31",
  "distribution_rules": {
    "amount_per_household": 75.00,
    "frequency": "monthly",
    "max_distributions": 7,
    "conditional_triggers": null
  }
}
```

**Response (201):**
```json
{
  "program_id": "prog_abc123",
  "status": "draft",
  "created_at": "2026-05-29T10:30:00Z"
}
```

---

#### POST /programs/{id}/beneficiaries
Upload beneficiary CSV data. Accepts `multipart/form-data`.

**Request:**
| Field | Type | Description |
|-------|------|-------------|
| `file` | CSV | UTF-8 encoded CSV with headers: `full_name`, `unhcr_id`, `phone`, `governorate`, `district`, `family_size`, `head_of_household` |
| `is_test` | bool | If true, validates without inserting |

**CSV Format:**
```csv
full_name,unhcr_id,phone,governorate,district,family_size,head_of_household
فاطمة العمر,SYR-8293-001,963955123456,Idlib,Atmeh,6,Y
أحمد المحمود,SYR-8293-002,963933789012,Idlib,Saraqib,4,Y
خديجة حسن,SYR-8293-003,963988456789,Aleppo,Al-Safira,3,N
```

**Response (202):**
```json
{
  "batch_id": "batch_xyz789",
  "total_records": 1500,
  "valid": 1492,
  "errors": 8,
  "error_details": [
    { "row": 15, "field": "phone", "message": "Invalid phone format" },
    { "row": 42, "field": "unhcr_id", "message": "Duplicate UNHCR ID" }
  ],
  "sanctions_screening": {
    "total_screened": 1492,
    "pending_review": 3,
    "cleared": 1489
  }
}
```

---

### Distribution

#### POST /distribute
Execute a distribution batch.

**Request:**
```json
{
  "program_id": "prog_abc123",
  "distribution_type": "mpc",
  "amount_per_beneficiary": 75.00,
  "schedule": "immediate",
  "note": "June 2026 MPC distribution"
}
```

**Response (202):**
```json
{
  "batch_id": "batch_dist_456",
  "status": "processing",
  "total_beneficiaries": 1489,
  "total_amount": 111675.00,
  "estimated_completion": "2026-05-29T10:32:00Z"
}
```

---

#### GET /distributions
List distributions with optional filters.

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `program_id` | string | Filter by program |
| `status` | string | `processing`, `completed`, `failed`, `partial` |
| `from` | date | Start date |
| `to` | date | End date |
| `page` | int | Page number |
| `per_page` | int | Items per page (default: 50) |

**Response (200):**
```json
{
  "data": [
    {
      "batch_id": "batch_dist_456",
      "program_id": "prog_abc123",
      "status": "completed",
      "total_beneficiaries": 1489,
      "successful": 1489,
      "failed": 0,
      "total_amount": 111675.00,
      "executed_at": "2026-05-29T10:31:45Z",
      "completed_at": "2026-05-29T10:31:45Z"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 50,
    "total": 12
  }
}
```

---

### MPC Spending Monitoring

#### GET /programs/{id}/spending
Get aggregated MPC spending data.

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `from` | date | Start date |
| `to` | date | End date |
| `governorate` | string | Filter by governorate |
| `group_by` | string | `category`, `governorate`, `household_size` |

**Response (200):**
```json
{
  "program_id": "prog_abc123",
  "period": { "from": "2026-06-01", "to": "2026-06-30" },
  "total_disbursed": 111675.00,
  "total_spent": 89340.00,
  "burn_rate": {
    "7_days": 0.45,
    "14_days": 0.72,
    "30_days": 0.80
  },
  "by_category": {
    "food": { "amount": 44670.00, "percentage": 50 },
    "rent": { "amount": 17868.00, "percentage": 20 },
    "health": { "amount": 8934.00, "percentage": 10 },
    "education": { "amount": 4467.00, "percentage": 5 },
    "transport": { "amount": 4467.00, "percentage": 5 },
    "other": { "amount": 8934.00, "percentage": 10 }
  },
  "by_governorate": {
    "Idlib": { "disbursed": 50000.00, "spent": 40000.00 },
    "Aleppo": { "disbursed": 61675.00, "spent": 49340.00 }
  }
}
```

---

### Voucher Management

#### POST /vouchers/create
Issue e-vouchers to beneficiaries.

**Request:**
```json
{
  "program_id": "prog_abc123",
  "item_list": [
    { "item_id": "rice_5kg", "name_ar": "أرز 5 كغم", "name_en": "Rice 5kg", "unit_price": 6.00, "max_quantity": 4 },
    { "item_id": "oil_1l", "name_ar": "زيت طبخ 1 لتر", "name_en": "Cooking Oil 1L", "unit_price": 2.00, "max_quantity": 6 },
    { "item_id": "flour_10kg", "name_ar": "طحين 10 كغم", "name_en": "Flour 10kg", "unit_price": 8.00, "max_quantity": 2 },
    { "item_id": "sugar_2kg", "name_ar": "سكر 2 كغم", "name_en": "Sugar 2kg", "unit_price": 3.00, "max_quantity": 3 }
  ],
  "voucher_value": 45.00,
  "expiry_days": 30
}
```

**Response (201):**
```json
{
  "batch_id": "vouch_batch_789",
  "vouchers_issued": 1489,
  "total_value": 67005.00
}
```

---

#### POST /vouchers/redeem
Redeem a voucher at a partner merchant.

**Request:**
```json
{
  "voucher_code": "VCH-8293-4721",
  "pin": "7381",
  "merchant_id": "merch_abukhaled_01",
  "items": [
    { "item_id": "rice_5kg", "quantity": 2 },
    { "item_id": "oil_1l", "quantity": 3 },
    { "item_id": "sugar_2kg", "quantity": 1 }
  ]
}
```

**Response (200):**
```json
{
  "transaction_id": "txn_vch_001",
  "voucher_code": "VCH-8293-4721",
  "items_redeemed": 3,
  "total_deducted": 21.00,
  "remaining_balance": 24.00,
  "merchant_settlement": {
    "amount": 21.00,
    "expected_settlement_date": "2026-06-01"
  }
}
```

---

### Donor Reporting

#### GET /reports/donor
Generate a donor report.

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `ngo_id` | string | NGO identifier |
| `program_id` | string | Filter by program |
| `from` | date | Start date |
| `to` | date | End date |
| `format` | string | `json`, `pdf`, `csv` (default: json) |

**Response (200):**
```json
{
  "report_id": "rpt_2026_q2",
  "generated_at": "2026-07-01T00:00:00Z",
  "ngo": {
    "id": "ngo_sarc_001",
    "name": "Syrian Arab Red Crescent"
  },
  "period": { "from": "2026-04-01", "to": "2026-06-30" },
  "summary": {
    "total_funds_received": 5000000.00,
    "total_disbursed": 4850000.00,
    "total_beneficiaries_reached": 64800,
    "average_transfer": 74.85,
    "programs_active": 4
  },
  "programs": [
    {
      "program_id": "prog_abc123",
      "name_en": "MPC Assistance",
      "beneficiaries": 15000,
      "distributions": 2,
      "total_disbursed": 2250000.00,
      "spending_breakdown": { "food": 50, "rent": 20, "health": 10, "education": 5, "transport": 5, "other": 10 }
    }
  ],
  "spending_analysis": {
    "burn_rate_7d": 0.45,
    "burn_rate_30d": 0.80,
    "top_categories": ["food", "rent", "health"]
  },
  "reconciliation": {
    "funds_sent_by_ngo": 5000000.00,
    "fees_deducted": 50000.00,
    "net_disbursed": 4850000.00,
    "unspent_returned": 100000.00
  }
}
```

---

## Error Responses

| Status | Code | Description |
|--------|------|-------------|
| 400 | `validation_error` | Invalid input data |
| 401 | `unauthorized` | Missing or invalid auth token |
| 403 | `forbidden` | Insufficient permissions |
| 404 | `not_found` | Resource not found |
| 409 | `conflict` | Duplicate or state conflict |
| 422 | `sanctions_blocked` | Beneficiary flagged by sanctions screening |
| 429 | `rate_limited` | Too many requests |
| 500 | `internal_error` | Server error |
