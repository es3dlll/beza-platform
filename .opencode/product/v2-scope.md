# V2 Scope — Beza Platform Expansion

**Status:** Backend complete (303 tests ✅) | **Flutter:** Hub screens done, full feature sets pending | **Admin:** Key pages pending

**Target:** Months 7–16 | **Products:** Tier B (V1.5) + Tier C | **Platform:** iOS, wallet-to-bank, subscriptions, fraud ML

---

## V2 CORE (Month 7–16)

### Settlement Module
- [x] Daily batch: merchant T+1, agent same-day
- [x] Reconciliation: CFE vs bank statement (BSO/SIIB SFTP adapter)
- [x] Settlement states: pending → processing → completed → failed → retry
- [x] Admin queue + detail screens
- [x] Difference report + suspense auto-route
- [x] `SettlementProcessDaily` artisan command
- [ ] Bank partner reconciliation report (XML/CSV daily)

### Payroll Module
- [x] Employer registration + business wallet + KYC
- [x] CSV upload with Syrian phone validation
- [x] Batch processing via CFE (PAYROLL_SUBMITTED → SALARY_DISBURSED)
- [x] Pending for unregistered employees + SMS invite
- [x] Salary certificate PDF report
- [x] Business portal upload/preview/confirm
- [x] Mobile employee: salary banner on home, payroll filter in transactions
- [x] Fee: 0.5% capped at 50,000 SYP per batch

### Savings Module
- [x] Goal-based savings (Umrah, School, Custom)
- [x] Target amount + target date
- [x] Auto-sweep from wallet (daily configurable)
- [x] Mudaraba profit pool + distribution engine
- [x] Profit distribution: daily calculation, monthly payout
- [x] CBS reserve limits
- [x] Mobile: goal list, create, fund, withdraw, progress ring
- [x] Admin: pool balance, profit reports

### Cards Module
- [x] Virtual card: instant issuance
- [x] Prepaid physical card: request + shipping
- [x] CardAuthorizationService: merchant, MCC, daily limits
- [x] Freeze/suspend/expire — CardTransactionAuthorized/Declined events
- [x] Local card scheme (no Visa/MC — sanctions compliant)
- [x] ATM cash-out (network-dependent)
- [x] Mobile: card list, details, limits, merchant blocks
- [x] Secure card number display (blur + PIN reveal)
- [x] NFC tap-to-pay (Android)
- [x] Admin: card program management, shipping, disputes

### Loyalty Module
- [x] Points earning: P2P, bills, merchant, FX (configurable rules)
- [x] Tiers: bronze/silver/gold — auto-recalculate monthly
- [x] Points redemption → wallet balance or fee discount
- [x] Merchant-funded campaigns
- [x] Mobile: points balance, history, rewards catalog

### Government Collections Module
- [x] Providers: CBS, BSO, tax, utility — inquire→pay pattern
- [x] Passport/civil registry fee inquiry (MoF)
- [x] Government receipt with QR verification
- [x] Mobile: gov categories, inquiry, payment
- [x] Reference number copy for follow-up
- [x] Admin: collection volume, daily MoF report

### Platform Capabilities
- [x] iOS app (TestFlight beta, App Store release)
- [x] Wallet-to-bank transfer SYP (Tier 2+)
- [x] Wallet-to-bank transfer USD (Tier 3)
- [x] Standing orders / subscriptions (weekly/monthly)
- [x] Merchant dynamic QR (merchant POS shows QR with amount)
- [x] Agent-to-agent float transfer
- [x] USSD expanded: bills `*123*4#` (Syriatel, MTN, electricity)
- [x] In-app support chat
- [x] WhatsApp Business API bot (FAQ + ticket creation)
- [x] Self-service account deletion
- [x] Accessibility: TalkBack/VoiceOver, WCAG AA, 44pt targets
- [x] Offline store-and-forward (non-financial ops)

### Fraud V2
- [x] LightGBM ML scorer (features from 90-day transactions)
- [x] Offline training + versioned model deployment
- [x] Score + rules weighted decision
- [x] Behavioral anomaly detection (time, device, amount pattern)
- [x] Graph mule detection (linked accounts via device/IP)
- [x] Agent float mismatch >10% alert
- [x] Scheduled retraining + false positive feedback
- [x] CreditScore read-only from wallet history

### Agent POS Application
- [x] Login PIN (no biometric — POS shared devices)
- [x] Dashboard: float balance, daily volume, commission
- [x] Cash-in: scan user QR or enter phone → amount → confirm
- [x] Cash-out: user request → agent gives cash → confirm
- [x] Float top-up via bank transfer
- [x] Agent-to-agent float transfer
- [x] Daily settlement report
- [x] Transaction history (last 50)
- [x] Receipt print (Bluetooth thermal optional)
- [x] API `/api/v1/agent/pos/*` — 5-min idle session timeout

---

## NOT IN V2 (V3+)

- [ ] Financing/murabaha as full product → V3
- [ ] BNPL → V3
- [ ] Credit scoring for lending (read-only foundation in V2) → V3
- [ ] Education fee payment → V3
- [ ] Humanitarian aid distribution → V3
- [ ] Open Finance API → V3
- [ ] E-commerce marketplace → V4
- [ ] Insurance (takaful) → V4
- [ ] Investments / crowdfunding → V4

---

## V2 KPIs

| Metric | Target | Measurement |
|--------|--------|-------------|
| Payroll volume (employers) | 50 by M12 | Active employers |
| Savings AUM | SYP 500M by M16 | Total pool balance |
| Card activation rate | >60% within 30 days | Activated / issued |
| Gov collection success rate | >95% | Successful / total inquiries |
| iOS users | 5,000 by M16 | App Store downloads |
| Wallet-to-bank volume | SYP 100M/month by M16 | Monthly transfer volume |
