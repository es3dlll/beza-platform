# Settlement Incident Response

## Incident Classification

| Severity | Level | Examples | Response Time |
|----------|-------|----------|---------------|
| SEV-1 | Critical | Settlement engine down, settlement pool imbalance, bank API outage | 15 min |
| SEV-2 | High | Batch processing stuck, confirmation timeout, high exception rate | 30 min |
| SEV-3 | Medium | Failed payment orders (individual), reconciliation delay | 2 hours |
| SEV-4 | Low | Report generation delay, dashboard lag | Next business day |

## Runbooks

### Runbook IR-001: Settlement Engine Down

**Symptoms**: All batch processing fails, workers crash, queue backlog

**Immediate (0-15 min)**:
```
1. Check worker health:
   php artisan horizon:status
   php artisan queue:monitor settlement-high

2. Check queue backlog:
   Queue::size('settlement-high')
   Queue::size('settlement-medium')

3. Check Horizon dashboard for failed jobs:
   • Navigate to /horizon/failed
   • Identify failure pattern

4. If worker crash:
   php artisan horizon:terminate
   php artisan horizon
   supervisorctl restart laravel-worker:*

5. If queue driver issue (Redis):
   redis-cli PING
   redis-cli INFO memory
   • Restart Redis if needed / check memory usage

6. If database connection issue:
   php artisan db:monitor
   • Check MySQL connection pool
```

**Resolution (15-60 min)**:
```
1. Resume failed jobs:
   php artisan queue:retry all
   // OR selectively retry:
   php artisan queue:retry --queue=settlement-high

2. Re-process stalled batches:
   // Find batches stuck in 'processing' for >30 min
   $batches = SettlementBatch::where('status', 'processing')
       ->where('processed_at', '<', now()->subMinutes(30))
       ->get();
   foreach ($batches as $batch) {
       dispatch(new ProcessBatchJob($batch));
   }

3. Verify settlement pool balance:
   SELECT * FROM cfe_ledger WHERE account = 'cfe_acc_int_settlement';
   // Must be 0 at EOD
```

**Post-Mortem**:
```
1. Root cause analysis within 24 hours
2. Update monitoring alerts
3. Improve worker resilience
```

### Runbook IR-002: Settlement Pool Imbalance

**Symptoms**: Pool balance not zero at EOD, CFE discrepancy

**Immediate (0-30 min)**:
```
1. Calculate expected pool balance:
   $opening = 0;
   $transfersIn = SettlementBatch::sum('total_amount'); // from CFE to pool
   $collected = SettlementBatchItem::where('net_amount', '<', 0)->sum('net_amount');
   $paid = SettlementBatchItem::where('net_amount', '>', 0)->sum('net_amount');
   $expected = $opening + $transfersIn + abs($collected) - $paid;
   // expected must be 0

2. Query actual balance:
   $actual = CfeService::getBalance('cfe_acc_int_settlement');

3. Identify discrepancy:
   $difference = abs($expected) - abs($actual);

4. Hold further settlements:
   php artisan settlement:hold-all "Pool imbalance: {$difference} SYP"
```

**Resolution**:
```
1. Trace each batch's journal entries
2. Find the batch with missing or duplicate entries
3. Create adjustment journal:
   DR/CR Pool Account     {$difference} SYP
   CR/DR Suspense Account {$difference} SYP
4. Mark incident in settlement_audit_log
5. Release held batches
```

### Runbook IR-003: Bank API Outage

**Symptoms**: Payment orders stuck in "generated" status, transmission failures

**Immediate (0-15 min)**:
```
1. Verify bank API status:
   curl -I https://api.bemo-syria.com/health

2. Switch to fallback protocol:
   // In config/banks.php
   'bemo_saudi_fransi' => [
       'primary' => 'api',
       'fallback' => 'sftp',
       'auto_fallback' => true,
       'fallback_after_failures' => 3,
   ]

3. Verify SFTP connectivity:
   sftp ops@bemo-sftp.bemo-syria.com

4. Re-route stuck payment orders:
   $orders = SettlementPaymentOrder::where('status', 'generated')
       ->where('created_at', '<', now()->subMinutes(15))
       ->get();
   foreach ($orders as $order) {
       $order->file_format = 'CSV'; // Switch format for SFTP
       dispatch(new TransmitPaymentOrderJob($order));
   }
```

**Restoration**:
```
1. When bank API recovers:
   php artisan settlement:restore-primary
2. Re-transmit any orders sent via fallback
3. Verify confirmations received
```

## Communication Templates

### SEV-1 Notification (SMS)
```
🚨 [BEZA-SETTLEMENT] استجابة للحوادث
Incident: {incident_type}
Batch: {batch_number}
Details: {brief_description}
Action: {immediate_action}
On-call: {engineer_name} | {phone}
```

### SEV-2 Email
```
Subject: [SETTLEMENT] Incident IR-{number} — {severity} — {title}

Description:
{detailed_description}

Affected:
- Batch(es): {batch_numbers}
- Service(s): {services_affected}
- Time: {incident_time}

Current Status: {status}
Action Taken: {actions}
Next Steps: {next_steps}

Owner: {assigned_engineer}
```

## Post-Mortem Template

```markdown
# Settlement Incident Post-Mortem: IR-{number}

## Summary
- **Date**: {date}
- **Duration**: {duration}
- **Severity**: {severity}
- **Impact**: Batches affected: {count}, Amount: {amount}, Exceptions: {count}

## Timeline
| Time | Event |
|------|-------|
| HH:MM | First alert |
| HH:MM | Engineer acknowledged |
| HH:MM | Root cause identified |
| HH:MM | Mitigation applied |
| HH:MM | Service restored |

## Root Cause
{detailed root cause analysis}

## Resolution
{steps taken to resolve}

## Action Items
- [ ] Item 1 (Owner, Due date)
- [ ] Item 2 (Owner, Due date)

## Lessons Learned
{what went well, what could be improved}
```
