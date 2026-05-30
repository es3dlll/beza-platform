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
│                           API GATEWAY (Nginx)                                 │
│  ┌────────┐  ┌────────┐  ┌────────┐  ┌────────┐  ┌────────┐                │
│  │  Auth  │  │  Rate  │  │  Route │  │  Cache │  │  TLS   │                │
│  │  Proxy │  │  Limit │  │  Match │  │  Layer │  │  Term  │                │
│  └────────┘  └────────┘  └────────┘  └────────┘  └────────┘                │
│  V1: Nginx only (no Kong). Kong added post-V1 when services extracted.      │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│     V1 ONLY: Moduler Monolith (no microservices, no service mesh)           │
│                           APPLICATION LAYER                                 │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                  Laravel Moduler Monolith (V1 Core)                  │   │
│  │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ │   │
│  │  │  Auth    │ │  Wallet  │ │   CFE    │ │  Ledger  │ │   FX     │ │   │
│  │  │  Module  │ │  Module  │ │  Module  │ │  Module  │ │  Module  │ │   │
│  │  └──────────┘ └──────────┘ └──────────┘ └──────────┘ └──────────┘ │   │
│  │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ │   │
│  │  │Settlement│ │Remittance│ │  Bills   │ │ Merchant │ │  Agent   │ │   │
│  │  │  Module  │ │  Module  │ │  Module  │ │  Module  │ │  Module  │ │   │
│  │  └──────────┘ └──────────┘ └──────────┘ └──────────┘ └──────────┘ │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│     V2+ (Future): Extracted Services for Notification, FX, Settlement       │
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

## V1 Architecture Rules (CRITICAL — DO NOT DEVIATE)
```
1. Moduler Monolith ONLY — No microservices, no separate service deployments
2. Single Laravel codebase — app/Modules/*, all modules in one process
3. Single database — MySQL with schemas per domain, no per-service databases
4. CFE owns ALL financial state — No module writes balances directly
5. Ledger = Single Source of Truth — Every financial event passes through CFE
6. Kong for API Gateway — Rate limiting, auth proxy, routing
7. RabbitMQ for events — Single cluster, no Kafka in V1
8. Redis for cache + sessions + rate limits — No Redis Cluster in V1
9. No service mesh — Not needed until microservice extraction in V2+

## V2+ Extraction Candidates (Future — NOT for V1)
- Notification Service (independent scaling)
- FX Engine (high compute, low latency)
- Settlement Service (batch processing isolation)
- Compliance Service (regulatory isolation)
```

## Core Architecture Decisions (V1)
| Decision | Choice | Rationale |
|----------|--------|-----------|
| Architecture | Moduler Monolith | Only approach for V1 — microservices kill speed |
| Backend Framework | Laravel 13+ | Rapid delivery, PHP ecosystem, Syrian developer availability |
| Mobile | Flutter 3+ | Single codebase, offline-first, USSD fallback |
| Admin Web | React 18+ | Universal, rich data tables |
| API Gateway | Kong | Rate limiting, auth proxy, logging |
| Database | MySQL 8.0 (single) | ACID compliance, proven reliability |
| Cache | Redis 7 (single) | Sessions, hot data, rate limits |
| Event Bus | RabbitMQ | Simple, reliable, Dead Letter support |
| Search | Elasticsearch | Transaction search, audit log |
| Container | Docker + Docker Compose | Until K8s expertise is available |

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
