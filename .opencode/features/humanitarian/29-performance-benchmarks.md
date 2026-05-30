# Performance Benchmarks & Targets

## API Performance Targets

| Endpoint | Target (p95) | Target (p99) | Load Test |
|----------|-------------|-------------|-----------|
| POST /programs | 1,000ms | 2,000ms | 50 concurrent |
| GET /programs | 300ms | 500ms | 200 concurrent |
| GET /programs/{id} | 500ms | 1,000ms | 200 concurrent |
| POST /programs/{id}/beneficiaries | 5,000ms (for 10k rows) | 10,000ms | 5 concurrent uploads |
| POST /distribute | 2,000ms (trigger) | 5,000ms | 10 concurrent |
| GET /distributions | 500ms | 1,000ms | 100 concurrent |
| GET /programs/{id}/spending | 2,000ms | 5,000ms | 50 concurrent |
| POST /vouchers/create | 3,000ms (for 10k) | 8,000ms | 5 concurrent |
| POST /vouchers/redeem | 500ms | 1,000ms | 500 concurrent |
| GET /reports/donor | 10,000ms | 30,000ms | 10 concurrent |

## Queue Processing Benchmarks

| Job Type | Target Throughput | Target Latency | Max Queue Depth |
|----------|------------------|----------------|-----------------|
| enrollment.batch-validate | 5,000 records/min | 30s to start | 50,000 |
| sanctions.screen-batch | 3,000 records/min | 10s to start | 100,000 |
| distribution.process-batch | 10,000 wallets/min | 5s to start | 10,000 |
| voucher.issue-batch | 5,000 vouchers/min | 5s to start | 10,000 |
| voucher.expire-check | 100,000 records/min | Scheduled daily | N/A |
| settlement.process-merchant | 1,000 settlements/min | 5s to start | 5,000 |
| notification.send-sms | 500 SMS/min | 2s to start | 50,000 |
| report.generate-donor | 5 min per report | Immediate trigger | 10 |

## Database Query Benchmarks

| Query | Target (no cache) | Target (with cache) | Rows |
|-------|-------------------|---------------------|------|
| Program list (by NGO) | 50ms | 5ms | 100 |
| Program detail with stats | 200ms | 10ms | 1 |
| Beneficiary list (paginated) | 100ms | 10ms | 50 |
| Beneficiary search (by phone hash) | 20ms | 5ms | 1-3 |
| Distribution history (program) | 150ms | 10ms | 500 |
| Spending aggregation (30 days) | 3,000ms | 50ms | 500k |
| Sanctions screening (batch) | 1,000ms | N/A | 10k |
| Donor report aggregation | 5,000ms | 100ms | 500k |

## Scalability Benchmarks

| Metric | Current Target | Future Target (Year 2) |
|--------|---------------|----------------------|
| Total beneficiaries | 500,000 | 5,000,000 |
| Annual distributions | 6,000,000 | 60,000,000 |
| Annual disbursement volume | $450M | $4.5B |
| Concurrent agents | 500 | 5,000 |
| Concurrent merchants | 2,000 | 10,000 |
| Concurrent NGO users | 100 | 500 |
| Daily API requests | 100,000 | 1,000,000 |
| Daily SMS notifications | 50,000 | 500,000 |
| Sanctions database size | 50,000 entries | 150,000 entries (lists grow) |

## Performance Test Scenarios (k6)

### Scenario 1: Distribution Peak Day
```
VUs: 50 (mimics 50 NGO staff triggering distributions)
Duration: 1 hour
Endpoints: POST /distribute, GET /distributions
Goal: Verify 10,000-distribution batches complete within 2 minutes
```

### Scenario 2: Agent Verification Burst
```
VUs: 500 (mimics 500 agents verifying simultaneously)
Duration: 30 minutes
Endpoints: POST /verify (biometric match), GET /beneficiary/{id}
Goal: Verify < 30 seconds end-to-end per beneficiary
```

### Scenario 3: Merchant Voucher Redemption Peak
```
VUs: 200 (mimics 200 merchants at peak hours)
Duration: 1 hour
Endpoints: POST /vouchers/redeem
Goal: < 500ms p95 redemption time
```

## Infrastructure Scaling Rules

| Metric Threshold | Action |
|-----------------|--------|
| CPU > 70% for 5 min | Scale out +2 ECS tasks (max 20) |
| Memory > 80% for 5 min | Scale out +2 ECS tasks |
| Queue depth > 10,000 for critical queue | Alert + scale worker pool +50% |
| DB connections > 80% of max | Alert + increase connection pool |
| Redis memory > 75% | Alert + scale Redis cluster |
| API error rate > 2% | Alert + auto-scale + rollback if > 5% |
