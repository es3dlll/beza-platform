# FX Engine Event Architecture

## Events Produced

### RateUpdated
```json
{
  "specversion": "1.0",
  "id": "evt_rate_upd_abc123",
  "source": "/beza/fx/1.0",
  "type": "com.beza.fx.rate_updated",
  "datacontenttype": "application/json",
  "subject": "pair_SYP/USD",
  "time": "2026-06-01T10:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "pair": "SYP/USD",
    "mid": 14550,
    "bid": 14400,
    "ask": 14700,
    "beza_rate": 14935,
    "spread_pct": 2.6,
    "source": "Parallel Market",
    "provider_id": 2,
    "response_time_ms": 85,
    "is_stale": false,
    "recorded_at": "2026-06-01T10:00:00Z",
    "sources_count": 3,
    "providers_online": 3,
    "providers_degraded": 0
  }
}
```
**Consumers**: Rate Cache (invalidate/update), Analytics (rate tracking), WebSocket (push to clients), CBS Report (rate logging)

### RateLocked
```json
{
  "specversion": "1.0",
  "id": "evt_rate_lock_abc123",
  "source": "/beza/fx/1.0",
  "type": "com.beza.fx.rate_locked",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-06-01T10:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "lock_id": "lock_abc123def456",
    "user_id": 42,
    "pair": "SYP/USD",
    "rate": 14935,
    "amount": 5000000,
    "source_currency": "SYP",
    "target_currency": "USD",
    "expires_at": "2026-06-01T10:00:30Z",
    "created_at": "2026-06-01T10:00:00Z"
  }
}
```
**Consumers**: Rate Lock Service (track), Analytics (lock rate), Hedge Service (exposure tracking)

### RateExpired
```json
{
  "specversion": "1.0",
  "id": "evt_rate_exp_abc123",
  "source": "/beza/fx/1.0",
  "type": "com.beza.fx.rate_expired",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-06-01T10:00:30Z",
  "tenant_id": "tenant_1",
  "data": {
    "lock_id": "lock_abc123def456",
    "user_id": 42,
    "pair": "SYP/USD",
    "rate": 14935,
    "amount": 5000000,
    "scheduled_expiry": "2026-06-01T10:00:30Z",
    "expired_at": "2026-06-01T10:00:30Z",
    "was_used": false
  }
}
```
**Consumers**: Rate Lock Service (cleanup), Analytics (abandonment rate), Hedge Service (exposure release)

### ConversionCompleted
```json
{
  "specversion": "1.0",
  "id": "evt_conv_cmpl_abc123",
  "source": "/beza/fx/1.0",
  "type": "com.beza.fx.conversion_completed",
  "datacontenttype": "application/json",
  "subject": "user_42",
  "time": "2026-06-01T10:00:15Z",
  "tenant_id": "tenant_1",
  "data": {
    "conversion_id": "conv_abc123",
    "user_id": 42,
    "lock_id": "lock_abc123def456",
    "pair": "SYP/USD",
    "source_currency": "SYP",
    "source_amount": 5000000,
    "target_currency": "USD",
    "target_amount": 334.78,
    "rate_used": 14935,
    "mid_rate": 14550,
    "spread_pct": 2.6,
    "spread_amount": 150000,
    "fee": 0,
    "total": 5000000,
    "reference": "FX-CONV-ABC123XYZ",
    "cfe_reference": "CFE-POST-789012",
    "source_balance_after": 5000000,
    "target_balance_after": 584.78,
    "created_at": "2026-06-01T10:00:15Z"
  }
}
```
**Consumers**: Wallet (update balances), Notification (push to user), Analytics (revenue tracking), Hedge Service (exposure update), CFE (posting confirmation)

### RateProviderHealthChanged
```json
{
  "specversion": "1.0",
  "id": "evt_prov_hlth_abc123",
  "source": "/beza/fx/1.0",
  "type": "com.beza.fx.provider_health_changed",
  "datacontenttype": "application/json",
  "subject": "provider_2",
  "time": "2026-06-01T10:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "provider_id": 2,
    "provider_name": "Parallel Market",
    "previous_status": "active",
    "new_status": "degraded",
    "consecutive_failures": 3,
    "last_failure_reason": "HTTP 503 Service Unavailable",
    "circuit_breaker_until": "2026-06-01T10:05:00Z",
    "last_success_at": "2026-06-01T09:58:30Z"
  }
}
```
**Consumers**: Ops Alert (Slack/PagerDuty), Rate Provider Service (trigger failover), Admin Dashboard (health update)

### RateAnomalyDetected
```json
{
  "specversion": "1.0",
  "id": "evt_anomaly_abc123",
  "source": "/beza/fx/1.0",
  "type": "com.beza.fx.anomaly_detected",
  "datacontenttype": "application/json",
  "subject": "pair_SYP/USD",
  "time": "2026-06-01T10:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "anomaly_type": "SPREAD_WIDENING",
    "severity": "warning",
    "pair": "SYP/USD",
    "message": "Spread widened from 2.1% to 4.2% (exceeds 2x threshold)",
    "current_spread_pct": 4.2,
    "avg_spread_pct": 2.1,
    "threshold": 2.0,
    "current_rate": 14550,
    "previous_rate": 14800,
    "providers": [
      {"name": "CBS Official", "rate": 13100, "status": "online"},
      {"name": "Parallel Market", "rate": 14550, "status": "online"},
      {"name": "Black Market", "rate": 15200, "status": "online"}
    ],
    "detected_at": "2026-06-01T10:00:00Z"
  }
}
```
**Consumers**: Ops Alert (Slack critical), Admin Dashboard (alert feed), ML Service (model retraining trigger)

## Event Flow Diagram
```
FetchRatesJob (cron every 15s)
    │
    ▼
RateProviderService::fetchRates()
    │
    ├── Provider A (CBS Official) ── Success ──┐
    ├── Provider B (Parallel Market) ── Success ─┤
    ├── Provider C (Black Market) ── Fail ──────┤
    │                                            │
    ▼                                            ▼
emit(RateUpdated) ←──────────── RateEngine applies spread
    │
    ├── Queue: CacheInvalidation
    ├── Queue: WebSocketBroadcast
    ├── Queue: AnalyticsIngestion
    ├── Queue: CBSReportLogging
    └── Queue: AnomalyDetection

User locks rate for conversion:
    │
    ▼
POST /fx/lock
    │
    ▼
emit(RateLocked) ───────────────────────────────┐
    │                                            │
    ├── Queue: HedgeExposureTracking            │
    └── Queue: UserNotification (lock confirmed)│
                                                 │
User confirms conversion:                       │
    │                                            │
    ▼                                            │
POST /fx/convert                                │
    │                                            │
    ├── Validate lock (not expired)              │
    ├── CFE: hold source wallet                  │
    ├── CFE: credit target wallet                │
    ├── CFE: post conversion fee                 │
    ├── Persist conversion                       │
    │                                            │
    ├── emit(ConversionCompleted) ───────────────┤
    │    ├── Queue: WalletBalanceUpdate          │
    │    ├── Queue: UserPushNotification         │
    │    ├── Queue: RevenueRecognition           │
    │    ├── Queue: HedgeExposureSettle          │
    │    └── Queue: AnalyticsIngestion           │
    │                                            │
    └── Lock used (RateLock.status = used)       │
                                                 │
Lock expires (30s TTL):                         │
    │                                            │
    ▼                                            │
emit(RateExpired)                                │
    ├── Queue: LockCleanup                       │
    └── Queue: HedgeExposureRelease              │

Provider health check (cron every 30s):
    │
    ▼
CheckRateProviderHealth job
    │
    ├── Provider healthy → no event
    └── Provider degraded/recovered →
         emit(RateProviderHealthChanged)
              ├── Queue: SlackAlert
              ├── Queue: ProviderFailover
              └── Queue: AdminDashboardUpdate

Anomaly detection (cron every 60s):
    │
    ▼
DetectRateAnomalies job
    │
    ├── No anomaly → no event
    └── Anomaly found →
         emit(RateAnomalyDetected)
              ├── Queue: CriticalSlackAlert
              ├── Queue: AdminDashboard
              └── Queue: MLRetrainingTrigger
```
