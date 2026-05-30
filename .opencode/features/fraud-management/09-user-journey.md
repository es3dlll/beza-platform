# User Journeys — Fraud Management

## Journey Map Overview

This document covers 5 distinct user journeys for fraud-related interactions. Each journey spans from trigger event through resolution.

## Journey 1: User Flagged (False Positive) — Instant Resolution

**Persona:** Ahmed, 34, teacher in Damascus. Casually uses Beza wallet for daily purchases.

### Trigger
Ahmed sends 150,000 SYP to his brother in Aleppo for a family expense. This is 3x his usual transaction amount.

### Journey Steps

| Step | Channel | User Action | System Action | Emotional State |
|------|---------|-------------|---------------|-----------------|
| 1. Initiate | App | Ahmed enters amount 150,000 SYP and recipient | Transaction submitted to fraud engine | Normal |
| 2. Flagged | App | Sees "Beza Security Check" screen — transaction is paused | Rule triggered: amount > 3σ of user baseline. Score: medium. | Confused, slight concern |
| 3. Verify | App | Taps "This is me" → prompted for PIN | System waits for verification | Hoping it works |
| 4. Verified | App | Enters PIN correctly | PIN matches. Score adjusted down. Transaction approved in 90ms. | Relief |
| 5. Complete | App | Sees "Transaction Complete" with usual confirmation | Transaction processed normally | Satisfied |
| 6. Follow-up | SMS | Receives SMS: "50,000 SYP sent to Mohammed A." | Standard confirmation sent | Neutral |

### Emotional Journey
```
😐 → 😕 → 😰 → 😌 → 😐
```

### Key Learnings
- Ahmed didn't need to contact support — self-resolved
- The extra step added 10 seconds but felt protective
- If PIN had failed, next step would be OTP → if OTP failed → manual review

### System Metrics
- Fraud score: 72/100 (medium)
- Rule triggered: TAMT-001 (Transaction Amount Threshold)
- Decision: approve_after_verification
- Processing time: 90ms

---

## Journey 2: User Account Taken Over (Fraud Victim)

**Persona:** Layla, 28, hairdresser in Homs. Uses Beza to receive payments from clients.

### Trigger
A fraudster obtained Layla's credentials through a phishing SMS. They log in from a new device and attempt to transfer 500,000 SYP to a mule account.

### Journey Steps

| Step | Channel | User Action | Fraudster Action | System Action | Emotional State |
|------|---------|-------------|------------------|---------------|-----------------|
| 1. Phishing | SMS | Layla clicks fake "Beza verification" link, enters PIN and OTP | Fraudster captures credentials | Suspicions link reported, none yet | Tricked |
| 2. New Device Login | Backend | (unaware) | Fraudster logs in on new device (emulator) | Device fingerprint new. Risk score elevated. Flags login. | (unaware) |
| 3. Large Transfer | Backend | (unaware) | Fraudster sends 500,000 SYP to account ending 7890 | Amount + new device + velocity check. Score: 95/100. Rule: BLOCK | (unaware) |
| 4. Blocked | App + SMS | Layla opens app → sees "Account Temporarily Frozen" with explanation | Transaction blocked. Account frozen. User notified. | Shocked, scared, confused |
| 5. Contact Support | Phone call | Layla calls support center | Support confirms suspicious activity, starts investigation | Anxious, wants reassurance |
| 6. Investigation | Backend | Layla provides ID verification (national ID + selfie) | Case FR-2025-5678 opened. Investigation playbook: Account Takeover. | Waiting, uncertain |
| 7. Confirmed Fraud | Call | Support confirms: yes, account was compromised | Transaction reversed (in-flight). Fraudster's receiving account frozen. | Relieved, but shaken |
| 8. Account Restored | App | Layla resets PIN. Account restored. | New PIN set. New device fingerprint registered. Fraud case closed. | Cautious |
| 9. Follow-up | SMS | Receives "Your account security has been upgraded" | Fraud alert sent to team. User added to high-risk monitoring. | Aware, safer |

### Emotional Journey
```
😌 → 😰 → 😨 → 😱 → 😰 → 😟 → 😮‍💨 → 😌
```

### Key Learnings
- Fraud detected BEFORE loss occurred (transaction blocked)
- Clear communication prevented panic
- Phone support was critical — Layla needed human voice
- Account restored within 4 hours (under SLA)
- Followed up with security education (future feature)

### System Metrics
- Risk score: 95/100 (high)
- Rule triggered: ATO-001 (New Device + Large Transfer) + VEL-003 (Velocity)
- Decision: block_and_freeze
- Case resolved: 3h 42m

---

## Journey 3: Agent Fraud Detection

**Persona:** Hassan, Beza agent in rural Idlib. Suspected of float manipulation.

### Trigger
Hassan's agent float suddenly decreases despite reporting no major cash-out transactions. System detects pattern mismatch.

### Journey Steps

| Step | Channel | User Action | Agent Action | System Action | Emotional State |
|------|---------|-------------|--------------|---------------|-----------------|
| 1. Pattern Detection | Backend | — | — | Float variance > 3σ of agent baseline detected | — |
| 2. Alert | Ops Dashboard | — | Hassan continues normal operations | P1 alert: "Agent Float Anomaly — Hassan, Idlib" | — |
| 3. Investigation | Dashboard | — | — | Ops team reviews: last 50 txns, float history, location data | — |
| 4. Pattern Confirmed | Field | — | Ops calls agent: "Explain 200,000 SYP variance" | Evidence: 5 fake cash-in transactions recorded | Defensive |
| 5. Agent Suspended | Ops | Customers redirected to other agents | Hassan suspended pending investigation | Agent account frozen. Case escalated. | Angry, worried |
| 6. Investigation | Field audit | — | Field agent visits, reconciles float | Evidence collected | — |
| 7. Fraud Confirmed | Legal | — | Hassan terminated. Legal action. | Case closed as confirmed_agent_fraud. CBS notified. | Negative outcome |
| 8. System Update | Backend | — | — | Agent blacklisted. Pattern added to fraud rules. | — |

### Emotional Journey (Operations Team)
```
😐 → 😐 → 🤔 → 🕵️ → ⚡ → 📋 → ✅
```

### System Metrics
- Detection time: Pattern identified within 2 hours of anomaly
- Investigation to resolution: 24 hours
- Amount at risk: 200,000 SYP (prevented further loss)
- Rule triggered: AGT-012 (Agent Float Variance)

---

## Journey 4: Remittance Interception Attempt

**Persona:** George, Syrian diaspora in Germany, sending 300 EUR to his mother in Damascus.

### Trigger
Fraudster intercepts the SMS OTP for George's mother's account via SIM swap.

### Journey Steps

| Step | Channel | User Action | System Action |
|------|---------|-------------|---------------|
| 1. Initiate Remittance | Web (Germany) | George sends 300 EUR via Beza remittance | Standard remittance processing |
| 2. SIM Swap Detection | Backend | — | Recipient SIM changed 2 hours ago. Rule SIM-001 triggered. |
| 3. Transaction Paused | App (George) | George sees: "Recipient verification needed" | Transaction held. Ops team notified. |
| 4. Verification | Phone call | George calls mother: "Did you change your SIM?" Mother: "No!" | Fraudster attempting to receive funds via swapped SIM. |
| 5. Fraud Confirmed | Backend | George confirms fraud intent | Transaction cancelled. Original SIM restored. Case filed. |
| 6. Resolution | SMS | Mother receives new SIM from Syriatel. Funds available at agent with ID. | 300 EUR returned. Case closed. |

### System Metrics
- Detection: SIM swap within 2h of transaction
- Rule triggered: SIM-001 (Recent SIM Swap + Remittance)
- Fraud prevented: 300 EUR (~1,500,000 SYP)
- User satisfaction: 5/5 — George grateful for protection

---

## Journey 5: False Positive Storm (Operations Scenario)

**Persona:** The Fraud Operations Team (collective journey).

### Trigger
A new fraud rule deployed at 02:00 causes 15% of all transactions to be flagged (vs. expected 3%). False positive storm.

### Journey Steps

| Step | Team Action | System Action |
|------|-------------|---------------|
| 1. Alert | — | Automated alert: "False positive rate > 10% — 3σ breach" |
| 2. Triage | Ops lead acknowledges alert | Rule performance dashboard shows FPR spike |
| 3. Identification | Data scientist identifies: new rule "VEL-050" too aggressive | Rule VEL-050 causing 80% of spurious flags |
| 4. Mitigation | Rule VEL-050 disabled/de-tuned | Rule disabled. FPR returns to normal within 5 minutes. |
| 5. User Impact | CS team sends apology SMS to affected users | "We incorrectly flagged your transaction. It has been processed. We apologize." |
| 6. Post-mortem | Root cause: velocity threshold too low for Friday peak | Rule updated with context-aware thresholds. |

### Timeline
- Alert at 02:05
- Rule disabled at 02:12
- Apologies sent at 02:30
- Post-mortem at 09:00

### Key Metrics
- Affected users: 2,500 (15% of transactions for 7 minutes)
- Financial impact: $0 (no blocked transactions, just delays)
- Trust impact: Minimal (apology sent, transactions processed)
- Learning: New rules must be shadow-mode deployed for 24h before active blocking

---

## Journey Summary Matrix

| Journey | User Type | Critical Moment | Resolution Path | SLA |
|---------|-----------|-----------------|-----------------|-----|
| False Positive | End user | Verification step | Self-service PIN/OTP | < 30s |
| Account Takeover | Victim | Account frozen call | Support + investigation | < 4h |
| Agent Fraud | Agent | Suspension | Field audit | < 48h |
| Remittance Intercept | Sender (diaspora) | Transaction held | Recipient verification | < 2h |
| FP Storm | Ops team | Alert detection | Rule rollback | < 15min |
