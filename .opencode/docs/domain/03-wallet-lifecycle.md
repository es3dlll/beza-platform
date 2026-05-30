# Wallet Lifecycle — State Machine

> Document version: 1.0.0
> Last updated: 2026-05-29
> Owner: Domain — Wallet & Payments

## 1. Wallet State Diagram

```
                 ┌──────────┐
                 │ PENDING  │
                 │  (initial)│
                 └────┬─────┘
                      │ First successful credit
                      ▼
                 ┌──────────┐
          ┌──────│  ACTIVE  │──────┐
          │      └──────────┘      │
          │           │            │
          ▼           ▼            ▼
    ┌──────────┐ ┌──────────┐ ┌──────────┐
    │ LIMITED  │ │SUSPENDED │ │  FROZEN  │
    │  (temp)  │ │ (review) │ │ (fraud)  │
    └──────────┘ └──────────┘ └──────────┘
          │           │            │
          └───────────┴────────────┘
                      │
                      ▼
                 ┌──────────┐
                 │  CLOSED  │
                 └──────────┘
```

## 2. Wallet Types

Beza supports two wallet currencies per customer:

| Currency | Wallet Code | Purpose |
|----------|-------------|---------|
| Syrian Pound | SYP | Primary wallet for local transactions, bill pay, cash-in/out, P2P, merchant |
| US Dollar | USD | Remittance receive, FX hold, merchant (select). Created on first USD remittance only |

Each wallet has an independent lifecycle. A customer may have zero, one, or both wallets.

## 3. State Definitions

### 3.1 PENDING

| Property | Value |
|----------|-------|
| **Entry** | Wallet created automatically upon user registration (SYP wallet) or upon first USD remittance (USD wallet) |
| **Properties** | Zero balance. No transactions allowed in or out. |
| **Balance** | Always 0.00 |
| **Allowed operations** | None. View-only in UI. |
| **Exit** | First successful credit (P2P receive, cash-in, remittance, cash-out reversal) |
| **Time limit** | If no credit within 30 calendar days of creation → auto-close wallet |
| **Auto-close notification** | Day 25: *"محفظتك بالليرة السورية غير نشطة. سيتم إغلاقها خلال 5 أيام في حال عدم استخدامها"* |
| **Notes** | A PENDING wallet cannot be manually closed by the user — only auto-closed. If user wants to close, they must wait 30 days or register a new account. |

### 3.2 ACTIVE

| Property | Value |
|----------|-------|
| **Entry** | First credit successfully settled on the wallet |
| **Properties** | Full send/receive capability within user's KYC tier limits |
| **Allowed operations** | Send (P2P, cash-out, merchant pay, bill pay, FX sell), Receive (P2P, cash-in, remittance, FX buy, bill refund), Hold balance, View history, View balance |
| **Balances** | Available balance = ledger balance - pending holds |
| **Pending hold types** | Merchant authorization hold (24h expiry), P2P send hold (30 min if unconfirmed), FX quote hold (5 min) |
| **Limits** | Per KYC tier (see 02-customer-lifecycle.md §5.2) |
| **Daily reset** | Limits reset at 00:00 Damascus time (UTC+3) |

### 3.3 LIMITED

| Property | Value |
|----------|-------|
| **Entry** | Temporary restriction for non-fraud reasons: suspicious activity flag (risk score 700–900), unusual location / IP geo mismatch, KYC expired, KYC rejected (max attempts reached), DORMANT → RESTRICTED customer state |
| **Properties** | Receive only. Send capability disabled. Balance viewable. |
| **Limit** | Max receive 100,000 SYP/day (or 50 USD/day for USD wallets) while in LIMITED state |
| **Exit** | Issue resolved: compliance clears the flag, location verified via additional OTP, KYC re-approved, customer state restored |
| **Time limit** | 7 calendar days → auto-escalate to SUSPENDED if unresolved |
| **Escalation notification** | Day 6: *"لم يتم حل مشكلة التقييد. سيتم تعليق المحفظة خلال 24 ساعة"* |
| **Combined with customer state** | If customer is RESTRICTED, ALL wallets become LIMITED simultaneously |
| **Partial restriction** | Only the flagged wallet is LIMITED; other wallets (e.g., USD) remain ACTIVE unless individually flagged |

### 3.4 SUSPENDED

| Property | Value |
|----------|-------|
| **Entry** | Escalated from LIMITED (7 days unresolved), multi-agent login detected (≥ 3 distinct devices in 1 hour), multiple failed PIN attempts (> 5 in 15 min), sanctions name similar match (fuzzy match >= 85%), compliance officer action |
| **Properties** | Both send AND receive blocked. User cannot transact at all on this wallet. Balance viewable only to compliance. |
| **Balance** | Frozen in place — no debits or credits possible |
| **Exit** | Investigation complete, compliance officer clears the wallet |
| **Time limit** | 14 calendar days → if unresolved, escalate to compliance management team |
| **Escalation notification** | Internal alert to compliance manager. No notification to user (investigation is confidential). |
| **Multi-wallet impact** | Only the flagged wallet is suspended unless the suspension reason is customer-level (e.g., sanctions match), in which case ALL wallets are suspended |
| **Re-activation guard** | Requires compliance officer + supporting evidence review |

### 3.5 FROZEN

| Property | Value |
|----------|-------|
| **Entry** | Confirmed fraud (risk score > 950, confirmed forgery, proven multi-account abuse), compliance block with case ID, court order received from Syrian courts, CBS directive |
| **Properties** | Completely frozen. No sends, no receives, no balance access (not even view). Balance hidden from user UI. |
| **Balance** | Preserved in database, held in suspense account |
| **Exit** | Only by compliance manager + 2-person approval (dual authorization). Court order must be vacated. |
| **Time limit** | Indefinite — until legal resolution or compliance clearance |
| **Notifications** | *"عذراً، تم تجميد محفظتك لأسباب أمنية. يرجى مراجعة فرع بيزا"* |
| **Support action** | P0 ticket, fraud case created in case management system, CBS suspicious transaction report filed |
| **Re-activation** | Extremely rare. Requires: compliance manager approval + fraud team lead approval + legal clearance (if court-ordered). All three signatures required. |
| **Balance disposal** | If frozen due to confirmed fraud with criminal investigation, balance may be forfeited per court order |

### 3.6 CLOSED

| Property | Value |
|----------|-------|
| **Entry** | User requests closure (7-day cooldown, balance must be zero), admin closure (death, legal order), account-level closure (all customer wallets closed together), auto-close (PENDING > 30 days) |
| **Properties** | Balance MUST be zero. No transactions possible. Wallet is read-only in archive. |
| **Pre-close payout** | If balance > 0 on closure request: user must cash-out or transfer remaining funds first. Cash-out to bank account (if available) or agent cash-out. |
| **Cooldown** | 7-day cooling-off period. User can cancel within this window. |
| **Data retention** | Soft delete: 30 days (reversible by admin support). After 30 days → hard delete of wallet record. Transaction records retained 5 years per CBS. |
| **Re-open** | Not possible. New wallet must be created via fresh registration or new wallet request. |
| **Notifications** | Request: *"تم استلام طلب إغلاق المحفظة. سيتم الإغلاق بعد 7 أيام. يمكنك إلغاء الطلب"*. Execution: *"تم إغلاق محفظتك. الرصيد المتبقي: 0 ل.س"* |

## 4. Transition Table

| From | To | Trigger | Guard | System Action |
|------|----|---------|-------|--------------|
| PENDING | ACTIVE | First credit transaction completed | Transaction successful, sender wallet ACTIVE | Update wallet state, enable all operations, send welcome notification |
| PENDING | CLOSED | 30 days no credit activity | Wallet created > 30 days ago, balance = 0 | Auto-close, log cleanup job, no notification (user never used it) |
| ACTIVE | LIMITED | Risk score 700–900 (automated) | Score threshold triggered, no manual review yet | Apply receive-only restriction, set LIMITED expiry +7 days, notify user |
| ACTIVE | LIMITED | KYC expired | KYC expiry date passed | Restrict to receive-only, send KYC renewal SMS |
| ACTIVE | LIMITED | Customer state = RESTRICTED | Customer lifecycle event `UserRestricted` | Mirror restriction to wallet, notify if not already notified |
| LIMITED | ACTIVE | Issue resolved (compliance clears) | Compliance officer action, or risk score re-evaluated < 700 | Remove restriction, restore all capabilities, send "تم رفع التقييد" SMS |
| LIMITED | SUSPENDED | 7 days unresolved (auto) | LIMITED expiry reached, no compliance action taken | Escalate to SUSPENDED, notify compliance team, send warning to user |
| LIMITED | SUSPENDED | Compliance officer action | Manual override for serious flags | Immediate escalation, skip 7-day timer |
| ACTIVE | SUSPENDED | Multi-agent login detected (>= 3 devices, 1 hour) | Automated detection rule | Suspend wallet, trigger compliance review, invalidate all sessions |
| ACTIVE | SUSPENDED | Failed PIN > 5 times in 15 min | PIN attempt counter threshold | Suspend wallet, send SMS: *"تم تعليق المحفظة بسبب محاولات دخول خاطئة. يرجى الاتصال بخدمة العملاء"* |
| ACTIVE | SUSPENDED | Sanctions fuzzy match >= 85% | Automated sanctions screening | Suspend ALL wallets, internal alert to compliance, manual review required |
| SUSPENDED | ACTIVE | Investigation cleared | Compliance officer + evidence review | Restore wallet to ACTIVE, notify user, log audit trail |
| SUSPENDED | FROZEN | Fraud confirmed during investigation | Fraud team confirmation + case ID | Freeze wallet, create fraud case, file CBS report, alert legal |
| ACTIVE | FROZEN | Confirmed fraud (risk score > 950) | Automated high-confidence detection | Immediate freeze, bypass SUSPENDED, P0 alert to fraud team |
| ACTIVE | FROZEN | Court order received | Legal team verification + order ID | Immediate freeze, document retention for legal, notify compliance |
| FROZEN | ACTIVE | Cleared after investigation | 2-person compliance approval + legal clearance (if court-ordered) | Unfreeze wallet, restore balance visibility, send notification |
| LIMITED | FROZEN | Fraud confirmed during LIMITED review | Fraud team action | Direct LIMITED → FROZEN (skip SUSPENDED) |
| ANY | CLOSED | User requests closure | Balance = 0, 7-day cooldown enforced or user confirms immediately | Start cooldown timer or execute immediate close |
| ANY | CLOSED | Admin closure (death, legal) | Admin role + legal documentation | Immediate closure, no cooldown, retain records |
| PENDING | CLOSED | User cancels registration | Within registration flow | Clean up wallet, no retention needed |
| CLOSED_(cooldown) | ACTIVE | User cancels closure request | Within 7-day cooldown window | Cancel closure, restore wallet to previous state, notify user |
| CLOSED | (none) | — | — | Terminal state — no transitions out |

## 5. Wallet Events

| Event | Trigger | Producer | Payload |
|-------|---------|----------|---------|
| `WalletActivated` | First credit settles | Transaction service | `{ wallet_id, currency, owner_id, activated_at, credit_txn_id }` |
| `WalletLimited` | Temp restriction applied | Risk engine / Compliance | `{ wallet_id, reason_code, risk_score (if auto), limited_by, expires_at }` |
| `WalletRestrictionLifted` | Issue resolved | Compliance service | `{ wallet_id, lifted_by, restored_state, resolution_note }` |
| `WalletSuspended` | Serious flag triggered | Risk engine / Compliance | `{ wallet_id, reason_code, triggered_by, device_ids[] (if multi-agent), suspension_ref }` |
| `WalletFrozen` | Confirmed fraud / court order | Fraud team / Legal | `{ wallet_id, case_id, blocked_by, frozen_at, legal_ref (if applicable) }` |
| `WalletClosed` | Closure executed | Self-service / Admin / Scheduler | `{ wallet_id, reason_code, closed_by, final_balance, closed_at }` |
| `ClosureRequested` | User initiates closure | Self-service | `{ wallet_id, requested_by, cooldown_ends_at }` |
| `ClosureCancelled` | User cancels within cooldown | Self-service | `{ wallet_id, cancelled_by, cancelled_at }` |

## 6. Wallet Balance & Holds

### 6.1 Balance Model

```
ledger_balance  = sum of all settled credits - sum of all settled debits
holds           = sum of all active holds (authorizations, pending sends)
available_balance = ledger_balance - holds
```

### 6.2 Hold Types

| Hold Type | Duration | Auto-Release | Description |
|-----------|----------|--------------|-------------|
| Merchant authorization | 24 hours | Yes, if not captured | Hold placed when user scans merchant QR and enters amount |
| P2P send hold | 30 minutes | Yes, if recipient not found | Hold placed when user initiates P2P send |
| FX quote hold | 5 minutes | Yes, if quote expires | Hold placed when user requests FX rate |
| Cash-out hold | 10 minutes | Yes, if agent does not confirm | Hold placed when user requests cash-out at agent |

### 6.3 Balance Check Rules (Pre-Transaction Guards)

| Operation | Balance Check |
|-----------|--------------|
| Send / P2P | available_balance >= txn_amount + fee |
| Cash-out | available_balance >= txn_amount + fee |
| Bill pay | available_balance >= txn_amount |
| Merchant pay | available_balance >= txn_amount |
| FX conversion | available_balance >= source_amount (for sell leg) |
| Hold placement | available_balance >= hold_amount |

## 7. Wallet Limits Per KYC Tier

| Tier | Max Balance (SYP) | Daily Send | Daily Receive | Monthly Send | Monthly Receive |
|------|-------------------|------------|---------------|--------------|-----------------|
| 1 | 500,000 SYP | 250,000 SYP | 500,000 SYP | 2,000,000 SYP | 5,000,000 SYP |
| 2 | 5,000,000 SYP | 2,000,000 SYP | 5,000,000 SYP | 20,000,000 SYP | 50,000,000 SYP |
| 3 | 20,000,000 SYP | 10,000,000 SYP | 20,000,000 SYP | 100,000,000 SYP | 200,000,000 SYP |

**USD wallet limits** (where applicable):

| Tier | Max Balance (USD) | Daily Send | Daily Receive | Monthly Send | Monthly Receive |
|------|-------------------|------------|---------------|--------------|-----------------|
| 2 | 500 USD | 200 USD | 500 USD | 1,000 USD | 2,000 USD |
| 3 | 2,000 USD | 500 USD | 2,000 USD | 3,000 USD | 5,000 USD |

## 8. Timeout & Expiry Summary

| Timer | Duration | From State | Action on Expiry |
|-------|----------|------------|------------------|
| PENDING inactivity | 30 days | PENDING | Auto-close wallet |
| LIMITED auto-escalation | 7 days | LIMITED → SUSPENDED | Escalate to compliance |
| SUSPENDED auto-escalation | 14 days | SUSPENDED → Management review | Escalate to compliance management |
| Merchant authorization hold | 24 hours | ACTIVE (hold) | Auto-release hold |
| P2P send hold | 30 minutes | ACTIVE (hold) | Auto-release hold if unconfirmed |
| FX quote hold | 5 minutes | ACTIVE (hold) | Auto-release hold |
| Cash-out hold | 10 minutes | ACTIVE (hold) | Auto-release hold if unconfirmed by agent |
| Closure cooldown | 7 days | CLOSED_(cooldown) | Execute closure (if not cancelled) |
| Soft delete retention | 30 days | CLOSED | Hard delete wallet record |

## 9. Wallet Creation Rules

1. SYP wallet created automatically on `UserRegistered` event
2. USD wallet created on first USD remittance received (not on registration)
3. Wallet ID format: `w_` + UUIDv4 (e.g., `w_a1b2c3d4-e5f6-7890-abcd-ef1234567890`)
4. Wallet number (user-facing): 15-digit numeric, first 3 digits = currency code (001 = SYP, 002 = USD), last 12 digits = unique sequential
5. Each customer can have exactly 1 SYP wallet and 1 USD wallet (no duplicate currencies)
6. Wallet creation is idempotent — if wallet exists for currency, return existing
