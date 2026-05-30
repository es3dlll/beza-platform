# Push Notification Pattern

> Single source of truth for push notification delivery across ALL Beza Platform features.

## Delivery Architecture

```
[Feature Service] → Event (RabbitMQ) → [Push Notification Service] → FCM → Device
```

### Event Flow
1. Feature service publishes `NotificationRequested` event to RabbitMQ
2. Push Notification Service consumes from `push_notifications` queue
3. Service resolves device tokens from `user_devices` table
4. Service constructs FCM payload, applies rate limiting
5. Service sends via FCM HTTP v1 API
6. On success: marks notification as `delivered`
7. On failure: schedules retry (max 3 attempts)

## RabbitMQ Configuration

| Property | Value |
|----------|-------|
| Exchange | `notifications` (topic) |
| Queue | `push_notifications` |
| Routing Key | `notification.push.*` |
| Dead Letter Exchange | `notifications.dlx` |
| Dead Letter Queue | `push_notifications.dlq` |
| Message TTL | 24 hours |
| Max Retries | 3 |
| QoS Prefetch | 50 |

### Message Schema (Event)
```json
{
  "specversion": "1.0",
  "type": "com.beza.notification.push.requested",
  "source": "/wallet-service/transfer",
  "id": "evt_uuid",
  "time": "2025-05-29T10:30:00Z",
  "datacontenttype": "application/json",
  "data": {
    "user_id": "user_uuid",
    "tenant_id": "tenant_uuid",
    "template_key": "transfer.received",
    "template_params": {
      "amount": "50000",
      "currency": "SYP",
      "sender_name": "أحمد",
      "balance": "250000"
    },
    "priority": "high",
    "correlation_id": "correlation_uuid"
  }
}
```

## FCM Integration

### Credentials
- Service account JSON stored in AWS Secrets Manager
- Rotated every 90 days
- Each tenant has a separate Firebase project with its own service account
- Scoped to `firebase.messaging` API only (principle of least privilege)

### FCM HTTP v1 Endpoint
```
POST https://fcm.googleapis.com/v1/projects/{project_id}/messages:send
Authorization: Bearer {access_token}
Content-Type: application/json
```

### FCM Payload Schema
```json
{
  "message": {
    "token": "device_fcm_token",
    "notification": {
      "title": "تحويل مستلم",
      "body": "لقد استلمت 50,000 ل.س من أحمد"
    },
    "data": {
      "type": "transfer.received",
      "transaction_id": "txn_uuid",
      "amount": "50000",
      "currency": "SYP",
      "sender_name": "أحمد",
      "correlation_id": "correlation_uuid",
      "click_action": "transaction_details"
    },
    "android": {
      "priority": "high",
      "ttl": "86400s",
      "notification": {
        "channel_id": "transactions",
        "sound": "default",
        "click_action": ".TransactionDetailsActivity",
        "color": "#1B5E20"
      }
    },
    "apns": {
      "headers": {
        "apns-priority": "10",
        "apns-expiration": "86400"
      },
      "payload": {
        "aps": {
          "alert": {
            "title": "تحويل مستلم",
            "body": "لقد استلمت 50,000 ل.س من أحمد"
          },
          "sound": "default",
          "badge": 1,
          "category": "TRANSACTION"
        }
      }
    }
  }
}
```

### Payload Fields

| Field | Required | Description |
|-------|----------|-------------|
| `notification.title` | Yes | Localized title (Arabic) |
| `notification.body` | Yes | Localized body (Arabic) |
| `data.type` | Yes | Notification type for routing |
| `data.transaction_id` | Conditional | For transaction notifications |
| `data.correlation_id` | Yes | Tracing correlation ID |
| `data.click_action` | Yes | Deep link action on tap |
| `android.channel_id` | Yes | Android notification channel |
| `apns.payload.aps.badge` | No | Badge count (increment) |

## Delivery Retry Logic

### Retry Schedule
| Attempt | Delay | Notes |
|---------|-------|-------|
| 1 | 0s (immediate) | First send |
| 2 | 30s | Network error or rate limited |
| 3 | 300s (5 min) | Server error |
| Dead Letter | — | After 3 failures, sent to DLQ |

### Retry Decision Table
| FCM Error | Retry? | Action |
|-----------|--------|--------|
| `UNREGISTERED` | No | Remove device token immediately |
| `INVALID_ARGUMENT` | No | Log error, discard message |
| `SENDER_ID_MISMATCH` | No | Log security alert |
| `QUOTA_EXCEEDED` | Yes | Exponential backoff |
| `UNAVAILABLE` | Yes | Retry with backoff |
| `INTERNAL` | Yes | Retry with backoff |
| `THIRD_PARTY_AUTH_ERROR` | No | Alert operations |

### Dead Letter Queue Processing
- DLQ messages reviewed daily by on-call engineer
- Re-queue via admin panel with manual decision
- DLQ retention: 7 days

## Priority Tiers

| Tier | Delivery | Use Cases | Rate Limit |
|------|----------|-----------|------------|
| `critical` | Instant (no batching) | OTP, security alerts, fraud alerts | Bypass limits |
| `high` | <30s | Transaction confirmations, KYC updates | 2/hour/user |
| `normal` | <5min | Promotions, marketing, reminders | 5/day/user |
| `low` | Batch (hourly) | Statements, monthly reports | 1/day/user |

## Rate Limiting

### Per User Rate Limits
| Limiter | Window | Limit | Scope |
|---------|--------|-------|-------|
| Push notifications | 1 hour | 2 per user | Per user_id |
| Push notifications (promotional) | 1 day | 5 per user | Per user_id |
| Critical (bypass) | — | Unlimited | Per user_id |

### Rate Limit Implementation
```php
class PushRateLimiter {
    private const PREFIX = 'rate_limit:push:';
    private const TTL = 3600; // 1 hour

    public function allow(string $userId, string $tier = 'normal'): bool {
        if ($tier === 'critical') return true;

        $key = self::PREFIX . $userId;
        $limit = $tier === 'high' ? 2 : 5;
        $current = Redis::incr($key);

        if ($current === 1) {
            Redis::expire($key, self::TTL);
        }

        return $current <= $limit;
    }
}
```

## Arabic Templates

### Template Engine
- **Library**: Laravel Blade with Arabic-first layout
- **RTL support**: All templates explicitly marked `dir="rtl"`
- **Number formatting**: Arabic-Indic digits (٠١٢٣٤٥٦٧٨٩)
- **Currency**: SYP appended as `ل.س` after amount

### Template Storage
```php
// Stored in database: notification_templates table
// Keyed by user's locale (ar by default)
class NotificationTemplate {
    public string $key;        // e.g. "transfer.received"
    public string $locale;     // "ar" or "en"
    public string $title;      // "تحويل مستلم"
    public string $body;       // "لقد استلمت {amount} ل.س من {sender_name}"
    public array $variables;   // ["amount", "sender_name"]
}
```

### Template Examples
| Key | Title (AR) | Body (AR) | Variables |
|-----|-----------|-----------|-----------|
| `transfer.received` | تحويل مستلم | لقد استلمت {amount} ل.س من {sender_name} | amount, sender_name |
| `transfer.sent` | تم التحويل | تم تحويل {amount} ل.س إلى {recipient_name} | amount, recipient_name |
| `kyc.approved` | تم قبول التوثيق | تم قبول طلب التوثيق الخاص بك، مرحباً بك! | — |
| `kyc.rejected` | رفض التوثيق | للأسف لم يتم قبول طلب التوثيق. السبب: {reason} | reason |
| `wallet.low` | رصيد منخفض | رصيد محفظتك أقل من {threshold} ل.س، برجاء إعادة الشحن | threshold |
| `promo.offer` | عرض خاص | {description} - ساري حتى {expiry} | description, expiry |

## Device Token Management

### Token Registration
```
POST /api/v1/devices
Body: { fcm_token, platform: "ios"|"android", app_version, device_id }
```

### Token Lifecycle
- **Active**: Token used for delivery
- **Stale**: No delivery success in 30 days → removed from active rotation
- **Invalid**: `UNREGISTERED` error from FCM → immediately deleted
- **Replaced**: New token for same device → old token deleted
