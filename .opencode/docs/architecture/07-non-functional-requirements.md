# Non-Functional Requirements — Beza Platform

> **Status:** Approved  
> **Last updated:** 2025-12-01  
> **Owner:** Platform Architecture

---

## 1. Performance

| Metric | Target | Measurement | Notes for Syria |
|--------|--------|-------------|-----------------|
| API P99 response (internal) | < 200ms | New Relic / Grafana | Excluding CBS integration latency |
| API P99 response (external) | < 500ms | New Relic / Grafana | CBS, Naratel SMS, bank integrations |
| Wallet transfer end-to-end | < 2s | AppDynamics | User-facing: sender confirms → recipient sees balance |
| Batch settlement | < 5 min for 100K txns | Job timer | Runs at EOD after CBS cutoff (15:00 Syria time) |
| FX quote | < 100ms | Per-request metric | Single CBS rate lookup, no aggregation needed V1 |
| Concurrent users | 10,000 (V1), 50,000 (V2) | Load test | Syria has ~8M smartphones; 10K concurrent = 0.125% penetration |
| Peak TPS | 500 (V1), 2000 (V2) | Load test | Peak = salary disbursement days (1st of month) |

### Performance Budgets (per request)

| Resource | Budget | Breach Action |
|----------|--------|---------------|
| CPU time | < 50ms | Alert P2 |
| DB queries | < 10 per request | Code review gate |
| External HTTP calls | < 2 per request | Circuit breaker |
| Memory allocation | < 5MB | Profile and fix |
| Serialization | < 5ms | Optimize resource |

### Syria Internet Reality

```
Median latency: Damascus → local hosting: 8-15ms
Median latency: Damascus → Beirut (nearest PoP): 35-50ms  
Median latency: Damascus → Frankfurt: 130-170ms
Packet loss (peak hours): 0.5-3%
Mobile internet (3G/4G): ~50% of users
```

All timeouts must account for Syria's infrastructure. A 500ms timeout in New York = a 2000ms timeout in Damascus.

---

## 2. Availability

| Tier | Target | Downtime/Year | Scope |
|------|--------|---------------|-------|
| **Critical** (transactions) | 99.95% | ≈4.38 hours | Wallet, Ledger, Identity, Agent transactions |
| **Standard** (reports) | 99.5% | ≈43.8 hours | Analytics, dashboards, admin panels |
| **Best effort** (historical) | 99.0% | ≈87.6 hours | Archived data, old reports |

### Planned Maintenance

- **Window:** Sunday 02:00–04:00 Syria time (UTC+3)
- **Communication:** 72h notice via SMS + in-app banner
- **Exceptions:** Security patches (24h notice), emergency hotfix (post-mortem required)
- **CBS coordination:** Must align with CBS maintenance calendar (shared quarterly)

### Known Availability Constraints

| Dependency | Typical Availability | Mitigation |
|------------|---------------------|------------|
| CBS core banking | 98–99.5% | Async queue, offline transaction approval (limited) |
| Naratel SMS gateway | 99–99.5% | Multi-provider SMS routing (V2) |
| Syria internet backbone | 99–99.9% | Local hosting in Damascus, BGP multi-homing |
| Electricity grid | 95–99% | UPS + generator + redundant PSUs at data center |
| Google Play / App Store | 99.5%+ | APK sideload fallback for agent devices |

---

## 3. Scalability

| Dimension | V1 Capacity | V2 Target | Strategy |
|-----------|-------------|-----------|----------|
| Web servers | 2–4 instances | 10–20 instances | Horizontal; stateless (sessions in Redis) |
| Queue workers | 2–4 instances | 10–20 instances | Horizontal; SQS/Redis queues |
| Database (write) | 1 primary | 1 primary + read replicas | Vertical first, then read replicas |
| Database (read) | Read from primary | 2–4 read replicas | Read replicas V2; sharding V3 |
| Storage (transaction) | 500 GB/year | 2 TB/year | MariaDB partitioning + archival |
| Storage (total) | 2 TB/year | 8 TB/year | S3-compatible object storage for documents |
| Redis | 4 GB | 16 GB | Cache + sessions + queues + rate limiting |
| Elasticsearch | 500 GB | 2 TB | Logs + audits + full-text search |

### Data Growth Projection (Syria Market)

| Year | Users | Daily Txns | Storage Need |
|------|-------|------------|-------------|
| 2026 (V1 launch) | 50,000 | 5,000 | 500 GB |
| 2027 | 200,000 | 20,000 | 2 TB |
| 2028 | 500,000 | 50,000 | 5 TB |
| 2029 | 1,000,000 | 100,000 | 10 TB |

---

## 4. Security

### Authentication & Authorization

| Control | Implementation | Rationale for Syria |
|---------|---------------|---------------------|
| 2FA | TOTP-based (Google Authenticator) + SMS OTP backup | SIM swap fraud is rising; TOTP provides offline 2FA |
| Device binding | Hardware-backed key attestation (Android SafetyNet / iOS DeviceCheck) | Prevents account takeover from stolen credentials |
| Biometric | Fingerprint / Face ID for transaction approval > 50,000 SYP (~$100) | Regulatory requirement for high-value |
| Password policy | Min 12 chars, bcrypt cost 12, no common passwords | Password reuse is prevalent due to multiple unregulated services |

### Cryptography

| Layer | Algorithm | Key Management |
|-------|-----------|----------------|
| Data at rest | AES-256-GCM | Keys in AWS KMS / HashiCorp Vault; rotated annually |
| Data in transit | TLS 1.3 (min) | Let's Encrypt for public; internal CA for service mesh |
| Password hashing | Argon2id (mem=64MB, time=3, threads=4) | Prefer over bcrypt for future-proofing |
| JWT signing | RS256 (4096-bit RSA) | Private key in Vault; public key in .well-known |
| API keys | HMAC-SHA256 | Rotated every 90 days; stored as SHA-256 hash |

### Regulatory Security (Syria-Specific)

| Requirement | Implementation |
|-------------|---------------|
| CBS data localization | All data stored in Syria (Damascus DC); no replication outside borders |
| AML Law 31/2010 | Transaction monitoring + SAR filing to AML Commission |
| Anti-terrorism Law 19/2012 | Real-time screening against CBS terrorist list + UN sanctions |
| Sharia compliance | Audit trail for Sharia board; no interest (riba) in any financial product |
| Record retention | All financial records: 10 years; KYC: 5 years post-account closure |

### Access Control

| Role | Can View | Can Modify | Can Approve |
|------|----------|------------|-------------|
| Customer | Own account, own transactions | Own profile, beneficiary list | Own transactions |
| Agent | Assigned customers, own float | Cash in/out, commission | Cash in/out |
| Compliance Officer | All transactions, all KYC | File SAR, flag accounts | Freeze/thaw accounts |
| Admin | Everything | Everything except audit logs | Role assignments |
| Super Admin | Everything | Everything | Everything + audit log |

---

## 5. Compliance & Regulatory

### Syrian Regulations

| Regulation | Requirement | Implementation |
|------------|-------------|----------------|
| **CBS Basic Regulation 2025** | All payment service providers must obtain CBS license | Platform built to CBS examination checklist |
| **Anti-Money Laundering Law 31/2010** | Transaction monitoring, customer due diligence, SAR filing | Automated screening + manual SAR workflow |
| **Anti-Terrorism Law 19/2012** | Freeze assets of listed individuals/organizations | Real-time sanctions screening at onboarding + transaction |
| **Cyber Crime Law 17/2022** | Data protection, breach notification within 72h | Incident response plan; encrypted storage |
| **Sharia Law** | No riba (interest), no gharar (excessive uncertainty) | All financial products reviewed by Sharia advisory board |
| **CBS Data Localization** | All financial data physically in Syria | Damascus data center; no cloud replication abroad |

### International Compliance

| Framework | Relevance | Implementation |
|-----------|-----------|----------------|
| **OFAC Sanctions** | Syria is comprehensively sanctioned; Beza must not facilitate sanctioned transactions | Full UN/US/EU sanctions screening; geo-blocking for sanctioned jurisdictions |
| **FATF Recommendations** | Syria under FATF increased monitoring ("grey list") | Enhanced due diligence for cross-border transactions |
| **GDPR** | Syrian diaspora users in EU | GDPR-compliant data handling for EU-resident users |
| **PCI DSS** | If Beza handles card data (V2) | Not in scope for V1 (no card processing) |

### Audit Requirements

- All financial records: **10 years retention** (CBS regulation)
- KYC records: **5 years after account closure**
- Audit log: **append-only, immutable** (write-once, read-many storage)
- Quarterly external audit by CBS-approved auditor
- Annual penetration test by Syrian-licensed security firm

---

## 6. Reliability

| Pattern | Configuration | Rationale |
|---------|---------------|-----------|
| **Idempotency** | ALL write operations must accept `Idempotency-Key` header | Prevents double-posting when client retries; critical for wallet operations |
| **Retry (internal)** | 3 attempts; exponential backoff: 100ms, 500ms, 2s | Network blips in Syria are common; retry must be fast |
| **Retry (external/CBS)** | 3 attempts; backoff: 1s, 5s, 15s; total timeout 30s | CBS systems are slow; longer backoff reduces load |
| **Timeout (internal)** | 5 seconds | Any longer = degraded UX |
| **Timeout (external)** | 15 seconds | CBS batch endpoints can be slow |
| **Circuit breaker** | 5 failures in 60s → open for 30s | Prevents cascade failures when CBS is down |
| **Bulkhead** | Separate thread pools per integration (CBS, SMS, Email) | One slow integration must not starve others |
| **Fallback** | CBS offline → queue txns for batch processing; user notified of delay | Better to queue than reject; CBS is highest-risk failure |

### Failure Modes (Syria-Specific)

| Failure | Frequency | Detection | Action |
|---------|-----------|-----------|--------|
| CBS offline | Weekly, 5-30 min | Health check timeout → circuit breaker | Queue transactions; reconcile when CBS returns |
| Internet backbone | Monthly, 1-15 min | Heartbeat monitoring | Failover to secondary ISP; queue incoming requests |
| Electricity outage at DC | Quarterly, 30-120 min | UPS → generator | Generator auto-start; automatic failover to DR site |
| SMS provider down | Monthly, 10-60 min | Provider health check | Fallback to secondary SMS aggregator |
| DNS resolution failure | Random, 1-5 min | Multiple DNS providers | Primary + secondary DNS; use IP fallback |

---

## 7. Observability

### Logging

| Aspect | Standard |
|--------|----------|
| Format | Structured JSON (`level`, `message`, `service`, `trace_id`, `request_id`) |
| Aggregation | ELK Stack (Elasticsearch 8.x, Logstash, Kibana) |
| Retention | 30 days hot, 90 days warm, 1 year cold (Glacier) |
| Sensitive data | Auto-redaction of PII (password, phone, national_id) via Logstash filter |
| Shipping | Filebeat from app servers → Logstash → Elasticsearch |

### Key Log Events

| Event | Level | Included Data |
|-------|-------|---------------|
| Transaction created | `INFO` | txn_id, amount, currency, sender, receiver |
| Transaction failed | `ERROR` | txn_id, failure_reason, error_code |
| Auth failure | `WARN` | user_id, ip_address, user_agent, reason |
| Suspicious activity | `CRITICAL` | Full context + immediate alert |
| CBS timeout | `ERROR` | endpoint, timeout_ms, retry_count |
| Circuit breaker opened | `ALERT` | service, duration, threshold |

### Metrics

| Category | Key Metrics | Tool |
|----------|-------------|------|
| **API** | P50/P95/P99 latency, error rate, throughput | Prometheus + Grafana |
| **Business** | Active users, transaction volume/velocity, conversion funnel | Prometheus + custom exporters |
| **Infrastructure** | CPU/memory/disk, DB connections, queue depth | Node exporter + Prometheus |
| **Integration** | CBS latency, SMS delivery rate, bank settlement time | Blackbox exporter |
| **Compliance** | Screening time, false positive rate, SAR filing SLA | Custom metrics |

### Alerting

| Priority | Response Time | Channel | Examples |
|----------|---------------|---------|----------|
| **P0** | 5 min | Phone call + Slack + SMS | Transaction pipeline down, CBS unreachable > 15min, data loss detected |
| **P1** | 15 min | Slack + SMS | Elevated error rate (>5%), circuit breaker open, queue backlog > 10K |
| **P2** | 1 hour | Slack | P99 latency > 2x baseline, CBS latency > 10s, disk > 80% |
| **P3** | 24 hours | Slack (during business hours) | Certificate expiring in 7 days, deprecation warnings |

### Dashboards

| Dashboard | Audience | Refresh |
|-----------|----------|---------|
| Real-Time Ops | SRE / DevOps | 10s |
| Business KPIs | Product / Management | 5 min |
| Compliance & Risk | Compliance Officer | 1 min |
| CBS Integration | Engineering | 10s |
| Financial (Ledger) | Finance / Audit | 1 min |

### Tracing

- **100% sampling** for transaction path (critical)
- **10% sampling** for read/idempotent endpoints
- Headers propagated: `traceparent`, `tracestate` (W3C Trace Context)
- Export to Jaeger / Grafana Tempo

---

## 8. Disaster Recovery

| Metric | Target | Notes |
|--------|--------|-------|
| **RPO** | 5 minutes | Near real-time replication (MariaDB GTID-based) |
| **RTO (critical path)** | 1 hour | Wallet, Ledger, Identity, Agent |
| **RTO (non-critical)** | 4 hours | Reports, analytics, admin |
| **DR test frequency** | Quarterly | Full failover test; documented and signed off |

### Backup Schedule

| Type | Frequency | Retention | Storage |
|------|-----------|-----------|---------|
| DB snapshot (hourly) | Every hour | 48 hours | Local + S3 |
| DB full backup | Daily at 23:00 SY | 30 days | S3 |
| DB monthly archive | 1st of month | 10 years | S3 Glacier |
| File storage (documents) | Continuous sync | 10 years | S3 + DR copy |
| Configuration | On every change | 90 days | Git + Vault |

### Failover Architecture

| Component | Primary | DR | Failover Mechanism |
|-----------|---------|-----|-------------------|
| Web servers | Active (Damascus DC) | Passive (Damascus DC, different availability zone) | DNS switch (TTL 60s) |
| Database | Primary (Damascus DC) | Replica (DR zone) | Automatic promotion + DNS update |
| Redis | Primary (Damascus DC) | Replica (DR zone) | Sentinel-based failover |
| Queues | Primary queue | Secondary queue | Consumer switches on heartbeat loss |
| Object storage | Primary bucket | Replicated to DR bucket | S3 cross-region replication (within Syria) |

### DR Runbook (Summary)

```
Trigger: Loss of primary DC OR critical component >15min degraded

1. Monitoring alerts on-call engineer (P0)
2. Engineer declares incident (verified >5min of unavailability)
3. DNS switch → DR (automatic, 60s propagation)
4. Database promotion (automatic, <5min)
5. Queue consumer shift (automatic, <2min)
6. Validation: run smoke tests (automated, <5min)
7. Communication: in-app banner + SMS to ops team

Recovery: Primary restored → sync data back → DNS switch → validate
```

---

## 9. Capacity Planning (Syria 2026)

| Resource | V1 Launch (Q2 2026) | 6-Month Forecast | 12-Month Forecast |
|----------|---------------------|------------------|-------------------|
| Registered users | 10,000 | 50,000 | 200,000 |
| Daily active users | 2,000 | 10,000 | 40,000 |
| Daily transactions | 500 | 5,000 | 20,000 |
| Peak TPS | 50 | 500 | 1,000 |
| API requests/day | 50,000 | 500,000 | 2,000,000 |
| SMS/day | 1,000 | 10,000 | 40,000 |
| Email/day | 500 | 5,000 | 20,000 |
| DB size (active) | 10 GB | 50 GB | 200 GB |
| DB size (archived) | 0 | 50 GB | 300 GB |
| CDN bandwidth | 50 GB/mo | 250 GB/mo | 1 TB/mo |

All NFRs reviewed and approved by:
- **CBS Technical Committee** — compliance and audit requirements
- **Sharia Advisory Board** — financial product constraints
- **Engineering Leadership** — feasibility and timeline
