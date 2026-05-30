# Merchant Infrastructure

## Deployment Architecture
```
┌───────────────────────────────────────────────────────────────┐
│                     Kubernetes Cluster                        │
│                                                               │
│  ┌─────────────────────────────────────────────────────┐     │
│  │              Merchant Module Pods                    │     │
│  │  ┌──────────────┐  ┌──────────────┐  ┌───────────┐ │     │
│  │  │  Merchant API  │  │  QR Gen       │  │ Settlement│ │     │
│  │  │  Replicas: 3   │  │  Service: 2   │  │  Worker:2 │ │     │
│  │  │  CPU: 2, RAM:4 │  │  CPU: 4,RAM:8 │  │  CPU:1    │ │     │
│  │  └──────────────┘  └──────────────┘  └───────────┘ │     │
│  │                                                       │     │
│  │  ┌──────────────┐  ┌──────────────┐  ┌───────────┐ │     │
│  │  │  Webhook      │  │  POS Sync     │  │  Link     │ │     │
│  │  │  Deliverer: 3 │  │  Service: 2   │  │  Expirer  │ │     │
│  │  └──────────────┘  └──────────────┘  └───────────┘ │     │
│  └─────────────────────────────────────────────────────┘     │
│                                                               │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐       │
│  │  Redis Cache  │  │  MySQL       │  │  RabbitMQ    │       │
│  │  Replicas: 2  │  │  Primary+2 RO│  │  Cluster 3   │       │
│  └──────────────┘  └──────────────┘  └──────────────┘       │
│                                                               │
│  ┌──────────────┐  ┌──────────────┐                           │
│  │  CDN (QR      │  │  Object      │                           │
│  │  Images)      │  │  Storage     │                           │
│  └──────────────┘  └──────────────┘                           │
└───────────────────────────────────────────────────────────────┘
```

## QR Code Generation Service

### Architecture
```
QR Code Generation Pipeline:
  1. Merchant requests QR (static or dynamic)
  2. QrService generates payload: beza://pay/merchant/{id}?type={type}[&amount={amount}]
  3. QrCodeGenerator (PHP GD/Imagick or external microservice):
     - Generate QR code matrix using QR code library
     - Overlay merchant logo (if available) at center
     - Add decorative frame with business name
     - Render at 400x400px PNG (print quality: 1200x1200 for laminated)
  4. Upload to CDN: cdn.beza.com/merchant/{merchant_id}/qr_{type}_{timestamp}.png
  5. Save URL to merchant_qr_codes table
  6. Return URL to merchant

CDN Requirements:
  - Provider: CloudFront / BunnyCDN / local edge
  - Cache: QR images immutable, cache 1 year
  - Edge locations: Middle East (Beirut, Istanbul, Dubai) for low latency
  - Fallback: Serve directly from object storage if CDN edge down

QR Payload Format:
  Static:  beza://pay/merchant/42?type=static
  Dynamic: beza://pay/merchant/42?type=dynamic&amount=45000
  Table:   beza://pay/merchant/42?type=static&table=5
  (Future: NFC tag support — same payload on NFC chip)
```

## POS Terminal Certificate Management (mTLS)

```
POS Terminal Authentication:
  1. Each POS terminal gets a unique client certificate during pairing
  2. Certificate generated server-side, installed on terminal via QR + OTP
  3. All POS-to-server API calls require mTLS (mutual TLS)
  4. Certificate pinned to specific merchant account

Certificate Lifecycle:
  Generation:
    - CA: Beza Internal CA (offline root, intermediate for POS)
    - Key: ECDSA P-384
    - Validity: 2 years
    - SAN: terminal serial number + merchant ID

  Installation:
    - Merchant scans QR on POS → POS downloads cert + key (encrypted)
    - POS stores cert in hardware-backed keystore (TEE/SE)
    - POS sends cert fingerprint → server verifies → activates terminal

  Rotation:
    - Auto-renewal 30 days before expiry
    - POS checks cert expiry daily
    - If expired: POS shows "شهادة منتهية — قم بتحديث الجهاز" 
    - Renewal: Server generates new cert → POS downloads → mutual re-auth

  Revocation:
    - Merchant reports terminal lost → immediate cert revocation
    - Revoked cert added to CRL, pushed to all services
    - POS with revoked cert rejected at API gateway
```

## Webhook Delivery System

```
Webhook Delivery Architecture:
  - Webhook URL configured by merchant via API or dashboard
  - Events: payment.completed, settlement.completed, refund.completed
  - Signature: HMAC-SHA256 of payload using merchant's webhook_secret
  - Delivery: HTTP POST with JSON body + signature header

Delivery Flow:
  1. Event emitted (e.g., QrPaymentCompleted)
  2. DeliverWebhookJob dispatched to queue
  3. Job loads merchant's webhook config
  4. Builds payload + signs with HMAC-SHA256
  5. POST to merchant's URL with headers:
     X-Beza-Signature: sha256=...
     X-Beza-Event: payment.completed
     X-Beza-Timestamp: 2026-06-01T10:30:00Z
     Content-Type: application/json
  6. Wait for HTTP 200/201 response within 10 seconds
  7. If success: log delivery (status: delivered)
  8. If failure/timeout: retry with exponential backoff

Retry Policy:
  Attempt 1: Immediate
  Attempt 2: 30 seconds
  Attempt 3: 5 minutes
  After 3 failures: status = failed, alert merchant via app notification
  
  Dead Letter Queue:
    - Webhooks failing after 3 retries go to DLQ
    - Ops reviews daily: retry manually or contact merchant
    - DLQ retention: 7 days

  Monitoring:
    - Webhook delivery rate dashboard
    - Alert if delivery rate < 99% over 5 minutes
    - Alert if any merchant has > 10 consecutive failures
```

## Settlement Batch Processing

```
Settlement Cron Job:
  Schedule: 23:59 daily (Kubernetes CronJob)
  Timeout: 30 minutes (must complete by 00:30)
  Concurrency: Process merchants in batches of 100

Processing:
  1. Query all merchants with completed, unsettled transactions today
  2. For each merchant (in batches of 100, parallel):
     a. Calculate gross amount
     b. Calculate MDR by payment method
     c. Calculate net amount
     d. Create settlement record (status: processing)
     e. Post CFE transfer: settlement clearing → merchant wallet
     f. Mark transactions as settled
     g. Mark settlement as completed
  3. Emit MerchantSettled events for each
  4. Generate settlement reports (PDF) and upload to object storage
  5. Log summary: merchants processed, total gross, total MDR, total net

Failure Handling:
  - Merchant-level failure: Skip merchant, add to retry queue, continue batch
  - Batch-level failure: Retry batch after 5 minutes (max 3 retries)
  - Job-level failure: Alert ops, manual intervention required

  Retry Queue:
    Merchants that failed settlement are queued for retry every 30 minutes
    After 5 retries → ops alert + manual settlement
```

## Scaling Strategy
```
Merchant API:
  - HPA: CPU > 70% OR memory > 75% → scale up to max 10
  - P99 latency > 1500ms → scale up
  - Concurrency: 300 req/s per replica

QR Generation:
  - CPU-intensive (image processing)
  - Pre-generate: Static QR generated once, cached permanently
  - Dynamic QR: Generated on-demand, cached with TTL = expiry
  - Scale: 2-4 replicas (burst to 8 during peak)

Settlement Worker:
  - Batch: Process 100 merchants/minute
  - Scale up during EOD: 2 replicas → 6 during 23:00-01:00
  - Auto-scaling: Based on queue depth

Webhook Deliverer:
  - I/O-bound (HTTP calls)
  - Scale: 3-10 replicas based on webhook queue depth
  - Max concurrency: 20 webhooks per replica
```

## Caching Strategy
```php
// QR Image URL (immutable, cache forever)
public function getQrImageUrl(int $qrId): string
{
    $cacheKey = "merchant:qr:{$qrId}:image";
    return Cache::rememberForever($cacheKey, function () use ($qrId) {
        return $this->qrRepo->findOrFail($qrId)->image_url;
    });
}

// Merchant Profile (frequently accessed during payment)
public function getMerchantProfile(int $merchantId): array
{
    $cacheKey = "merchant:{$merchantId}:profile";
    return Cache::remember($cacheKey, 300, function () use ($merchantId) {
        return $this->merchantRepo->findOrFail($merchantId)->getPublicProfile();
    });
}

// Daily Sales (real-time, short TTL)
public function getDailySales(int $merchantId): int
{
    $cacheKey = "merchant:{$merchantId}:sales:today";
    return Cache::remember($cacheKey, 30, function () use ($merchantId) {
        return $this->txnRepo->getDailyTotal($merchantId);
    });
}

// Invalidate on new transaction
Event::listen(function (QrPaymentCompleted|PaymentLinkPaid $event) {
    Cache::forget("merchant:{$event->merchantId}:sales:today");
});
```

## Rate Limiting (Kong Gateway)
```yaml
rate_limits:
  merchant_qr_generate:
    merchant: 10/minute
    burst: 5
  
  merchant_payment_link:
    merchant: 30/minute
    burst: 10
  
  merchant_pos_pair:
    merchant: 3/minute
    burst: 2
  
  merchant_transaction_list:
    merchant: 60/minute
    admin: 500/minute
    burst: 20
  
  merchant_settlement_list:
    merchant: 30/minute
    admin: 200/minute
    burst: 10
  
  merchant_webhook_config:
    merchant: 5/minute
    burst: 3
  
  # Public endpoints (no auth)
  qr_image_serve:
    anonymous: 1000/minute
    burst: 100
  
  payment_link_view:
    anonymous: 100/minute
    burst: 20
```
