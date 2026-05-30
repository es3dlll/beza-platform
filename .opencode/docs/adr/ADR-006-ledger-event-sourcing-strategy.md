# ADR-006: Ledger Event Sourcing Strategy

## Status

Accepted

## Context

The Beza Financial OS has a Ledger (CFE) that records all financial postings. Two approaches exist:

1. **Event Sourced Ledger** — The ledger IS the event stream. Every posting is an event. No separate balance table; balances are derived by replaying events.

2. **Transactional Ledger** — Events emit FROM the ledger but the ledger itself is a traditional double-entry database (journal entries + account balances updated inline).

Syria constraints: limited DevOps capacity for event replay infrastructure, need for real-time balance queries, CBS audit requirements for immutable audit trail.

## Decision

**Transactional Ledger with Append-Only Journal (Hybrid).**

The CFE uses a traditional double-entry database (MySQL InnoDB) for its primary store:

- `accounts` table: current balances (updated on every posting, with optimistic locking)
- `journal_entries` table: append-only, immutable after posting (no UPDATE, no DELETE)
- `journal_entry_lines` table: the individual debit/credit lines

Balance queries read from `accounts` (real-time, sub-millisecond). Audit/reconciliation reads from `journal_entries` (immutable, timestamp-ordered).

## Why Not Pure Event Sourcing

1. **Query complexity** — Event sourcing requires event replay for balance queries, adding latency and complexity for Syria's limited infrastructure.

2. **Operational overhead** — Event store management, snapshot management, replay debugging are complex.

3. **Syria team capacity** — The ops team needs to be able to investigate ledger issues with simple SQL queries, not event replay tools.

4. **CBS audit requirements** — Auditors expect a journal ledger format, not an event stream. The append-only journal provides the same immutability guarantee in a familiar format.

## Event Emission

While the ledger is NOT event sourced, it DOES emit events for every state change:

- On journal entry creation → `EntryPosted` event emitted
- On reversal → `EntryReversed` event emitted
- On account creation → `AccountOpened` and `AccountBalanceUpdated`

These events feed the Event Platform (documented in 02-events-catalog.md) for consumers: Fraud, Analytics, Reconciliation, Compliance.

## Immutability Guarantee

- Journal entries: INSERT only. No UPDATE/DELETE allowed at DB level (MySQL TRIGGER blocks modifications).
- Reversals: accomplished via reversing journal entry (debit↔credit swap), linked by `reversed_entry_id`.
- Balance changes: only via CFE posting engine (no direct SQL updates to `accounts.balance`).

## Audit Trail

- Every journal entry has: `created_by` (user_id or system), `source` (API/queue/USSD), `transaction_id` (ULID).
- `journal_entries` table is WORM (Write Once Read Many) with DB-level protections.
- Daily checksum: SHA-256 hash of all journal entries for the day, stored separately for tamper detection.

## Consequences

Positive:

- Real-time balance queries (sub-millisecond, no event replay).
- Familiar SQL-based audit and reconciliation.
- Simpler ops team onboarding.
- Same immutability guarantee as event sourcing (append-only journal).

Negative:

- Not purely event-sourced (cannot rebuild full system state from events alone).
- `accounts` table must be backed up with journal entries (RPO 5min).

## Compliance

- All CFE posting code must use the `CfePostingService` (no direct account balance updates).
- All journal entry modifications must go through the `JournalEntryRepository`.
- DB triggers on `journal_entries` table enforce append-only (reject UPDATE/DELETE).
- Weekly audit: checksum verification.

## References

- ADR-001: Architecture Overview (context on Syria deployment constraints)
- ADR-002: Events Catalog (downstream event consumers)
- ADR-004: Data Layer (MySQL InnoDB selection rationale)
- 12factor.net/disposability — Stateless services principle guiding ledger design
