# 13 — Data Model

## 13.1 Entity Relationship Overview

```
School ──1:N──> Faculty/Branch
  │                 │
  │                 └──1:N──> Grade/Level
  │                               │
  │                               └──1:N──> Section/Class
  │                                             │
  │                                             └──1:N──> Student
  │                                                         │
  │                                                         ├──1:N──> Enrolment
  │                                                         │           │
  │                                                         │           └──1:N──> FeeInvoice
  │                                                         │                       │
  │                                                         │                       ├──1:N──> InvoiceLineItem
  │                                                         │                       └──1:N──> Payment
  │                                                         │                                   │
  │                                                         │                                   └──1:1──> Receipt
  │                                                         │
  │                                                         └──1:N──> GuardianLink
  │                                                                     │
  │                                                                     └──N:1──> User (Parent)
  │
  ├──1:N──> FeeTemplate
  │           │
  │           └──1:N──> FeeLineItem
  │
  ├──1:N──> Staff
  │
  └──1:1──> SettlementAccount
```

## 13.2 Core Entities

### School
| Field | Type | Notes |
|---|---|---|
| id | UUID | PK |
| name_ar | String | Arabic name |
| name_en | String | English name |
| type | Enum | PUBLIC, PRIVATE, UNIVERSITY, TUTORING, ONLINE |
| licence_number | String | Ministry of Education licence |
| tax_number | String | Tax ID |
| governorate | String | |
| city | String | |
| district | String | |
| principal_name | String | |
| finance_contact | String | Phone number |
| status | Enum | PENDING, ACTIVE, SUSPENDED, CLOSED |
| tier | Enum | FREE, STARTER, PRO, ENTERPRISE |
| max_students | Integer | Per tier limit |
| created_at | Timestamp | |
| updated_at | Timestamp | |

### FeeTemplate
| Field | Type | Notes |
|---|---|---|
| id | UUID | PK |
| school_id | UUID | FK → School |
| name | String | e.g. "Grade 10 — Term 1 — 2025/2026" |
| academic_year | String | "2025/2026" |
| term | String | "Term 1", "Term 2", "Term 3", "Annual" |
| grade | String | e.g. "Grade 10", "Year 3" |
| due_date | Date | Full payment due |
| late_fee_percent | Decimal | e.g. 2.00 (2%) |
| late_fee_max_percent | Decimal | e.g. 10.00 (capped at 10%) |
| instalment_allowed | Boolean | |
| num_instalments | Integer | 1, 2, 3, 6, 12 |
| status | Enum | DRAFT, PUBLISHED, ARCHIVED |

### FeeLineItem
| Field | Type | Notes |
|---|---|---|
| id | UUID | PK |
| fee_template_id | UUID | FK → FeeTemplate |
| name | String | "Tuition", "Books", "Activities" |
| amount | Decimal | In SYP |
| is_mandatory | Boolean | |
| sort_order | Integer | |

### Student
| Field | Type | Notes |
|---|---|---|
| id | UUID | PK |
| school_id | UUID | FK → School |
| student_id_local | String | School's internal ID |
| full_name_ar | String | |
| full_name_en | String | |
| grade | String | |
| section | String | |
| date_of_birth | Date | |
| gender | Enum | |
| guardian_primary_id | UUID | FK → User |
| guardian_secondary_id | UUID | FK → User (nullable) |
| status | Enum | ACTIVE, GRADUATED, TRANSFERRED, WITHDRAWN |
| enrolment_date | Date | |
| created_at | Timestamp | |

### FeeInvoice
| Field | Type | Notes |
|---|---|---|
| id | UUID | PK |
| student_id | UUID | FK → Student |
| fee_template_id | UUID | FK → FeeTemplate |
| invoice_number | String | Human-readable: "INV-2026-00001" |
| total_amount | Decimal | Sum of line items after discounts |
| discount_amount | Decimal | |
| discount_type | Enum | SIBLING, EARLY_BIRD, SCHOLARSHIP |
| late_fee_amount | Decimal | Calculated if overdue |
| total_due | Decimal | total_amount - discount + late_fee |
| total_paid | Decimal | |
| balance | Decimal | |
| status | Enum | PENDING, PAID, PARTIAL, OVERDUE, CANCELLED |
| due_date | Date | |
| issued_date | Date | |
| settled_date | Date | Nullable |

### Payment
| Field | Type | Notes |
|---|---|---|
| id | UUID | PK |
| invoice_id | UUID | FK → FeeInvoice |
| parent_id | UUID | FK → User |
| amount | Decimal | In SYP |
| payment_method | Enum | BEZA_WALLET, CARD, BANK_TRANSFER, OFFLINE_CASH |
| payment_reference | String | Unique |
| idempotency_key | String | Prevents duplicates |
| status | Enum | PENDING, COMPLETED, FAILED, REFUNDED |
| fx_rate | Decimal | Nullable (for diaspora payments) |
| fx_from_currency | String | Nullable (e.g. "EUR") |
| settled_to_school | Boolean | Yes when settlement batch is done |
| created_at | Timestamp | |

### Receipt
| Field | Type | Notes |
|---|---|---|
| id | UUID | PK |
| payment_id | UUID | FK → Payment (1:1) |
| receipt_number | String | "RCT-2026-00001" |
| pdf_url | String | S3/storage path |
| qr_code | String | QR content |
| generated_at | Timestamp | |

### User (Parent / Guardian)
| Field | Type | Notes |
|---|---|---|
| id | UUID | PK — shared with Beza core auth |
| phone | String | Primary identifier in Syria |
| email | String | |
| full_name_ar | String | |
| preferred_language | String | "ar" or "en" |
| notification_preferences | JSON | WhatsApp, SMS, push, email flags |
| credit_score | Integer | 0-1000 |
| created_at | Timestamp | |
