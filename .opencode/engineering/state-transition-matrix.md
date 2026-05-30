# State Transition Matrix — Beza Platform

> **Last Updated:** 2026-05-29
> **Owner:** Platform Engineering
> **Scope:** All domain entities with state machines

---

## 1. Wallet States

| # | From State | To State | Trigger | Guard | Valid Roles | System Action | Side Effects | Timestamp |
|---|-----------|---------|---------|-------|-------------|---------------|--------------|-----------|
| 1 | `pending` | `active` | First successful transaction | User verified, PIN set | System | Enable all wallet features | Send welcome notification | First txn time |
| 2 | `active` | `frozen` | Compliance flag raised | Must have active compliance case | Compliance, Admin | Block all debits, allow credits | Notify user, freeze related cards | Now |
| 3 | `active` | `frozen` | Fraud alert (score > 900) | Fraud case opened | System, Fraud | Block all debits, allow credits | Notify user + support, create fraud case | Now |
| 4 | `active` | `frozen` | Court order / legal hold | Legal document reference | Super Admin | Block all movements | Notify compliance team, preserve balance | Now |
| 5 | `active` | `dormant` | No activity for 180 days | No pending txns, balance ≥ 0 | System | Restrict to read-only | Send dormant notification + reminder | Last activity + 180d |
| 6 | `dormant` | `active` | Any successful auth | User re-authenticates | User, System | Full restore | Remove dormant flag | Now |
| 7 | `frozen` | `active` | Compliance review cleared | Case resolved, risk score < 500 | Admin, Compliance | Restore all functions | Notify user of unfreeze | Review time |
| 8 | `frozen` | `closed` | Final disposition | 90 days frozen + legal approval | Super Admin | Initiate closure process | Notify user, schedule balance payout | Decision time |
| 9 | `active` | `closed` | User initiated closure | 7-day cooldown period observed | User, System | Final balance payout to bank, soft delete | Send closure confirmation, tax report | Cooldown end |
| 10 | `active` | `closed` | Admin forced closure | Fraud, death, legal requirement | Super Admin, Legal | Force payout or forfeit, hard close | Notify legal, document justification | Decision time |
| 11 | `closed` | `archived` | 365 days after closure | No outstanding balance | System | Anonymize PII, remove from active queries | Delete soft references | Closure + 365d |
| 12 | `any` | `under_review` | Suspicious activity flagged | System flag or manual | System, Compliance | Hold all pending operations | Notify compliance team | Flag time |

## 2. Transaction States

| # | From State | To State | Trigger | Guard | Valid Roles | System Action | Side Effects | Timestamp |
|---|-----------|---------|---------|-------|-------------|---------------|--------------|-----------|
| 13 | `initiated` | `pending` | Client submits transaction | Idempotency key unique, all validation passed | System | Generate txn_id, prepare for CFE | Start SLA timer | Now |
| 14 | `pending` | `held` | CFE hold successful | Sufficient balance, daily limit not exceeded | System | Reserve amount in ledger, set hold expiry | Decrement available balance | Hold time |
| 15 | `held` | `processing` | Fraud screening passing | Risk score < 900, no compliance block | System | Initiate posting workflow | Log fraud score | Screen time |
| 16 | `held` | `failed` | Fraud block | Risk score ≥ 900 | System | Release hold, notify sender | Create fraud alert, increment fail counter | Screen time |
| 17 | `held` | `failed` | Insufficient balance at post | Race condition, balance changed | System | Release hold, log discrepancy | Alert ops for reconciliation | Post attempt |
| 18 | `held` | `failed` | Hold expired | TTL exceeded (15 min) | System | Release hold automatically | Notify sender "Transaction expired" | Hold expiry |
| 19 | `processing` | `completed` | CFE posting successful (both legs) | All ledger entries balanced | System | Mark as completed, schedule notifications | Send push/SMS to both parties | Post time |
| 20 | `processing` | `failed` | CFE posting failed (partial) | Debit succeeded, credit failed | System | Reverse debit immediately, mark failed | Alert ops for reconciliation | Post time |
| 21 | `completed` | `disputed` | User initiates dispute within 24h | Txn within dispute window | User, Support | Freeze amount in dispute hold, create dispute ticket | Email support team, notify compliance | Dispute time |
| 22 | `completed` | `flagged` | Post-txn fraud detection | ML model or rule triggers | System | Flag for manual review, temporary hold | Alert fraud ops team | Detection time |
| 23 | `disputed` | `under_review` | Support team takes case | Dispute assigned | Support, Admin | Lock transaction amount, start SLAs | Notify user case in progress | Assignment |
| 24 | `under_review` | `resolved_sender` | Support decides for sender | Admin approval, evidence reviewed | Admin, Support | Reverse transaction to sender, deduct from receiver | Notify both parties, log decision | Resolution |
| 25 | `under_review` | `resolved_receiver` | Support decides for receiver | Admin approval, evidence reviewed | Admin, Support | Release dispute hold to receiver | Notify both parties, log decision | Resolution |
| 26 | `under_review` | `resolved_split` | Partial refund agreed | Both parties accept, admin approves | Admin | Reverse partial amount, release remainder | Notify both parties of split | Resolution |
| 27 | `flagged` | `completed` | Manual review clears | Admin confirms legitimate | Admin, Fraud Ops | Release temporary hold, close alert | Log review outcome | Clear time |
| 28 | `flagged` | `disputed` | Manual review confirms issue | Admin flags as suspicious | Admin, Fraud Ops | Convert to dispute, freeze amount | Notify support team | Flag time |
| 29 | `completed` | `reversed` | Admin reversal (corrective) | Admin + 2FA, reason recorded | Admin, Super Admin | Reverse full amount, reverse fees | Notify both parties, audit log entry | Reversal time |
| 30 | `reversed` | — | Terminal state | — | — | — | — | — |
| 31 | `failed` | — | Terminal state | — | — | — | — | — |

## 3. Agent States

| # | From State | To State | Trigger | Guard | Valid Roles | System Action | Side Effects | Timestamp |
|---|-----------|---------|---------|-------|-------------|---------------|--------------|-----------|
| 32 | `lead` | `documents_submitted` | Agent submits KYC + application | All required docs provided | Agent, System | Create agent profile, queue for review | Notify onboarding team | Submission |
| 33 | `documents_submitted` | `under_review` | Compliance officer picks up case | Case assigned | Compliance, Admin | Lock documents for review, start SLA timer | Notify agent "Under review" | Assignment |
| 34 | `under_review` | `active` | KYC approved, training complete | Field visit report positive, training test passed | Compliance, Admin | Enable agent portal, assign float limit, set commission | Send onboarding SMS, print agent QR code | Approval |
| 35 | `under_review` | `rejected` | KYC failed, documents invalid | Clear reason documented | Compliance, Admin | Reject application, notify agent | Notify agent with rejection reason + appeal process | Rejection |
| 36 | `under_review` | `documents_resubmitted` | Agent resubmits after rejection | Within 30-day resubmission window | Agent, System | Reset review queue, re-assign reviewer | Notify compliance of resubmission | Resubmission |
| 37 | `active` | `suspended` | Fraud alert triggered | Suspicious activity detected | Admin, Fraud Ops | Disable all agent operations, freeze float | Notify agent, schedule investigation | Suspension |
| 38 | `active` | `suspended` | Float mismatch > 15% | Variance exceeds threshold | Admin, Finance | Disable cash-in/out, allow settlement | Trigger float reconciliation | Detection |
| 39 | `active` | `suspended` | Inactivity > 90 days | No transactions | System | Soft suspend, retain data | Send re-activation offer | 90d inactivity |
| 40 | `suspended` | `active` | Investigation cleared | Root cause resolved, retraining completed | Admin, Compliance | Restore all operations, reset float if needed | Notify agent of reactivation | Clearance |
| 41 | `suspended` | `terminated` | Repeated violations | 3+ suspensions or single major violation | Admin, Legal | Force settlement, close agent account | Send termination notice, final settlement | Termination |
| 42 | `active` | `terminated` | Agent resignation | 30-day notice given, settlement completed | Agent, Admin | Close account, float recovery, final commission | Exit interview, handover of materials | Resignation |
| 43 | `active` | `terminated` | Legal/regulatory action | Court order, CBS directive | Super Admin, Legal | Immediate freeze, force settlement | Legal hold on records | Legal order |
| 44 | `active` | `upgraded` | Higher tier approved | Increased float limit, extended services | Admin | Assign new commission rates, new limits | Notify agent of new capabilities | Upgrade |
| 45 | `terminated` | `archived` | 365 days after termination | No outstanding legal matters | System | Anonymize PII, soft delete | Remove from agent directory | 365d post-termination |

## 4. KYC/User Tier States

| # | From State | To State | Trigger | Guard | Valid Roles | System Action | Side Effects | Timestamp |
|---|-----------|---------|---------|-------|-------------|---------------|--------------|-----------|
| 46 | `tier_1` (unverified) | `tier_1_pending` | User uploads basic documents | Documents valid format | User, System | Queue for automated verification | Show "Pending verification" status | Upload |
| 47 | `tier_1_pending` | `tier_1` (verified) | Automated verification passes | OCR matched, no watchlist hit | System | Confirm basic tier, increase limits | Send SMS: "KYC Tier 1 approved" | Verification |
| 48 | `tier_1_pending` | `tier_1` (rejected) | Automated verification fails | OCR mismatch, document expired | System, Compliance | Notify user, allow re-upload | Show reason for rejection | Rejection |
| 49 | `tier_1` (verified) | `tier_2_pending` | User submits Tier 2 upgrade | National ID + selfie + proof of address | User, System | Queue for manual compliance review | Notify compliance team | Upgrade request |
| 50 | `tier_2_pending` | `tier_2` (verified) | Human review approves | Documents verified, liveness check passed | Compliance, Admin | Upgrade to Tier 2, increase limits dramatically | Send SMS: "Tier 2 approved — increased limits" | Approval |
| 51 | `tier_2_pending` | `tier_1` (verified) | Human review rejects | Documents invalid or suspicious | Compliance, Admin | Revert to Tier 1, notify user | Store rejection reason, allow limited re-upload | Rejection |
| 52 | `tier_2` (verified) | `tier_3_pending` | User requests Tier 3 upgrade | In-person verification scheduled | User, Admin | Generate appointment, assign agent/branch | Send appointment confirmation | Scheduling |
| 53 | `tier_3_pending` | `tier_3` (verified) | In-person verification complete | Agent verifies ID, photo taken, biometric match | Agent, Admin | Enable highest limits, all features | Send SMS: "Tier 3 approved — full access" | Verification |
| 54 | `tier_3_pending` | `tier_2` (verified) | In-person verification fails | Identity mismatch, documents forged | Agent, Compliance | Revert to Tier 2, flag for review | Generate compliance report | Failure |
| 55 | `any_tier` | `kyc_revoked` | Compliance investigation | Fraud confirmed, identity theft | Compliance, Admin | Downgrade to minimum tier, freeze high-value features | Notify user, legal review | Revocation |

## 5. Remittance States

| # | From State | To State | Trigger | Guard | Valid Roles | System Action | Side Effects | Timestamp |
|---|-----------|---------|---------|-------|-------------|---------------|--------------|-----------|
| 56 | `draft` | `submitted` | User confirms remittance | PIN verified, amount within limits | User, System | Debit sender wallet, hold funds, generate remittance ID | Lock FX rate for 15 min | Submission |
| 57 | `submitted` | `screening` | AML/sanctions check initiated | Beneficiary details provided | System | Send to World-Check, OFAC, EU sanctions lists | Start compliance timer | Submission + 0s |
| 58 | `screening` | `queued` | Screening passed | No match, or false positive confirmed | System, Compliance | Queue for MTO submission | Updates sender on status | Screening pass |
| 59 | `screening` | `blocked` | Sanctions/PEP hit confirmed | Real match, compliance officer confirms | Compliance, Admin | Block remittance, reverse debit, file SAR | Notify sender of block, legal review | Screening hit |
| 60 | `screening` | `manual_review` | Fuzzy match requires review | Score between 80-95% | System | Notify compliance officer for manual review | Pause SLA timer | Fuzzy match |
| 61 | `manual_review` | `queued` | Compliance officer clears | Assessed as false positive | Compliance, Admin | Move to queue | Log review decision | Clearance |
| 62 | `manual_review` | `blocked` | Compliance officer confirms | Real match confirmed | Compliance, Admin | Block, reverse, file SAR | Full documentation required | Confirmation |
| 63 | `queued` | `sent_to_mto` | MTO API receives transfer | MTO accepts transaction | Remittance | Submit to MTO partner (Western Union, MoneyGram, etc.) | Receive MTO tracking ID | Submission to MTO |
| 64 | `sent_to_mto` | `paid_out` | MTO confirms beneficiary paid | MTO status = PAID | System (webhook) | Mark remittance as complete, update ledger | Notify sender + beneficiary, store proof | MTO callback |
| 65 | `sent_to_mto` | `failed_mto` | MTO rejects or timeout | MTO status = FAILED or no response in 2h | System | Reverse debit, notify sender, retry new MTO | Full refund to wallet | MTO rejection |
| 66 | `paid_out` | `completed` | Final confirmation | All checks passed, 24h elapsed | System | Close remittance, archive | Send final receipt | Completion |
| 67 | `failed_mto` | `refunded` | Funds returned to wallet | Reversal posted to ledger | System | Credit sender wallet, deduct from remittance payable | Notify sender of refund | Reversal |
| 68 | `refunded` | — | Terminal state | — | — | — | — | — |

## 6. FX Quote States

| # | From State | To State | Trigger | Guard | Valid Roles | System Action | Side Effects | Timestamp |
|---|-----------|---------|---------|-------|-------------|---------------|--------------|-----------|
| 69 | `pending` | `locked` | Rate fetched, user confirms | Rate within acceptable spread, user accepts | User, System | Lock rate for 15 seconds (real-time) or 15 min (remittance) | Store locked rate in quote record | Lock |
| 70 | `locked` | `converted` | User submits conversion | Sufficient funds, within time window | User, System | Execute both legs of conversion, post to ledger | Update wallet balances | Conversion |
| 71 | `locked` | `expired` | Time window exceeded | 15s (real-time) or 15min (remittance) elapsed | System | Release rate lock, notify user | Show stale rate warning to user | Expiry |
| 72 | `locked` | `cancelled` | User cancels | User action before expiry | User, System | Release rate lock, no charge | Return to FX screen | Cancellation |
| 73 | `converted` | `completed` | Reconciliation pass | All entries match | System | Close quote record | Archive for audit | EOD |
| 74 | `expired` | — | Terminal state | — | — | — | — | — |
| 75 | `cancelled` | — | Terminal state | — | — | — | — | — |

## 7. Settlement Batch States

| # | From State | To State | Trigger | Guard | Valid Roles | System Action | Side Effects | Timestamp |
|---|-----------|---------|---------|-------|-------------|---------------|--------------|-----------|
| 76 | `calculating` | `calculated` | EOD aggregation complete | All transactions before cut-off processed | System | Generate settlement summary per entity | Lock new transactions, begin processing | 23:30 |
| 77 | `calculated` | `pending_approval` | Summary generated | Variance within tolerance (±0.01%) | System | Send summary to finance team | Notify finance of pending settlement | 23:35 |
| 78 | `calculated` | `failed` | Reconciliation mismatch | Variance > tolerance, GL out of balance | System | Alert ops, halt settlement, flag for investigation | Page finance team, run reconciliation report | 23:35 |
| 79 | `pending_approval` | `approved` | Finance admin approves | 2FA verification, balance confirmed | Finance, Admin | Proceed to posting | Log approval with user ID | Approval |
| 80 | `pending_approval` | `rejected` | Finance rejects | Irregularity detected | Finance, Admin | Hold settlement, notify ops | Create investigation ticket | Rejection |
| 81 | `approved` | `posting` | CFE batch posting initiated | Sufficient bank balance confirmed | System | Post all settlement entries | Send confirmation to finance | Posting start |
| 82 | `posting` | `posted` | All CFE entries successful | Double-entry balanced per batch | System | Mark settlement batch as complete | Generate settlement files (CSV) | Posting complete |
| 83 | `posting` | `posting_failed` | Partial CFE failure | Ledger out of balance | System | Halt posting, reversal of partial entries | Page ops + engineering immediately | Failure |
| 84 | `posted` | `bank_submitted` | Settlement files sent to bank | SFTP transfer successful | System | Upload CSV to BSO/Bemo/SIIB | Log file transfer confirmation | File transfer |
| 85 | `posted` | `bank_failed` | SFTP transfer failed | Network or auth error | System | Retry 3x, then alert ops | Manual file upload required | Transfer fail |
| 86 | `bank_submitted` | `settled` | Bank confirms receipt (next day) | Bank statement reconciliation | Finance, System | Mark settlement as final, archive | Update GL with bank charges | T+1 |

## 8. Card States (If V1.5+)

| # | From State | To State | Trigger | Guard | Valid Roles | System Action | Side Effects |
|---|-----------|---------|---------|-------|-------------|---------------|--------------|
| 87 | `ordered` | `printed` | Card manufacturer confirms | Card design approved | System, Partner | Generate card number, CVV, PIN | Send to activation queue |
| 88 | `printed` | `dispatched` | Card shipped to agent/branch | Carrier confirmed | Partner | Update tracking info | Notify user of dispatch |
| 89 | `dispatched` | `delivered` | Agent confirms delivery | User ID verified | Agent, System | Mark as delivered, ready for activation | Notify user to activate |
| 90 | `delivered` | `active` | User activates via PIN/biometric | User confirmed receipt, PIN set | User, System | Enable card transactions, set daily limits | Send activation SMS |
| 91 | `active` | `temporarily_blocked` | Suspicious transaction | Fraud rule triggered | System, User | Block transactions, notify user | Send "Did you make this transaction?" alert |
| 92 | `temporarily_blocked` | `active` | User confirms legitimate | User confirms via app | User, System | Unblock card, adjust fraud model if false positive | Log user confirmation |
| 93 | `active` | `permanently_blocked` | User reports lost/stolen | User action | User, System | Block permanently, order replacement | Notify support, send replacement flow |
| 94 | `active` | `expired` | Card validity period ended | Expiry date passed | System | Disable card, send renewal offer | Archive card data per PCI DSS |
| 95 | `permanently_blocked` | — | Terminal state | — | — | — | — |

## 9. Fraud Case States

| # | From State | To State | Trigger | Guard | Valid Roles | System Action | Side Effects |
|---|-----------|---------|---------|-------|-------------|---------------|--------------|
| 96 | `auto_flagged` | `open` | Transaction risk score ≥ threshold | Score between 700-900 | System | Create fraud case, assign to queue, notify fraud ops | Increment fraud counter on user profile |
| 97 | `auto_flagged` | `dismissed` | Risk score < 700 re-evaluation | Follow-up check shows false positive | System | Auto-close case, no action | Update ML model |
| 98 | `open` | `investigating` | Fraud analyst picks up case | Case assigned to user | Fraud Ops | Lock case, start SLA timer (4h for P0) | Notify user if needed |
| 99 | `investigating` | `confirmed` | Evidence supports fraud | Transaction pattern analysis, user admission | Fraud Ops, Admin | Freeze wallet, escalate to compliance | File incident report, prepare SAR |
| 100 | `investigating` | `dismissed` | No evidence found | User verified legitimate, transaction normal | Fraud Ops | Close case, release any holds | Log investigation notes |
| 101 | `investigating` | `escalated` | Requires legal action | Criminal activity suspected | Fraud Ops, Legal | Transfer to legal team, notify authorities | Preserve all evidence |
| 102 | `confirmed` | `resolved` | Recovery completed or write-off | Funds recovered or loss accepted | Admin, Finance | Close case, update financials | Update risk models with outcome |
| 103 | `escalated` | `legal_proceedings` | Authorities take up case | Police/financial regulator involved | Legal, Admin | Cooperate with authorities, provide data | Legal hold on accounts |
| 104 | `dismissed` | — | Terminal state | — | — | — | — |
| 105 | `resolved` | — | Terminal state | — | — | — | — |

## 10. Compliance Case States

| # | From State | To State | Trigger | Guard | Valid Roles | System Action | Side Effects |
|---|-----------|---------|---------|-------|-------------|---------------|--------------|
| 106 | `triggered` | `screening` | User action or scheduled check | Any of: new user, high-value txn, periodic review | System | Run AML/PEP screening, calculate risk score | Create compliance record |
| 107 | `screening` | `cleared` | All checks pass | No matches, risk score < threshold | System | Close case, no further action | Update last screening date |
| 108 | `screening` | `alerted` | Match found or risk score elevated | Score above threshold, fuzzy match 80-95% | System | Create alert, queue for analyst review | Notify compliance team |
| 109 | `screening` | `escalated` | Exact sanctions/PEP match | Score ≥ 95%, confirmed identity | System | Freeze wallet, flag account, notify MLRO | Create immediate SAR draft |
| 110 | `alerted` | `under_review` | Compliance analyst picks up | Analyst assigned | Compliance | Lock alert, start review timer | Open case file |
| 111 | `under_review` | `cleared` | False positive confirmed | Evidence supports clearance, analyst approves | Compliance | Close case, update user risk profile | Log clearance rationale |
| 112 | `under_review` | `escalated` | Match confirmed | Analyst determines real risk | Compliance | Escalate to MLRO, freeze account | Begin SAR process |
| 113 | `escalated` | `sar_filed` | SAR submitted to FIU/CBS | MLRO approval, legal review | Compliance, Legal | File SAR with regulatory body | Maintain SAR register |
| 114 | `escalated` | `cleared` | MLRO overrules escalation | Further evidence exonerates | MLRO, Super Admin | Close case, update risk profile, unfreeze | Document MLRO decision |
| 115 | `sar_filed` | `authorities_contacted` | Regulator requests further info | FIU/CBS inquiry received | Legal, Compliance | Provide additional data as required | Legal hold on all related records |
| 116 | `sar_filed` | `closed` | Regulator accepts SAR | No further action required | System, Compliance | Close case, archive | Update case outcome |
| 117 | `cleared` | — | Terminal state | — | — | — | — |
| 118 | `closed` | — | Terminal state | — | — | — | — |

## 11. Notification States

| # | From State | To State | Trigger | Guard | Valid Roles | System Action | Side Effects |
|---|-----------|---------|---------|-------|-------------|---------------|--------------|
| 119 | `queued` | `picked_up` | Worker picks from queue | Queue not empty | System | Assign to SMS/Push/Email channel | Start delivery timer |
| 120 | `picked_up` | `sent` | Provider accepts request | SMPP/REST response OK | System (External) | Mark as sent, wait for delivery receipt | Log provider reference |
| 121 | `picked_up` | `failed` | Provider rejects (invalid number, rate limit) | Provider error response | System | Increment retry counter, check max retries | Route to fail queue |
| 122 | `picked_up` | `failed` | Timeout (no response in 30s) | Network issue, provider down | System | Retry up to 3x with exponential backoff | Alert ops if provider down |
| 123 | `sent` | `delivered` | Delivery receipt received | Provider callback | System (External) | Mark as delivered, measure latency | Update delivery stats |
| 124 | `sent` | `delivery_failed` | Delivery failure receipt | Provider sends FAILURE | System (External) | Mark as undelivered, log reason | CSP fallback channel |
| 125 | `sent` | `delivery_unknown` | No receipt within 60 min | Provider doesn't send receipt | System | Mark as unknown, log for reconciliation | Periodic delivery audit |
| 126 | `failed` | `queued` | Retry attempt | Retry count < max (3) | System | Re-queue with exponential backoff | Update retry counter |
| 127 | `failed` | `dead_letter` | Max retries exceeded | Retry count = max (3) | System | Move to dead letter queue, alert ops | Manual intervention required |
| 128 | `delivered` | — | Terminal state | — | — | — | — |
| 129 | `delivery_failed` | — | Terminal state | — | — | — | — |
| 130 | `dead_letter` | — | Terminal (requires manual fix) | — | — | — | — |

## 12. Scheduled Task States (Payroll, Recurring Payments)

| # | From State | To State | Trigger | Guard | Valid Roles | System Action | Side Effects |
|---|-----------|---------|---------|-------|-------------|---------------|--------------|
| 131 | `scheduled` | `executing` | Cron trigger fires | Schedule time reached, task active | System | Begin batch execution | Lock schedule, prevent re-execution |
| 132 | `executing` | `partially_completed` | Some items succeed, some fail | Partial batch failure | System | Record per-item state, retry failed items | Queue failed items for retry |
| 133 | `executing` | `completed` | All items succeed | All transactions posted | System | Mark batch complete, update schedule | Send summary notification |
| 134 | `executing` | `failed` | Critical system error | DB down, provider down, total failure | System | Mark batch failed, retry at next window | Page engineering team |
| 135 | `partially_completed` | `retrying` | Retry window triggered | 5 min after partial failure | System | Attempt failed items only | Notify support of individual failures |
| 136 | `retrying` | `completed` | All retried items succeed | All items resolved | System | Mark batch complete | Send partial success notification |
| 137 | `retrying` | `failed` | Retry fails again | Max retries exhausted | System | Escalate to manual processing | Create support ticket |
| 138 | `completed` | — | Terminal state | — | — | — | — |
| 139 | `failed` | — | Terminal state | — | — | — | — |

---

## 13. State Transition Summary by Entity

| Entity | States | Transitions | Notable |
|--------|--------|-------------|---------|
| Wallet | 7 | 12 | freeze → unfreeze, dormant → active |
| Transaction | 10 | 19 | dispute lifecycle, reversal |
| Agent | 8 | 14 | suspension due to float mismatch |
| KYC/Tier | 8 | 10 | tier upgrades, revocation |
| Remittance | 9 | 13 | MTO integration states |
| FX Quote | 5 | 7 | rate lock expiration |
| Settlement Batch | 9 | 10 | bank settlement lifecycle |
| Card | 6 | 9 | lost/stolen flow |
| Fraud Case | 7 | 10 | auto-flagged → resolved |
| Compliance Case | 8 | 12 | SAR filing flow |
| Notification | 8 | 11 | retry + dead letter |
| Scheduled Task | 5 | 9 | partial completion handling |
| **Total** | **90** | **136** | — |

---

*End of State Transition Matrix. 136 state transitions across 12 domain entities documented.*
