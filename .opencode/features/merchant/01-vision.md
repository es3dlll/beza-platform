# Merchant Acquiring Feature Vision

## Elevator Pitch
Every Syrian business accepts Beza payments. From Abu Bilal's fruit cart with a laminated QR code taped to his cardboard sign, to Al-Sham Supermarket with a POS terminal and receipt printer, to Damascus Bazar's online checkout — Beza turns any business into a digital payment merchant. No monthly fees, no minimums, no bank account required. Just a smartphone and a will to grow.

## Problem Statement
- 95%+ of Syrian SMEs are unbanked and operate on cash-only — unsafe, untraceable, limits growth
- No affordable digital payment solution exists for micro-merchants (street vendors, kiosks, home businesses)
- Existing POS terminal providers charge $50-100/month + 3-5% MDR — prohibitive for small merchants
- Customers increasingly expect digital payment but merchants have no way to accept it
- No integrated solution spans QR (micro), POS (retail), Payment Links (social), and Web Checkout (e-commerce)
- Cash handling costs merchants 2-3% in theft, counterfeit notes, and counting errors

## Target Merchants
- **Tier 1 — Micro (Street vendors, kiosks, home businesses)**: 40K target by Y3, QR only, no upfront cost
- **Tier 2 — Small Retail (Corner shops, bakeries, pharmacies)**: 8K target by Y3, POS terminal + QR
- **Tier 3 — Mid-Market (Supermarkets, restaurants, electronics)**: 1.5K target by Y3, POS + Web Checkout
- **Tier 4 — Enterprise (Wholesalers, chains, e-commerce)**: 500 target by Y3, Full API integration

## Core Capabilities
| Capability | Priority | Description |
|------------|----------|-------------|
| Static QR Code | P0 | Fixed QR per merchant — customer scans and enters amount |
| Dynamic QR Code | P0 | QR with pre-set amount — scan and pay instantly |
| Payment Links | P0 | Shareable link via WhatsApp, SMS, or social media |
| POS Terminal | P1 | Android-based POS with receipt printing, transaction sync |
| Web Checkout | P1 | API + redirect for e-commerce merchants |
| POS Terminal Pairing | P0 | Secure pairing of terminal to merchant account |
| Transaction History | P0 | Filterable, searchable by date/amount/customer |
| Settlement Reports | P0 | Daily batch settlement with MDR deduction |
| Merchant Dashboard | P0 | Real-time sales, settlement status, QR management |
| Webhook Notifications | P1 | Real-time payment notification to merchant server |
| Refunds | P1 | Full/partial refund within settlement window |
| Split Bill | P2 | QR on restaurant table + customer pays their share |
| Customer Receipts | P0 | Digital receipt via SMS or WhatsApp |
| QR with Logo | P0 | Branded QR with merchant/business logo |
| Multi-store | P2 | Single merchant account with multiple locations |
| Employee Management | P2 | POS PIN per employee, sales attribution |
| Inventory Sync | P3 | Basic inventory tracking via POS |

## Success Metrics
| Metric | Y1 Target | Y3 Target |
|--------|-----------|-----------|
| Registered merchants | 5K | 50K |
| Active merchants (30d) | 2K | 25K |
| Monthly TP (merchant) | $2M | $50M |
| Merchant MDR revenue | $40K/mo | $1M/mo |
| Avg txn per merchant/day | 5 | 15 |
| Settlement success rate | 99.5% | 99.9% |
| QR payment success rate | 98% | 99.5% |
| POS uptime | 99% | 99.5% |
| Webhook delivery rate | 99% | 99.9% |
