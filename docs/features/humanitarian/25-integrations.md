# Integrations

## Integration Map

```
┌─────────────────────────────────────────────────────────────────────┐
│                         Beza Humanitarian                            │
│                                                                     │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐  ┌────────────┐   │
│  │  UNHCR     │  │  Sanctions │  │  Wallet    │  │  SMS       │   │
│  │  API       │  │  Providers │  │  Service   │  │  Gateway   │   │
│  └─────┬──────┘  └─────┬──────┘  └─────┬──────┘  └─────┬──────┘   │
│        │               │               │               │           │
│        ▼               ▼               ▼               ▼           │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │                    Beza Humanitarian Core                    │   │
│  │     (AidProgram, Distribution, Voucher, Monitoring)         │   │
│  └─────────────────────────────────────────────────────────────┘   │
│        │               │               │               │           │
│        ▼               ▼               ▼               ▼           │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐  ┌────────────┐   │
│  │  NGO MIS   │  │  Donor     │  │  Merchant  │  │  Mapping / │   │
│  │  (API)     │  │  Portals   │  │  Network   │  │  IM        │   │
│  └────────────┘  └────────────┘  └────────────┘  └────────────┘   │
└─────────────────────────────────────────────────────────────────────┘
```

## 1. UNHCR Registration API

| Attribute | Value |
|-----------|-------|
| **Purpose** | Validate UNHCR registration numbers for beneficiary verification |
| **Integration type** | REST API (proxy via Beza backend — not directly from agent app) |
| **Endpoint** | `https://api.unhcr.org/v1/registration/{reg_id}` |
| **Authentication** | OAuth 2.0 client credentials (UNHCR-provided) |
| **Rate limit** | 100 req/min |
| **Fallback** | Manual verification if UNHCR API is unavailable |
| **Data received** | Registration status, name (partial), family composition |
| **Data sent** | UNHCR registration ID only |

## 2. Sanctions List Data Feeds

| List | Feed Type | Update | Integration |
|------|-----------|--------|-------------|
| **UN Consolidated List** | XML download (`consolidated.xml`) | Real-time | Downloaded every 6 hours, parsed, indexed into local PostgreSQL with trigram indexes |
| **EU CFSP List** | XML download | Daily | Downloaded daily at 06:00 UTC |
| **OFAC SDN List** | XML download | Multiple times daily | Download triggered via SNS webhook |
| **UK Sanctions List** | XML download | Daily | Downloaded daily at 06:00 UTC |

**Screening Engine:** Local in-process — sanctions lists are downloaded and indexed in Beza's infrastructure to avoid sending beneficiary names to third parties (privacy requirement).

## 3. NGO MIS Integration

| NGO | System | Integration Type | Data Exchanged |
|-----|--------|-----------------|----------------|
| Syrian Arab Red Crescent | RedRose | Webhook | Beneficiary list, distribution confirmations |
| WFP | SCOPE / mSupply | REST API | Beneficiary registration, SCOPE ID linking |
| UNICEF | ActivityInfo | REST API | Program data, spending indicators |
| UNHCR | UNHCR Registration | REST API | Beneficiary verification |
| Local NGOs | CSV/Excel | Manual upload | Beneficiary lists (via CSV) |

## 4. Wallet Service (Internal Beza)

| Attribute | Value |
|-----------|-------|
| **Service** | Beza Wallet Core (`beza-wallet-api`) |
| **Communication** | Internal gRPC (mutual TLS) |
| **Endpoints used** | `WalletService.BatchCredit()`, `WalletService.Credit()`, `WalletService.Debit()`, `WalletService.GetBalance()` |
| **Payload** | `{ beneficiary_id, amount, currency, reference_type, reference_id, idempotency_key }` |
| **Retry** | Exponential backoff, max 3 retries |

## 5. SMS / USSD Gateway

| Provider | Region | Service |
|----------|--------|---------|
| Twilio | International | SMS delivery (fallback) |
| Syrian Telecom | Syria (regime areas) | Local SMS aggregator |
| MTN Syria | Syria (various) | Local SMS aggregator |
| Syriatel | Syria (various) | Local SMS aggregator |
| Local aggregator (TBD) | Syria (opposition areas) | USSD gateway for feature phones |

**Delivery strategy:**
1. Try local Syrian SMS provider (lower cost, higher delivery rate)
2. Fallback to aggregator API
3. Final fallback: Twilio (international route)
4. Each SMS delivery attempt logged with delivery status tracking

## 6. Biometric Service

| Attribute | Value |
|-----------|-------|
| **Provider** | Simprints / IDEMIA (for fingerprint) |
| **Integration** | SDK integrated into agent Android app |
| **Face recognition** | On-device matching (Edge ML) using MobileFaceNet |
| **Fingerprint** | On-devise matching against enrolled template |
| **Enrolment** | First-time capture at agent point → template stored encrypted |
| **Verification** | Live capture → compare with stored template → match score |
| **Offline** | All matching happens on-device; result synced to server |
| **Fallback** | If biometric fails 3 times → manual verification (UNHCR ID + agent photo) |

## 7. Humanitarian Cluster Coordination

| Cluster | Data Shared |
|---------|-------------|
| **Cash Working Group (CWG) — Syria** | Aggregate MPC amounts, beneficiary reach (no PII), market impact |
| **FSAC (Food Security)** | Food voucher program data |
| **Health Cluster** | Health cash program data |
| **Education Cluster** | Education cash program data |
| **Protection Cluster** | Protection-sensitive distribution data |
| **OCHA FTS (Financial Tracking Service)** | Total funds disbursed for Syria response |

## 8. OpenStreetMap / Location Data

| Use | Tool |
|-----|------|
| Governorate/district validation | OSM administrative boundaries |
| Agent verification GPS logging | MapLibre GL |
| Market/merchant location | OSM points of interest |
| Camp/settlement mapping | UNOSAT camp boundary data (displacement camps in Idlib) |

**Note:** Google Maps not used due to sanctions restrictions on Syria mapping data. All mapping via OpenStreetMap + UNOSAT.

## 9. Monitoring & Evaluation Platforms

| Platform | Integration | Data Sent |
|----------|-------------|-----------|
| **ActivityInfo** | REST API push | Program indicators, beneficiary reach, spending categories |
| **RedRose** | REST API pull | Beneficiary registration sync |
| **Kobo Toolbox** | REST API | Survey/outcome data import |
| **PowerBI** | Direct query (read replica) | NGO custom dashboards |
