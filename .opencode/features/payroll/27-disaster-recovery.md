# 27 — Disaster Recovery & Business Continuity

---

## RTO / RPO Targets

| Tier | Component | RTO (Recovery Time) | RPO (Recovery Point) |
|------|-----------|---------------------|---------------------|
| Critical | Payroll API + processing | 30 minutes | < 1 minute |
| Critical | Company dashboard | 1 hour | < 5 minutes |
| Critical | CFE integration | 30 minutes | < 1 minute |
| Important | Employee wallet (salary view) | 2 hours | < 5 minutes |
| Normal | Payslip downloads | 4 hours | < 1 hour |
| Normal | Historical reports | 24 hours | < 24 hours |

## Failure Scenarios

### Scenario 1: Primary Data Centre Failure (Damascus)

```
Trigger: Building inaccessible, power outage, network cut

Action:
  1. Route traffic to DR site (Aleppo) via DNS failover (TTL: 60s)
  2. Promote DR PostgreSQL replica (async standby → primary)
  3. Start Payroll workers on DR Kubernetes cluster
  4. Start CFE failover (CFE has its own DR)

Expected: Payroll API available within 30 minutes
Data loss: < 1 minute (async replication lag)
```

### Scenario 2: CFE Unavailable

```
Trigger: CFE API returns 5xx for > 30 seconds

Action:
  1. PayrollService enters "CFE Degraded" mode
  2. NO new batches accepted (cannot hold/credit)
  3. In-flight batches: complete remaining credits if possible; fail otherwise
  4. Notify ops team via P1 alert
  5. Every 30 seconds, attempt CFE health check
  6. Once healthy, resume accepting batches

Expected: Batch processing paused for < 5 minutes
Data loss: None (idempotency keys prevent double processing)
```

### Scenario 3: Database Corruption

```
Trigger: Schema migration gone wrong, data corruption detected

Action:
  1. Immediate read-only mode for all payroll endpoints
  2. Restore from latest WAL archive (point-in-time recovery)
  3. Verify data integrity
  4. Replay any transactions since restore point from idempotency log
  5. Switch back to read-write

Expected: Read-only for < 15 minutes
Data loss: Dependant on WAL archive frequency (RPO < 5 min)
```

## Backup Strategy

| Data | Frequency | Retention | Method |
|------|-----------|-----------|--------|
| PostgreSQL (full) | Daily | 30 days | pg_dump → encrypted S3 |
| PostgreSQL (WAL) | Continuous | 7 days | WAL archiving to S3 |
| Payslip PDFs | Continuous (S3) | 7 years | S3 versioning + cross-region replication |
| Audit logs | Append-only | 7 years | DB (immutable) |
| Application config | Per-deploy | 90 days | Git + Vault backup |

## DR Plan — Runbook

### Failover to Aleppo

```bash
# 1. Update DNS
curl -X POST https://api.cloudflare.sy/dns/update \
  -d '{"record": "api.beza.sy", "target": "aleppo-dr.beza.sy"}'

# 2. Promote DR database
pg_ctl promote -D /var/lib/postgresql/dr-data

# 3. Start application
kubectl config use-context aleppo-dr
kubectl rollout restart deployment/payroll-api
kubectl rollout restart deployment/payroll-worker

# 4. Verify
curl -f https://api.beza.sy/payroll/v1/health

# 5. Alert team
#    #ops channel: "FAILOVER TO ALEPPO COMPLETE"
```

### Failback to Damascus

```bash
# 1. Repair Damascus DC
# 2. Sync data: rsync from Aleppo → Damascus
# 3. Reverse DNS back to Damascus
# 4. Verify
# 5. Switch operations
```
