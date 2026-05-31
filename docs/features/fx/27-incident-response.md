# FX Engine Incident Response

## Incident Types

### P0: Complete FX Outage
```
Description: All rate providers down, no rates available (>90% failure rate)
Impact: Users cannot check rates, cannot perform conversions, cross-currency wallet features broken
Response Time: 5 minutes
Team: Engineering on-call + DevOps + Treasury (if sustained)
```

### P1: Conversion Failures
```
Description: >5% of conversions failing
Impact: Lost revenue, user funds potentially at risk, negative trust impact
Response Time: 15 minutes
Team: Backend engineer + CFE engineer
```

### P2: Rate Staleness
```
Description: Rates not updating for > 60 seconds (4x expected TTL)
Impact: Stale rates shown to users, potential for rate arbitrage at old rates
Response Time: 30 minutes
Team: Backend engineer
```

### P3: Provider Degradation
```
Description: One provider offline but others still serving rates
Impact: Reduced rate accuracy, possible slight spread impact
Response Time: 4 hours
Team: Backend engineer
```

## Runbook: P0 — Complete FX Outage

### Step 1: Detection (0-2 min)
```
Alert: PagerDuty notification "All FX Providers Down"
Check: Grafana dashboard
  - beza_fx_providers_online_total = 0
  - Rate fetch error rate 100%
  - Cache hit ratio dropping to 0
```

### Step 2: Triage (2-5 min)
```
1. Check each provider individually:
   curl -f -m 2 https://cbs.gov.sy/api/rates
   curl -f -m 2 https://exchangehouse.example.com/api/rates
   curl -f -m 2 http://scraper-service/health

2. Check Redis:
   redis-cli PING
   redis-cli KEYS "fx:rate:*"
   redis-cli TTL "fx:rate:SYP/USD"

3. Check scraper fleet:
   kubectl get pods -l app=fx-scraper
   kubectl logs deployment/fx-scraper --tail=50

4. Check rate fetch cron:
   kubectl get cronjobs
   kubectl logs job/fetch-rates --tail=50
```

### Step 3: Mitigation (5-15 min)
```
Common Causes & Fixes:

[All Provider APIs Down (External)]
  → If CBS and Parallel Market both fail:
    1. Enable "Emergency Mode": serve last known rates (stale, flagged)
    2. Set manual override rate via admin panel
    3. Post maintenance banner: "جاري تحديث الأسعار"
    4. Alert treasury team to manually source rates
  → If only external APIs: enable internal rate estimation
    - Use last 24h trend to estimate current rate
    - Apply conservative spread (+1% standard)
    - Mark all rates as "estimated"

[Redis Cluster Down]
  → Check Redis pod status: kubectl get pods -l app=redis
  → Restart: kubectl rollout restart statefulset/redis
  → If Redis unavailable > 30s: fall back to DB for rate cache
    - Serve rates directly from fx_rates table
    - Much slower (50ms vs 1ms) but functional
    - Rate locking disabled in this mode

[Rate Fetcher Cron Not Running]
  → Check cron job: kubectl get cronjobs fetch-rates
  → Trigger manually: kubectl create job --from=cronjob/fetch-rates manual-fetch
  → If cron job broken: deploy hotfix, restart scheduler pod

[Scraper Fleet Down]
  → Check scraper pods: kubectl logs -l app=fx-scraper
  → Restart: kubectl rollout restart deployment/fx-scraper
  → If scraper cannot be fixed: remove scraper providers from active list
    - Continue with API-based providers only
```

### Step 4: Recovery (15-30 min)
```
1. Verify at least 2 providers back online
2. Run rate integrity check:
   - Compare new rates vs pre-outage rates
   - Verify no anomalous jumps (>5% from pre-outage)
3. Disable emergency mode, resume normal rate fetching
4. Run catch-up: the fetcher will have missed ~60 fetches
   - Backfill by fetching from each provider for last 5 min gaps
5. Notify users: "تم استعادة خدمة أسعار الصرف"
6. Post-mortem within 24 hours
```

## Runbook: P1 — Conversion Failures

### Investigation
```
1. Check error types:
   SELECT failure_reason, COUNT(*) FROM fx_conversions
   WHERE created_at > NOW() - INTERVAL 15 MINUTE
   AND status = 'failed'
   GROUP BY failure_reason;

2. Common patterns:
   - "lock_expired": Rate lock expired before conversion
     → Check lock TTL, user confirmation time
   - "cfe_posting_failed": CFE couldn't post the conversion
     → Check CFE health, wallet balances
   - "insufficient_balance": Wallet balance changed between lock and convert
     → Check for concurrent transactions on same wallet
   - "rate_changed": Locked rate no longer matches current rate
     → Check rate provider, check if override happened
   - "validation_failed": Rate validation rejected the conversion
     → Check spread limits, amount limits
```

### Fixes
```
[Lock Expired]
  → Is 30s TTL too short for users?
    → Consider increasing to 45s for standard, 60s for premium
  → Is the conversion pipeline too slow?
    → Optimize CFE hold/post to < 2s total

[CFE Posting Failed]
  → Check CFE service health: curl -f http://cfe-service/health
  → Check CFE database connections
  → If CFE degraded: enable "Phase 2" mode
    - Record conversion in DB as "pending"
    - Queue for async CFE posting
    - User sees "pending — سيتم التأكيد قريباً"

[Concurrent Wallet Transactions]
  → This is a race condition
  → Implement pessimistic lock on wallet during conversion
  → Check if wallet_balance < source_amount at conversion time

[Rate Changed Mid-Flow]
  → This should not happen if lock is valid
  → Check: did admin override fire during lock window?
  → Fix: lock includes rate + provider source, override should invalidate active locks
```

### Recovery
```
1. Identify affected users (conversions failed with money movement)
2. For each: verify CFE state (was money deducted?)
   - If deducted and failed: schedule manual reversal
   - If not deducted: no action needed
3. Run reconciliation:
   SELECT * FROM fx_conversions WHERE status = 'failed' AND created_at > NOW() - INTERVAL 1 HOUR
   CROSS JOIN cfe_postings ON reference
   → Find orphans (deducted but not recorded)
4. Process reversals batch (admin panel tool)
5. Notify users of successful reversals
```

## Runbook: P2 — Rate Staleness

### Detection & Fix
```
Check:
  1. Last successful rate fetch time:
     SELECT MAX(recorded_at) FROM fx_rates WHERE pair = 'SYP/USD';
  2. If > 60s ago:
     - Check fetch cron job
     - Check provider health
     - Check Redis TTL on rate keys

Fix:
  1. Restart fetch cron if stopped
  2. If provider slow: temporarily reduce priority, let faster provider serve
  3. If all providers slow: investigate network/infrastructure
  4. Manually trigger rate fetch:
     Artisan::call('fx:fetch-rates', ['pair' => 'SYP/USD']);

During staleness:
  - Stale indicator shown on all rates
  - Rate locking disabled (prevent arbitrage at stale rates)
  - Users can still view rates but not convert
  - Auto-resume when fresh rate fetched
```

## Runbook: P3 — Provider Degradation

### Response
```
1. Acknowledge alert — this may be transient
2. Check provider for 2 min — if it recovers, no action needed
3. If degraded > 5 min:
   - Deprioritize provider (priority = 99)
   - Other providers will serve rates automatically
   - Notify treasury team about degraded source
4. Investigate root cause:
   - API provider: check their status page, contact support
   - Scraper: check website structure changes, update selectors
5. Fix and re-prioritize when healthy
```

## Post-Mortem Template
```markdown
# Post-Mortem: [TITLE]

Date: YYYY-MM-DD
Duration: XX minutes
Severity: P0/P1/P2/P3
Feature: FX Engine
Summary: One-line description

## Timeline
- HH:MM — Alert triggered
- HH:MM — Engineer acknowledged
- HH:MM — Root cause identified
- HH:MM — Mitigation applied
- HH:MM — Service restored
- HH:MM — Monitoring confirmed healthy

## Root Cause
[Detailed description of what caused the incident]

## Impact
- Users affected: XX
- Conversions failed: XX
- Financial impact: XX SYP
- Duration of degraded service: XX minutes
- Average rate staleness during incident: XX seconds

## Metrics During Incident
- Provider online count: 0/3
- Conversion error rate: XX%
- Rate staleness: XX seconds
- Support tickets created: XX

## Actions
| Action | Owner | Due Date |
|--------|-------|----------|
| Fix root cause | Engineer | YYYY-MM-DD |
| Add monitoring | DevOps | YYYY-MM-DD |
| Update runbook | Engineer | YYYY-MM-DD |
| Test fix | QA | YYYY-MM-DD |
| Add alert threshold | DevOps | YYYY-MM-DD |
| Client communication | Support | YYYY-MM-DD |

## Lessons Learned
[What went well, what went wrong, what to improve]
```
