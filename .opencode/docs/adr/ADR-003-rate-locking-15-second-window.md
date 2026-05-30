# ADR-003: Rate Locking with 15-Second Window

## Status
Accepted

## Context
Beza Financial OS facilitates foreign exchange (FX) transactions for Syrian agents and end-users. A critical UX and correctness problem arises from rate volatility during the multi-step transfer flow:

1. User A (in Damascus) wants to send $100 USD to a recipient. They use SYP as their source currency.
2. At step 1, the system shows: "Send $100 USD → 1,305,000 SYP" (rate: 13,050 SYP/USD).
3. User reviews the details and confirms at step 2, 8 seconds later.
4. The market rate has moved to 13,100 SYP/USD in those 8 seconds.
5. Which rate applies?

Three strategies were evaluated:

**Strategy A — Lock-on-view (time-based):** When the user views the FX confirmation screen, the displayed rate is locked in Redis with a TTL. If the user confirms within the TTL, that rate applies. If they exceed the TTL, the screen refreshes with a new rate and the user must re-confirm.

**Strategy B — Lock-on-confirm (point-in-time):** The displayed rate is informational. The actual rate is fetched at the moment of confirmation. The user may see a different rate than what was displayed.

**Strategy C — No lock:** The user accepts whatever rate is active at confirmation by virtue of submitting the form. Rate displayed earlier is not guaranteed.

Syria-specific considerations:
- Internet connectivity in rural areas (Idlib, Deir ez-Zor, rural Homs) is unstable (2G/3G with frequent packet loss). A user might take 20-30 seconds to complete a confirmation due to page load latency.
- SYP exchange rates are highly volatile (daily swings of 2-5% are common). A 15-second window could expose Beza to spread risk if the rate moves against the platform.
- Trust in digital financial services is low. Users must feel the rate they saw is honored; otherwise they revert to cash-based hawala networks.
- Syrian remittance volume is approximately $2B/year (World Bank 2023 estimate), mostly through informal channels. A fair locking mechanism is critical for formalization.

## Decision
Adopt **Strategy A — Lock-on-view with 15-second window** as the standard rate-locking mechanism for all consumer-facing FX transactions.

**Implementation details:**

```
1. User requests FX quote (GET /fx/quote?from=USD&to=SYP&amount=100)
2. System fetches current rate from FX provider/aggregator
3. Rate is stored in Redis with key pattern:
   fx:lock:{session_id}:{quote_id}
   Value: { rate: 13050, from: "USD", to: "SYP", amount: 100, expires_at: TTL }
   TTL: 15 seconds
4. Rate displayed to user with countdown timer
5. On confirmation (POST /fx/confirm):
   a. Redis GET fx:lock:{session_id}:{quote_id}
   b. If exists and not expired → use locked rate
   c. If expired or nonexistent → fetch fresh rate, return 409 Conflict with new rate
6. User must re-confirm if rate expired
```

**Business transactions exempt from locking:**
- Inter-agent float rebalancing (uses current rate always)
- Batch settlement operations (uses rate at time of settlement)
- Admin manual corrections (requires supervisor approval)

**B2B API behavior:**
- Partner API clients receive the locked rate and TTL in the response body
- They must include `quote_id` and `locked_rate` in the confirmation request
- TTL for API clients is 10 seconds (tighter, assuming machine-initiated confirmations)

## Consequences
**Positive:**
- User trust: the rate seen is the rate honored — critical for migrating users from informal hawala to digital
- Standard fintech practice: M-PESA uses 30 seconds, Wave uses 15 seconds, mobile money operators in MENA use 10-30 seconds
- Redis-based locking is fast (< 5ms lookup) and survives application restarts
- Rate gaming is limited: a user cannot lock a rate and wait 5 minutes for a favorable move
- 15 seconds is sufficient for 95th-percentile confirmation time even on slow Syrian mobile networks

**Negative / Trade-offs:**
- During high volatility (e.g., central bank rate announcement), rates may expire before confirmation, causing user frustration
- 15 seconds exposes Beza to spread risk: if the rate moves 1% against Beza in 15 seconds and the user confirms, Beza bears the loss
- Redis outage would disable FX transactions — requires Redis Sentinel or cluster for high availability
- Two nonces (quote_id from Redis + session_id from Laravel) must match to prevent replay attacks

## Compliance
Enforced via:
1. Unit tests: `RateLockServiceTest` — assert locked rate is used within TTL, fresh rate is fetched after TTL
2. Integration test: Redis mock with controllable TTL to test boundary conditions (14.9s, 15.0s, 15.1s)
3. Monitoring: Datadog/ Laravel Pulse dashboard for "rate lock expired" events — alert if > 5% of quotes expire
4. Security audit: ensure quote_id is cryptographically random (32 bytes via `random_bytes()`), not sequential
5. B2B API compliance tested via `tests/Feature/Fx/ApiRateLockTest.php`
