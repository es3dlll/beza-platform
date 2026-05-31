# 15 — Backend Architecture

## 15.1 Service Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    API Gateway                           │
│           (Rate limit · Auth · Routing)                  │
└────────┬──────────────┬──────────────┬─────────────────┘
         │              │              │
         ▼              ▼              ▼
┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│ Education    │ │ Payment      │ │ Notification │
│ Service      │ │ Service      │ │ Service      │
│              │ │              │ │              │
│ • Schools    │ │ • Processing │ │ • WhatsApp   │
│ • Students   │ │ • FX         │ │ • SMS        │
│ • Invoices   │ │ • Settlement │ │ • Push/Email │
│ • Templates  │ │ • Financing  │ │ • Templates  │
└──────┬───────┘ └──────┬───────┘ └──────┬───────┘
       │                │                │
       └────────────────┼────────────────┘
                        │
                        ▼
              ┌──────────────────┐
              │   Event Bus      │
              │  (Kafka/RabbitMQ)│
              └──────────────────┘
                        │
         ┌──────────────┼──────────────┐
         ▼              ▼              ▼
┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│ Reporting    │ │ Credit       │ │ Audit        │
│ Service      │ │ Scoring Svc  │ │ Service      │
└──────────────┘ └──────────────┘ └──────────────┘
```

## 15.2 Key Service Responsibilities

### Education Service
- CRUD for schools, students, grades, sections
- Fee template management
- Invoice generation (batch)
- Student roster import (CSV)
- Dashboard aggregation queries

### Payment Service
- Payment processing (wallet deduction)
- FX conversion via FX Engine (diaspora)
- Settlement to school bank accounts (daily batch)
- Refund processing
- Instalment/financing plan management

### Notification Service
- WhatsApp Business API integration
- SMS gateway (local Syrian providers)
- Push notifications (Firebase/APNs)
- Email service
- Template management (Arabic/English)
- Delivery tracking and retry logic

### Credit Scoring Service
- Parent credit score calculation
- Financing eligibility check
- Default risk modelling

### Audit Service
- Immutable log of all financial actions
- Tamper-evident using hash chains
- regulatory compliance queries

## 15.3 Key Business Logic

### Late Fee Calculation
```
Every day after due_date:
  IF days_overdue > 7 (grace period):
    late_fee = MIN(
      balance * (late_fee_percent / 100) * CEIL(days_overdue / 30),
      total_amount * (late_fee_max_percent / 100)
    )
```
Recalculated on every dashboard view and before payment confirmation.

### Settlement Flow (Daily)
```
1. 23:00 — System aggregates all completed payments not yet settled
2. Groups by school bank account
3. Calculates: settlement = Σ payments - Σ transaction_fees - Σ refunds
4. Creates settlement batch record
5. Sends transfer instruction to Payment Core
6. On completion → updates settlement status → sends webhook to school
```

### Auto-Pay Scheduler
```
Cron: Runs every hour
1. Check: auto_pay_schedules WHERE scheduled_date <= NOW() AND status = PENDING
2. For each: attempt payment
3. Success → mark complete, notify
4. Fail (insufficient funds) → retry in 24h (max 3 retries)
5. After 3 failures → notify parent + school
```

## 15.4 Technology Stack (Suggested)

| Component | Technology | Justification |
|---|---|---|
| API Layer | Node.js (Fastify) or Kotlin (Ktor) | High throughput, low latency |
| Database | PostgreSQL 15+ | Relational data, complex queries |
| Cache | Redis | Session, dashboard aggregation |
| Event Bus | Kafka | Durable event streaming |
| Storage | MinIO / S3 | Receipt PDFs, CSVs |
| Queue | Bull / RabbitMQ | Notification jobs |
| Search | Elasticsearch | Student/payment search |
