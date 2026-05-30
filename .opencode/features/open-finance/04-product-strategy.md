# Open Finance Product Strategy

## Product Phases
```
Phase 1 (Months 1-2) — Foundation
  - Developer portal (registration, docs, API console)
  - API key management (create, rotate, revoke)
  - Sandbox environment
  - OAuth 2.0 (client_credentials, authorization_code)
  - Basic rate limiting

Phase 2 (Months 3-4) — Core APIs
  - Payment Initiation API (single + bulk)
  - Account Information API (balance + transactions)
  - Wallet API (create, fund, check balance)
  - Webhooks (transaction status, balance changes)
  - API versioning strategy (v1)

Phase 3 (Months 5-6) — Advanced APIs
  - Transaction API (search, export, reconciliation)
  - Agent Locator API
  - FX Rate API (live + historical)
  - Bulk payment disbursement
  - Batch processing endpoints

Phase 4 (Months 7-12) — Scale & Monetize
  - Subscription plans (tiered pricing)
  - Usage analytics dashboard
  - SLA monitoring for enterprise
  - Premium support tier
  - API marketplace / partner directory
```

## Developer Tiers
| Tier | Monthly Fee | API Calls/Day | Rate Limit | Support |
|------|------------|---------------|------------|---------|
| Free | $0 | 1,000 | 10 req/min | Community |
| Startup | $50 | 10,000 | 100 req/min | Email |
| Business | $200 | 100,000 | 500 req/min | Email + Chat |
| Enterprise | $1,000 | Unlimited | Custom | Dedicated |

## Sandbox Strategy
```
Sandbox Environment:
  - Simulated CFE engine (in-memory ledger)
  - Pre-funded test wallets (1M SYP, $10K USD)
  - Test phone numbers: +963900000001..100
  - Test API keys auto-generated on registration
  - Daily reset option for test data
  - Webhook inspector (see events in real-time)

Sandbox → Production Transition:
  1. Developer completes integration in sandbox
  2. Passes automated test suite (10 test cases)
  3. Pays onboarding fee ($500)
  4. KYC verification of business
  5. Production API keys issued
  6. Rate limits increase gradually over 30 days
```

## API Versioning Policy
```
Strategy: URL-based versioning (/api/v1/, /api/v2/)
Lifecycle:
  - Current version: Active development + support
  - Previous version: Bug fixes only, 12-month support window
  - Deprecated: 6-month sunset notice
  - Sunset: 404 for all endpoints

Version header: Accept: application/vnd.beza.v1+json
Breaking changes require new version
Non-breaking additions allowed within version
```
