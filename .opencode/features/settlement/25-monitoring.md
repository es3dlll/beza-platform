# Settlement Monitoring

## Dashboard

### Real-Time Settlement Dashboard
```
┌──────────────────────────────────────────────────────────┐
│  منصة مراقبة التسوية —实时                               │
├──────────────────────────────────────────────────────────┤
│  KPI Bar                                                  │
│  ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐ │
│  │ دفعات  │ │ قيمة   │ │ نسبة   │ │ استثنا │ │ وقت   │ │
│  │ اليوم  │ │ اليوم  │ │ مطابقة │ │ءات     │ │ المعا  │ │
│  │ 13     │ │ 125.8م │ │ 99.3%  │ │ 3      │ │ لجية   │ │
│  └────────┘ └────────┘ └────────┘ └────────┘ └────────┘ │
│                                                           │
│  Active Batches (Live Updates via WebSocket)              │
│  ┌────┬────────────┬────────┬────────┬────────┬────────┐ │
│  │ #  │ النوع      │ الحالة │ العناصر│ القيمة │ الوقت  │ │
│  ├────┼────────────┼────────┼────────┼────────┼────────┤ │
│  │ 1  │ EOD        │ جاري💫 │ 8,400  │ 85م   │ 12:30  │ │
│  │ 2  │ EOD        │ مكتمل✅│ 2,800  │ 25م   │ 11:45  │ │
│  │ 3  │ EOD        │ مع وقف │ 1,200  │ 12م   │ 10:20  │ │
│  │ 4  │ RT         │ مكتمل✅│ 1      │ 100أ  │ 10:05  │ │
│  └────┴────────────┴────────┴────────┴────────┴────────┘ │
│                                                           │
│  Exception Feed (Auto-refresh every 15s)                  │
│  ┌──┬──────────────┬──────────┬────────┬────────────────┐ │
│  │# │ النوع        │ الخطورة  │ المبلغ │ الحالة         │ │
│  ├──┼──────────────┼──────────┼────────┼────────────────┤ │
│  │1 │ عدم تطابق    │ عالي 🔴  │ 5,000  │ قيد التحقيق    │ │
│  │2 │ تأكيد مفقود  │ متوسط 🟡│ 750,000│ مفتوح          │ │
│  │3 │ مكرر         │ منخفض 🟢│ 2,000  │ محلول ✅       │ │
│  └──┴──────────────┴──────────┴────────┴────────────────┘ │
└──────────────────────────────────────────────────────────┘
```

## Grafana Dashboards

### Settlement Overview Dashboard
```json
{
  "title": "Settlement Engine Overview",
  "panels": [
    {
      "title": "Batches Processed / Hour",
      "type": "timeseries",
      "targets": ["settlement_batches_total"],
      "unit": "count"
    },
    {
      "title": "Batch Processing Duration (p50/p95/p99)",
      "type": "heatmap",
      "targets": ["settlement_batch_processing_seconds"],
      "unit": "seconds"
    },
    {
      "title": "Open Exceptions by Severity",
      "type": "bargauge",
      "targets": ["settlement_exceptions_open"],
      "unit": "count"
    },
    {
      "title": "Settlement Pool Balance",
      "type": "stat",
      "targets": ["settlement_pool_balance"],
      "unit": "SYP"
    },
    {
      "title": "Payment Order Confirmation Rate",
      "type": "timeseries",
      "targets": ["settlement_payment_orders_total{status='confirmed'}"],
      "unit": "percent"
    },
    {
      "title": "Reconciliation Match Rate (7d)",
      "type": "timeseries",
      "targets": ["settlement_reconciliation_match_rate"],
      "unit": "percent"
    }
  ]
}
```

## Logging

### Structured Logging Context
```php
// Every settlement operation includes context:
Log::channel('settlement')->info('Batch processed', [
    'batch_id' => $batch->id,
    'batch_number' => $batch->batch_number,
    'type' => $batch->type->value,
    'duration_ms' => $duration,
    'transaction_count' => $batch->transaction_count,
    'total_amount' => $batch->total_amount,
    'net_amount' => $batch->net_amount,
    'exception_count' => $exceptions,
    'correlation_id' => request()->header('X-Correlation-ID'),
]);
```

### Log Channels
```php
// config/logging.php
'channels' => [
    'settlement' => [
        'driver' => 'daily',
        'path' => storage_path('logs/settlement/settlement.log'),
        'level' => 'info',
        'days' => 90,
    ],
    'settlement-exceptions' => [
        'driver' => 'daily',
        'path' => storage_path('logs/settlement/exceptions.log'),
        'level' => 'warning',
        'days' => 365,
    ],
    'settlement-audit' => [
        'driver' => 'daily',
        'path' => storage_path('logs/settlement/audit.log'),
        'level' => 'info',
        'days' => 365 * 7, // 7 years
    ],
],
```

## Health Checks

```php
// routes/api.php — health endpoint
Route::get('/api/v1/settlement/health', function () {
    return [
        'status' => 'ok',
        'timestamp' => now(),
        'services' => [
            'database' => DB::connection()->getPdo() ? 'ok' : 'error',
            'redis' => Cache::store('redis')->set('health-check', 'ok') ? 'ok' : 'error',
            'queue' => Queue::size('settlement-high') !== false ? 'ok' : 'error',
            'bank_integration' => BankIntegrationService::healthCheck(),
        ],
        'metrics' => [
            'pending_batches' => SettlementBatch::where('status', 'processing')->count(),
            'awaiting_confirmation' => SettlementBatch::where('status', 'awaiting_confirmation')->count(),
            'open_exceptions' => SettlementException::where('status', 'open')->count(),
            'queued_payment_orders' => SettlementPaymentOrder::where('status', 'generated')->count(),
        ],
    ];
});
```
