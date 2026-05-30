# Financial Flows

## Flow 1: Multi-Purpose Cash (MPC) Distribution

### End-to-End Flow

```
 NGO / UN Agency                    Beza Platform             Beneficiary Wallet       Agent / Merchant
 ─────────────                     ──────────────            ─────────────────        ────────────────
      │                                  │                          │                       │
      │  1. Transfer funds (e.g.,       │                          │                       │
      │     $5M USD to Beza             │                          │                       │
      │     operational account)        │                          │                       │
      │ ──────────────────────────────► │                          │                       │
      │                                  │                          │                       │
      │  2. Create aid program           │                          │                       │
      │     (MPC, 75 USD/month,          │                          │                       │
      │     10,000 beneficiaries)        │                          │                       │
      │ ──────────────────────────────► │                          │                       │
      │                                  │                          │                       │
      │  3. Upload beneficiary CSV       │                          │                       │
      │ ──────────────────────────────► │                          │                       │
      │                                  │  4. Screen against       │                       │
      │                                  │     sanctions lists      │                       │
      │                                  │     (UN/EU/OFAC)        │                       │
      │                                  │     ← [internal] →      │                       │
      │                                  │                          │                       │
      │                                  │  5. Enrol cleared        │                       │
      │                                  │     beneficiaries        │                       │
      │                                  │     ← [internal] →      │                       │
      │                                  │                          │                       │
      │  6. Trigger distribution         │                          │                       │
      │     (June 2026 MPC, $750K)       │                          │                       │
      │ ──────────────────────────────► │                          │                       │
      │                                  │  7. Debit NGO program    │                       │
      │                                  │     budget (internal)    │                       │
      │                                  │     ← [internal] →      │                       │
      │                                  │                          │                       │
      │                                  │  8. Credit beneficiary   │                       │
      │                                  │     wallets ($75 each)   │                       │
      │                                  │ ─────────────────────►  │                       │
      │                                  │                          │  9. SMS notification  │
      │                                  │                          │     "تم إيداع ٧٥ دولار" │
      │                                  │                          │                       │
      │                                  │                          │  10. Beneficiary       │
      │                                  │                          │      spends at         │
      │                                  │                          │      merchant/agent    │
      │                                  │                          │ ───────────────────►  │
      │                                  │                          │                       │
      │                                  │ 11. Transaction recorded │                       │
      │                                  │     with MCC category     │                       │
      │                                  │     ← [internal] →      │                       │
      │                                  │                          │                       │
      │ 12. View spending dashboard      │                          │                       │
      │     (food: 50%, rent: 20%,       │                          │                       │
      │      health: 10%, education: 5%, │                          │                       │
      │      transport: 5%, other: 10%)  │                          │                       │
      │ ◄────────────────────────────── │                          │                       │
      │                                  │                          │                       │
      │ 13. Download donor report        │                          │                       │
      │     (end of quarter)             │                          │                       │
      │ ◄────────────────────────────── │                          │                       │
```

### Fund Flow Diagram (MPC)

```
┌──────────────┐     $5M USD     ┌──────────────────┐
│              │ ──────────────► │                  │
│  NGO / UN    │                 │  Beza Trust      │
│  (Donor      │                 │  Account         │
│   Funds)     │                 │                  │
│              │                 └────────┬─────────┘
└──────────────┘                          │
                                          │ $75/household/month
                                          ▼
                                ┌──────────────────┐
                                │  Beneficiary      │
                                │  Wallets          │
                                │  (10,000 × $75    │
                                │   = $750K/month)  │
                                └────────┬─────────┘
                                          │
                           ┌──────────────┼──────────────┐
                           │              │              │
                           ▼              ▼              ▼
                    ┌───────────┐  ┌───────────┐  ┌───────────┐
                    │ Merchant  │  │ Agent     │  │ Bill Pay  │
                    │ (food,    │  │ (cash-out) │  │ (rent,    │
                    │  goods)   │  │           │  │  utilities│
                    └───────────┘  └───────────┘  └───────────┘
```

### Distribution Economics (MPC Example)

| Item | Amount |
|------|--------|
| NGO funds deposited | $5,000,000 |
| Beza platform fee (2%) | -$100,000 |
| Total program budget | $4,900,000 |
| Per-household monthly MPC | $75 |
| Beneficiary households | 10,000 |
| Monthly disbursement | $750,000 |
| Months covered | ~6.5 months |
| Average burn rate (30 days) | 80% |
| Typical spending: food | 50% ($37.50/household) |
| Typical spending: rent | 20% ($15.00/household) |
| Typical spending: health | 10% ($7.50/household) |
| Typical spending: education | 5% ($3.75/household) |
| Typical spending: transport | 5% ($3.75/household) |
| Typical spending: other | 10% ($7.50/household) |
| Unspent (saved) | 20% ($15.00/household) |

---

## Flow 2: Voucher Program

### End-to-End Flow

```
 NGO / UN Agency                    Beza Platform               Beneficiary            Partner Merchant
 ─────────────                     ──────────────              ────────────           ────────────────
      │                                  │                          │                       │
      │  1. Fund program ($500K          │                          │                       │
      │     for food vouchers)           │                          │                       │
      │ ──────────────────────────────► │                          │                       │
      │                                  │                          │                       │
      │  2. Create voucher program       │                          │                       │
      │     (45 USD/month, item list:    │                          │                       │
      │     rice, oil, flour, sugar)     │                          │                       │
      │ ──────────────────────────────► │                          │                       │
      │                                  │                          │                       │
      │  3. Enrol beneficiaries          │                          │                       │
      │ ──────────────────────────────► │                          │                       │
      │                                  │                          │                       │
      │  4. Issue e-vouchers             │                          │                       │
      │     (11,111 vouchers × $45)      │                          │                       │
      │     ← [internal] →              │                          │                       │
      │                                  │  5. SMS voucher code     │                       │
      │                                  │     "قسيمتك الغذائية"    │                       │
      │                                  │ ─────────────────────►  │                       │
      │                                  │                          │                       │
      │                                  │                          │  6. Visit merchant     │
      │                                  │                          │     select items       │
      │                                  │                          │ ───────────────────►  │
      │                                  │                          │                       │
      │                                  │                          │  7. Enter voucher code │
      │                                  │                          │     + PIN or scan QR   │
      │                                  │                          │ ◄──────────────────── │
      │                                  │                          │                       │
      │                                  │  8. Verify voucher       │                       │
      │                                  │     (valid, not expired, │                       │
      │                                  │     sufficient balance)  │                       │
      │                                  │     → Deduct items       │                       │
      │                                  │     ← [internal] →      │                       │
      │                                  │                          │                       │
      │                                  │  9. Confirm redemption   │                       │
      │                                  │ ◄───────────────────────┼────────────────────── │
      │                                  │                          │                       │
      │                                  │                          │ 10. SMS: "تم استخدام  │
      │                                  │                          │     ٢١ دولار من قسيمتك"│
      │                                  │                          │                       │
      │                                  │ 11. T+2: Settle         │                       │
      │                                  │     merchant wallet      │                       │
      │                                  │ ───────────────────────────────────────────────► │
      │                                  │                          │                       │
      │ 12. View voucher                 │                          │                       │
      │     redemption report            │                          │                       │
      │ ◄────────────────────────────── │                          │                       │
```

### Voucher Economics (Food Voucher Example)

| Item | Value |
|------|-------|
| Voucher face value | $45.00 |
| Rice 5kg ($6.00 × 2) | $12.00 |
| Cooking Oil 1L ($2.00 × 3) | $6.00 |
| Flour 10kg ($8.00 × 1) | $8.00 |
| Sugar 2kg ($3.00 × 2) | $6.00 |
| **Total basket** | **$32.00** |
| **Remaining balance** | **$13.00** (for next visit) |
| Merchant settlement (T+2) | $21.00 (after this redemption) |

### Merchant Settlement Flow

```
  Voucher Redemption ($21.00)
          │
          ▼
  ┌───────────────────┐
  │ T+0: Redemption   │  Merchant delivers $21.00 worth of goods
  │     recorded       │  to beneficiary
  └─────────┬─────────┘
            │
            ▼
  ┌───────────────────┐
  │ T+1: Settlement   │  Beza aggregates merchant's redemptions
  │     processing    │  for the day
  └─────────┬─────────┘
            │
            ▼
  ┌───────────────────┐
  │ T+2: Merchant     │  Beza credits merchant's wallet
  │     Wallet Credited│  $21.00 → Merchant Wallet
  └─────────┬─────────┘
            │
            ▼
  ┌───────────────────┐
  │ T+2: Fee Deducted │  Beza platform fee: $0.42 (2%)
  │     Net: $20.58   │  Merchants typically pay 1-2%
  └───────────────────┘
```

## Key Financial Controls

| Control | Description |
|---------|-------------|
| Pre-funding | NGO must pre-fund the Beza operational account before distributions |
| Budget cap | Distribution cannot exceed remaining program budget |
| Idempotency | Each distribution has unique idempotency_key — no double-credit |
| Settlement hold | Merchant settlement held for 2 days to allow dispute window |
| Fee transparency | All platform fees itemised in donor reconciliation report |
| Unspent return | Unspent program budget returned to NGO on program completion |
| Currency peg | All values in USD-equivalent; beneficiary sees USD amounts |
| Audit trail | Every financial event logged to immutable audit store |
