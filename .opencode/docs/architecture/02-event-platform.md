# Event Platform Architecture

## Philosophy
Every meaningful state change in Beza emits an event. Events are the source of truth for all downstream consumers: notifications, analytics, compliance, fraud detection, AI models, and audit.

## Event Schema (CloudEvents 1.0)
```json
{
  "specversion": "1.0",
  "id": "evt_abc123",
  "source": "/beza/transfer/1.0",
  "type": "com.beza.transfer.completed",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-06-01T10:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "transfer_id": "txn_abc123",
    "sender_id": 42,
    "recipient_id": 87,
    "amount": 10000,
    "currency": "SYP",
    "fee": 150,
    "fx_rate_id": 15,
    "hold_id": "hold_456"
  }
}
```

## Event Catalog (Core)
| Event | Producer | Consumers | Priority |
|-------|----------|-----------|----------|
| `com.beza.transfer.completed` | Transfer Service | Notification, Analytics, Ledger, Compliance, Fraud | High |
| `com.beza.wallet.credited` | CFE | Notification, Analytics, Savings (round-up) | High |
| `com.beza.wallet.debited` | CFE | Notification, Analytics | High |
| `com.beza.fx.locked` | FX Engine | Ledger, Audit, Compliance | High |
| `com.beza.fx.rate.updated` | Admin | FX Engine (cache flush) | Medium |
| `com.beza.agent.cash-in` | Agent Service | Analytics, Commission, Settlement | High |
| `com.beza.loan.disbursed` | Lending Service | Notification, Analytics, Collection | High |
| `com.beza.loan.repaid` | Lending Service | Notification, Credit Score, Collection | High |
| `com.beza.merchant.settled` | Settlement Engine | Merchant Service, Analytics, Ledger | High |
| `com.beza.user.registered` | Auth Service | Analytics, Onboarding | Medium |
| `com.beza.user.kyc-approved` | Compliance | Auth Service, Limits, Notification | High |
| `com.beza.fraud.detected` | Fraud Engine | Compliance, Auth Service, Admin | Critical |
| `com.beza.savings.goal-completed` | Savings Service | Notification, Loyalty | Low |
| `com.beza.bill.paid` | Bill Payment | Notification, Analytics, Receipt | High |
| `com.beza.payroll.processed` | Payroll Service | Notification, Settlement, Ledger | High |

## Event Flow Architecture
```
Producer → Laravel Event → Queue Job → RabbitMQ Exchange
                                        ├── Queue: Notifications (Push, SMS, Email)
                                        ├── Queue: Analytics (ClickHouse insert)
                                        ├── Queue: Compliance (AML screening)
                                        ├── Queue: Fraud Detection (ML scoring)
                                        ├── Queue: Audit Log (Elasticsearch)
                                        └── DLQ (after 3 retries → manual review)
```

## Retry & Dead Letter Policy
| Retry | Delay | Action |
|-------|-------|--------|
| 1 | 5s | Immediate retry |
| 2 | 30s | Exponential backoff |
| 3 | 5m | Exponential backoff |
| DLQ | N/A | Notify ops, manual replay |

## Idempotency
- Each event has a unique `id` (ULID)
- Consumers store processed event IDs (TTL: 7 days)
- Duplicate events with same ID → silently ignored
- Replay: events can be re-published with same ID (consumers will skip)

## Event Versioning
- Schema stored in Schema Registry (Avro)
- Backward compatible: new fields must be optional
- Breaking change → new event type version: `com.beza.transfer.completed.v2`
- Migration: dual-emit for 2 release cycles
