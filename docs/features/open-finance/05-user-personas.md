# Open Finance User Personas

## Persona 1: Rami — Fintech Founder
```
Age: 32
Occupation: Founder of PayFast, a Syrian payment gateway startup
Technical: CTO-level, full-stack developer
Team: 5 engineers
Pain points:
  - Building payment infrastructure from scratch
  - Struggles with bank integrations (slow, undocumented)
  - Wastes time on SFTP-based reconciliation
  - Wants to focus on product, not plumbing
Needs:
  - Well-documented REST APIs
  - Sandbox for rapid testing
  - Webhooks for real-time payment status
  - Clear pricing they can build into their model
  - Fast onboarding (days, not months)
```

## Persona 2: Lina — E-commerce Platform CTO
```
Age: 38
Occupation: CTO of ShopSyria, a WooCommerce-based marketplace
Technical: Technical, manages team of 3
Pain points:
  - Manual reconciliation of payments vs orders
  - Customers pay cash on delivery (COD) — high failure
  - Wants to offer "Pay with Beza" at checkout
  - Needs automated settlement to merchants
Needs:
  - Payment initiation API for checkout integration
  - Webhooks for order status updates
  - Bulk payout API for merchant settlements
  - Transaction reconciliation reports
  - Arabic plugin for WooCommerce
```

## Persona 3: Sara — NGO Program Manager
```
Age: 45
Occupation: Program Manager at UNHCR Syria
Technical: Low, works with field officers
Pain points:
  - Disburses cash assistance through physical distribution (slow, unsafe)
  - Cannot track who received what in real-time
  - Reconciliation takes weeks per distribution
  - Beneficiaries lack bank accounts
Needs:
  - Bulk disbursement API (upload CSV → send to wallets)
  - Beneficiary wallet creation API
  - Real-time delivery confirmation
  - Exportable transaction reports for donors
  - Secure API with audit trail
```

## Persona 4: Tarek — Developer at Accounting Startup
```
Age: 29
Occupation: Backend developer at Khasem, a Syrian accounting SaaS
Technical: Senior engineer
Pain points:
  - Clients manually enter bank transactions
  - No way to auto-import payment data
  - Bank download formats are inconsistent
  - Wants to offer "Auto-reconciliation" feature
Needs:
  - Account Information API (read-only)
  - Transaction export (CSV, JSON, PDF)
  - Date-range filtered queries
  - Webhook for new transactions
  - Compatible with local accounting standards
```
