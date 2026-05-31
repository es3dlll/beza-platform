# Operational KPI Catalog

Every metric the operations team tracks, formatted for monitoring dashboards and alerting.

---

## KPI Format

```markdown
## KPI-001: Daily Active Users (DAU)

| Attribute       | Value                                                                         |
| --------------- | ----------------------------------------------------------------------------- |
| Category        | Growth                                                                        |
| Definition      | Unique users who performed at least one financial transaction in the last 24h |
| Formula         | COUNT(DISTINCT user_id) WHERE transaction_date = TODAY                        |
| Target          | 5,000 (V1 M1), 50,000 (V1 M6)                                                 |
| Alert Threshold | < 3,000 (P2)                                                                  |
| Frequency       | Real-time                                                                     |
| Owner           | Product Manager                                                               |
| Source          | `wallet_transactions.created_at`                                              |
| Dashboard       | Command Center → Financial Health                                             |
```

---

## KPI-001: Daily Active Users (DAU)

| Attribute       | Value                                                                         |
| --------------- | ----------------------------------------------------------------------------- |
| Category        | Growth                                                                        |
| Definition      | Unique users who performed at least one financial transaction in the last 24h |
| Formula         | COUNT(DISTINCT user_id) WHERE transaction_date = TODAY                        |
| Target          | 5,000 (V1 M1), 50,000 (V1 M6)                                                 |
| Alert Threshold | < 3,000 (P2)                                                                  |
| Frequency       | Real-time                                                                     |
| Owner           | Product Manager                                                               |
| Source          | `wallet_transactions.created_at`                                              |
| Dashboard       | Command Center → Financial Health                                             |

---

## KPI-002: Monthly Active Users (MAU)

| Attribute       | Value                                                                             |
| --------------- | --------------------------------------------------------------------------------- |
| Category        | Growth                                                                            |
| Definition      | Unique users who performed at least one financial transaction in the last 30 days |
| Formula         | COUNT(DISTINCT user_id) WHERE transaction_date >= TODAY - 30                      |
| Target          | 20,000 (V1 M1), 200,000 (V1 M6)                                                   |
| Alert Threshold | < 12,000 (P2)                                                                     |
| Frequency       | Daily                                                                             |
| Owner           | Product Manager                                                                   |
| Source          | `wallet_transactions.created_at`                                                  |
| Dashboard       | Command Center → Financial Health                                                 |

---

## KPI-003: Wallet Activation Rate

| Attribute       | Value                                                                                       |
| --------------- | ------------------------------------------------------------------------------------------- |
| Category        | Growth                                                                                      |
| Definition      | % of registered users who completed their first transaction within 7 days of registration   |
| Formula         | COUNT(users WHERE first_txn_date - registration_date <= 7) / COUNT(registered_users) \* 100 |
| Target          | > 60%                                                                                       |
| Alert Threshold | < 45% (P3)                                                                                  |
| Frequency       | Daily                                                                                       |
| Owner           | Growth Lead                                                                                 |
| Source          | `users.created_at`, `wallet_transactions.created_at`                                        |
| Dashboard       | Command Center → User Funnel                                                                |

---

## KPI-004: KYC Conversion Rate

| Attribute       | Value                                                                                                   |
| --------------- | ------------------------------------------------------------------------------------------------------- |
| Category        | Growth                                                                                                  |
| Definition      | % of registered users who reach KYC Tier 2 within 30 days of sign-up                                    |
| Formula         | COUNT(users WHERE kyc_tier = 2 AND kyc_approved_at - created_at <= 30) / COUNT(registered_users) \* 100 |
| Target          | > 50%                                                                                                   |
| Alert Threshold | < 35% (P3)                                                                                              |
| Frequency       | Daily                                                                                                   |
| Owner           | Compliance Lead                                                                                         |
| Source          | `users.kyc_tier`, `users.kyc_approved_at`                                                               |
| Dashboard       | Command Center → User Funnel                                                                            |

---

## KPI-005: Average Transaction Value (ATV)

| Attribute       | Value                                                             |
| --------------- | ----------------------------------------------------------------- |
| Category        | Usage                                                             |
| Definition      | Mean transaction amount in SYP across all successful transactions |
| Formula         | SUM(amount) / COUNT(transaction_id) WHERE status = 'completed'    |
| Target          | 25,000 SYP (target), 15,000–50,000 SYP (healthy range)            |
| Alert Threshold | < 10,000 SYP or > 75,000 SYP (P3)                                 |
| Frequency       | Daily                                                             |
| Owner           | Product Manager                                                   |
| Source          | `wallet_transactions.amount`                                      |
| Dashboard       | Command Center → Financial Health                                 |

---

## KPI-006: Transaction Success Rate

| Attribute       | Value                                                                                      |
| --------------- | ------------------------------------------------------------------------------------------ |
| Category        | Reliability                                                                                |
| Definition      | % of initiated transactions that complete with status 'completed' (excluding fraud blocks) |
| Formula         | COUNT(completed) / COUNT(initiated) \* 100                                                 |
| Target          | > 98%                                                                                      |
| Alert Threshold | < 95% (P1)                                                                                 |
| Frequency       | Real-time                                                                                  |
| Owner           | Engineering Lead                                                                           |
| Source          | `wallet_transactions.status`                                                               |
| Dashboard       | Ops Center → Transaction Monitoring                                                        |

---

## KPI-007: Cash-Out Success Rate

| Attribute       | Value                                                                               |
| --------------- | ----------------------------------------------------------------------------------- |
| Category        | Reliability                                                                         |
| Definition      | % of agent cash-out requests that are fulfilled within 10 minutes                   |
| Formula         | COUNT(cashouts WHERE fulfilled_at - requested_at <= 10min) / COUNT(cashouts) \* 100 |
| Target          | > 95%                                                                               |
| Alert Threshold | < 90% (P1)                                                                          |
| Frequency       | Real-time                                                                           |
| Owner           | Agent Operations Lead                                                               |
| Source          | `agent_cashouts.status`, `agent_cashouts.fulfilled_at`                              |
| Dashboard       | Ops Center → Agent Operations                                                       |

---

## KPI-008: Remittance Success Rate

| Attribute       | Value                                                                                                            |
| --------------- | ---------------------------------------------------------------------------------------------------------------- |
| Category        | Reliability                                                                                                      |
| Definition      | % of remittance transactions delivered to recipient wallet within SLA (4 hours domestic, 24 hours international) |
| Formula         | COUNT(remittances WHERE delivered_at - initiated_at <= SLA) / COUNT(remittances) \* 100                          |
| Target          | > 97%                                                                                                            |
| Alert Threshold | < 93% (P1)                                                                                                       |
| Frequency       | Daily                                                                                                            |
| Owner           | Remittance Operations Lead                                                                                       |
| Source          | `remittances.status`, `remittances.delivered_at`                                                                 |
| Dashboard       | Ops Center → Remittance Monitoring                                                                               |

---

## KPI-009: Settlement SLA

| Attribute       | Value                                                                              |
| --------------- | ---------------------------------------------------------------------------------- |
| Category        | Reliability                                                                        |
| Definition      | % of settlement batches completed within target: D+0 for agents, D+1 for merchants |
| Formula         | COUNT(batches WHERE settled_at <= target) / COUNT(batches) \* 100                  |
| Target          | > 99%                                                                              |
| Alert Threshold | < 97% (P1)                                                                         |
| Frequency       | Daily                                                                              |
| Owner           | Treasury Lead                                                                      |
| Source          | `settlement_batches.settled_at`, `settlement_batches.batch_date`                   |
| Dashboard       | Ops Center → Settlement Status                                                     |

---

## KPI-010: Agent Liquidity Coverage

| Attribute       | Value                                                                                       |
| --------------- | ------------------------------------------------------------------------------------------- |
| Category        | Financial Health                                                                            |
| Definition      | % of agents whose current float balance exceeds 110% of their average daily cash-out volume |
| Formula         | COUNT(agents WHERE float_balance > 1.1 _ avg_daily_cashout_7d) / COUNT(active_agents) _ 100 |
| Target          | > 85%                                                                                       |
| Alert Threshold | < 70% (P2)                                                                                  |
| Frequency       | Daily                                                                                       |
| Owner           | Agent Operations Lead                                                                       |
| Source          | `agent_wallets.float_balance`, `agent_cashouts.amount`                                      |
| Dashboard       | Treasury → Agent Liquidity Map                                                              |

---

## KPI-011: Fraud Loss %

| Attribute       | Value                                                                                       |
| --------------- | ------------------------------------------------------------------------------------------- |
| Category        | Risk                                                                                        |
| Definition      | Total confirmed fraud losses divided by total transaction volume, expressed as a percentage |
| Formula         | SUM(fraud_loss_amount) / SUM(transaction_amount) \* 100                                     |
| Target          | < 0.1%                                                                                      |
| Alert Threshold | > 0.2% (P1)                                                                                 |
| Frequency       | Daily                                                                                       |
| Owner           | Risk Lead                                                                                   |
| Source          | `fraud_cases.loss_amount`, `wallet_transactions.amount`                                     |
| Dashboard       | Risk Center → Fraud Summary                                                                 |

---

## KPI-012: False Positive Rate

| Attribute       | Value                                                                                     |
| --------------- | ----------------------------------------------------------------------------------------- |
| Category        | Risk                                                                                      |
| Definition      | % of transactions blocked by fraud engine that are later manually confirmed as legitimate |
| Formula         | COUNT(blocked_txns WHERE review_outcome = 'legitimate') / COUNT(blocked_txns) \* 100      |
| Target          | < 3%                                                                                      |
| Alert Threshold | > 5% (P2)                                                                                 |
| Frequency       | Daily                                                                                     |
| Owner           | Risk Lead                                                                                 |
| Source          | `fraud_engine_logs.block_reason`, `manual_reviews.outcome`                                |
| Dashboard       | Risk Center → Fraud Engine Tuning                                                         |

---

## KPI-013: Chargeback Rate

| Attribute       | Value                                                                                    |
| --------------- | ---------------------------------------------------------------------------------------- |
| Category        | Risk                                                                                     |
| Definition      | % of merchant transactions disputed by the user or reversed through a chargeback process |
| Formula         | COUNT(disputed_txns) / COUNT(merchant_txns) \* 100                                       |
| Target          | < 0.5%                                                                                   |
| Alert Threshold | > 1.0% (P2)                                                                              |
| Frequency       | Daily                                                                                    |
| Owner           | Risk Lead                                                                                |
| Source          | `wallet_transactions.dispute_flag`, `disputes.status`                                    |
| Dashboard       | Risk Center → Merchant Risk                                                              |

---

## KPI-014: API P99 Latency

| Attribute       | Value                                                                                                        |
| --------------- | ------------------------------------------------------------------------------------------------------------ |
| Category        | Performance                                                                                                  |
| Definition      | 99th percentile response time for all core API endpoints (wallet, remittance, payments) measured server-side |
| Formula         | PERCENTILE(response_time, 0.99)                                                                              |
| Target          | < 200ms                                                                                                      |
| Alert Threshold | > 500ms (P1)                                                                                                 |
| Frequency       | Real-time (5-min buckets)                                                                                    |
| Owner           | Engineering Lead                                                                                             |
| Source          | `api_metrics.p99_latency_ms`                                                                                 |
| Dashboard       | Engineering → API Performance                                                                                |

---

## KPI-015: SMS Delivery Rate

| Attribute       | Value                                                                                               |
| --------------- | --------------------------------------------------------------------------------------------------- |
| Category        | Reliability                                                                                         |
| Definition      | % of transactional SMS messages successfully delivered to the handset within 30 seconds of dispatch |
| Formula         | COUNT(sms WHERE delivered = true AND delivery_time <= 30s) / COUNT(sms_sent) \* 100                 |
| Target          | > 99%                                                                                               |
| Alert Threshold | < 97% (P1)                                                                                          |
| Frequency       | Real-time                                                                                           |
| Owner           | Engineering Lead                                                                                    |
| Source          | `sms_logs.delivery_status`, `sms_logs.delivery_time_ms`                                             |
| Dashboard       | Ops Center → Notification Health                                                                    |

---

## KPI-016: First Response Time (Support)

| Attribute       | Value                                                                                         |
| --------------- | --------------------------------------------------------------------------------------------- |
| Category        | Customer Experience                                                                           |
| Definition      | Average time elapsed between a support ticket being opened and the first human agent response |
| Formula         | AVG(first_agent_reply_at - ticket_created_at)                                                 |
| Target          | < 4 hours                                                                                     |
| Alert Threshold | > 8 hours (P2)                                                                                |
| Frequency       | Daily                                                                                         |
| Owner           | Support Lead                                                                                  |
| Source          | `support_tickets.created_at`, `support_tickets.first_response_at`                             |
| Dashboard       | Support Center → SLA Dashboard                                                                |

---

## KPI-017: KYC Review SLA

| Attribute       | Value                                                                                              |
| --------------- | -------------------------------------------------------------------------------------------------- |
| Category        | Compliance                                                                                         |
| Definition      | % of KYC Tier 2 applications reviewed (approved or rejected) within 48 hours of submission         |
| Formula         | COUNT(kyc_reviews WHERE review_completed_at - submitted_at <= 48h) / COUNT(kyc_submissions) \* 100 |
| Target          | > 95%                                                                                              |
| Alert Threshold | < 90% (P2)                                                                                         |
| Frequency       | Daily                                                                                              |
| Owner           | Compliance Lead                                                                                    |
| Source          | `kyc_submissions.submitted_at`, `kyc_reviews.review_completed_at`                                  |
| Dashboard       | Support Center → KYC Queue                                                                         |

---

## KPI-018: AML Queue Age

| Attribute       | Value                                                                                |
| --------------- | ------------------------------------------------------------------------------------ |
| Category        | Compliance                                                                           |
| Definition      | Maximum elapsed time of any pending AML review in the queue (oldest unreviewed case) |
| Formula         | MAX(NOW() - pending_review.created_at)                                               |
| Target          | < 2 hours                                                                            |
| Alert Threshold | > 4 hours (P1)                                                                       |
| Frequency       | Real-time                                                                            |
| Owner           | Compliance Lead                                                                      |
| Source          | `aml_reviews.created_at`, `aml_reviews.status`                                       |
| Dashboard       | Risk Center → AML Queue Monitoring                                                   |
