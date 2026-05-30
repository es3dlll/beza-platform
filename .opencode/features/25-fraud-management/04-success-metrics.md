# Success Metrics — Fraud Management

## Definition of Done

The Fraud Management feature is considered successful when ALL of the following metrics are met and sustained for 3 consecutive months.

## Primary Metrics (North Star)

### 1. Fraud Rate
**Definition:** Value of confirmed fraudulent transactions / Total transaction value × 100

| Target | Acceptable | Critical Threshold |
|--------|-----------|-------------------|
| < 0.1% | < 0.3% | > 0.5% |

**Calculation:**
```
Fraud Rate = (Total confirmed fraud losses in SYP / Total transaction volume in SYP) × 100
```

**Measurement:** Daily, reported as 30-day rolling average.

**Syria context:** Industry estimate for Syria's digital payments is 0.5–2%. Sub-0.1% is world-class (comparable to M-PESA's 0.09% in Kenya).

### 2. False Positive Rate
**Definition:** Transactions flagged as suspicious that are later confirmed legitimate / Total flagged transactions × 100

| Target | Acceptable | Critical Threshold |
|--------|-----------|-------------------|
| < 3% | < 5% | > 10% |

**Measurement:** Weekly, reported as 7-day rolling average.

**Impact:** Each false positive = one angry user. At 3% FP rate with 100K daily transactions flagged, that's 3,000 potentially frustrated users/day.

### 3. Real-time Decision Speed
**Definition:** P50 and P99 latency for fraud screening from transaction submission to decision returned

| Target | Acceptable | Critical |
|--------|-----------|---------|
| P50 < 100ms | P50 < 200ms | P50 > 500ms |
| P99 < 200ms | P99 < 500ms | P99 > 1s |

**Measurement:** Every transaction, reported as hourly P50/P99.

**Syria context:** Syriatel and MTN networks have variable latency. The fraud engine must account for network round-trip in total perceived speed.

### 4. Fraud Recovery Rate
**Definition:** Value recovered from confirmed fraud / Total confirmed fraud loss × 100

| Target | Acceptable | Critical |
|--------|-----------|---------|
| > 20% | > 15% | < 10% |

**Measurement:** Monthly, rolling 6-month average.

**Syria context:** Recovery in Syria is challenging — fraudsters often move funds through mule networks. Recovery > 20% is aggressive but achievable with frozen accounts and CBS cooperation.

## Secondary Metrics

### 5. Model Accuracy (AUC-ROC)
**Definition:** Area Under the Receiver Operating Characteristic Curve for ML fraud model

| Target | Acceptable | Retrain Required |
|--------|-----------|-----------------|
| > 0.95 | > 0.90 | < 0.85 |

**Measurement:** Daily, on holdout validation set.

### 6. Rule Hit Rate
**Definition:** Transactions triggering at least one rule / Total transactions × 100

| Target | Notes |
|--------|-------|
| < 5% | Too high = too many flags |
| > 0.5% | Too low = rules may be too narrow |

**Measurement:** Daily, per-rule breakdown.

### 7. Decision Time Breakdown

| Component | Target | Max |
|-----------|--------|-----|
| Feature computation | 30ms | 80ms |
| Rule engine | 20ms | 50ms |
| ML scoring | 30ms | 70ms |
| Decision + action | 20ms | 50ms |
| Total | 100ms | 250ms |

### 8. Case Resolution Time

| Priority | Target Resolution | Escalation SLA |
|----------|-------------------|----------------|
| P0 — Active fraud in progress | 15 minutes | 10 min |
| P1 — Confirmed fraud with loss | 1 hour | 30 min |
| P2 — Suspicious transaction | 4 hours | 2 hours |
| P3 — User appeal | 30 minutes | 15 min |

### 9. Coverage Metrics

| Metric | Target |
|--------|--------|
| Transaction types covered | 100% |
| Fraud typologies detected | > 90% |
| User base screened | 100% |
| Agent network screened | 100% |
| Real-time decisions | > 99% |
| Manual review rate | < 2% of transactions |

### 10. Regulatory Compliance

| Metric | Target | Regulatory Basis |
|--------|--------|-----------------|
| SAR filing within 24h | 100% | CBS AML Law 31/2010 |
| Quarterly fraud reporting | On-time 100% | CBS supervisory expectations |
| IFRS 9 provisioning accuracy | ±10% of expected loss | IFRS 9 |
| Fraud audit pass rate | 100% | CBS annual review |

## Syria-Specific Metrics

### 11. Agent Fraud Detection Rate
**Definition:** Agent-initiated fraud detected / Total agent fraud × 100

| Target | Acceptable |
|--------|-----------|
| > 80% | > 60% |

### 12. Remittance Fraud Protection
**Definition:** Cross-border remittance transactions screened + blocked if fraudulent

| Target | Notes |
|--------|-------|
| 100% screening | Remittances are high-risk |

### 13. False Positive Rate by Region

Track FP rate separately for:
- Damascus / Rural Damascus
- Aleppo
- Homs / Hama
- Coastal region (Latakia, Tartous)
- Northeast (Deir ez-Zor, Hasakah)
- Displacement areas (IDP camps)

Target: No region has FP rate > 5%.

## Reporting Cadence

| Report | Audience | Frequency | Format |
|--------|----------|-----------|--------|
| Fraud executive summary | C-suite | Monthly | 1-page PDF + dashboard |
| Fraud ops report | Fraud team | Daily | Dashboard + Slack |
| Model performance | Data science | Weekly | Jupyter notebook |
| CBS regulatory report | Compliance + CBS | Quarterly | Formal report |
| Fraud incident report | All stakeholders | Per incident | Email + case |
| User impact report | PM + CS | Weekly | Dashboard |

## Goals by Phase

| Metric | Phase 1 (Rules only) | Phase 2 (+ML) | Phase 3 (+Advanced) | Phase 4 (Mature) |
|--------|---------------------|---------------|---------------------|------------------|
| Fraud rate | < 0.5% | < 0.2% | < 0.15% | < 0.1% |
| False positive rate | < 10% | < 5% | < 3% | < 3% |
| Decision speed | < 300ms | < 200ms | < 150ms | < 100ms |
| Recovery rate | < 10% | > 15% | > 20% | > 25% |
| Model AUC | N/A | > 0.90 | > 0.93 | > 0.95 |
| Automation rate | 50% | 75% | 90% | 95% |
