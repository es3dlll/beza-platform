# Fraud Types Catalog — Beza Fraud Prevention

## Overview

This catalog documents all fraud types that Beza's Fraud Prevention Platform guards against. Each entry includes detection methodology, Syria-specific risk assessment, and prevention controls.

## Fraud Type Catalog

### 1. Account Takeover (ATO)

**Description:** Fraudster gains unauthorized access to a user's Beza wallet through credential theft (phishing, credential stuffing, brute force) and initiates unauthorized transactions.

**Detection:**
| Signal | Weight | Rule |
|--------|--------|------|
| New device login | High | DEV-001 |
| Failed login attempts > 3 in 24h | High | LOG-001 |
| Password reset followed by large txn | High | PWR-001 |
| Device + location mismatch | Medium | LOC-002 |
| Transaction amount > user baseline | Medium | TAMT-001 |
| Off-hours transaction from new device | Medium | TIM-001 |

**Syria Risk:** **HIGH** — Weak digital literacy, common PIN sharing, phishing via SMS, carrier-grade NAT makes IP-based detection unreliable.

**Prevention:**

- Device fingerprinting at login
- Step-up authentication for new devices
- Behavioral biometrics (typing patterns)
- Session management (single session enforcement)
- Login anomaly detection (time, location, device)

**If confirmed:**

1. Freeze account immediately
2. Invalidate all sessions and tokens
3. Reverse unauthorized transactions (if in-flight)
4. Notify user via phone call (not just SMS)
5. File SAR if amount > 1M SYP

---

### 2. SIM Swap Fraud

**Description:** Fraudster convinces Syriatel/MTN to transfer the victim's phone number to a new SIM card in their possession. They then intercept OTPs and access the victim's Beza wallet.

**Detection:**
| Signal | Weight | Rule |
|--------|--------|------|
| SIM change detected within 48h | High | SIM-001 |
| SIM change + remittance transaction | Critical | SIM-002 |
| SIM change + large P2P transfer | High | SIM-003 |
| User device changed same day as SIM | Medium | SIM-004 |
| No transaction for 7d + SIM + txn | Medium | SIM-005 |

**Syria Risk:** **HIGH** — Weak carrier controls at Syriatel/MTN, lack of biometric SIM registration enforcement, corruption risk at carrier retail points. SIM swap is one of the most common attack vectors in Syria.

**Prevention:**

- Telecom API integration for SIM change detection (Syriatel + MTN)
- Cooldown period after SIM change (48h before high-value txns)
- Out-of-band verification via USSD callback
- Require in-person agent verification for high-value txns after SIM change
- Notify user when SIM change detected on their line

**If confirmed:**

1. Freeze account immediately
2. Notify user via alternate channel (email, in-app message)
3. Coordinate with Syriatel/MTN to restore original SIM
4. Reverse any fraudulently initiated transactions
5. File SAR

---

### 3. Mule Account Networks

**Description:** Fraudsters recruit individuals (often low-income Syrians) to open Beza wallets that serve as pass-through accounts for stolen funds. Multiple mule accounts are used to split and layer funds.

**Detection:**
| Signal | Weight | Rule |
|--------|--------|------|
| New account receives funds from multiple senders | High | RCV-001 |
| Account cashes out immediately after receiving | High | RCV-002 |
| Shared device across multiple accounts | Critical | DEV-007 |
| Shared IP across multiple accounts | High | NET-003 |
| Account created in batch (same hour) | Medium | ACT-001 |
| Same home location for multiple accounts | Medium | ACT-002 |
| Rapid in-and-out flow (no balance held) | High | VEL-008 |

**Syria Risk:** **MEDIUM** — Economic hardship makes Syrians vulnerable to becoming mules (paid small fees for account use). ID verification at KYC provides some barrier but not sufficient.

**Prevention:**

- Graph analysis (device/IP sharing across accounts)
- Velocity limits on new accounts (receiving + cash-out)
- KYC review for new accounts with unusual patterns
- Education campaign: "Don't let others use your account — it's illegal"
- Enhanced monitoring for accounts ≤ 30 days old

**If confirmed:**

1. Freeze all identified mule accounts
2. Trace funds and identify ultimate recipients
3. File comprehensive SAR with account network map
4. Refer connected accounts for enhanced KYC review

---

### 4. Agent Fraud

**Description:** Beza agents commit fraud through:

- **Float theft:** Recording fake cash-ins to inflate float, then stealing physical cash
- **Fake transactions:** Creating phantom transactions to earn commissions
- **Customer collusion:** Agent colludes with fraudsters to bypass checks
- **PIN harvesting:** Agent records customer PIN during transactions
- **Cash substitution:** Accepting counterfeit SYP during cash-in
- **Balance falsification:** Reporting incorrect agent float balance

**Detection:**
| Signal | Weight | Rule |
|--------|--------|------|
| Float variance > 3σ from baseline | Critical | AGT-012 |
| Rapid cash-in/cash-out to same recipient | High | AGT-013 |
| Agent transaction volume spike | High | AGT-014 |
| Transactions when no customers at location (geo check) | Medium | AGT-015 |
| Agent's personal account receiving from customers | Critical | AGT-016 |
| Agent commission-to-transaction ratio anomaly | Medium | AGT-017 |
| Agent dispute rate > 5% | High | AGT-018 |

**Syria Risk:** **HIGH** — Large agent network with varying levels of training and oversight. Rural agents particularly hard to monitor. Economic pressure increases fraud incentive. Weak legal enforcement in some regions.

**Prevention:**

- Agent cash and digital float reconciliation (daily automated)
- Mystery shopping program (audit agents)
- Agent training on fraud prevention
- Agent trust scoring system
- Dual control for high-value agent transactions
- Agent location verification via GPS

**If confirmed:**

1. Suspend agent immediately
2. Send field auditor to reconcile float
3. Freeze agent's personal accounts
4. File internal fraud report
5. If loss > thresholds, file with CBS + law enforcement
6. Blacklist agent in Beza system

---

### 5. Social Engineering

**Description:** Fraudster psychologically manipulates user into revealing sensitive information (PIN, OTP) or performing actions that benefit the fraudster.

**Types in Syria:**

- **Grandparent scam:** "I'm your relative, I need money urgently"
- **Beza support impersonation:** "I'm from Beza support, verify your account"
- **Prize scam:** "You won a prize, pay a fee to claim"
- **Job scam:** "Pay a registration fee for this job opportunity"
- **Charity scam:** "Donate to this charity for displaced families" (during Ramadan)
- **Agent impersonation:** Fraudster poses as agent and collects money

**Detection:**
| Signal | Weight | Rule |
|--------|--------|------|
| User transacting with new recipient repeatedly | Medium | SOC-001 |
| Unusual transaction amount (e.g., exactly 49,999 SYP) | Medium | SOC-002 |
| User transferred to multiple new recipients in short time | Medium | SOC-003 |
| User behavior change (login confusion, multiple OTP requests) | Medium | SOC-004 |
| Agent reports customer acting unusually | Low | SOC-005 (manual report) |

**Syria Risk:** **MEDIUM** — Social engineering is prevalent in Syria. Low digital literacy, family-centric culture (trusting "relatives"), and economic desperation make people vulnerable.

**Prevention:**

- User education: push notifications about common scams (Arabic)
- Transaction warnings: "Are you sure you know this person?"
- Delayed settlement for first-time recipients
- Out-of-band verification for high-value transfers to new recipients
- Agent training to identify and warn vulnerable customers
- SMS alerts for every login from new device

**If confirmed:**

1. Freeze affected accounts
2. Trace and freeze receiving accounts
3. Notify user with guidance on avoiding future scams
4. File report with local authorities if identifiable fraudster
5. Add receiving accounts/phones to fraud watchlist

---

### 6. Phishing

**Description:** Fraudster sends fraudulent communications (SMS, WhatsApp, fake website) impersonating Beza to steal credentials.

**Syria-specific:**

- SMS phishing (smishing) is most common — "Your Beza account has been locked, click here to verify"
- Fake Beza mobile apps distributed via WhatsApp groups
- Fake agent portals collecting agent credentials
- Phone call phishing (vishing) — "This is Beza security, confirm your PIN"

**Detection:**
| Signal | Weight | Rule |
|--------|--------|------|
| User reports phishing SMS/WhatsApp | High | PHS-001 (manual) |
| Multiple users report same phishing content | Critical | PHS-002 |
| Login from known phishing IP/domain | Medium | PHS-003 |
| Credential stuffing attempt | Medium | PHS-004 |
| Fake app install detected | Medium | PHS-005 |

**Syria Risk:** **MEDIUM** — Growing threat as digital adoption increases. CBS has issued warnings about SMS phishing targeting mobile wallet users.

**Prevention:**

- Anti-phishing: SMS sender ID registration (Beza as registered sender)
- URL monitoring: identify and takedown fake Beza websites
- In-app security: Never ask for PIN/OTP outside the official app
- User education: "Beza will NEVER ask for your PIN or OTP"
- Fraud awareness campaigns during high-risk periods (Ramadan)
- Report phishing button in Beza app

**If confirmed:**

1. Takedown phishing site/phone number
2. Notify all potentially affected users
3. Reset credentials for affected accounts
4. Enhance phishing detection rules
5. File CBS report if customer data compromised

---

### 7. Synthetic Identity Fraud

**Description:** Fraudster creates a fake identity combining real and fabricated information to open a Beza wallet for fraudulent purposes.

**Syria Risk:** **LOW** — Physical KYC at agents provides significant barrier. However, forged Syrian national IDs exist, particularly from conflict-affected areas where civil registry data is damaged.

**Detection:**
| Signal | Weight | Rule |
|--------|--------|------|
| KYC document validation fails | High | SYN-001 |
| National ID number doesn't match civil registry | High | SYN-002 |
| Photo on ID doesn't match selfie | High | SYN-003 |
| Multiple accounts with similar name/phone/IP | Medium | SYN-004 |
| Account created but no transactions for 30+ days | Low | SYN-005 |
| Account unusual behavior (perfect pattern) | Medium | SYN-006 |

**Prevention:**

- Physical KYC at agents (in-person verification)
- National ID validation against civil registry (if API available)
- Liveness detection during agent verification
- Duplicate detection across identity attributes
- Agent training on detecting forged IDs
- Cross-reference with other PSPs (if data sharing available)

---

### 8. Merchant Collusion

**Description:** Merchant and customer collude to defraud Beza through fake transactions, refund abuse, or bonus/reward exploitation.

**Syria Risk:** **LOW** — Merchant payment feature is newer, fewer merchants integrated. Risk will grow with merchant network expansion.

**Detection:**
| Signal | Weight | Rule |
|--------|--------|------|
| Same merchant-customer pair transacts frequently | High | MER-001 |
| Merchant and customer share device | Critical | MER-002 |
| Circular transactions (A→B→C→A) | High | MER-003 |
| Merchant refund rate > 10% | High | MER-004 |
| Merchant average transaction exactly equals promo threshold | Medium | MER-005 |
| Customer only transacts with one merchant | Medium | MER-006 |

**Prevention:**

- Merchant transaction pattern monitoring
- Device fingerprinting for merchant and customer
- Promo abuse detection rules
- Merchant trust scoring
- Limited refund windows and frequency checks

---

### 9. Insider Fraud

**Description:** Beza employee or agent abuses their privileged access to commit fraud.

**Types:**

- Unauthorized access to user accounts
- Manipulation of transaction records
- Creating fraudulent accounts
- Disabling fraud rules for specific accounts
- Selling user data to fraudsters
- Bypassing KYC for synthetic accounts

**Detection:**
| Signal | Weight | Rule |
|--------|--------|------|
| Employee accesses unusual number of accounts | Critical | INT-001 |
| Employee modifies fraud case decision inappropriately | Critical | INT-002 |
| Employee account linked to suspicious activity | Critical | INT-003 |
| Employee disables fraud rules without authorization | Critical | INT-004 |
| Employee accesses own account from work systems | High | INT-005 |
| Employee login at unusual hours | Medium | INT-006 |

**Syria Risk:** **MEDIUM** — Economic pressure increases insider threat risk. Limited background check infrastructure. Must have strong access controls and monitoring.

**Prevention:**

- Strict role-based access control
- All actions logged and audited
- Dual control for sensitive actions (fraud override, account unfreeze)
- Background checks for fraud team employees
- Separation of duties (investigator ≠ maker of final decision)
- Regular access review and recertification
- Anomaly detection on employee actions
- Mandatory vacations (fraud uncovered during absence)

### 10. Additional Fraud Types

| Type                          | Syria Risk | Description                                               | Detection                             |
| ----------------------------- | ---------- | --------------------------------------------------------- | ------------------------------------- |
| **Friendly Fraud**            | Medium     | User disputes legitimate transaction as fraud             | Behavioral analysis, device matching  |
| **Chargeback Fraud**          | Low        | Merchant claims customer didn't pay (digital goods)       | Transaction confirmation matching     |
| **Round Amount Fraud**        | Medium     | Fraudsters use round amounts (50K, 100K, 250K, 500K)      | Amount pattern rule                   |
| **Micro-Fraud / Structuring** | High       | Multiple small transactions under radar                   | Velocity + cumulative amount rules    |
| **Ghost Agent**               | High       | Fraudster poses as Beza agent, collects cash              | Agent registration verification       |
| **Fake Remittance**           | Medium     | Fraudster claims to send remittance, doesn't              | Remittance confirmation matching      |
| **Cash-out Mule**             | High       | Mule receives funds and immediately cashes out            | Velocity + immediate cash-out pattern |
| **Abandoned Cart Fraud**      | Low        | Fraud uses testing accounts left dormant                  | Account activity monitoring           |
| **SYP Arbitrage**             | Low        | Exploit rate differences between official/parallel market | Amount ratio monitoring               |
| **Promo Abuse**               | Medium     | Create multiple accounts to exploit referral/cashback     | Device/IP duplicate detection         |

## Fraud Type Risk Matrix

```
┌────────────────────────────────────────────────────────────────┐
│  RISK MATRIX: FRAUD TYPES IN SYRIA                            │
│                                                               │
│  HIGH IMPACT        │ Account Takeover  │ SIM Swap           │
│                     │ Agent Fraud       │ Social Engineering  │
│                     │ Phishing          │                    │
│                     ──────────────────────────────────────────┤
│  MEDIUM IMPACT      │ Mule Accounts     │ Insider Fraud      │
│                     │ Friendly Fraud    │ Structuring        │
│                     │ Ghost Agent       │                    │
│                     ──────────────────────────────────────────┤
│  LOW IMPACT         │ Synthetic ID      │ Merchant Collusion │
│                     │ Chargeback Fraud  │ SYP Arbitrage      │
│                     ──────────────────────────────────────────┤
│                     HIGH LIKELIHOOD     │ MEDIUM LIKELIHOOD   │
└────────────────────────────────────────────────────────────────┘
```
