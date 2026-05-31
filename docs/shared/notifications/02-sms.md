# SMS Notification Pattern

> Single source of truth for SMS delivery across ALL Beza Platform features.

## Delivery Architecture

```
[Feature Service] → Event (RabbitMQ) → [SMS Service] → Twilio/Syrian Provider → User Phone
                                                    ↘ Database (log + cost)
```

### SMS Providers

| Provider | Use Case | Priority | Failover |
|----------|----------|----------|----------|
| Twilio (primary) | OTP, alerts, all transactional | 1 | Syrian Provider |
| Syrian Provider (fallback) | Local SYP-only messages | 2 | Twilio |
| Twilio (secondary) | International SMS | 3 | — |

### Provider Selection Logic
```php
function selectProvider(string $phoneNumber, string $messageType): SmsProvider {
    $countryCode = PhoneNumber::parse($phoneNumber)->getCountryCode();

    return match (true) {
        $messageType === 'otp' && $countryCode === '963' => new SyrianProvider(),
        $messageType === 'otp'                          => new TwilioProvider(),
        $countryCode === '963'                          => new SyrianProvider(),
        default                                         => new TwilioProvider(),
    };
}
```

## Twilio Integration

### Credentials
- `TWILIO_ACCOUNT_SID` — AWS Secrets Manager
- `TWILIO_AUTH_TOKEN` — AWS Secrets Manager
- `TWILIO_MESSAGING_SERVICE_SID` — AWS Secrets Manager
- Rotated every 90 days
- Separate sub-accounts per tenant for cost tracking

### API Endpoint
```
POST /2010-04-01/Accounts/{AccountSid}/Messages.json
```

### Request
```php
use Twilio\Rest\Client;

$twilio = new Client($sid, $token);
$message = $twilio->messages->create(
    '+963944123456',           // To
    [
        'messagingServiceSid' => $messagingServiceSid,
        'body' => 'رمز التحقق الخاص بك هو: 123456',
        'statusCallback' => 'https://api.beza.com/v1/sms/status/twilio',
    ]
);
```

## Syrian Provider Integration

### Credentials
- `SYRIAN_SMS_API_KEY` — AWS Secrets Manager
- `SYRIAN_SMS_API_URL` — `https://sms-provider.sy/api/v1/send`
- `SYRIAN_SMS_SENDER_ID` — Registered with Syrian telecom authority

### Request Format
```json
POST {provider_url}
Authorization: Bearer {api_key}
Content-Type: application/json

{
  "to": "963944123456",
  "sender": "BezaPay",
  "message": "رمز التحقق الخاص بك هو: 123456",
  "type": "otp",
  "reference_id": "sms_ref_uuid"
}
```

## OTP Templates

### Template Format
```php
class SmsTemplate {
    public string $key;        // "otp.login", "otp.transfer"
    public string $locale;     // "ar" (primary), "en" (fallback)
    public string $body;       // "رمز التحقق الخاص بك هو: {otp_code}"
    public int $ttlMinutes;    // 5
}
```

### OTP Templates
| Key | Body (AR) | Body (EN) | TTL |
|-----|-----------|-----------|-----|
| `otp.login` | رمز التحقق الخاص بك هو: {otp_code}. صالح لمدة 5 دقائق | Your verification code is: {otp_code}. Valid for 5 minutes | 5min |
| `otp.transfer` | رمز تأكيد التحويل {amount} ل.س هو: {otp_code} | Transfer confirmation code for {amount} SYP: {otp_code} | 5min |
| `otp.register` | رمز تفعيل الحساب: {otp_code}. مرحباً بك في بيزا! | Account activation code: {otp_code}. Welcome to Beza! | 10min |
| `otp.password_reset` | رمز إعادة تعيين كلمة المرور: {otp_code} | Password reset code: {otp_code} | 5min |
| `otp.device_verify` | رمز توثيق الجهاز الجديد: {otp_code} | New device verification code: {otp_code} | 5min |

## Transaction Alert Templates

| Key | Body (AR) | Body (EN) |
|-----|-----------|-----------|
| `alert.transfer_received` | تم استلام {amount} ل.س من {sender_name}. الرصيد: {balance} ل.س | Received {amount} SYP from {sender_name}. Balance: {balance} SYP |
| `alert.transfer_sent` | تم إرسال {amount} ل.س إلى {recipient_name}. الرصيد: {balance} ل.س | Sent {amount} SYP to {recipient_name}. Balance: {balance} SYP |
| `alert.withdrawal` | تم السحب {amount} ل.س. الرصيد: {balance} ل.س | Withdrawal of {amount} SYP. Balance: {balance} SYP |
| `alert.deposit` | تم الإيداع {amount} ل.س. الرصيد: {balance} ل.س | Deposit of {amount} SYP. Balance: {balance} SYP |
| `alert.low_balance` | رصيد محفظتك: {balance} ل.س. برجاء إعادة الشحن | Your wallet balance: {balance} SYP. Please top up |

## Delivery Status Webhooks

### Webhook Endpoint
```
POST /api/v1/sms/status/twilio
POST /api/v1/sms/status/syrian-provider
```

### Twilio Status Callback
```json
{
  "MessageSid": "SMxxxxxxxx",
  "MessageStatus": "delivered",
  "To": "+963944123456",
  "ErrorCode": null,
  "Error": null,
  "Price": "-0.0075",
  "PriceUnit": "USD"
}
```

### Status Values
| Status | Meaning | Action |
|--------|---------|--------|
| `queued` | Submitted to provider | Wait |
| `sent` | Sent to carrier | Wait |
| `delivered` | Delivered to handset | Mark success |
| `failed` | Delivery failed | Retry or alert |
| `undelivered` | Carrier rejected | Check number validity |
| `read` | Read receipt (if available) | Log |

### Syrian Provider Callback
```json
{
  "reference_id": "sms_ref_uuid",
  "status": "delivered",
  "delivered_at": "2025-05-29T10:30:00+03:00",
  "cost": "50",
  "cost_currency": "SYP"
}
```

## Cost Tracking

### Cost Logging Schema
```sql
CREATE TABLE sms_logs (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id       UUID NOT NULL,
    user_id         UUID,
    phone_number    TEXT NOT NULL,       -- last 4 digits logged, full encrypted
    provider        TEXT NOT NULL,       -- 'twilio' | 'syrian_provider'
    template_key    TEXT NOT NULL,
    status          TEXT NOT NULL,       -- 'sent' | 'delivered' | 'failed'
    cost_amount     DECIMAL(20, 4),
    cost_currency   TEXT,                -- 'USD' | 'SYP'
    correlation_id  UUID NOT NULL,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_sms_logs_tenant_date ON sms_logs(tenant_id, created_at);
CREATE INDEX idx_sms_logs_user ON sms_logs(user_id, created_at);
```

### Monthly Cost Report
```sql
SELECT
    tenant_id,
    provider,
    COUNT(*) as total_sms,
    SUM(cost_amount) as total_cost,
    cost_currency
FROM sms_logs
WHERE created_at >= date_trunc('month', NOW())
  AND created_at < date_trunc('month', NOW()) + INTERVAL '1 month'
GROUP BY tenant_id, provider, cost_currency;
```

## Rate Limiting

### Per User Rate Limits
| Type | Limit | Window | Scope |
|------|-------|--------|-------|
| OTP | 5 | 1 hour | Per phone number |
| Transaction alerts | 20 | 1 day | Per user |
| Promotional | 2 | 1 day | Per user (opt-in only) |

### Rate Limit Implementation
```php
class SmsRateLimiter {
    private const OTP_PREFIX = 'rate_limit:sms:otp:';
    private const ALERT_PREFIX = 'rate_limit:sms:alert:';
    private const OTP_LIMIT = 5;
    private const OTP_WINDOW = 3600;

    public function allowOtp(string $phoneNumber): bool {
        $key = self::OTP_PREFIX . $phoneNumber;
        $current = Redis::incr($key);

        if ($current === 1) {
            Redis::expire($key, self::OTP_WINDOW);
        }

        return $current <= self::OTP_LIMIT;
    }

    public function allowAlert(string $userId): bool {
        $key = self::ALERT_PREFIX . $userId;
        $count = Redis::incr($key);

        if ($count === 1) {
            Redis::expire($key, 86400);
        }

        return $count <= 20;
    }
}
```

## Failover & Retry

### Retry Schedule
| Attempt | Delay | Condition |
|---------|-------|-----------|
| 1 | 0s | Initial send via primary provider |
| 2 | 30s | Primary failed → retry same provider |
| 3 | 60s | Primary failed → fallback provider |
| Dead Letter | — | All attempts failed |

### Health Checks
- Twilio: Check API health endpoint every 60s
- Syrian Provider: Send test SMS every 5min
- If provider unhealthy for 3 consecutive checks → auto-failover to other provider
- Provider health stored in Redis with TTL 300s
