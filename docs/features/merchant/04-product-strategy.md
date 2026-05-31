# Merchant Acquiring Product Strategy

## Product Phases

```
Phase 1 (Months 1-2) — QR MVP
  - Merchant registration (app + web)
  - Generate static QR code (download PNG)
  - Customer scan → enter amount → confirm → pay
  - Merchant receives notification
  - Transaction history (merchant app)
  - Daily settlement to merchant wallet

Phase 2 (Months 3-4) — Payment Links & QR Evolution
  - Dynamic QR codes (pre-set amount)
  - Payment link generation (WhatsApp share)
  - QR with logo/branding
  - Multiple QR codes per merchant (different counters/items)
  - Split bill QR for restaurants

Phase 3 (Months 5-7) — POS Terminal
  - Android POS app (sunmi, PAX, generic Android)
  - Terminal pairing (QR scan + OTP)
  - Receipt printing (Bluetooth/USB thermal printer)
  - Transaction sync (offline-capable)
  - Employee PIN management
  - Cash register mode

Phase 4 (Months 8-10) — Web Checkout & Scale
  - Web checkout API (redirect to Beza payment page)
  - Merchant dashboard (web)
  - Settlement reports (downloadable PDF/CSV)
  - Webhook notification system
  - Multi-store support
  - Refund management
  - Transaction search + export
```

## Feature Gating by Merchant Tier
| Feature | Tier 1 (Micro) | Tier 2 (Small) | Tier 3 (Mid) | Tier 4 (Enterprise) |
|---------|---------------|---------------|-------------|---------------------|
| Static QR Code | ✓ | ✓ | ✓ | ✓ |
| Dynamic QR Code | ✓ | ✓ | ✓ | ✓ |
| Payment Links | ✓ | ✓ | ✓ | ✓ |
| POS Terminal | ✗ | ✓ | ✓ | ✓ |
| Web Checkout | ✗ | ✗ | ✓ | ✓ |
| Webhooks | ✗ | ✓ | ✓ | ✓ |
| Refunds | ✓ | ✓ | ✓ | ✓ |
| Multi-store | ✗ | ✗ | ✓ | ✓ |
| Employee Mgmt | ✗ | ✓ | ✓ | ✓ |
| API Access | ✗ | ✗ | ✓ | ✓ |
| Dedicated Support | ✗ | ✗ | ✓ | ✓ |
| Daily Settlement | ✓ | ✓ | ✓ | ✓ |
| T+0 Settlement | ✗ | ✗ | ✗ | ✓ (0.5% fee) |

## Pricing Strategy
| Product | MDR | Setup Fee | Monthly Fee |
|---------|-----|-----------|-------------|
| Static QR | 1.5% | Free | Free |
| Dynamic QR | 1.5% | Free | Free |
| Payment Link | 2.0% | Free | Free |
| POS Terminal | 2.0% | $150 purchase OR $15/mo rental | Free |
| Web Checkout | 2.5% | Free (API docs) | Free |
| Premium Merchant | Reduced (1.0% QR, 1.5% POS) | Free | $20/month |
| Settlement Fast-Track | Extra 0.5% | — | Per-use |
