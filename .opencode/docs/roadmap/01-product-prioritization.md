# Product Prioritization — Beza Platform

## Tier Breakdown

| Tier | Timeframe | Products | Rationale |
|------|-----------|----------|-----------|
| **A (Launch, V1)** | Months 1–6 | Wallet, Agent Network, FX, Remittance, Bill Payment, Merchant QR | Core financial infrastructure; enables cash-in/cash-out loops; generates transaction volume immediately; lowest regulatory friction |
| **B (V1.5)** | Months 7–10 | Payroll, Savings, Settlement | Requires active transaction base (V1); CBS approval for savings needs 2–3 months; payroll needs employer onboarding pipeline |
| **C (V2)** | Months 11–16 | Financing, Cards, Loyalty, Government Collections | Cards need CBS physical card scheme approval (6–12 months); financing requires credit history from V1 wallet data; government collections need MoF MoU |
| **D (V3)** | Months 17–24 | Education, Humanitarian, Marketplace, Open Finance | Partner-heavy; humanitarian requires UN/NGO framework agreements; marketplace needs merchant density from V2; open finance requires CBS sandbox approval |

## Detailed Rationale

### Tier A — V1 (Months 1–6)

| Product | Rationale | Dependencies | Market Need | Revenue Impact | Regulatory (Syria) |
|---------|-----------|-------------|-------------|----------------|-------------------|
| **Wallet** | Foundation for everything; SYP + USD multi-currency wallets solve real dual-currency cash problem | CBS e-money license (obtain pre-launch); Syriatel/MTN SMS gateway | 70%+ unbanked but 85% mobile penetration; cash-heavy economy | Float income on wallet balances; interchange on transfers | CBS Law No. 23/2005 for non-banking financial; must register as payment service provider |
| **Agent Network** | Physical cash-in/cash-out is mandatory in Syria where card infrastructure <5% | Wallet module; agent onboarding app; POS/QR hardware supply chain | Banking deserts in rural areas (Idlib countryside, Deir Ezzor, Hasakeh); 90%+ cash transactions | Commission spread (cash-in fee 0.5–1%, cash-out fee 1–2%) | CBS agent banking circular; agent must be registered merchant; agent due diligence required |
| **FX** | Multi-currency (SYP, USD, EUR) essential; parallel market dominates | Wallet with multi-currency ledger; CBS daily rate feed | USD is de facto second currency; businesses need rate transparency | Spread on FX conversion (~2–3% spread vs parallel market) | CBS foreign exchange circulars; must reference official CBS rate; dual display of official vs market rate required |
| **Remittance** | Largest FX inflow into Syria (~$2B/year via informal channels) | FX module; Agent network for cash payout; international corridor partnerships | Syrian diaspora in Lebanon, Jordan, UAE, Germany, Sweden; informal hawala dominates currently | ~3–5% fee on inbound remittances; high volume | AML Law No. 31/2010; FATF compliance; must report >$10k equivalent; sanctions screening of corridors (USD clearing restricted) |
| **Bill Payment** | Quick win; state telecom (Syriatel, MTN) and electricity bills are pain points | Wallet; biller API integration (Syriatel/MTN/PEED) | Long queues at bill payment offices; 30%+ of urban monthly spend is utilities | ~1% biller commission; drives wallet reloads | Must integrate with state billing systems; no special license needed beyond payment services |
| **Merchant QR** | Digital payment at point of sale; reduces cash handling | Wallet; agent network for onboarding; QR standard (Syria-specific or EMV) | Merchants want to reduce cash theft/forgery risk; younger demographics in Damascus/Aleppo prefer digital | MDR 0.5–1.5% per transaction; drives wallet transaction velocity | CBS QR standard pending adoption; may use proprietary QR until national standard; merchant registration required |

### Tier B — V1.5 (Months 7–10)

| Product | Rationale | Dependencies | Market Need | Revenue Impact | Regulatory (Syria) |
|---------|-----------|-------------|-------------|----------------|-------------------|
| **Payroll** | B2B stickiness; recurring monthly transaction volume | Wallet; corporate banking integration; employer onboarding | Businesses want to digitize salary distribution; reduces cash logistics for employers | ~1% fee on payroll volume; employer onboarding fees | CBS wage protection system? Not mandatory in Syria yet, but trend; needs employer verification |
| **Savings** | Wallet balance drives savings demand; no-yield savings accounts replace mattress storage | Wallet; CBS approval for savings/pool accounts; Islamic banking-compliant structure | 90%+ of Syrians save informally (cash under mattress, gold); no trust in banks | Float income on savings pool; possible management fee | Requires CBS non-banking savings license; must be Sharia-compliant (mudaraba structure); minimum reserve requirements |
| **Settlement** | Merchant and agent settlement automation | Wallet; agent network; merchant QR; batch processing system | Agents need same-day settlement; merchants need D+1 | Revenue-neutral (operational efficiency) | CBS settlement timeline guidelines; must settle in SYP via bank transfer |

### Tier C — V2 (Months 11–16)

| Product | Rationale | Dependencies | Market Need | Revenue Impact | Regulatory (Syria) |
|---------|-----------|-------------|-------------|----------------|-------------------|
| **Financing** | Highest margin product; uses V1 transaction history as credit scoring | 6 months+ wallet transaction history; credit scoring model; collections team | SMEs and individuals have no access to formal credit (<10% credit penetration) | Interest/markup income 15–25% (Sharia-compliant murabaha) | CBS lending license or partnership with licensed bank; anti-usury law; Sharia board approval needed for murabaha |
| **Cards** | Physical/digital card for ATM and POS; brand credibility | CBS card scheme approval (6–12 month timeline); card manufacturer partnership (possibly limited due to sanctions) | Only ~2M cards in circulation in Syria (2024); mostly state bank prepaid | Card issuance fee (~$5–10); annual fee; interchange income | CBS physical card issuance approval; must comply with international sanctions (no Visa/MC direct; may use local scheme or national switch) |
| **Loyalty** | Wallet retention; reduces churn | Merchant QR; bill payment; wallet transaction data | Users need incentive to choose Beza over cash | Increased transaction volume; merchant-funded points | No regulatory issues; simple program |
| **Government Collections** | High-volume, low-margin; B2G credibility | Bill payment; MoF MoU; state system integration | Ministries collect fees/taxes inefficiently; cash-based | ~0.5% collection fee on high volume | MoF approval required; government procurement process (slow); CBS circular 2021 on digital payments |

### Tier D — V3 (Months 17–24)

| Product | Rationale | Dependencies | Market Need | Revenue Impact | Regulatory (Syria) |
|---------|-----------|-------------|-------------|----------------|-------------------|
| **Education** | Social impact + brand; tuition payment platform | School/university API integration; disbursement engine | Parents struggle with school fee payments; cash-heavy process | Low margin; CSR play | Ministry of Education coordination; no specific license |
| **Humanitarian** | UN/NGO cash transfer programs; largest humanitarian operation globally in Syria | Wallet; agent network; UN/WFP/UNHCR partnership agreements | 15.3M people need humanitarian assistance (OCHA 2024); cash-based assistance growing | ~2% disbursement fee from humanitarian organizations | UN sanctions exception; Must comply with UN sanctions screening; OFAC licenses needed for USD programs |
| **Marketplace** | Ecosystem lock-in; wallet transaction volume | Merchant QR; agent network; logistics partners | No dominant local marketplace; Souq.com shut; Syria-specific e-commerce gap | Commission ~5–10% per transaction | E-commerce regulation under Ministry of Economy; no specific license needed |
| **Open Finance** | API platform for third-party innovation | All V1/V2 modules; API gateway; developer portal | Fintech ecosystem in Syria is nascent; regulatory sandbox opportunity | API call fees; revenue share from third-party apps | CBS regulatory sandbox application; CMT data privacy law; needs comprehensive API agreement |

---

## Gantt-Style Timeline (Months 1–24)

```
Month       1    2    3    4    5    6    7    8    9   10   11   12   13   14   15   16   17   18   19   20   21   22   23   24
            |    |    |    |    |    |    |    |    |    |    |    |    |    |    |    |    |    |    |    |    |    |    |
TIER A ──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────
Wallet      ████ ████ ████ ████ ████ ████
Agent Net                    ████ ████ ████ ████
FX                               ████ ████ ████
Remittance                                        ████ ████ ████ ████
Bill Pay                                              ████ ████ ████ ████
Merchant QR                                                         ████ ████ ████ ████
TIER B ──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────
Payroll                                                                        ████ ████ ████
Savings                                                                                 ████ ████ ████
Settlement                                                                                         ████ ████ ████
TIER C ──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────
Financing                                                                                                   ████ ████ ████ ████ ████
Cards                                                                                                            ████ ████ ████ ████ ████ ████
Loyalty                                                                                                                         ████ ████ ████ ████
Gov Collections                                                                                                                   ████ ████ ████ ████
TIER D ──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────
Education                                                                                                                                       ████ ████ ████
Humanitarian                                                                                                                                       ████ ████ ████
Marketplace                                                                                                                                               ████ ████ ████
Open Finance                                                                                                                                                      ████ ████ ████
```

### Milestone Markers

| Month | Milestone |
|-------|-----------|
| M0 | CBS e-money license obtained; wallet development starts |
| M1 | MVP wallet (SYP only) internal alpha |
| M2 | Multi-currency wallet (SYP/USD) beta; agent app alpha |
| M3 | Wallet + Agent Network soft launch (Damascus) |
| M4 | FX module live; Remittance corridors: Lebanon, UAE |
| M5 | V1 full launch (Damascus, Aleppo, Latakia); Bill Payment + Merchant QR |
| M6 | V1 stabilization; Tier B development begins |
| M7 | Payroll pilot with 5 employers |
| M8 | Savings product with CBS approval |
| M9 | Settlement automation live |
| M10 | V1.5 launch (all Tier B) |
| M11 | Financing credit model training on V1 data |
| M12 | CBS card scheme application submitted (M6 start → M18 approval expected) |
| M14 | Financing pilot (< 50 users) |
| M16 | V2 launch (Financing, Loyalty) |
| M18 | Cards scheme approval expected; card manufacturing begins |
| M20 | Cards live; Government Collections MoU signed |
| M24 | V3 launch (Education, Humanitarian, Marketplace, Open Finance) |

---

## Key Syria-Specific Dependencies

| Dependency | Criticality | Timeline Risk |
|-----------|-------------|---------------|
| CBS e-money license | **BLOCKER** (no license = no launch) | 3–6 months to obtain |
| CBS card scheme approval | **BLOCKER for cards** | 6–12 months |
| Syriatel/MTN SMS gateway agreement | High (wallet OTP, USSD) | 1–2 months |
| Parallel market FX rate feed | High (FX product viability) | Ongoing risk |
| US/EU sanctions compliance | **CRITICAL** (USD clearing, card schemes) | Continuous monitoring |
| Internet infrastructure (Tier 3/4 cities) | Medium (app offline mode needed) | Ongoing |
| National ID database access (e-kyc) | Medium (KYC automation) | Requires CMT/CBS approval |
| Civil registry API integration | Medium (verify national ID) | Requires MoI approval |
