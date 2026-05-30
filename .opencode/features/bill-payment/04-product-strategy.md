# Bill Payment Product Strategy

## Product Phases

### Phase 1 (Months 1-3) — Core Bill Payment
```
Supported Billers (P0):
  - PEED (Public Establishment for Electricity) — API direct
  - Damascus Water Authority — API direct
  - Syriatel (mobile & fixed line) — API direct
  - MTN Syria (mobile & fixed line) — API direct
  - Syria Telecom (landline & ADSL) — API direct

Capabilities:
  - Customer ID entry with biller-specific format validation
  - Real-time bill fetch (amount, due date, late fees, breakdown)
  - Bill payment from Beza wallet
  - Real-time biller confirmation
  - Digital receipt (in-app + PDF)
  - Payment history
```

### Phase 2 (Months 4-6) — Expanded Billers & Features
```
Additional Billers:
  - Government fees (Civil Affairs, Passport, Justice) — CSV batch
  - Aya Internet — API direct
  - Saman Internet — API direct
  - Damascus University — CSV batch
  - Private universities (Al-Sham, Arab International) — CSV batch

Capabilities:
  - Scheduled bill reminders (SMS + push)
  - Multi-bill cart (select & pay multiple bills at once)
  - Biller customer ID validation (format + check digit)
  - Late fee calculation display
  - Payment splitting between Beza users
```

### Phase 3 (Months 7-9) — Advanced Features
```
Additional Billers:
  - Aleppo Electricity Directorate — CSV batch
  - Homs Water Authority — CSV batch
  - Latakia Electricity — CSV batch
  - Ministry of Education (school fees) — CSV batch
  - Damascus Municipality fees — CSV batch

Capabilities:
  - Auto-pay (authorize automatic payment on due date)
  - Partial payment (supported billers only)
  - Recurring bills (monthly rent, standing phone charges)
  - CSV batch billing engine (upload → reconcile → notify users)
  - Bill payment gifting (pay a bill for another person)
```

### Phase 4 (Months 10-12) — Scale & Monetization
```
Additional Billers:
  - Insurance (Syrian Insurance Company) — API
  - Traffic fines — CSV batch
  - Professional unions (Engineers, Doctors, Lawyers) — CSV batch
  - Real Estate Registry fees — CSV batch
  - Chamber of Commerce fees — CSV batch

Capabilities:
  - Premium auto-pay subscription (2,000 SYP/month)
  - Bill spending insights & analytics
  - Export bill history to PDF/CSV
  - Biller dispute management
  - Bulk bill payment for businesses
```

## Feature Gating by KYC Level
| Feature | Level 0 (Basic) | Level 1 (ID) | Level 2 (Full KYC) |
|---------|----------------|--------------|---------------------|
| View supported billers | ✓ | ✓ | ✓ |
| Fetch bill by customer ID | ✗ | ✓ | ✓ |
| Pay bill (per txn max) | ✗ | 200,000 SYP | 2,000,000 SYP |
| Pay bill (daily max) | ✗ | 500,000 SYP | 5,000,000 SYP |
| Bill history | ✗ | 30 days | All |
| Scheduled reminders | ✗ | ✓ | ✓ |
| Auto-pay | ✗ | ✗ | ✓ |
| Multi-bill cart | ✗ | ✓ | ✓ |
| CSV batch billing | ✗ | ✗ | ✓ |
| Payment splitting | ✗ | ✗ | ✓ |

## Pricing Strategy
| Operation | Standard | Premium (2,000 SYP/mo) |
|-----------|----------|----------------------|
| Bill payment (wallet) | Free | Free |
| Auto-pay | 500 SYP/month per biller | Unlimited |
| SMS receipt | 50 SYP | Free |
| Late payment processing | 500 SYP | Free |
| Payment splitting | 0.5% split amount | Free |
| Biller customer ID lookup | Free | Free |
