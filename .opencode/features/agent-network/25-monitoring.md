# Agent Network Monitoring

## Prometheus Metrics

### Agent-Level Metrics
```prometheus
# Agent float balance (gauge per agent)
beza_agent_float_balance{agent_id="10234", agent_code="BZ-10234", tier="bronze", city="دمشق"}

# Agent float status (0=ok, 1=low, 2=critical)
beza_agent_float_status{agent_id="10234"}

# Agent daily transaction counts (counter, reset daily)
beza_agent_transactions_total{agent_id="10234", type="cash_in"} 12
beza_agent_transactions_total{agent_id="10234", type="cash_out"} 8

# Agent daily volume (counter, reset daily)
beza_agent_volume_total{agent_id="10234", type="cash_in"} 1,500,000
beza_agent_volume_total{agent_id="10234", type="cash_out"} 850,000

# Agent commission accrued today
beza_agent_commission_accrued{agent_id="10234"} 12,500

# Agent commission pending settlement
beza_agent_commission_pending{agent_id="10234"} 45,000

# Agent last activity timestamp
beza_agent_last_activity{agent_id="10234"}

# Agent device online (1=online, 0=offline)
beza_agent_device_online{agent_id="10234", device_serial="DEVICE-SN-ABC123"} 1

# Agent offline queue depth
beza_agent_offline_queue_depth{agent_id="10234"} 0

# Agent fraud flags (counter)
beza_agent_fraud_flags_total{agent_id="10234"} 0
```

### System-Level Metrics
```prometheus
# API request rates
beza_api_requests_total{endpoint="/agent/cash-in", status="200"} 1500
beza_api_requests_total{endpoint="/agent/cash-in", status="400"} 12
beza_api_requests_total{endpoint="/agent/cash-in", status="500"} 1

# API latency (p50, p95, p99)
beza_api_latency_seconds{endpoint="/agent/cash-in", quantile="0.5"} 0.350
beza_api_latency_seconds{endpoint="/agent/cash-in", quantile="0.95"} 1.200
beza_api_latency_seconds{endpoint="/agent/cash-in", quantile="0.99"} 2.800

# Sync service metrics
beza_sync_batch_size{status="success"}
beza_sync_batch_size{status="failed"}
beza_sync_retry_count{agent_id="10234"}

# Offline transaction metrics
beza_offline_transactions_queued{agent_id="10234"}
beza_offline_transactions_synced{agent_id="10234"}
beza_offline_transactions_failed{agent_id="10234"}

# Active agents gauge
beza_active_agents{tier="bronze"} 4500
beza_active_agents{tier="silver"} 3000
beza_active_agents{tier="gold"} 1500
beza_active_agents{tier="platinum"} 500

# Transaction throughput
beza_transactions_per_second{type="all"} 20
```

## Grafana Dashboard: Agent Network Overview

### Dashboard: Agent Network Overview
```
Layout: 4 rows with panels

Row 1: Network KPIs
┌──────────────────┐ ┌──────────────────┐ ┌──────────────────┐ ┌──────────────────┐
│ Active Agents     │ │ Today's Volume   │ │ Today's Txns     │ │ Avg Agent Float   │
│ 8,000 / 10,000   │ │ 2.3B SYP         │ │ 45,000           │ │ 1,250,000 SYP     │
│ 🟢 80% active    │ │ ▲ 12% vs yday   │ │ ▲ 8% vs yday    │ │ 🟡 Normal         │
└──────────────────┘ └──────────────────┘ └──────────────────┘ └──────────────────┘

Row 2: Agent Map
┌─────────────────────────────────────────────────────────────────────────────────┐
│  [Map of Syria with agent markers]                                              │
│  ● Active (green)  ● Low float (yellow)  ● Critical (red)  ● Offline (gray)    │
│                                                                                  │
│  Filters: City dropdown, Tier dropdown, Status dropdown, Search agent code      │
│  Hover: Agent name, float, last activity                                        │
│  Click: Agent detail drill-down                                                  │
└─────────────────────────────────────────────────────────────────────────────────┘

Row 3: Charts
┌─────────────────────────────┐ ┌─────────────────────────────┐
│ Cash-in/Out Volume (24h)    │ │ Agent Activation Trend      │
│ [Area chart: cash-in green, │ │ [Line chart: cumulative     │
│  cash-out red, x=hour]      │ │  active agents, last 30d]   │
│ ─────────────────────────   │ │ ─────────────────────────   │
│ Current hour:               │ │ Today: 8,000                │
│   Cash-in: 120M SYP         │ │ This month: +350            │
│   Cash-out: 80M SYP         │ │ Target: 10,000 by EOY       │
└─────────────────────────────┘ └─────────────────────────────┘

┌─────────────────────────────┐ ┌─────────────────────────────┐
│ Commission Accrual (7d)     │ │ Float Health Distribution   │
│ [Bar chart: daily           │ │ [Pie chart: % agents by     │
│  commission in SYP]         │ │  float status]              │
│ ─────────────────────────   │ │ ─────────────────────────   │
│ Today: 5.2M SYP             │ │ OK (75%)  Low (18%)  Crit (7%)│
│ MTD: 125M SYP               │ │ Alert: 560 agents low float │
└─────────────────────────────┘ └─────────────────────────────┘

Row 4: Alerts & Exceptions
┌─────────────────────────────┐ ┌─────────────────────────────┐
│ Active Alerts               │ │ Recent Anomalies            │
│ ─────────────────────────   │ │ ─────────────────────────   │
│ 🟡 Float low: 342 agents   │ │ ⚠️ Agent 10234: Float       │
│ 🔴 Float critical: 78     │ │    discrepancy 12,000 SYP    │
│ ⚫ Device offline >4h: 23  │ │ ⚠️ Agent 10678: 15 rapid    │
│ 🟢 Pending sync: 156 txns  │ │    cash-outs in 30 min      │
│                            │ │ ⚠️ Agent 11002: Operating   │
│                            │ │    outside hours (3x)        │
└─────────────────────────────┘ └─────────────────────────────┘
```

### Agent Detail Drill-Down Panel
```
Agent Detail: BZ-10234 — بقالة أبو محمد
┌──────────────────────────────────────────────────────────────┐
│ Info: Tier ⭐ Bronze | City دمشق | District المزة             │
│ Status: 🟢 Active | Device: ✅ Online | Last seen: 2 min ago │
│ Float: 1,250,000 SYP (🟡 Low alert at 100K)                 │
├──────────────────────────────────────────────────────────────┤
│ Today: Cash-in 1.5M (12 txns) | Cash-out 850K (8 txns)     │
│ Commission today: 12,500 | This month: 325,000              │
│ Pending settlement: 45,000                                   │
├──────────────────────────────────────────────────────────────┤
│ Last 10 transactions (scrollable list)                       │
│ 🟢 CI 100,000 10:30 | 🟢 CI 50,000 10:15                   │
│ 🔴 CO 25,000 09:45 | 🟢 CI 200,000 09:30                   │
│ ...                                                          │
├──────────────────────────────────────────────────────────────┤
│ Actions: [Send Notification] [Suspend] [View All Txns]      │
└──────────────────────────────────────────────────────────────┘
```

## Alerts

### Critical Alerts (PagerDuty / Slack #ops-critical)
| Alert | Condition | Action |
|-------|-----------|--------|
| Agent Network Outage | API error rate > 5% for 5 min | Page on-call engineer |
| Float Discrepancy > 100K | Single agent discrepancy > 100,000 SYP | Investigate immediately |
| Sync Service Down | Sync replicas < 1 for 2 min | Restart service |
| Database Replication Lag | Replica lag > 30 seconds | Check DB health |

### Warning Alerts (Slack #ops-warning)
| Alert | Condition | Action |
|-------|-----------|--------|
| Agent Float < 50K | Any agent float below 50,000 SYP | Send SMS alert to agent |
| Transaction Spike > 2x | Agent volume > 2x their 7-day avg | Check for fraud |
| Device Offline > 4h | Device not seen for 4+ hours | Call agent to check |
| Commission Calculation Error | Batch settlement failure | Manual re-run |
| Agent Registration Spike | >50 new registrations in 1 hour | Verify no bot registrations |

### Informational Alerts (Slack #ops-info)
| Alert | Condition |
|-------|-----------|
| Daily summary | Every 23:00: Today's volume, new agents, top agents |
| Tier upgrade eligible | Agent meets criteria for next tier |
| KYC expiry reminder | Agent KYC expires in 30 days |
| Monthly report ready | Agent monthly performance report generated |

### Alert Routing
```
P0 (Critical): PagerDuty → on-call engineer (phone call, 15 min response)
P1 (High): Slack #ops-critical → engineering team (30 min response)
P2 (Medium): Slack #ops-warning → ops team (2 hour response)
P3 (Low): Slack #ops-info → no response needed (informational)
```

## Monitoring SLOs
| Metric | Target | Measurement |
|--------|--------|-------------|
| Cash-in API availability (200 OK) | 99.9% | Request success rate |
| Cash-out API availability | 99.9% | Request success rate |
| API latency P95 (cash-in/out) | < 2 seconds | Prometheus |
| Sync success rate | > 99% | Sync batches |
| Float accuracy (|actual-expected|) | < 5,000 SYP per agent | EOD reconciliation |
| Offline queue sync time | < 5 min | Queue -> synced |
| Agent alert delivery (SMS) | < 30 seconds | SMS provider |
| Dashboard data freshness | < 1 minute | Grafana |
