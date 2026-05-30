# Monitoring — Fraud Management System

## Monitoring Philosophy

Fraud prevention is a real-time safety system. Monitoring must detect anomalies in system health, fraud metrics, and model performance — with automated alerting and escalation.

## Metrics Dashboard

### Primary Fraud KPIs (Real-time)

| Metric                    | Unit              | Refresh                           | Alert Threshold            |
| ------------------------- | ----------------- | --------------------------------- | -------------------------- |
| **Fraud Rate**            | % of txn volume   | 1 min                             | > 0.3% (single day)        |
| **False Positive Rate**   | % of flagged txns | 1 min                             | > 5% (1h rolling)          |
| **Average Decision Time** | ms (P50/P99)      | 1 min                             | P50 > 200ms, P99 > 500ms   |
| **Fraud Cases Opened**    | count (24h)       | 5 min                             | > 3σ from baseline         |
| **Blocked Amount**        | SYP (24h)         | 5 min                             | > 10M SYP/day (systematic) |
| **Recovery Rate**         | % (30d rolling)   | 1h                                | < 15%                      |
| **Model AUC**             | score (0-1)       | 1h (updated daily after training) | < 0.85                     |
| **Rule Hit Rate**         | % of txns         | 5 min                             | > 5% or < 0.1%             |

### Dashboard Layout

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ FRAUD MONITORING                                    [Last updated: 15:42:03]│
├─────────────────────────────────────────────────────────────────────────────┤
│ GREEN         │ FRAUD RATE    │ FP RATE       │ DECISION TIME │ MODEL AUC  │
│ All Systems   │ 0.08%  ───   │ 2.7%  ───    │ 87ms  ───    │ 0.942 ───  │
│ Normal        │ [───███──]   │ [───██──]     │ [───████]    │ [───████]  │
│               │ Target: 0.1% │ Target: 3%    │ Target: 100ms│ Target: 0.9│
├───────────────┴──────────────┴───────────────┴──────────────┴─────────────┤
│ ⏰ FRAUD ALERTS (24h)                   │ 📊 TOP FRAUD TYPES (24h)        │
│ ┌────────────────────────────────────┐ │ ┌────────────────────────────┐   │
│ │ [🔴] P0: Account Takeover — 8492  │ │ │ Account Takeover   ████ 41%│   │
│ │ [🔴] P0: SIM Swap — Remit #4521  │ │ │ Agent Fraud        ███  28%│   │
│ │ [🟠] P1: Agent Float — Idlib    │ │ │ SIM Swap           ██   18%│   │
│ │ [🟠] P1: Velocity — User 3317   │ │ │ Social Engineering  █    9%│   │
│ │ [🟡] P2: New Device — User 5582 │ │ │ Phishing            ▏   4%│   │
│ └────────────────────────────────────┘ │ └────────────────────────────┘   │
├─────────────────────────────────────────┴─────────────────────────────────┤
│ 📈 FRAUD RATE (7-day)                  │ 📈 FP RATE (24h, hourly)        │
│ ┌────────────────────────────────────┐ │ ┌────────────────────────────┐   │
│ │ ▁▃▄▆█▇▅▃▁▂▄▆█▇▆▅▄▃▂▁▂ 0.15%      │ │ ▂▃▅▇▆▅▄▃▂▁ 3.2%            │   │
│ │ ───────────────────────────        │ │ ────────────────────         │   │
│ │ ─────────────────────────── 0.10%  │ │ ──────────────────── 3.0%    │   │
│ │ ─────────────────────────── 0.05%  │ │ ──────────────────── 2.5%    │   │
│ │ ─────────────────────────── 0.00%  │ │ ──────────────────── 2.0%    │   │
│ └────────────────────────────────────┘ │ └────────────────────────────┘   │
├─────────────────────────────────────────┴─────────────────────────────────┤
│ ⚙️ SYSTEM HEALTH                        │ 🔬 MODEL PERFORMANCE           │
│ ┌────────────────────────────────────┐ │ ┌────────────────────────────┐   │
│ │ Fraud Engine: 🟢 Online (87ms avg)│ │ │ Current Model: v1.2.4      │   │
│ │ ML Service:    🟢 Online (32ms)   │ │ │ AUC: 0.942   ▲ +0.004     │   │
│ │ Feature Store: 🟢 Online (12ms)   │ │ │ Precision: 0.845           │   │
│ │ Database:      🟢 Online (5ms)    │ │ │ Recall: 0.782              │   │
│ │ Queue:         🟢 Online (0 msgs) │ │ │ F1: 0.812                  │   │
│ │                                    │ │ │ Trained: Today 03:00       │   │
│ │ Uptime (30d): 99.97%              │ │ │ Features: 218              │   │
│ └────────────────────────────────────┘ │ └────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────────────┘
```

## Alerting Rules

### Fraud Rate Anomaly Alerts

```
┌─────────────────────────────────────────────────────────────────────┐
│ ALERT: FRAUD RATE SPIKE                                            │
│                                                                    │
│ Condition: Fraud rate > 3σ from 7-day rolling baseline             │
│ Severity: Critical (P0)                                            │
│ Channel: Slack #fraud-alerts + SMS to on-call manager              │
│ Message: "⚠️ FRAUD RATE SPIKE: Current {rate}%, Baseline {base}%  │
│           ({std}σ above mean). Top rules: {rules}. Affected:       │
│           {users} users."                                          │
│                                                                    │
│ Auto-response:                                                     │
│ 1. Check recent rule deployments (last 24h)                        │
│ 2. Check ML model drift metrics                                    │
│ 3. If rule-related: auto-disable recent rules                      │
│ 4. If ML-related: fallback to rules-only                           │
│ 5. If unknown: escalate to on-call data scientist                  │
└─────────────────────────────────────────────────────────────────────┘
```

### All Alert Rules

| Alert                   | Condition                 | Severity | Channel            | Auto-action                    |
| ----------------------- | ------------------------- | -------- | ------------------ | ------------------------------ |
| Fraud rate spike        | > 3σ baseline (1h)        | P0       | Slack + SMS        | Investigate rules + model      |
| Fraud rate critical     | > 0.5% (1h)               | P0       | Slack + SMS + Call | Auto-escalate to CTO           |
| FP rate spike           | > 5% (1h) or > 3σ         | P0       | Slack + SMS        | Auto-disable recent rules      |
| FP rate critical        | > 10% (1h)                | P0       | Slack + SMS + Call | Switch to "all review" mode    |
| Decision time high      | P50 > 200ms (5min)        | P1       | Slack              | Scale ML service               |
| Decision time critical  | P50 > 500ms or P99 > 1s   | P0       | Slack + SMS        | Circuit break, reduce features |
| ML service down         | > 30s unavailable         | P0       | Slack + SMS        | Fallback to rules-only         |
| Database slow           | Write latency > 100ms     | P1       | Slack              | Scale database                 |
| Model AUC drop          | < 0.85 after retrain      | P1       | Slack              | Auto-rollback to previous      |
| Rule hit rate anomaly   | > 5% or < 0.1% per rule   | P2       | Slack              | Flag for review                |
| Fraud cases surge       | > 3σ from baseline (1h)   | P1       | Slack              | Investigate attack vector      |
| Recovery rate low       | < 10% (30d rolling)       | P2       | Slack              | Review recovery procedures     |
| Feature extraction fail | > 1% timeouts (5min)      | P1       | Slack              | Check feature service          |
| SIM swap API down       | > 5min unavailable        | P2       | Slack              | Fallback to no SIM check       |
| Offline queue full      | > 80% capacity            | P2       | Slack              | Check agent connectivity       |
| Batch screening alert   | Daily batch > 100 flagged | P2       | Slack              | Review offline txns            |

## Health Check Endpoints (Internal)

| Endpoint                    | Check                          | Expected                        |
| --------------------------- | ------------------------------ | ------------------------------- |
| `GET /health/fraud-engine`  | Full pipeline test transaction | Respond < 200ms, valid decision |
| `GET /health/ml-service`    | ML model loads + scores test   | Probability returned < 50ms     |
| `GET /health/rules-engine`  | Rule set loaded + test         | Top N rules evaluate correctly  |
| `GET /health/feature-store` | Feature computation test       | Features computed < 50ms        |
| `GET /health/database`      | DB read/write test             | Query < 10ms                    |
| `GET /health/queue`         | Queue depth                    | < 1000 messages                 |
| `GET /health/cache`         | Redis read/write               | < 5ms                           |

## Logging

### Fraud Decision Log

Every transaction decision is logged:

```json
{
  "timestamp": "2025-03-14T15:42:30.150+03:00",
  "level": "info",
  "channel": "fraud-decision",
  "transaction_id": "txn_8hJ2kL4mN6pQ9rS1tU3v",
  "decision": "block",
  "risk_score": 92,
  "processing_time_ms": 87,
  "rules_triggered": ["DEV-001", "TAMT-001", "LOC-002"],
  "ml_score": 0.88,
  "source_module": "wallet",
  "amount": 500000,
  "currency": "SYP"
}
```

### Structured Log Levels

| Level   | Use Case              | Example                                  |
| ------- | --------------------- | ---------------------------------------- |
| ERROR   | System failure        | ML service unreachable, database timeout |
| WARNING | Anomaly detected      | FP rate > 5%, decision time > 300ms      |
| INFO    | Normal operations     | Transaction screened, decision made      |
| DEBUG   | Detailed tracing      | Feature values per transaction           |
| AUDIT   | Immutable audit trail | State transition, user appeal            |

## Monitoring Infrastructure

| Tool                 | Purpose           | Metrics                                   |
| -------------------- | ----------------- | ----------------------------------------- |
| Laravel Horizon      | Queue monitoring  | Queue depth, processing time, failed jobs |
| Grafana + Prometheus | Metrics dashboard | Fraud KPI charts, system health           |
| Laravel Telescope    | Request profiling | Slow requests, query performance          |
| Sentry               | Error tracking    | Exception monitoring, stack traces        |
| PagerDuty / On-call  | Alert routing     | P0/P1 alerts routed to on-call            |
| Custom Slack Bot     | Fraued alerts     | Real-time alert feed in #fraud-alerts     |
| Logtail / ELK        | Log aggregation   | Fraud decision logs, searchable           |

## Runbook

### Daily Monitoring Checklist

```
☐ Dashboard review: fraud rate, FP rate, decision time
☐ Rule performance review: top rules, hit rate, FP rate per rule
☐ Model metrics check: AUC, precision, recall, drift
☐ Case queue check: open cases, SLA compliance
☐ Agent fraud alerts review
☐ CBS reportability check (any fraud > 1M SYP?)
☐ System health: ML service, database, queue depth
☐ Offline queue check (from POS devices)
```

### Weekly Review

```
☐ Model performance deep dive: feature importance, confusion matrix
☐ Rule efficiency: underperforming rules identified for improvement
☐ Fraud typology trends: new patterns emerging?
☐ False positive analysis: common FP patterns to fix
☐ Recovery rate review: are we recovering enough?
☐ CBS SAR queue: any pending SAR filings?
☐ User appeal metrics: volume, resolution time, satisfaction
```

### Monthly Review

```
☐ Fraud loss P&L for CFO
☐ Model retraining effectiveness
☐ Red team test results (if conducted)
☐ Regulatory report preparation
☐ Fraud team performance (case resolution, SLA compliance)
☐ Budget vs actual for fraud prevention
☐ Next month's fraud risk forecast
```
