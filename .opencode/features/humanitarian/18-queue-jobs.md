# Queue Jobs & Async Processing

## Infrastructure
- **Queue System:** BullMQ (Redis-backed)
- **Priority Levels:** critical, high, default, low
- **Retry Policy:** 3 retries with exponential backoff (1min, 5min, 30min)
- **DLQ:** Failed jobs routed to Dead Letter Queue for manual review

## Job Definitions

### 1. `enrollment.batch-validate`
| Attribute | Value |
|-----------|-------|
| **Trigger** | CSV upload to POST /programs/{id}/beneficiaries |
| **Priority** | High |
| **Description** | Parse CSV, validate each row, create beneficiary records |
| **Payload** | `{ batch_id, program_id, file_url }` |
| **Processing** | Stream read CSV → validate row-by-row → bulk insert valid → return error report |
| **Timeout** | 10 minutes |
| **Output** | `{ total, valid, errors[], sanctions_pending_count }` |

### 2. `sanctions.screen-batch`
| Attribute | Value |
|-----------|-------|
| **Trigger** | After enrollment.batch-validate completes |
| **Priority** | Critical |
| **Description** | Screen all new beneficiaries against UN/EU/OFAC lists |
| **Payload** | `{ batch_id, program_id, beneficiary_ids[] }` |
| **Processing** | For each beneficiary → fuzzy match name against 3 sanctions lists → score → flag for review if score > 80% |
| **Timeout** | 30 minutes (for 10k beneficiaries) |
| **Output** | `{ cleared: n, pending_review: n, blocked: n }` |

### 3. `distribution.process-batch`
| Attribute | Value |
|-----------|-------|
| **Trigger** | POST /distribute |
| **Priority** | Critical |
| **Description** | Execute batch distribution: credit wallets or issue vouchers |
| **Payload** | `{ batch_id, program_id, distribution_type, amount, beneficiary_ids[] }` |
| **Processing** | For MPC: call WalletService.batchCredit → mark aid_distribution_items → on complete, send notification jobs |
| **Timeout** | 5 minutes per 10k beneficiaries |
| **Idempotency** | Check idempotency_key before processing; skip already-processed |

### 3a. `distribution.credit-retry`
| Attribute | Value |
|-----------|-------|
| **Trigger** | distribution.process-batch single-item failure |
| **Priority** | Default |
| **Description** | Retry a failed wallet credit |
| **Payload** | `{ distribution_item_id, beneficiary_id, amount }` |
| **Max Retries** | 3 (then mark as failed, alert program manager) |

### 4. `notification.send-sms`
| Attribute | Value |
|-----------|-------|
| **Trigger** | distribution.process-batch completed / voucher.issued |
| **Priority** | High |
| **Description** | Send SMS notification to beneficiary |
| **Payload** | `{ phone_hash, template_name, variables{} }` |
| **Integration** | Twilio / local Syrian telco aggregator |
| **Timeout** | 30 seconds per message |
| **Batching** | Batch up to 100 SMS in a single job |

### 5. `voucher.issue-batch`
| Attribute | Value |
|-----------|-------|
| **Trigger** | POST /vouchers/create |
| **Priority** | High |
| **Description** | Generate unique 12-digit voucher codes + PINs for each beneficiary |
| **Payload** | `{ batch_id, program_id, voucher_value, item_list, expiry_days, beneficiary_ids[] }` |
| **Processing** | For each beneficiary → generate code+PIN → hash PIN → insert aid_vouchers → trigger notification.send-sms |
| **Timeout** | 10 minutes |

### 6. `voucher.expire-check`
| Attribute | Value |
|-----------|-------|
| **Trigger** | Scheduled (daily at 00:00 Syria time) |
| **Priority** | Low |
| **Description** | Expire all vouchers past expiry_date |
| **Payload** | None (query-based) |
| **Processing** | UPDATE aid_vouchers SET status='expired' WHERE expiry_date < NOW() AND status = 'active' |

### 7. `settlement.process-merchant`
| Attribute | Value |
|-----------|-------|
| **Trigger** | voucher redeemed (after T+2 settling period) |
| **Priority** | High |
| **Description** | Credit merchant wallet for redeemed voucher amounts |
| **Payload** | `{ merchant_id, voucher_redemption_ids[], total_amount }` |
| **Processing** | Aggregate redemptions → call WalletService.credit → update settlement_status 'settled' |

### 8. `report.generate-donor`
| Attribute | Value |
|-----------|-------|
| **Trigger** | User request or scheduled (monthly) |
| **Priority** | Default |
| **Description** | Aggregate data and generate donor report |
| **Payload** | `{ ngo_id, program_id, period_from, period_to, format }` |
| **Timeout** | 15 minutes |
| **Output** | Report stored in aid_donor_reports, URL returned to user |

### 9. `spending.aggregate-daily`
| Attribute | Value |
|-----------|-------|
| **Trigger** | Scheduled (daily at 02:00 Syria time) |
| **Priority** | Low |
| **Description** | Aggregate daily spending data by program, category, governorate |
| **Payload** | None |
| **Processing** | Read aid_spending_tracking for previous day → compute aggregates → write to materialised view |

## Queue Architecture

```
Client → API → Queue (BullMQ/Redis) → Worker Pool (Node.js)
                                         │
                    ┌────────────────────┼────────────────────┐
                    ▼                    ▼                    ▼
          enrollment.worker    distribution.worker    sanctions.worker
                    │                    │                    │
                    ▼                    ▼                    ▼
                PostgreSQL           Wallet API         Sanctions API
```

## Monitoring

| Metric | Alert Threshold |
|--------|-----------------|
| Queue wait time (critical) | > 30 seconds |
| Queue wait time (high) | > 2 minutes |
| Job failure rate | > 1% |
| DLQ job count | > 10 |
| distribution.process-batch timeout | Any timeout |
