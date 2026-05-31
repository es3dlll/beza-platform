# Government Collections Infrastructure

## Deployment Architecture

```
┌──────────────────────────────────────────────────────────────────┐
│                          Load Balancer                           │
│  (HAProxy / AWS ALB — TLS termination)                           │
└────────┬────────────────────┬──────────────────────┬──────────────┘
         │                    │                      │
┌────────▼──────┐   ┌────────▼──────┐   ┌────────────▼──────────┐
│  App Server 1  │   │  App Server 2  │   │   App Server N        │
│  (Laravel)    │   │  (Laravel)    │   │   (Laravel)            │
├────────────────┤   ├────────────────┤   ├───────────────────────┤
│ GovtCollect    │   │ GovtCollect    │   │ GovtCollect            │
│ Module         │   │ Module         │   │ Module                 │
│ Ministry       │   │ Ministry       │   │ Ministry               │
│ Adapters       │   │ Adapters       │   │ Adapters               │
└────────┬───────┘   └────────┬───────┘   └───────────┬───────────┘
         │                    │                        │
         └────────────────────┼────────────────────────┘
                              │
         ┌────────────────────┼────────────────────────┐
         │                    │                        │
┌────────▼───────┐   ┌───────▼────────┐   ┌───────────▼───────────┐
│   MySQL        │   │   Redis        │   │   RabbitMQ / SQS      │
│   Primary      │   │   - Cache      │   │   - Events            │
│   + Replica    │   │   - Rate Limit │   │   - Queue             │
│   + Gov Receipt│   │   - Idempotency│   │   - Ministry Sync     │
│     Archive    │   │   - Session    │   │   - Settlement Batches│
└────────────────┘   └────────────────┘   └───────────────────────┘
                              │                        │
                              │                        │
         ┌────────────────────┴────────────────────────┘
         │
┌────────▼──────────────────────────────────────────────────────────┐
│                    Ministry Integration Layer                       │
├────────────────────────────────────────────────────────────────────┤
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐            │
│  │ MoF Adapter  │  │ MoI Adapter  │  │ TRAF Adapter │   ...      │
│  │ (REST/SOAP)  │  │ (REST)       │  │ (File SFTP)  │            │
│  └──────────────┘  └──────────────┘  └──────────────┘            │
│                                                                   │
│  ┌──────────────────────────────────────────────────────────┐    │
│  │  Syria Central E-Payment Gateway (if available)          │    │
│  │  Adapter: CentralGatewayAdapter                          │    │
│  └──────────────────────────────────────────────────────────┘    │
└──────────────────────────────────────────────────────────────────┘
```

## Cron Jobs

```php
// Console/Kernel.php — Schedule definitions

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Daily reconciliation: 02:00
        $schedule->command('government:reconcile')
            ->dailyAt('02:00')
            ->withoutOverlapping(30)
            ->runInBackground()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/gov-reconcile.log'));

        // Sync payment statuses from ministries: every 30 minutes
        $schedule->command('government:sync-statuses')
            ->everyThirtyMinutes()
            ->withoutOverlapping(10)
            ->runInBackground()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/gov-sync.log'));

        // Retry failed payments: every hour
        $schedule->command('government:retry-failed')
            ->hourly()
            ->withoutOverlapping(15)
            ->runInBackground()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/gov-retry.log'));

        // Send deadline reminders: daily at 09:00
        $schedule->command('government:send-reminders')
            ->dailyAt('09:00')
            ->withoutOverlapping(10)
            ->runInBackground()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/gov-reminders.log'));

        // Process ministry settlements: daily at 23:30
        $schedule->command('government:settle')
            ->dailyAt('23:30')
            ->withoutOverlapping(30)
            ->runInBackground()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/gov-settle.log'));

        // Cleanup expired idempotency keys: daily at 04:00
        $schedule->command('government:cleanup-idempotency')
            ->dailyAt('04:00')
            ->appendOutputTo(storage_path('logs/gov-cleanup.log'));
    }
}
```

## Ministry Adapter Architecture

```
Each ministry adapter implements the MinistryAdapter interface:

interface MinistryAdapter {
    public function queryObligations(string $referenceId): QueryResult;
    public function confirmPayment(PaymentConfirmation $confirmation): ConfirmationResult;
    public function checkStatus(string $referenceId): StatusResult;
    public function settleBatch(array $transactions): SettlementResult;
}

Adapters are loaded dynamically by MinistryIntegrator based on
government_billers.adapter_class configuration.
```

## Infrastructure Requirements

| Component | Specification | Notes |
|-----------|--------------|-------|
| App servers | 4+ nodes (N+2 redundancy) | Laravel Octane (RoadRunner) |
| Database | MySQL 8.0+ with read replicas | Receipt archive on separate volume |
| Cache | Redis 7+ cluster | Idempotency keys TTL: 24h |
| Queue | RabbitMQ or SQS | Separate queues: payments, settlements, sync |
| Storage | S3-compatible (MinIO) | Receipt PDFs + QR codes encrypted at rest |
| Ministry connectivity | VPN + dedicated IP whitelist | Static IPs for ministry API access |
| File transfer | SFTP server | For file-based ministries (Traffic, Courts) |
| Monitoring | Prometheus + Grafana + PagerDuty | ministry_health, settlement_lag, reconciliation_variance |
| Backup | Daily DB snapshots + receipt archive | Point-in-time recovery: 30 days |

## Rate Limiting

| Endpoint | Limit | Burst |
|----------|-------|-------|
| POST /government/*/query | 10 req/min per user | 20 |
| POST /government/*/pay | 5 req/min per user | 10 |
| GET /government/history | 30 req/min per user | 50 |
| Ministry API calls (aggregate) | 100 req/min per ministry | 200 |
| Idempotency key reuse | 1 per key per 24h | — |
