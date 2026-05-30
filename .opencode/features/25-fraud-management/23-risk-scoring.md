# Risk Scoring — Fraud Engine Methodology

## Scoring Overview

Every transaction receives a risk score from 0 (safe) to 100 (definitely fraud). The score is a weighted combination of rule-based scoring and ML-based scoring.

## Scoring Formula

```
Risk Score = (Rules Score × 0.60) + (ML Score × 0.40)

Where:
- Rules Score = Weighted sum of all triggered rule scores (capped at 100)
- ML Score = Model probability × 100 (0.0 to 1.0 → 0 to 100)
- Weights configurable (60/40 default, adjustable by product/region)
```

## Decision Matrix

| Score Range | Risk Level | Decision | Action |
|-------------|------------|----------|--------|
| 0–39 | Safe | APPROVE | No action. Transaction completes normally. |
| 40–59 | Suspicious | VERIFY | Require user verification (PIN, OTP, biometric). If passed, approve. |
| 60–79 | Highly Suspicious | REVIEW | Flag for manual review by ops team. Transaction held. |
| 80–100 | Critical | BLOCK | Transaction blocked. User notified. Account may be frozen. |

## Risk Factors

### Factor Table

| # | Factor | Weight | Type | Syria-Specific Context | Threshold |
|---|--------|--------|------|----------------------|-----------|
| 1 | New Device | 25 pts | Device | High SIM swap risk; many Syrians use shared/family phones; device fingerprint must account for emulators | Device not seen in user's 90-day history |
| 2 | Transaction Amount Spike | 20 pts | Amount | Syria has wide income variance — 3σ threshold must be dynamic; use user's own history not global avg | Amount > 3σ from user's 90-day average |
| 3 | New Location | 15 pts | Location | Population displacement is legitimate; differentiate between conflict displacement and fraud | Location > 50km from any previous user location |
| 4 | Agent History | 15 pts | Agent | Rural agent fraud risk is high; agent reputation score based on 90-day performance | Agent risk score > 70/100 or agent in bottom 10% |
| 5 | High Velocity | 25 pts | Velocity | Mule account detection — high velocity is strongest mule indicator; threshold must be lower than cash-based patterns | > 5 txns in 30 minutes OR > 2x user's normal velocity |
| 6 | ML Prediction | 10 pts | ML | Ensemble model (GBDT + DL); score is model's fraud probability | ML probability > 0.70 |
| 7 | Time Since Last Transaction | 5 pts | Time | Irregular usage patterns are common in Syria (sporadic usage); low weight to avoid penalizing infrequent users | > 30 days since last txn AND amount > 3σ |
| 8 | SIM Recently Changed | 15 pts | SIM | Weak carrier controls at Syriatel/MTN; SIM swap is a primary attack vector for account takeover | SIM changed within 48 hours |
| 9 | Recipient New Account | 10 pts | Recipient | Mule accounts are often recently created; combined with velocity this is very strong signal | Recipient account < 7 days old |
| 10 | Transaction Type Mismatch | 8 pts | Behavioral | User typically does P2P but suddenly sends merchant txns; behavior change is suspicious | Current type ≠ top 2 most common user types in 90 days |
| 11 | Off-Hours Transaction | 5 pts | Time | Syria has distinct daily patterns; late-night txns (23:00–05:00) are higher risk for most users | Time 23:00–05:00 AND amount > user's daily avg |
| 12 | Round Amount | 5 pts | Amount | Round amounts (50,000, 100,000, 250,000, 500,000) often used in fraud (but also common in legitimate txns); low weight | Amount in {50000, 100000, 150000, 200000, 250000, 500000, 1000000} |
| 13 | Failed Login Attempts | 10 pts | Device | Multiple failed logins before transaction suggests account takeover | > 3 failed logins in 24h before transaction |
| 14 | Cross-Product Activity | 8 pts | Behavioral | User who only does wallet suddenly does agent cash-out | User doing first transaction of this product type |
| 15 | Device Emulator | 20 pts | Device | Fraudsters use Android emulators; detection of emulator is strong fraud signal | Device detected as emulator (build.prop, Play Integrity) |
| 16 | Network Proxy/VPN | 10 pts | Network | VPN usage is less common in Syria; may indicate fraudster masking location | Traffic via known VPN/proxy/Tor exit node |
| 17 | Recipient Velocity | 15 pts | Velocity | Receiving account getting many transactions from different senders is mule pattern | Recipient received > 5 txns from different senders in 1h |
| 18 | KYC Level | 10 pts | Profile | Lower KYC users are higher risk; Syria's KYC challenges (weak ID) mean KYC level is meaningful | KYC Level 1 (basic) for transaction > 200,000 SYP |
| 19 | Account Age | 8 pts | Profile | New accounts are higher risk; but many legitimate users are new (growing platform) | Account age < 30 days |
| 20 | Agent Distance | 10 pts | Agent | User transacts at agent far from their home location; displacement vs. fraud | Distance from user's home agent > 30km |

### Scoring Weights by Product

| Product | Device | Amount | Location | Velocity | Agent | ML | SIM | Time | Other |
|---------|--------|--------|----------|----------|-------|-----|-----|------|-------|
| Wallet P2P | 25 | 20 | 15 | 25 | — | 10 | 15 | 5 | 10 |
| Agent Cash-In | 15 | 15 | 10 | 20 | 25 | 10 | 10 | 5 | 15 |
| Agent Cash-Out | 15 | 20 | 15 | 20 | 25 | 10 | 10 | 5 | 10 |
| Remittance (In) | 20 | 15 | 25 | 15 | 10 | 10 | 25 | 5 | 10 |
| Remittance (Out) | 25 | 20 | 15 | 15 | — | 10 | 15 | 5 | 15 |
| Merchant Payment | 20 | 20 | 10 | 15 | 15 | 10 | 10 | 5 | 20 |
| Bill Payment | 15 | 15 | 10 | 10 | — | 10 | 10 | 5 | 25 |
| Payroll | 20 | 25 | 15 | 10 | — | 10 | 10 | 5 | 20 |
| Bulk Disbursement | 15 | 20 | 15 | 30 | — | 10 | 10 | 5 | 15 |

## Score Calculation Example

```
Transaction: Wallet P2P, 150,000 SYP
User: Ahmed, avg 45,000 SYP, uses Samsung S22 from Damascus

Rules Evaluated:
┌──────────────────────────────────────────────────────┐
│ Factor              | Triggered? | Score | Weight   │
│─────────────────────|────────────|───────|──────────│
│ New Device (DEV-001)| YES (S23)  | 85    | 25       │
│ Amount Spike        | YES (3.3σ) | 72    | 20       │
│ New Location        | YES (Aleppo)| 65   | 15       │
│ High Velocity       | NO         | 0     | 25       │
│ ML Prediction       | —          | 82    | 10       │
│ SIM Changed         | NO         | 0     | 15       │
│ Time Since Last     | NO         | 30    | 5        │
├─────────────────────┼────────────┼───────┼──────────┤
│ Rules Score         |            |       | 58.25    │
│ ML Score            |            |       | 82       │
│ FINAL SCORE         |            |       | 72       │
└──────────────────────────────────────────────────────┘

Decision: REVIEW (60-79 range)
Action: Transaction held for manual review
```

## Score Categories

### Per-Factor Score Calculation

Each factor computes a score from 0 (no risk) to 100 (maximum risk).

```
Factor Score = min(100, raw_score × scaling_factor)

Where raw_score depends on the factor:
- Amount: z-score × 20 (e.g., 3.3σ → 66)
- Velocity: (actual_count / threshold) × 100
- Device: 85 if new, 0 if known
- Location: normalized distance score
- Agent: 100 - agent_trust_score
```

### Scaling Factors

| Factor | Scaling | Range |
|--------|---------|-------|
| Amount z-score | z × 20 | 0–100 |
| Velocity ratio | count/threshold × 100 | 0–100 |
| Device trust | 85 (new), 0 (known) | 0 or 85 |
| Location distance | min(100, distance_km × 0.5) | 0–100 |
| Agent reputation | 100 - trust_score | 0–100 |
| ML probability | prob × 100 | 0–100 |

## Syria-Specific Score Adjustments

### Regional Multipliers

| Region | Multiplier | Rationale |
|--------|-----------|-----------|
| Damascus | 1.0 | Baseline |
| Aleppo | 1.15 | Higher agent fraud risk |
| Rural areas | 1.20 | Less agent oversight, higher potential for agent fraud |
| IDP camps | 0.85 | Lower multiplier — displacement is legitimate |
| Coastal | 1.0 | Baseline |
| Northeast | 1.10 | Conflict-affected, less infrastructure |

### Time-Based Adjustments

| Time | Multiplier | Rationale |
|------|-----------|-----------|
| Weekday 06:00–22:00 | 1.0 | Normal business hours |
| Weekday 22:00–06:00 | 1.20 | Off-hours suspected fraud |
| Friday (prayer time) | 0.85 | Lower activity expected |
| Eid holidays | 0.80 | Unusual patterns expected (charity, gifts) |
| Pension disbursement day | 1.30 | Higher fraud attempts targeting pension recipients |
