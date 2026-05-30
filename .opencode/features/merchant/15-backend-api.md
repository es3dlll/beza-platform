# Merchant Backend API Specification

## Endpoint: Register Merchant

```http
POST /api/v1/merchant/register
Content-Type: application/json
Idempotency-Key: {uuid}

{
  "phone": "+963912345678",
  "pin": "hashed_pin_value",
  "business_name": "متجر الشمّام",
  "business_type": "grocery",
  "license_number": "12345",
  "license_image": "data:image/jpeg;base64,...",
  "shop_photos": ["data:image/jpeg;base64,..."],
  "location": {
    "lat": 33.5138,
    "lng": 36.2765
  },
  "customer_phone": "+963912345600"
}
```

### Response: 201 Created
```json
{
  "status": "success",
  "data": {
    "merchant_id": 42,
    "business_name": "متجر الشمّام",
    "status": "pending",
    "tier": "small",
    "mdr_rate": 1.5,
    "qr_code": {
      "id": 1,
      "type": "static",
      "image_url": "https://cdn.beza.com/merchant/42/qr_static.png",
      "download_url": "https://cdn.beza.com/merchant/42/qr_static.png?download=1"
    },
    "created_at": "2026-06-01T10:00:00Z"
  }
}
```

## Endpoint: Generate QR Code

```http
POST /api/v1/merchant/qr/generate
Authorization: Bearer {token}
Content-Type: application/json

{
  "type": "dynamic",
  "amount": 45000,
  "expires_in": 3600
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "qr_id": 5,
    "type": "dynamic",
    "amount": 45000,
    "image_url": "https://cdn.beza.com/merchant/42/qr_dynamic_45000.png",
    "qr_data": "beza://pay/merchant/42?type=dynamic&amount=45000",
    "expires_at": "2026-06-01T11:00:00Z",
    "created_at": "2026-06-01T10:00:00Z"
  }
}
```

## Endpoint: Serve QR Image

```http
GET /api/v1/merchant/qr/{id}
```
Serves the QR image directly (redirect to CDN). No auth required — designed to be printed and displayed publicly.

### Response: 302 Redirect
Redirects to `https://cdn.beza.com/merchant/42/qr_static.png` with Cache-Control: public, max-age=86400

## Endpoint: Create Payment Link

```http
POST /api/v1/merchant/payment-link
Authorization: Bearer {token}
Content-Type: application/json

{
  "amount": 45000,
  "currency": "SYP",
  "description": "شنطة ظهر جلدية",
  "expires_in": 3600
}
```

### Response: 201 Created
```json
{
  "status": "success",
  "data": {
    "link_id": "pl_abc123",
    "short_url": "https://pay.beza.com/pay/pl_abc123",
    "full_url": "https://pay.beza.com/pay/pl_abc123?merchant=42",
    "amount": 45000,
    "currency": "SYP",
    "description": "شنطة ظهر جلدية",
    "status": "pending",
    "expires_at": "2026-06-01T11:00:00Z",
    "created_at": "2026-06-01T10:00:00Z"
  }
}
```

## Endpoint: POS Terminal Pairing

```http
POST /api/v1/merchant/pos/pair
Authorization: Bearer {token}
Content-Type: application/json

{
  "serial_number": "SN-ABC123XYZ",
  "model": "Sunmi V2s",
  "terminal_id": "TERM-001"
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "terminal_id": 1,
    "merchant_id": 42,
    "serial_number": "SN-ABC123XYZ",
    "model": "Sunmi V2s",
    "status": "active",
    "paired_at": "2026-06-01T10:00:00Z",
    "certificate_sn": "CERT-001-ABC"
  }
}
```

## Endpoint: Transaction History

```http
GET /api/v1/merchant/transactions?page=1&per_page=20&from=2026-05-01&to=2026-06-01&method=qr&search=0912&sort=desc
Authorization: Bearer {token}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "transactions": [
      {
        "id": "txn_mer_abc123",
        "amount": 45000,
        "mdr": 675,
        "net_amount": 44325,
        "currency": "SYP",
        "method": "qr",
        "customer_phone": "+963912345678",
        "status": "completed",
        "reference": "TXN-MER-ABC123",
        "timestamp": "2026-06-01T10:00:00Z"
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
      "total_gross": 850000,
      "total_mdr": 12750,
      "total_net": 837250,
      "period": "2026-05-01 to 2026-06-01"
    }
  }
}
```

## Endpoint: Settlements

```http
GET /api/v1/merchant/settlements?page=1&per_page=10
Authorization: Bearer {token}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "settlements": [
      {
        "id": 1,
        "period_start": "2026-06-01T00:00:00Z",
        "period_end": "2026-06-01T23:59:59Z",
        "gross_amount": 850000,
        "mdr_amount": 12750,
        "net_amount": 837250,
        "currency": "SYP",
        "transaction_count": 12,
        "status": "completed",
        "paid_at": "2026-06-02T00:15:00Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 10,
      "total": 15,
      "last_page": 2
    }
  }
}
```

## Endpoint: Configure Webhook

```http
POST /api/v1/merchant/webhook/configure
Authorization: Bearer {token}
Content-Type: application/json

{
  "url": "https://api.damascusbazar.com/beza-webhook",
  "events": ["payment.completed", "settlement.completed"],
  "secret": "whsec_abc123"
}
```

### Response: 200 OK
```json
{
  "status": "success",
  "data": {
    "webhook_id": 1,
    "url": "https://api.damascusbazar.com/beza-webhook",
    "events": ["payment.completed", "settlement.completed"],
    "status": "active",
    "created_at": "2026-06-01T10:00:00Z"
  }
}
```

### Error Responses
```json
// 400 — Validation Error
{
  "status": "error",
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "بيانات الإدخال غير صحيحة",
    "details": {
      "business_name": ["اسم المتجر مطلوب"],
      "amount": ["يجب أن يكون المبلغ 1,000 ل.س على الأقل"]
    }
  }
}

// 402 — Payment Required (MDR not covered)
{
  "status": "error",
  "error": {
    "code": "INSUFFICIENT_SETTLEMENT_AMOUNT",
    "message": "المبلغ صافي أقل من الحد الأدنى للتسوية"
  }
}

// 404 — QR/Link Not Found
{
  "status": "error",
  "error": {
    "code": "QR_NOT_FOUND",
    "message": "رمز QR غير موجود"
  }
}

// 410 — Payment Link Expired
{
  "status": "error",
  "error": {
    "code": "PAYMENT_LINK_EXPIRED",
    "message": "انتهت صلاحية رابط الدفع",
    "details": {
      "link_id": "pl_abc123",
      "expired_at": "2026-06-01T11:00:00Z"
    }
  }
}
```
