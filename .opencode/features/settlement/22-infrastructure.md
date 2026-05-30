# Settlement Infrastructure

## Deployment Architecture

```
┌────────────────────────────────────────────────────────┐
│                     Load Balancer                       │
└──────────┬──────────────────────────┬──────────────────┘
           │                          │
┌──────────▼──────────┐   ┌──────────▼──────────┐
│  Web Server 1       │   │  Web Server 2       │
│  (Laravel + Queue)  │   │  (Laravel + Queue)  │
│  settlement-1       │   │  settlement-2       │
└──────────┬──────────┘   └──────────┬──────────┘
           │                          │
           └──────────┬───────────────┘
                      │
           ┌──────────▼──────────┐
           │   Redis Cluster      │  ← Queue + Cache
           │   (6 nodes)          │
           └──────────┬──────────┘
                      │
           ┌──────────▼──────────┐
           │   MySQL Cluster      │  ← Primary DB
           │   (3 nodes + replica)│
           └─────────────────────┘
                      │
           ┌──────────▼──────────┐
           │   S3/MinIO           │  ← Payment files, reports
           │   (Object Store)     │
           └─────────────────────┘
```

## Queue Infrastructure

### Queue Tiers
| Queue | Workers | Priority | Use Case |
|-------|---------|----------|----------|
| `settlement-high` | 5 workers | Immediate | ProcessBatch, TransmitPaymentOrder |
| `settlement-medium` | 3 workers | 30s delay | PollBankConfirmation, RetryFailedPayment |
| `settlement-low` | 2 workers | 5min delay | RunReconciliation, GenerateReport |

### Worker Configuration (Horizon)
```php
// config/horizon.php
'environments' => [
    'production' => [
        'settlement-high' => [
            'connection' => 'redis',
            'queue' => ['settlement-high'],
            'balance' => 'auto',
            'minProcesses' => 2,
            'maxProcesses' => 10,
            'tries' => 3,
            'timeout' => 300,
        ],
        'settlement-medium' => [
            'connection' => 'redis',
            'queue' => ['settlement-medium'],
            'balance' => 'auto',
            'minProcesses' => 1,
            'maxProcesses' => 5,
            'tries' => 5,
            'timeout' => 120,
        ],
        'settlement-low' => [
            'connection' => 'redis',
            'queue' => ['settlement-low'],
            'balance' => 'auto',
            'minProcesses' => 1,
            'maxProcesses' => 3,
            'tries' => 2,
            'timeout' => 600,
        ],
    ],
],
```

## Scheduled Tasks (Cron)
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // Every 5 minutes: process pending batches
    $schedule->command('settlement:process-pending')
        ->everyFiveMinutes()
        ->withoutOverlapping(10)
        ->onOneServer()
        ->runInBackground();

    // Every minute: transmit pending payment orders
    $schedule->command('settlement:transmit-orders')
        ->everyMinute()
        ->withoutOverlapping(5)
        ->onOneServer();

    // Every 2 minutes: poll bank confirmations
    $schedule->command('settlement:poll-confirmations')
        ->everyTwoMinutes()
        ->withoutOverlapping(10)
        ->onOneServer();

    // Every 30 minutes: retry failed payments
    $schedule->command('settlement:retry-failed')
        ->everyThirtyMinutes()
        ->withoutOverlapping(30)
        ->onOneServer();

    // 23:00 daily: EOD settlement
    $schedule->command('settlement:run-eod')
        ->dailyAt('23:00')
        ->timezone('Asia/Damascus')
        ->withoutOverlapping(60)
        ->onOneServer();

    // 00:30 daily: generate settlement report
    $schedule->command('settlement:generate-report', ['--type=daily'])
        ->dailyAt('00:30')
        ->timezone('Asia/Damascus')
        ->withoutOverlapping(30);
}
```

## Monitoring & Observability

### Key Metrics
```php
// Prometheus metrics
SettlementMetrics::counter('settlement_batches_total', ['type', 'status']);
SettlementMetrics::histogram('settlement_batch_processing_seconds', ['type']);
SettlementMetrics::gauge('settlement_exceptions_open', ['severity']);
SettlementMetrics::gauge('settlement_pool_balance', ['currency']);
SettlementMetrics::counter('settlement_payment_orders_total', ['status']);
SettlementMetrics::histogram('settlement_reconciliation_duration_seconds');
SettlementMetrics::counter('settlement_exceptions_created_total', ['type']);
```

### Alerts
| Condition | Severity | Channel |
|-----------|----------|---------|
| Batch processing > 30 min | Warning | Dashboard, Email |
| Batch processing > 60 min | Critical | SMS, Phone |
| Open exceptions > 10 | Warning | Dashboard |
| Open exceptions > 50 | Critical | SMS |
| Bank confirmation timeout | Critical | SMS, Phone |
| Payment order failure rate > 5% | Warning | Dashboard, Email |
| Settlement pool imbalance | Critical | SMS, Phone |
| Reconciliation match rate < 95% | Warning | Dashboard, Email |
| EOD batch not started by 23:15 | Critical | SMS, Phone |

## Disaster Recovery

### Recovery Point Objective (RPO)
- Settlement batches: 0 (real-time sync to replica)
- Payment orders: 0 (real-time)
- Reconciliation results: 5 minutes

### Recovery Time Objective (RTO)
- Settlement processing: 15 minutes
- Payment transmission: 30 minutes
- Report generation: 1 hour

### Backup Strategy
```
- MySQL: Point-in-time recovery (PITR) — every 5 minutes binlog
- Redis: AOF persistence + hourly snapshot
- Object Store: Versioned with 30-day retention
- Payment files: Immutable archive with 7-year retention (regulatory)
```
