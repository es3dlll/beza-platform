# Open Finance Event Architecture

## Events Produced

### PaymentCompleted
```json
{
  "specversion": "1.0",
  "id": "evt_pay_comp_abc123",
  "source": "/beza/open-finance/1.0",
  "type": "com.beza.payment.completed",
  "datacontenttype": "application/json",
  "subject": "pay_abc123",
  "time": "2026-06-01T10:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "payment_id": "pay_abc123",
    "amount": 25000,
    "fee": 50,
    "currency": "SYP",
    "status": "completed",
    "recipient_phone": "+963912345678",
    "recipient_name": "Ahmad Khaled",
    "description": "Order #12345",
    "reference": "TXN-ABC123XYZ",
    "metadata": {"order_id": "ORD-12345"},
    "developer_id": 42,
    "created_at": "2026-06-01T10:00:00Z"
  }
}
```
**Consumers**: WebhookDeliveryService (dispatch to webhook endpoints)

### PaymentFailed
```json
{
  "specversion": "1.0",
  "id": "evt_pay_fail_abc124",
  "source": "/beza/open-finance/1.0",
  "type": "com.beza.payment.failed",
  "datacontenttype": "application/json",
  "subject": "pay_abc124",
  "time": "2026-06-01T10:00:05Z",
  "tenant_id": "tenant_1",
  "data": {
    "payment_id": "pay_abc124",
    "amount": 50000,
    "currency": "SYP",
    "status": "failed",
    "failure_reason": "insufficient_balance",
    "failure_code": "INSUFFICIENT_BALANCE",
    "recipient_phone": "+963912345678",
    "developer_id": 42,
    "created_at": "2026-06-01T10:00:05Z"
  }
}
```
**Consumers**: WebhookDeliveryService, Developer notification

### ApiKeyCreated
```json
{
  "specversion": "1.0",
  "id": "evt_key_created_abc",
  "source": "/beza/open-finance/1.0",
  "type": "com.beza.apikey.created",
  "datacontenttype": "application/json",
  "subject": "developer_42",
  "time": "2026-06-01T10:00:00Z",
  "data": {
    "key_id": 1,
    "developer_id": 42,
    "label": "Production Shop API",
    "environment": "production",
    "scopes": ["payments.write", "accounts.read"],
    "created_at": "2026-06-01T10:00:00Z"
  }
}
```
**Consumers**: Notification, Audit log

### RateLimitExceeded
```json
{
  "specversion": "1.0",
  "id": "evt_rate_limited_abc",
  "source": "/beza/open-finance/1.0",
  "type": "com.beza.ratelimit.exceeded",
  "datacontenttype": "application/json",
  "subject": "developer_42",
  "time": "2026-06-01T10:00:00Z",
  "data": {
    "developer_id": 42,
    "tier": "free",
    "limit_type": "per_minute",
    "limit": 10,
    "current_usage": 11,
    "endpoint": "/v1/of/payments",
    "suggested_upgrade": "startup",
    "created_at": "2026-06-01T10:00:00Z"
  }
}
```
**Consumers**: Notification (suggest tier upgrade), Analytics

## Event Flow Diagram
```
PaymentController::initiate()
    │
    ▼
InitiatePaymentAction::execute()
    │
    ├── Validate API key scopes
    ├── Check rate limit
    ├── Check idempotency
    ├── Process via WalletService/TransferService
    │
    ├── emit(PaymentCompleted) ─────────────────────────────┐
    │    ├── WebhookDeliveryService                         │
    │    │    ├── Find subscribed endpoints                  │
    │    │    ├── Queue ProcessWebhookDeliveryJob            │
    │    │    └── Retry 3x with backoff                      │
    │    │                                                   │
    │    └── LogApiUsageAction                               │
    │         └── Insert api_usage_logs record               │
    │                                                        │
    ├── emit(ApiKeyUsed)                                     │
    │    └── Update api_keys.last_used_at                    │
    │                                                        │
    └── Return PaymentResult                                 │
```

## Webhook Delivery Flow
```
External Event (e.g., TransferCompleted)
    │
    ▼
WebhookDeliveryService::deliver()
    │
    ├── Find active webhook endpoints subscribed to this event
    ├── For each endpoint:
    │    ├── Generate HMAC-SHA256 signature
    │    ├── Create WebhookDelivery record (status: pending)
    │    ├── Dispatch ProcessWebhookDeliveryJob
    │    │
    │    └── Job Execution:
    │         ├── POST to endpoint URL (timeout: 10s)
    │         ├── 200 OK → status: delivered
    │         ├── 4xx/5xx/Timeout → retry (3x, exponential backoff)
    │         └── All retries failed → status: failed
    │
    └── Return delivery summary
```
