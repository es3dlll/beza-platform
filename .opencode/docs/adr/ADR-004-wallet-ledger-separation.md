# ADR-004: Wallet-Ledger Separation (Not Single Ledger)

## Status
Accepted

## Context
A core architectural decision for Beza Financial OS is how to model the relationship between user-facing Wallet balances and the accounting Ledger. Three approaches were evaluated:

**Option 1 — Single source (Wallet = Ledger):** Wallet balances ARE ledger accounts. Every wallet read queries the double-entry ledger directly. There is one truth.

**Option 2 — Wallet as cached view of Ledger (eventual consistency):** The Central Financial Engine (CFE) owns all financial postings. Wallet balances are read-optimized projections of ledger state, maintained via events. Ledger remains the single source of truth.

**Option 3 — Fully separated (CFE posts to both):** Wallet and Ledger are independent systems. The CFE posts the same transaction to both, and reconciliation detects mismatches. Neither is derived from the other.

Syria-specific considerations:

- **Regulatory environment:** The Syrian Central Bank (SCB) requires licensed financial institutions to maintain double-entry accounting records with audit trails. Wallet balances shown to users must be traceable to ledger entries. A system where wallet IS the ledger simplifies audit but couples user-facing performance to accounting throughput.

- **Reconciliation reality:** Syrian banks and mobile money operators perform end-of-day batch reconciliation. Real-time reconciliation (as in Option 3) would be novel but adds operational complexity that auditors and regulators are not equipped to validate.

- **Transaction volume:** V1 targets 500 TPS peak. Accounting-grade double-entry posting at 500 TPS with full referential integrity is possible with MySQL, but the query patterns are different: wallet reads need sub-millisecond responses, while ledger reads are analytical (range queries, aggregation, audit filters).

- **Accounting integrity requirement:** Financial transactions must not be lost. If a wallet shows a balance of SYP 1,000,000 but the ledger disagrees, the platform is untrustworthy. This is existential for a fintech operating in a low-trust environment.

## Decision
Adopt **Option 2: Wallet balances as cached views of the Ledger**, with the following architecture:

**Posting flow:**
```
1. Client request (e.g., Transfer) → validated by Wallet module for balance sufficiency
2. Wallet module sends command to CFE: CFE::debit($agentWallet, $amount)
3. CFE performs double-entry posting:
   - Debit: Agent Wallet (liability) account
   - Credit: Settlement (suspense) account
   - Both entries logged in ledger_transactions and ledger_entries tables
4. CFE emits event: LedgerEntryPosted { wallet_id, amount, new_ledger_balance, entry_id }
5. Wallet module listens for event, updates cached balance in:
   a. Redis (hot cache, TTL: 5 minutes, key: wallet:balance:{wallet_id})
   b. MySQL wallet_balances table (cold storage, updated via queue worker)
6. Wallet balance reads query Redis → fallback to wallet_balances → fallback to ledger aggregation
```

**Read path:**
- `GET /wallet/balance` → Redis hit (99th percentile: 2ms) → MySQL `wallet_balances` (50ms) → Ledger aggregation (200ms+)
- If Redis is down, serve from `wallet_balances` table and warm Redis asynchronously

**Reconciliation:**
- Scheduled job (every 6 hours): compares `wallet_balances.balance` against `SUM(ledger_entries.amount)` grouped by wallet
- Discrepancy > SYP 500 triggers alert for manual investigation
- Discrepancy > SYP 10,000 freezes wallet until resolved

## Consequences
**Positive:**
- Accounting integrity is paramount — the CFE is the single source of truth, and no code path can modify a wallet balance without a corresponding ledger entry
- Wallet reads are fast (Redis, sub-millisecond) without burdening the accounting database
- Ledger can be optimized for analytical queries (indexes on date, account code, entry type) without impacting user-facing performance
- Clear ownership boundary: CFE team owns posting correctness, Wallet team owns balance presentation
- Reconciliation provides a safety net; the 6-hour window is acceptable given Syrian regulatory norms (daily settlement)

**Negative / Trade-offs:**
- Eventual consistency window: up to 500ms between ledger posting and wallet balance update — a user could see a stale balance if they read immediately after a transfer from another agent
- Balance mismatch during failure: if the CFE posts but the event is lost (queue failure), `wallet_balances` is stale until reconciliation catches it (up to 6 hours)
- More moving parts: Redis, queue workers, scheduled reconciliation — each is a failure point
- Wallet balance cannot be used as authoritative for accounting reports — always query the Ledger for audit

## Compliance
Enforced via:
1. `tests/Unit/Cfe/PostingTest.php` — every transaction type must create exactly 2 ledger entries (debit + credit) with matching totals
2. `tests/Feature/Wallet/BalanceConsistencyTest.php` — simulated race conditions where wallet balance is read immediately after CFE post
3. Reconciliation alert thresholds in `config/reconciliation.php` with PagerDuty integration for > SYP 10K discrepancies
4. Code review: no `UPDATE wallet_balances SET balance = ...` allowed outside the CFE event listener
5. PHPStan rule: `Wallet` module cannot import `Ledger` models directly — only via CFE interface
