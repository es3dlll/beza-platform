# Open Finance Incident Response

## Incident Types

### P0: Complete API Outage
```
Description: All Open Finance APIs failing (>90% error rate)
Impact: Developers cannot initiate payments, read accounts
Response Time: 5 minutes
Team: Engineering on-call + DevOps
```

### P1: Payment API Failures
```
Description: >5% of payment requests failing
Impact: Developer payment flows broken, revenue loss
Response Time: 15 minutes
Team: Backend engineer + CFE engineer
```

### P2: Latency Degradation
```
Description: P99 > 2s on payment endpoint
Impact: Poor developer experience, timeouts
Response Time: 1 hour
Team: Backend engineer
```

### P3: Developer Portal Issues
```
Description: Portal slow or inaccessible
Impact: Developer frustration, cannot manage keys
Response Time: 4 hours
Team: Backend engineer
```

## Runbook: P0 — API Outage

### Step 1: Detection (0-2 min)
```
Alert: PagerDuty notification
Check: Grafana dashboard
  - API error rate > 90%
  - API Gateway health
  - Database connection pool
```

### Step 2: Triage (2-5 min)
```
1. Check if deployment occurred recently → rollback
2. Check API Gateway health:
   - Kong status: curl http://kong-admin:8001/status
   - Upstream health: curl http://open-finance-svc:8080/health
3. Check database:
   - SHOW PROCESSLIST;
   - Connection pool utilization
4. Check Redis:
   - redis-cli PING
   - Memory usage
```

### Step 3: Mitigation (5-15 min)
```
Common Causes & Fixes:

[Database Connection Exhausted]
  → Kill idle connections
  → Scale up connection pool
  → Restart database if needed

[API Gateway Misconfiguration]
  → Check Kong config changes
  → Rollback Kong config
  → Restart Kong pods

[Rate Limit Cache Corruption]
  → Clear Redis rate limit keys:
    redis-cli KEYS "ratelimit:*" | xargs redis-cli DEL
  → Temporarily disable rate limiting

[Downstream Service Failure]
  → Check CFE service health
  → Check wallet service health
  → Enable degraded mode for read-only endpoints
```

## Runbook: P1 — Payment Failures

### Investigation
```
1. Check error types:
   SELECT error_code, COUNT(*) FROM api_usage_logs
   WHERE created_at > NOW() - INTERVAL 15 MINUTE
   AND status_code >= 400
   GROUP BY error_code;

2. Common patterns:
   - "INSUFFICIENT_BALANCE": Developer funding wallet empty
   - "RATE_LIMIT_EXCEEDED": Client exceeding limits
   - "INVALID_API_KEY": Recently rotated keys not updated
   - "IDEMPOTENCY_CONFLICT": Duplicate requests
```

### Fixes
```
[Developer Funding Issue]
  → Notify developer to top up wallet
  → Temporarily increase credit limit for enterprise

[Rate Limit Too Aggressive]
  → Adjust limits for legitimate developer
  → Whitelist enterprise developers
```

## Post-Mortem Template
```markdown
# Post-Mortem: Open Finance [TITLE]

Date: YYYY-MM-DD
Duration: XX minutes
Severity: P0/P1/P2
Summary: One-line description

## Timeline
- HH:MM — Alert triggered
- HH:MM — Engineer acknowledged
- HH:MM — Root cause identified
- HH:MM — Mitigation applied
- HH:MM — Service restored

## Root Cause
[Detailed description]

## Impact
- Developers affected: XX
- API calls failed: XX
- Financial impact: XX SYP

## Actions
| Action | Owner | Due Date |
|--------|-------|----------|
| Fix root cause | Engineer | YYYY-MM-DD |
| Add monitoring | DevOps | YYYY-MM-DD |
| Notify affected developers | Support | YYYY-MM-DD |

## Lessons Learned
[What went well, what went wrong, what to improve]
```
