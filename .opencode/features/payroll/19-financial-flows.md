# 19 — Financial Flows & Settlement

> **Key File** — Money movement diagrams and rules.

---

## Flow 1: Standard Salary Batch (Pre-funded)

```
┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│   Company    │     │     Beza     │     │  Employees  │
│  Account     │     │   Payroll    │     │   Wallets   │
│  (CFE)       │     │   Engine     │     │   (CFE)     │
└──────┬───────┘     └──────┬───────┘     └──────┬──────┘
       │                    │                    │
       │  Fund Account      │                    │
       │ (bank transfer)    │                    │
       │───────────────────>│                    │
       │                    │                    │
       │  Step 1:           │                    │
       │  Check Balance     │                    │
       │<──────────────────>│                    │
       │                    │                    │
       │  Step 2: Hold      │                    │
       │  total_amount+fee  │                    │
       │<───────────────────│                    │
       │  hold_ref = H-001  │                    │
       │                    │                    │
       │  Step 3: Process   │                    │
       │  for each employee:│                    │
       │                    │───────────────────>│
       │                    │  Credit wallet     │
       │                    │  transaction_id    │
       │                    │<───────────────────│
       │                    │  200 OK            │
       │                    │                    │
       │  Step 4: Release   │                    │
       │  remaining hold    │                    │
       │<───────────────────│                    │
       │  release H-001     │                    │
       │                    │                    │
       │  Step 5: Debit fee │                    │
       │<───────────────────│                    │
       │  fee deducted      │                    │
       │                    │                    │
       │  Step 6: Notify    │                    │
       │                    │───────────────────>│
       │                    │  Push + SMS        │
       │                    │                    │
```

### Detailed Steps

| Step | Action | CFE Call | Amount |
|------|--------|----------|--------|
| 1 | Validate company balance | `GET /accounts/{id}/balance` | — |
| 2 | Place hold | `POST /holds` | total_amount + total_fee |
| 3a | Credit employee wallet | `POST /wallets/{id}/credit` | employee.amount |
| 3b | Record PayrollTransaction | DB INSERT | employee.amount |
| 4 | Repeat 3a–3b for all employees | — | — |
| 5 | Release remaining hold | `DELETE /holds/{ref}` | hold - sum(credited) - actual_fee |
| 6 | Debit service fee | `POST /accounts/{id}/debit` | actual_fee |
| 7 | Generate payslips | PayslipGenerator | — |
| 8 | Send notifications | Push + SMS | — |

---

## Flow 2: Failed Payment — Retry Cycle

```
  [Batch Processing]
        │
        │ Employee #42: credit failed
        │ (reason: wallet_not_active)
        ▼
  ┌──────────────┐
  │ Transaction  │  status = failed, retry_count = 0
  │ Recorded     │
  └──────┬───────┘
         │
         ├─── 5 min later ─── Retry #1 ─── failed (still not active)
         ├─── 30 min later ── Retry #2 ─── failed
         ├─── 2 hours later ─ Retry #3 ─── failed
         │
         ▼
  ┌──────────────┐
  │ Status →     │  failed_permanent
  │ Notify HR    │
  └──────┬───────┘
         │
         │ HR contacts employee → employee activates wallet
         │ HR clicks "Retry" in dashboard
         ▼
  ┌──────────────┐
  │ Manual Retry │  retry_count reset → attempts → success
  │ Transaction  │  status = success, paid_at = now
  └──────────────┘
```

### Retry Rules

| Attempt | Timing | Action on Failure |
|---------|--------|-------------------|
| 1 | 5 min after initial failure | Log + schedule next |
| 2 | 30 min after attempt 1 | Log + schedule next |
| 3 | 2 hours after attempt 2 | Mark `failed_permanent`, notify company |
| Manual | HR-triggered | Reset retry_count, attempt immediately, follow same 3x logic |

---

## Flow 3: Settlement Models

### Pre-funded (T+0)

```
Company sends bank transfer → Beza ledger → Company available balance
  │
  ├── Payroll Day: Balance = SYP 100M, Payroll = SYP 95M
  ├── Hold = SYP 95M + fees → Process → Release remaining
  ├── Balance after = SYP 5M (or less if fees deducted)
  └── No settlement needed — already funded
```

### T+1 Settlement (Trusted Companies)

```
Company has settlement_limit = SYP 200M
  │
  ├── Payroll Day: Balance = SYP 0, Payroll = SYP 95M
  ├── Beza fronts SYP 95M (no hold needed on company balance)
  ├── Company owes Beza SYP 95M + T+1 fee (0.25 % = SYP 237,500)
  ├── Due date: next business day (T+1)
  ├── If unpaid by T+1 17:00 → escalation
  │     ├── Auto-send reminder
  │     ├── Freeze new batches
  │     └── If T+3 overdue → suspend company, legal escalation
  └── On settlement: Beza receives SYP 95,237,500
```

### Settlement Thresholds

| Settlement Period | Fee | Limit | Eligibility |
|-----------------|-----|-------|-------------|
| T+0 (pre-fund) | 0.5 % | No limit | All companies |
| T+1 | 0.75 % (0.5 + 0.25) | SYP 200M max | 12+ months on platform, > 95 % batch success rate |
| T+3 | 1.0 % (0.5 + 0.5) | SYP 100M max | 6+ months, negotiated |

---

## Accounting Entries (Double-Entry)

| Event | Debit | Credit | Amount |
|-------|-------|--------|--------|
| Company funds account | Cash (Beza bank) | Company liability | SYP 100M |
| Batch hold placed | Company liability (hold) | Company liability (available) | SYP 95.5M |
| Employee wallet credited | Company liability (hold) | Employee wallet liability | SYP 95M |
| Service fee earned | Company liability (hold) | Beza fee income | SYP 475,000 |
| Hold released | Company liability (available) | Company liability (hold) | SYP 25,000 |
| T+1 settlement due | Company receivable | Deferred revenue | SYP 95.5M |
| T+1 settlement cleared | Cash (Beza bank) | Company receivable | SYP 95.5M |
