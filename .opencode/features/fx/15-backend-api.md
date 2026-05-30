# FX Engine Backend API Specification

## Endpoint: Get Live Rates

```http
GET /api/v1/fx/rates
Authorization: Bearer {token}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "rates": {
      "SYP/USD": {
        "pair": "SYP/USD",
        "bid": 14700,
        "ask": 15000,
        "mid": 14850,
        "beza_rate": 14935,
        "spread_pct": 2.6,
        "spread_amount": 385,
        "last_updated": "2026-06-01T10:00:00Z",
        "sources": [
          {"name": "CBS Official", "rate": 13100, "type": "official", "status": "online", "priority": 1},
          {"name": "Parallel Market", "rate": 14550, "type": "parallel", "status": "online", "priority": 2},
          {"name": "Black Market", "rate": 15200, "type": "black_market", "status": "online", "priority": 3}
        ],
        "change_24h": 2.3,
        "high_24h": 15100,
        "low_24h": 14400,
        "sparkline": [14500, 14600, 14750, 14800, 14750, 14850, 14900, 14850]
      },
      "SYP/EUR": {
        "pair": "SYP/EUR",
        "bid": 16100,
        "ask": 16400,
        "mid": 16250,
        "beza_rate": 16413,
        "spread_pct": 2.8,
        "spread_amount": 163,
        "last_updated": "2026-06-01T10:00:00Z",
        "sources": [
          {"name": "CBS Official", "rate": 14200, "type": "official", "status": "online"},
          {"name": "Parallel Market", "rate": 16200, "type": "parallel", "status": "online"},
          {"name": "Black Market", "rate": 16500, "type": "black_market", "status": "online"}
        ],
        "change_24h": -0.5,
        "high_24h": 16500,
        "low_24h": 16100,
        "sparkline": [16300, 16250, 16200, 16150, 16200, 16250, 16250, 16250]
      },
      "USD/EUR": {
        "pair": "USD/EUR",
        "bid": 1.088,
        "ask": 1.100,
        "mid": 1.094,
        "beza_rate": 1.099,
        "spread_pct": 1.2,
        "spread_amount": 0.013,
        "last_updated": "2026-06-01T10:00:00Z",
        "sources": [
          {"name": "XE.com Feed", "rate": 1.094, "type": "api", "status": "online"},
          {"name": "Reuters Feed", "rate": 1.093, "type": "api", "status": "online"}
        ],
        "change_24h": 0.1,
        "high_24h": 1.098,
        "low_24h": 1.090,
        "sparkline": [1.093, 1.094, 1.095, 1.094, 1.093, 1.094, 1.094, 1.094]
      }
    },
    "market_status": "open",
    "fetched_at": "2026-06-01T10:00:00Z",
    "next_fetch_at": "2026-06-01T10:00:15Z",
    "stale": false
  }
}
```

### Error: All Providers Down
```json
// 503 — Service Unavailable
{
  "status": "error",
  "error": {
    "code": "ALL_PROVIDERS_DOWN",
    "message": "جميع مصادر الأسعار غير متاحة حالياً",
    "details": {
      "providers_attempted": 3,
      "last_successful_fetch": "2026-06-01T09:59:45Z",
      "recommendation": "يرجى المحاولة بعد قليل"
    }
  }
}
```

## Endpoint: Get Rate Detail

```http
GET /api/v1/fx/rates/SYP-USD
Authorization: Bearer {token}
```

### Response
```json
{
  "status": "success",
  "data": {
    "pair": "SYP/USD",
    "bid": 14700,
    "ask": 15000,
    "mid": 14850,
    "beza_rate": 14935,
    "spread_pct": 2.6,
    "last_updated": "2026-06-01T10:00:00Z",
    "source_used": "Parallel Market",
    "sources": [
      {
        "id": 1,
        "name": "CBS Official",
        "type": "api",
        "status": "online",
        "priority": 1,
        "bid": 12900,
        "ask": 13300,
        "mid": 13100,
        "last_fetched": "2026-06-01T10:00:00Z",
        "response_time_ms": 120,
        "health_url": "https://cbs.gov.sy/rates"
      },
      {
        "id": 2,
        "name": "Parallel Market",
        "type": "api",
        "status": "online",
        "priority": 2,
        "bid": 14400,
        "ask": 14700,
        "mid": 14550,
        "last_fetched": "2026-06-01T10:00:00Z",
        "response_time_ms": 85,
        "health_url": "https://exchangehouse.al-maghrib.com/api/rates"
      }
    ],
    "history": [
      {"rate": 14850, "timestamp": "2026-06-01T10:00:00Z"},
      {"rate": 14800, "timestamp": "2026-06-01T09:59:45Z"},
      {"rate": 14750, "timestamp": "2026-06-01T09:59:30Z"}
    ],
    "stats": {
      "change_24h": 2.3,
      "high_24h": 15100,
      "low_24h": 14400,
      "avg_24h": 14750,
      "volatility_24h": 1.8
    }
  }
}
```

## Endpoint: Lock Rate

```http
POST /api/v1/fx/lock
Authorization: Bearer {token}
Content-Type: application/json
Idempotency-Key: {uuid}

{
  "pair": "SYP/USD",
  "amount": 5000000,
  "rate": 14935
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "lock_id": "lock_abc123def456",
    "pair": "SYP/USD",
    "rate": 14935,
    "amount": 5000000,
    "expires_at": "2026-06-01T10:00:30Z",
    "remaining_seconds": 30,
    "status": "active"
  }
}
```

### Error: Rate Already Changed
```json
// 409 — Conflict
{
  "status": "error",
  "error": {
    "code": "RATE_CHANGED",
    "message": "تغير السعر منذ آخر تحديث",
    "details": {
      "requested_rate": 14935,
      "current_rate": 14950,
      "deviation": 15
    }
  }
}
```

### Error: Rate Lock Conflict
```json
// 409 — Conflict
{
  "status": "error",
  "error": {
    "code": "RATE_ALREADY_LOCKED",
    "message": "لديك تثبيت سعر نشط بالفعل",
    "details": {
      "existing_lock_id": "lock_xyz789",
      "expires_at": "2026-06-01T10:00:20Z"
    }
  }
}
```

## Endpoint: Execute Conversion

```http
POST /api/v1/fx/convert
Authorization: Bearer {token}
Content-Type: application/json
Idempotency-Key: {uuid}

{
  "lock_id": "lock_abc123def456",
  "source_wallet_id": 1,
  "target_wallet_id": 2,
  "amount": 5000000,
  "pin": "hashed_pin_value"
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "conversion_id": "conv_abc123",
    "status": "completed",
    "source": {
      "wallet_id": 1,
      "currency": "SYP",
      "amount": 5000000,
      "balance_after": 5000000
    },
    "target": {
      "wallet_id": 2,
      "currency": "USD",
      "amount": 334.78,
      "balance_after": 584.78
    },
    "rate": {
      "pair": "SYP/USD",
      "rate": 14935,
      "spread_pct": 2.6,
      "mid_market_rate": 14550
    },
    "fee": {
      "spread_implied_fee": 150000,
      "conversion_fee": 0,
      "total_fees": 150000
    },
    "timestamp": "2026-06-01T10:00:15Z",
    "reference": "FX-CONV-ABC123XYZ",
    "lock_id": "lock_abc123def456"
  }
}
```

### Error: Lock Expired
```json
// 410 — Gone
{
  "status": "error",
  "error": {
    "code": "LOCK_EXPIRED",
    "message": "انتهت صلاحية تثبيت السعر",
    "details": {
      "lock_id": "lock_abc123def456",
      "expired_at": "2026-06-01T10:00:30Z",
      "current_rate": 14950
    }
  }
}
```

## Endpoint: Conversion History

```http
GET /api/v1/fx/conversions?page=1&per_page=20&pair=SYP-USD&from=2026-01-01&to=2026-06-01
Authorization: Bearer {token}
```

### Response
```json
{
  "status": "success",
  "data": {
    "conversions": [
      {
        "id": "conv_abc123",
        "pair": "SYP/USD",
        "source_currency": "SYP",
        "source_amount": 5000000,
        "target_currency": "USD",
        "target_amount": 334.78,
        "rate": 14935,
        "status": "completed",
        "timestamp": "2026-06-01T10:00:15Z",
        "reference": "FX-CONV-ABC123XYZ"
      },
      {
        "id": "conv_def456",
        "pair": "USD/SYP",
        "source_currency": "USD",
        "source_amount": 250.00,
        "target_currency": "SYP",
        "target_amount": 3687500,
        "rate": 14750,
        "status": "completed",
        "timestamp": "2026-05-28T14:30:00Z",
        "reference": "FX-CONV-DEF456UVW"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 45,
      "last_page": 3,
      "has_more": true
    },
    "summary": {
      "total_converted_syp": 25000000,
      "total_converted_usd": 1750.50,
      "total_fees_paid": 750000,
      "period": "2026-01-01 to 2026-06-01"
    }
  }
}
```

## Endpoint: Admin Rate Override

```http
POST /api/v1/fx/override
Authorization: Bearer {token}
Content-Type: application/json
X-2FA-Code: {totp}

{
  "pair": "SYP/USD",
  "rate": 14600,
  "reason": "Black market spike, using parallel rate as reference",
  "duration_minutes": 5
}
```

### Response
```json
{
  "status": "success",
  "data": {
    "override_id": "ovr_789",
    "pair": "SYP/USD",
    "overridden_rate": 14600,
    "previous_rate": 14935,
    "effective_from": "2026-06-01T10:05:00Z",
    "effective_until": "2026-06-01T10:10:00Z",
    "reason": "Black market spike, using parallel rate as reference",
    "overridden_by": "admin_user_5",
    "audit": {
      "created_at": "2026-06-01T10:05:00Z",
      "ip_address": "176.203.12.34",
      "user_agent": "Beza Admin Console v2.1"
    }
  }
}
```

## Endpoint: Manage Providers (Admin)

```http
POST /api/v1/fx/providers/manage
Authorization: Bearer {token}
Content-Type: application/json

{
  "action": "update_priority",
  "provider_id": 2,
  "priority": 1,
  "status": "active"
}
```

### Response
```json
{
  "status": "success",
  "data": {
    "provider_id": 2,
    "name": "Parallel Market",
    "type": "api",
    "priority": 1,
    "status": "active",
    "updated_at": "2026-06-01T10:00:00Z"
  }
}
```

## Endpoint: Provider Health (Admin)

```http
GET /api/v1/fx/health
Authorization: Bearer {token}
```

### Response
```json
{
  "status": "success",
  "data": {
    "providers": [
      {
        "id": 1,
        "name": "CBS Official",
        "type": "api",
        "status": "online",
        "priority": 1,
        "uptime_24h": 99.8,
        "avg_response_time_ms": 120,
        "last_success": "2026-06-01T10:00:00Z",
        "last_failure": null,
        "consecutive_failures": 0,
        "rates_fetched_24h": 5760,
        "current_rate": 13100
      },
      {
        "id": 2,
        "name": "Black Market Scraper",
        "type": "scraper",
        "status": "degraded",
        "priority": 3,
        "uptime_24h": 92.1,
        "avg_response_time_ms": 450,
        "last_success": "2026-06-01T09:45:00Z",
        "last_failure": "2026-06-01T09:59:45Z",
        "consecutive_failures": 3,
        "rates_fetched_24h": 5300,
        "current_rate": null
      }
    ],
    "overall_health": "degraded",
    "last_updated": "2026-06-01T10:00:00Z"
  }
}
```

## Rate Source Hierarchy Logic

The FX Engine uses a cascading priority system to determine the best available rate:

```
Priority 1: CBS Official Rate (API)
  → If online: use as reference anchor
  → If offline after 3 retries: move to Priority 2

Priority 2: Parallel Market (API, Exchange House Feed)
  → If online: use as primary rate source
  → If offline after 3 retries: move to Priority 3

Priority 3: Black Market Aggregator (Scraper/Data Feed)
  → If online: use as fallback
  → If offline: move to Priority 4

Priority 4: Manual Override (Admin)
  → If a manual override is active: use that rate
  → If no override: move to stale cache

Priority 5: Stale Cache
  → Serve last known rates with stale indicator
  → If no cache: return 503 Service Unavailable
```

Beza Rate Calculation:
```
beza_rate = source_mid × (1 + spread_pct)
  where spread_pct = base_spread × tier_multiplier
  where base_spread = config("fx.spreads.{pair}", 0.03)  // 3% default
  where tier_multiplier = config("fx.tier_spreads.{tier}", 1.0)

Example:
  source_mid = 14,550 (Parallel Market)
  base_spread = 0.03 (3%)
  tier_multiplier = 0.86 (Premium user gets 14% discount on spread)
  beza_rate = 14,550 × (1 + 0.03 × 0.86) = 14,550 × 1.0258 = 14,935
```
