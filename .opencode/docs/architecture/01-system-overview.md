# Beza System Architecture V4

## High-Level Architecture
```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              PRESENTATION LAYER                             │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────┐ │
│  │ Flutter  │  │  React   │  │  Admin   │  │   USSD   │  │  Agent POS   │ │
│  │   App    │  │   Web    │  │  Panel   │  │  *123#   │  │  Terminal    │ │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘  └────┬─────┘  └──────┬───────┘ │
│       └──────────────┴─────────────┴─────────────┴──────────────┘          │
├─────────────────────────────────────────────────────────────────────────────┤
│                           API GATEWAY (Kong)                                │
│  ┌────────┐  ┌────────┐  ┌────────┐  ┌────────┐  ┌────────┐  ┌──────────┐│
│  │  Auth  │  │  Rate  │  │  Route │  │  Cache │  │  Log   │  │  mTLS    ││
│  │  Proxy │  │  Limit │  │  Match │  │  Layer │  │  Audit │  │  Term    ││
│  └────────┘  └────────┘  └────────┘  └────────┘  └────────┘  └──────────┘│
├─────────────────────────────────────────────────────────────────────────────┤
│                           SERVICE MESH (Istio)                               │
├─────────────────────────────────────────────────────────────────────────────┤
│                           APPLICATION LAYER                                 │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                     Laravel Modular Monolith                        │   │
│  │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ │   │
│  │  │  Auth    │ │  Wallet  │ │   CFE    │ │  Ledger  │ │   FX     │ │   │
│  │  │  Module  │ │  Module  │ │  Module  │ │  Module  │ │  Module  │ │   │
│  │  └──────────┘ └──────────┘ └──────────┘ └──────────┘ └──────────┘ │   │
│  │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ │   │
│  │  │Settlement│ │Remittance│ │  Bills   │ │  Payroll │ │ Merchant │ │   │
│  │  │  Module  │ │  Module  │ │  Module  │ │  Module  │ │  Module  │ │   │
│  │  └──────────┘ └──────────┘ └──────────┘ └──────────┘ └──────────┘ │   │
│  │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ │   │
│  │  │  Agent   │ │  Savings │ │Financing │ │   Cards  │ │  Loyalty │ │   │
│  │  │  Module  │ │  Module  │ │  Module  │ │  Module  │ │  Module  │ │   │
│  │  └──────────┘ └──────────┘ └──────────┘ └──────────┘ └──────────┘ │   │
│  │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ │   │
│  │  │Compliance│ │  Fraud   │ │  Audit   │ │   AML    │ │ Marketpl.│ │   │
│  │  │  Module  │ │  Module  │ │  Module  │ │  Module  │ │  Module  │ │   │
│  │  └──────────┘ └──────────┘ └──────────┘ └──────────┘ └──────────┘ │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
├─────────────────────────────────────────────────────────────────────────────┤
│                           EVENT PLATFORM                                    │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  RabbitMQ Cluster  │  Kafka (Analytics)  │  Event Store (Audit)    │   │
│  │  Exchange Types: direct, topic, fanout, delayed                     │   │
│  │  Schemas: CloudEvents 1.0 + Avro serialization                     │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
├─────────────────────────────────────────────────────────────────────────────┤
│                              DATA PLATFORM                                  │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  MySQL 8.0  │  Redis 7  │  ClickHouse  │  S3  │  Elasticsearch    │   │
│  │  (PerSvc)   │  (Cache)  │  (Analytics) │ (Doc)│  (Search+Logs)    │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
├─────────────────────────────────────────────────────────────────────────────┤
│                           AI PLATFORM                                       │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  ML Engine (ONNX)  │  Fraud Detection  │  Credit Scoring          │   │
│  │  NLP (Arabic)      │  Document OCR     │  Behavior Analysis      │   │
│  │  Chatbot (Rasa)    │  Risk Engine      │  Anomaly Detection      │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Core Architecture Decisions
| Decision | Choice | Rationale |
|----------|--------|-----------|
| Monolith vs Microservices | Modular Monolith | Speed of delivery initially, extraction path to microservices |
| Backend Framework | Laravel 11+ | PHP ecosystem fit for Syria, rapid development |
| Mobile | Flutter 3+ | Single codebase iOS + Android, USSD fallback |
| Web | React 18+ | Universal, large ecosystem |
| API Gateway | Kong | Industry standard, plugin ecosystem |
| Service Mesh | Istio | mTLS, observability, traffic management |
| Event Bus | RabbitMQ + Kafka | RabbitMQ for commands/events, Kafka for analytics streams |
| Database | MySQL 8.0 | Percona, proven reliability |
| Cache | Redis 7 | Session, rate limits, hot data |
| Analytics | ClickHouse | Real-time OLAP for reporting |
| Monitoring | Prometheus + Grafana + ELK | Industry standard |
| ML Serving | ONNX Runtime | Cross-platform model inference |
| Container | Docker + Kubernetes | Portability, orchestration |

## Financial Transaction Flow (Standard)
```
1. User Action → Mobile App / USSD / Web
2. API Gateway → JWT Verify → Rate Check → Route Match
3. Module Controller → Validate Input → Authorize
4. Domain Service → Business Rules → Compute
5. CFE → Hold → Post → Ledger → Fee → FX
6. Event Bus → Emit TransactionEvent
7. Listeners → Notification → Analytics → Compliance
8. Response → User Confirmation → Receipt
```

## Cross-Cutting Concerns
| Concern | Implementation |
|---------|---------------|
| Multi-tenancy | tenant_id on every table, TenantResolver middleware |
| Idempotency | Idempotency-key header, stored 24h |
| Audit | Immutable event log for all financial operations |
| Rate Limiting | 3 tiers: user (60/min), admin (300/min), internal (1000/min) |
| Encryption | AES-256-GCM at rest, TLS 1.3 in transit |
| Backup | Hourly WAL archiving, daily full backup, cross-region |
