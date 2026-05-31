# Cards Infrastructure

## Deployment Architecture
```
┌──────────────────────────────────────────────────────────────────────┐
│                        Kubernetes Cluster                            │
│                                                                      │
│  ┌────────────────────┐  ┌────────────────────┐  ┌────────────────┐ │
│  │  Laravel API       │  │  Card Processor    │  │  Queue Workers │ │
│  │  Replicas: 3       │  │  Replicas: 2       │  │  Replicas: 5   │ │
│  │  CPU: 2, RAM: 4GB  │  │  CPU: 4, RAM: 8GB  │  │  CPU: 1, RAM:2 │ │
│  └────────────────────┘  └────────────────────┘  └────────────────┘ │
│                                                                      │
│  ┌────────────────────┐  ┌────────────────────┐  ┌────────────────┐ │
│  │  Redis Cache       │  │  MySQL             │  │  RabbitMQ      │ │
│  │  Replicas: 2       │  │  Primary + 2 RO    │  │  Cluster 3     │ │
│  └────────────────────┘  └────────────────────┘  └────────────────┘ │
│                                                                      │
│  ┌────────────────────┐  ┌────────────────────┐                      │
│  │  HSM (Thales)      │  │  Fraud ML Service  │                      │
│  │  Dedicated HW      │  │  Replicas: 2       │                      │
│  └────────────────────┘  └────────────────────┘                      │
└──────────────────────────────────────────────────────────────────────┘
```

## External Systems Integration
```
┌─────────────────────────────────────────────────────────────────────────────┐
│                          Cards Infrastructure                                │
│                                                                              │
│  ┌─────────────────┐     ┌──────────────────┐     ┌──────────────────┐      │
│  │ Local Switch    │◄───►│  Card Processor  │◄───►│  BIN Sponsor     │      │
│  │ (Syrian Scheme) │ ISO │  (Auth, Clearing,│ ISO │  (Mastercard/    │      │
│  │ 8583/TCP        │     │   Settlement)    │8583 │   Visa)          │      │
│  └─────────────────┘     └────────┬─────────┘     └──────────────────┘      │
│                                   │                                         │
│  ┌─────────────────┐             │              ┌──────────────────┐      │
│  │ HSM (Thales)    │◄────────────┤─────────────►│  Token Service   │      │
│  │ PIN verification│  LAN        │      REST    │  Provider (TSP)  │      │
│  │ CVV generation  │             │              │  Apple Pay/G Pay │      │
│  │ Key management  │             │              └──────────────────┘      │
│  └─────────────────┘             │                                         │
│                                  │                                         │
│  ┌─────────────────┐             │              ┌──────────────────┐      │
│  │ Card Bureau     │◄────────────┘    API       │  Fraud Detection │      │
│  │ Personalization │  REST/    ──────────────►  │  ML Service      │      │
│  │ (Printing)      │  SFTP                  │  │  Real-time scoring│      │
│  └─────────────────┘                          │  └──────────────────┘      │
│                                                                              │
│  ┌─────────────────┐                          ┌──────────────────┐      │
│  │ CFE (Core F.E.) │◄────────────────────────┤  CMS (Card Mgmt) │      │
│  │ Ledger Posting  │  gRPC                    │  Card lifecycle  │      │
│  │ Hold / Release  │                          │  Admin panel     │      │
│  └─────────────────┘                          └──────────────────┘      │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Key Infrastructure Components

### Card Management System (CMS)
```
Purpose: Card lifecycle management, admin operations
Functionality:
  - Card issuance (batch single)
  - Status management (freeze/unfreeze/close)
  - PIN management (init, reset, unblock)
  - Card replacement workflow
  - BIN management (range allocation, exhaustion alerts)
  - Card program configuration
  - Reporting (issued, active, frozen, lost counts)
  - Manual transaction reversal
Integration: Laravel admin panel (Filament)
```

### Card Processor
```
Purpose: Transaction authorization, clearing, settlement
Functionality:
  - ISO 8583 message handling (0100 auth, 0110 response, 0420 clearing)
  - Real-time authorization engine (limits, fraud, balance)
  - BIN routing (local switch vs international sponsor)
  - Clearing file processing
  - Settlement calculation (interchange, fees, net positions)
  - Reconciliation reports
Deployment: Standalone Go/Java service for low-latency auth (< 100ms)
```

### HSM (Thales payShield / Utimaco)
```
Purpose: Cryptographic operations for card security
Functionality:
  - PIN verification (ISO 9564 format 0, 1, 3)
  - PIN block translation (between switch and card schemes)
  - CVV/CVC generation and verification
  - ARQC/ARPC verification (EMV chip offline auth)
  - Key generation and storage (TMK, TPK, PVK, CVK)
  - MAC generation for ISO 8583 messages
Deployment: Dedicated hardware appliance (FIPS 140-2 Level 3)
```

### Token Service Provider (TSP)
```
Purpose: Digital wallet tokenization for Apple Pay / Google Pay
Integration:
  - Apple Pay: Via Apple's TSP API (Visa MDES or Mastercard MDES)
  - Google Pay: Via Google's TSP API (same underlying MDES)
Flow:
  1. Beza sends card details to TSP
  2. TSP generates DPAN (device PAN) linked to FPAN (funding PAN)
  3. DPAN stored on device, FPAN never shared with merchant
  4. TSP handles token transaction routing
  5. Beza receives token transactions for authorization
```

### Card Personalization Bureau
```
Purpose: Physical card printing and embossing
Integration:
  - API/SFTP for batch card personalization files
  - File format: Card image, embossing data, chip data
  - Delivery: Personalized cards shipped to Beza distribution center
  - Chip personalization: EMV keys loaded via HSM
  - Physical security: Tamper-evident packaging, inventory tracking
```

### Scaling Strategy
```
Card Processor:
  - HPA: CPU > 60% OR txn latency > 200ms → scale to max 10
  - P99 latency target: < 100ms for auth (local), < 200ms for international
  - Concurrency: 200 tps per replica (estimate)

Card API:
  - HPA: CPU > 70% → scale to max 6
  - P99 latency: < 500ms for create/freeze, < 200ms for list/detail

Database:
  - card_transactions: Partitioned by month, auto-create partitions
  - Index maintenance: Weekly on high-write tables
  - Archival: Transactions > 12 months moved to archive table

Cache:
  - Card status (active/frozen): TTL 60s (invalidated on status change)
  - Card limits: TTL 120s (invalidated on limit update)
  - Card spending totals: TTL 30s (invalidated on new transaction)
  - Card list per user: TTL 60s
```
