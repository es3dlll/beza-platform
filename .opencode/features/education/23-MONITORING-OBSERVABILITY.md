# 23 — Monitoring & Observability

## 23.1 Key Metrics to Monitor

### Business Metrics (Grafana Dashboard)

| Metric | Threshold | Alert |
|---|---|---|
| TPV (daily) | < 50% of expected | 🔴 TPV anomaly |
| Payment success rate | < 98% | 🔴 High failure rate |
| School onboarding rate | < 5/week | 🟡 Slow onboarding |
| Parent activation rate | < 60% of registered students | 🟡 Activation low |
| Collection rate (across schools) | < 70% | 🟡 Low collection |
| Avg payment completion time | > 15s | 🔴 Slow payments |
| Financing approval rate | < 40% | 🟡 Tight scoring |
| Diaspora payment volume | < 5% of total | 🟡 Low diaspora uptake |

### Technical Metrics

| Metric | Threshold | Alert |
|---|---|---|
| API p99 latency | > 3s | 🟡 Slow API |
| API error rate (5xx) | > 1% | 🔴 API errors |
| DB connection count | > 100 | 🟡 DB connections |
| DB replication lag | > 5s | 🟡 Replication lag |
| Kafka consumer lag | > 10,000 | 🟡 Consumer lag |
| Redis memory | > 80% | 🟡 Redis memory |
| Disk space (DB) | > 75% | 🟡 Disk space |
| SSL certificate expiry | < 30 days | 🟡 Cert expiring |

## 23.2 Logging Strategy

| Layer | Tool | Retention | Detail |
|---|---|---|---|
| Application logs | ELK (Elasticsearch + Kibana) | 90 days | All JSON-structured logs |
| API access logs | ELK | 180 days | Full request/response |
| Payment audit logs | Immutable store (DB) | 10 years | Financial audit trail |
| Infrastructure logs | Grafana Loki | 30 days | Container/system logs |
| Security logs | Wazuh SIEM | 1 year | IDS/IPS, auth attempts |

### Log Levels
- **ERROR**: Payment failure, DB connection loss, external API failure
- **WARN**: Rate limit approaching, high latency, retry attempts
- **INFO**: Payment completed, invoice generated, school onboarded
- **DEBUG**: Full request/response (enabled on-demand per user)

## 23.3 Tracing

- Distributed tracing via OpenTelemetry (Jaeger backend)
- All inter-service requests include trace context (W3C Trace Context)
- Key traces: full payment flow, invoice generation batch, settlement batch
- Sampling: 100% for payments, 10% for read operations

## 23.4 Alerting

| Severity | Channel | Response Time | Escalation |
|---|---|---|---|
| 🔴 Critical | Phone call + PagerDuty | 5 min | Engineering lead → CTO |
| 🟡 Warning | WhatsApp group + PagerDuty | 15 min | On-call engineer |
| 🔵 Info | Slack channel | Next business day | Team review |

### On-Call Rotation
- Primary: 1 backend engineer (24h, weekly rotation)
- Secondary: 1 infrastructure engineer
- Escalation: Engineering Manager → Head of Engineering

## 23.5 Synthetic Monitoring

| Check | Frequency | Locations |
|---|---|---|
| Parent payment flow (full E2E) | Every 5 min | Damascus, Aleppo, Latakia |
| School dashboard login | Every 5 min | Damascus |
| API health (GET /health) | Every 1 min | All locations |
| Receipt PDF generation | Every 15 min | Damascus |
| WhatsApp notification delivery | Every 30 min | Damascus |
| School onboarding flow (staging) | Daily | Damascus |

## 23.6 Uptime SLA

| Component | Target SLA | Measured By |
|---|---|---|
| API (payment + queries) | 99.9% | Synthetic + real user monitoring |
| School Dashboard | 99.5% | Synthetic checks |
| Parent app backend | 99.9% | API uptime |
| Payment processing | 99.99% (during term start) | Transaction monitoring |
| Report downloads | 99.5% | Synthetic checks |
