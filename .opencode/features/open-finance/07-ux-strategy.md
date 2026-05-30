# Open Finance UX Strategy

## Design Principles
1. **Developer-first** — Every decision optimized for developer experience
2. **Consistency** — Uniform patterns across all APIs (auth, errors, pagination)
3. **Transparency** — Clear error messages, predictable pricing, request logging
4. **Security by default** — OAuth 2.0, API key scopes, payload signing
5. **Failure resilience** — Idempotency, retry strategies, clear error codes
6. **Self-service** — Developers should never need to talk to sales to start
7. **Arabic + English** — Full bilingual documentation and error messages

## Information Architecture (Developer Portal)
```
developers.beza.com
├── Home — Overview, quick start, SDK links
├── Docs
│   ├── Getting Started
│   │   ├── Quick Start (5 min)
│   │   ├── Authentication
│   │   └── Sandbox Guide
│   ├── APIs
│   │   ├── Payment Initiation API
│   │   ├── Account Information API
│   │   ├── Wallet API
│   │   ├── Transaction API
│   │   ├── Agent Locator API
│   │   └── FX Rate API
│   ├── Webhooks
│   │   ├── Overview
│   │   ├── Event Types
│   │   └── Security & Retry
│   ├── Guides
│   │   ├── WooCommerce Plugin
│   │   ├── NGO Disbursement Guide
│   │   └── Reconciliation Guide
│   └── API Reference (OpenAPI 3.0)
├── Console
│   ├── Dashboard (usage, errors, latency)
│   ├── API Keys (create, rotate, revoke)
│   ├── Webhooks (configure, logs, retry)
│   ├── Sandbox (reset, inspector)
│   └── Billing (plan, usage, invoices)
├── API Playground — Interactive API tester
└── Support — Docs, community, tickets
```

## Console Design Goals
| Section | Goal |
|---------|------|
| Dashboard | Developer sees: call volume, error rate, avg latency, last 10 requests |
| API Keys | Easily create scoped keys with clear labels and expiry |
| Webhooks | Visual endpoint configuration, real-time event log, manual retry |
| Sandbox | One-click reset, webhook inspector, test account management |
| Billing | Transparent usage metering, invoice download, plan upgrade |

## Error Message Philosophy
```
Every error response includes:
  - code: Machine-readable (e.g., "INSUFFICIENT_BALANCE")
  - message: Human-readable in Arabic + English
  - details: Field-level validation errors
  - request_id: For support debugging
  - documentation_url: Link to relevant docs

Example:
{
  "error": {
    "code": "INVALID_AMOUNT",
    "message": "المبلغ يجب أن يكون بين 1,000 و 10,000,000 ل.س",
    "message_en": "Amount must be between 1,000 and 10,000,000 SYP",
    "details": { "amount": ["minimum: 1000", "maximum: 10000000"] },
    "request_id": "req_abc123",
    "documentation_url": "https://developers.beza.com/docs/errors#INVALID_AMOUNT"
  }
}
```

## SDK & Tooling Strategy
- Officially maintained SDKs: JavaScript/TypeScript, Python, PHP, Dart
- Community SDKs encouraged with template
- Postman collection for quick exploration
- OpenAPI 3.0 spec downloadable from portal
- CLI tool for debugging: `beza-cli payments create --amount 25000`
