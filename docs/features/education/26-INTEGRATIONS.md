# 26 — Integrations

## 26.1 External Integrations

| System | Integration Type | Purpose | Priority |
|---|---|---|---|
| **Syriateq School Manager** | REST API | Sync student roster, fee templates | P1 |
| **Edusys (Biladi Tech)** | REST API | Sync student data, attendance | P1 |
| **Sakhr School System** | REST API + DB connector | Full ERP sync | P2 |
| **WhatsApp Business API** | REST API + Webhook | Send payment reminders, receipts | P0 |
| **Syrian SMS Gateways** | SMPP / HTTP | SMS reminders for non-WhatsApp parents | P0 |
| **FCM / APNs** | Push SDK | Push notifications to app | P0 |
| **Firefly / SADAD** | REST API | Instant bank transfer in Syria | P1 |
| **BoS (Bank of Syria)** | File-based (SFTP) | School settlement to bank accounts | P1 |
| **FX Engine (Beza internal)** | gRPC | Diaspora FX conversion | P0 |
| **Payment Core (Beza internal)** | gRPC | Wallet debit, refund, settlement | P0 |
| **Remittance Hub (Beza internal)** | REST API | Diaspora parent → education payment | P1 |
| **Beza Super App notification bus** | Kafka | Cross-feature notifications | P2 |

## 26.2 WhatsApp Business Integration

### Message Templates (Pre-Approved by Meta)

| Template Name | Category | Variables |
|---|---|---|
| `fee_reminder` | Transactional | {{parent_name}}, {{student_name}}, {{amount}}, {{due_date}}, {{late_fee}}, {{school_name}} |
| `payment_confirmation` | Transactional | {{parent_name}}, {{amount}}, {{student_name}}, {{receipt_no}}, {{school_name}} |
| `payment_failed` | Transactional | {{parent_name}}, {{student_name}}, {{amount}}, {{reason}} |
| `auto_pay_scheduled` | Transactional | {{parent_name}}, {{amount}}, {{date}}, {{school_name}} |
| `fee_change_notice` | Transactional | {{parent_name}}, {{school_name}}, {{old_amount}}, {{new_amount}}, {{effective_date}} |

### Flow
1. School clicks "Send Reminder" → Beza server calls WhatsApp Business API
2. WhatsApp delivers message → user receives on WhatsApp
3. Delivery receipt → Beza captures status (sent/delivered/read/failed)
4. Failed → fallback to SMS

## 26.3 SMS Gateway Integration

| Gateway | Protocol | Coverage | Cost/SMS |
|---|---|---|---|
| **MTN Syria (Syriatel)** | SMPP | National | ~15 SYP |
| **Syriatel Telecom** | HTTP API | National | ~12 SYP |
| **Yaman (private)** | HTTP API | Damascus/Aleppo | ~18 SYP |

### Fallback Logic
1. Attempt WhatsApp delivery
2. If WhatsApp fails (number not registered, 24h window closed) → send SMS
3. If SMS fails → mark as undeliverable in dashboard
4. School finance manager sees delivery report with failed numbers

## 26.4 SIS/ERP Integration Pattern

### For REST API-based SIS (Syriateq, Edusys)

```
Beza                           SIS
 │                              │
 │ POST /webhooks/student.new   │
 │ ← New student notification ─│
 │                              │
 │ GET /students/{id}           │
 │ ← Student details ──────────│
 │                              │
 │ POST /webhooks/payment.confirm
 │ → Payment receipt ──────────│
 │                              │
 │ POST /webhooks/fee.update    │
 │ ← Fee structure changed ────│
```

### For File-based SIS (legacy schools)
- Daily SFTP pull: school exports CSV of enrolled students
- Beza processes: match existing, create new, flag discrepancies
- Payment confirmation written to CSV and placed on SFTP for school import

## 26.5 Bank Integration (Settlement)

### Flow
1. Daily batch: Beza aggregates all payments per school
2. Calculates net settlement (payments - fees)
3. Sends payment instruction to bank (BoS SADAD or file-based)
4. Bank transfers to school's registered account
5. Bank sends confirmation → Beza marks settlement completed
6. School dashboard updates → school sees "Settled: 12,450,000 SYP on 16 May"

## 26.6 Internal Beza Integration Points

| Beza Feature | Integration | Data Flow |
|---|---|---|
| **Wallet** | Payment Core gRPC | Debit parent wallet, check balance |
| **Remittance** | REST API | Diaspora sends money → marked as Education → routed to Education Service |
| **FX Engine** | gRPC | Get live rate for EUR/USD/SAR → SYP conversion |
| **Super App** | Event Bus | Education notifications appear in Beza global notification centre |
| **Payroll** | Future | Schools pay staff salaries via Beza Payroll (cross-sell) |
| **Credit Scoring** | REST API | Parent credit history for financing eligibility |
