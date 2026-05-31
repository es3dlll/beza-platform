# Backend Architecture — Marketplace Module

## Module Overview

The Marketplace module is a domain-driven microservice within the Beza platform. It handles product catalog management, order processing, payment holds, fulfillment, telecom integration, voucher management, and commission calculations.

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                   API Gateway Layer                          │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────────┐  │
│  │  REST    │ │  GraphQL │ │ WebSocket│ │  Webhook     │  │
│  │  API     │ │  (admin) │ │ (status) │ │ (telecom)    │  │
│  └──────────┘ └──────────┘ └──────────┘ └──────────────┘  │
└─────────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────────┐
│                   Service Layer                              │
│  ┌───────────────┐  ┌───────────────┐  ┌───────────────┐  │
│  │ProductCatalog │  │  OrderService │  │ Fulfillment   │  │
│  │   Service     │  │               │  │   Service     │  │
│  │ - categories  │  │ - cart mgmt   │  │ - digital     │  │
│  │ - products    │  │ - checkout    │  │ - physical    │  │
│  │ - pricing     │  │ - payment hold│  │ - tracking    │  │
│  │ - inventory   │  │ - refunds     │  │               │  │
│  └──────┬────────┘  └──────┬────────┘  └──────┬────────┘  │
│         │                  │                   │           │
│  ┌──────┴────────┐  ┌─────┴──────┐  ┌─────────┴───────┐  │
│  │Telecom        │  │  Voucher   │  │  Commission     │  │
│  │Integration    │  │   Service  │  │    Service      │  │
│  │   Service     │  │            │  │                 │  │
│  │ - SyriatelAPI │  │ - generate │  │ - calculate     │  │
│  │ - MTN API     │  │ - redeem   │  │ - record        │  │
│  │ - SMS/USSD    │  │ - validate │  │ - settlement    │  │
│  └───────────────┘  └────────────┘  └─────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────────┐
│                   Data Layer                                 │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────┐ │
│  │ PostgreSQL   │  │    Redis     │  │  Object Store    │ │
│  │ - products   │  │ - cart cache │  │  (images/docs)   │ │
│  │ - orders     │  │ - sessions   │  │                  │ │
│  │ - users      │  │ - rate limit │  │                  │ │
│  └──────────────┘  └──────────────┘  └──────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────────┐
│              External Integration Layer                      │
│  ┌────────────┐  ┌──────────┐  ┌──────────┐  ┌─────────┐  │
│  │ Syriatel   │  │   MTN    │  │  Vendor  │  │ SMS     │  │
│  │   API      │  │   API    │  │  APIs    │  │ Gateway │  │
│  └────────────┘  └──────────┘  └──────────┘  └─────────┘  │
└─────────────────────────────────────────────────────────────┘
```

## Service Specifications

### 1. ProductCatalogService

**Purpose**: Manage the product catalog — categories, products, pricing, and inventory.

**Key Responsibilities**:
- CRUD operations for categories and products
- Product search with full-text, filters, and faceted navigation
- Price management with support for multiple currencies (SYP primary, USD for international)
- Real-time inventory tracking with stock depletion alerts
- Product moderation workflow (vendor submits → admin approves)
- Category hierarchy management (up to 3 levels)

**Key Classes / Data Models**:
```
ProductCatalogService
├── CategoryController (admin)
├── ProductController (public + admin)
├── CategoryService
│   ├── getTree()
│   └── getProductsByCategory(categoryId, filters)
├── ProductService
│   ├── search(query, filters, page)
│   ├── getById(productId)
│   ├── create(product) [vendor]
│   ├── update(productId, data) [vendor]
│   ├── moderate(productId, action) [admin]
│   └── updateStock(productId, delta)
├── InventoryService
│   ├── checkAvailability(productId, quantity)
│   ├── reserve(productId, quantity)
│   ├── release(productId, quantity)
│   └── confirmDeduction(productId, quantity)
├── PricingService
│   ├── calculatePrice(productId, quantity, promoCode)
│   ├── getCommissionAmount(productId)
│   └── getFinalPrice(productId, userId)
└── ProductRepository
```

**Integration Points**:
- OrderService: checks product availability during checkout
- FulfillmentService: confirms inventory deduction post-fulfillment
- CommissionService: provides commission rates per product/category

### 2. OrderService

**Purpose**: Handle the complete order lifecycle — cart, checkout, payment hold, and order management.

**Key Responsibilities**:
- Session-based cart management (Redis-backed)
- Checkout flow with multi-item validation
- Wallet payment hold (two-phase commit: hold → confirm or release)
- Order creation with unique ID generation (format: MKT-YYYY-SEQ)
- Order status tracking through lifecycle states
- Refund processing with approval workflow
- Order history and re-order functionality

**Order Lifecycle States**:
```
PENDING → HOLD_PLACED → PROCESSING → FULFILLED → COMPLETED
   │           │            │
   └── CANCELLED     REFUND_PENDING → REFUNDED
```

**Key Classes**:
```
OrderService
├── CartController
├── OrderController
├── CartService
│   ├── getCart(userId)
│   ├── addItem(userId, productId, quantity)
│   ├── updateItem(userId, itemId, quantity)
│   ├── removeItem(userId, itemId)
│   ├── applyPromo(userId, code)
│   └── clearCart(userId)
├── CheckoutService
│   ├── validateCart(userId)
│   ├── placeHold(userId, amount)
│   ├── createOrder(userId, cartData)
│   └── confirmOrder(orderId)
├── OrderManagementService
│   ├── getOrder(orderId)
│   ├── getUserOrders(userId, status, page)
│   ├── cancelOrder(orderId)
│   ├── requestRefund(orderId, reason)
│   ├── approveRefund(orderId) [admin]
│   └── rejectRefund(orderId, reason) [admin]
└── OrderRepository
```

**Integration Points**:
- ProductCatalogService: validates product existence, price, stock
- FulfillmentService: triggers fulfillment on order confirmation
- WalletService (external): places and releases payment holds
- CommissionService: records commission on order completion
- VoucherService: generates gift cards on order completion

### 3. FulfillmentService

**Purpose**: Deliver products to users — digital goods delivery (instant codes), physical goods tracking.

**Key Responsibilities**:
- Auto-fulfill digital goods via integrated vendor APIs
- Manual fulfillment interface for non-integrated vendors
- Delivery confirmation and status updates
- Retry logic for failed deliveries (3 attempts, 5-minute intervals)
- Physical goods tracking with carrier integration
- Partial fulfillment for multi-item orders

**Fulfillment Types**:
| Type | Description | SLA |
|---|---|---|
| INSTANT_CODE | Digital code delivered immediately | < 5s |
| VENDOR_API | Vendor API delivers directly | < 30s |
| MANUAL | Vendor manually marks delivered | < 24h |
| PHYSICAL | Physical goods shipped | 3–7 days |

**Key Classes**:
```
FulfillmentService
├── FulfillmentController (vendor dashboard)
├── FulfillmentEngine
│   ├── processOrder(orderId)
│   ├── fulfillItem(orderItemId)
│   ├── retryFulfillment(orderItemId, attempt)
│   └── markFailed(orderItemId, reason)
├── DigitalFulfillmentProvider
│   ├── deliverCode(productId, quantity) → [code(s)]
│   ├── verifyCode(code)
│   └── revokeCode(code)
├── PhysicalFulfillmentProvider
│   ├── createShipment(orderId, address)
│   ├── trackShipment(trackingNumber)
│   └── confirmDelivery(trackingNumber)
└── FulfillmentRepository
```

**Integration Points**:
- OrderService: receives fulfillment requests, reports status
- Vendor API: calls vendor endpoints for digital delivery
- TelecomIntegrationService: handles telecom-specific fulfillment

### 4. TelecomIntegrationService

**Purpose**: Direct API integration with Syrian telecom operators for mobile top-up and data bundle purchases.

**Supported Operators**:
| Operator | Prefixes | API Type | Auth |
|---|---|---|---|
| Syriatel | 093x, 094x | REST + SOAP | API Key + IP Whitelist |
| MTN | 095x, 096x | REST | OAuth 2.0 Client Credentials |

**Key Responsibilities**:
- Real-time balance check and top-up execution
- Transaction status polling with configurable timeout (30s default)
- Idempotency via unique request ID for each transaction
- Retry logic with exponential backoff (3 retries, base 2s)
- Fallback SMS/USSD gateway when API is unavailable
- Weekly settlement reconciliation report generation

**Top-Up Flow**:
```
User → Beza App → OrderService → TelecomIntegrationService → Syriatel/MTN API
                              │
                          [Idempotency Key]
                              │
                    ┌─────────┴──────────┐
                    │     Send Top-Up     │
                    │     Request         │
                    └─────────┬──────────┘
                              │
                    ┌─────────┴──────────┐
                    │  Poll Status (30s)  │
                    │  200 OK → Success   │
                    │  Timeout → Fallback │
                    └─────────┬──────────┘
                              │
                    ┌─────────┴──────────┐
                    │  Notify OrderService│
                    │  of Result          │
                    └────────────────────┘
```

**Key Classes**:
```
TelecomIntegrationService
├── TopUpController
├── TelecomProviderFactory
│   ├── getProvider(network) → ITelecomProvider
│   └── registerProvider(provider)
├── SyriatelProvider (implements ITelecomProvider)
│   ├── topUp(phoneNumber, amount, requestId)
│   ├── checkBalance(phoneNumber)
│   ├── getDataPlans()
│   ├── purchaseDataPlan(phoneNumber, planId)
│   └── getTransactionStatus(requestId)
├── MTNProvider (implements ITelecomProvider)
│   ├── topUp(phoneNumber, amount, requestId)
│   ├── checkBalance(phoneNumber)
│   ├── getDataPlans()
│   ├── purchaseDataPlan(phoneNumber, planId)
│   └── getTransactionStatus(requestId)
├── SmsFallbackProvider
│   └── sendSmsTopUp(phoneNumber, amount)
├── SettlementService
│   ├── generateReport(startDate, endDate)
│   └── reconcile(records)
└── TelecomRepository
```

**API Configuration**:
```json
{
  "syriatel": {
    "baseUrl": "https://api.syriatel.sy/topup/v2",
    "apiKey": "${SYRIATEL_API_KEY}",
    "secret": "${SYRIATEL_SECRET}",
    "ipWhitelist": ["10.0.0.0/8"],
    "timeout": 15000,
    "retryCount": 3,
    "retryBaseDelayMs": 2000
  },
  "mtn": {
    "baseUrl": "https://api.mtn.com.sy/topup/v1",
    "clientId": "${MTN_CLIENT_ID}",
    "clientSecret": "${MTN_CLIENT_SECRET}",
    "tokenUrl": "https://api.mtn.com.sy/oauth/v2/token",
    "timeout": 10000,
    "retryCount": 3,
    "retryBaseDelayMs": 2000
  }
}
```

### 5. VoucherService

**Purpose**: Generate, manage, and redeem digital gift cards (vouchers) for partner merchants.

**Key Responsibilities**:
- Gift card generation with unique 16-digit alphanumeric codes + QR
- Merchant-branded card design template
- Multi-channel delivery (WhatsApp, SMS, email, in-app, PDF print)
- Redemption at partner merchants (online via code, in-store via QR)
- Balance tracking per card
- Expiration management (auto-deactivate + notify holder)
- Partial redemption support
- Bulk gift card purchase for enterprises

**Key Classes**:
```
VoucherService
├── GiftCardController
├── GiftCardService
│   ├── purchase(merchantId, amount, quantity, recipient)
│   ├── generate(merchantId, amount, quantity)
│   ├── getCard(cardCode)
│   ├── getUserCards(userId)
│   ├── redeem(cardCode, merchantId, amount)
│   ├── getBalance(cardCode)
│   ├── cancel(cardCode)
│   └── expireCards()
├── DeliveryService
│   ├── sendWhatsApp(phoneNumber, cardData)
│   ├── sendSMS(phoneNumber, cardData)
│   ├── sendEmail(email, cardData)
│   ├── generatePdf(cardData)
│   └── generateQr(cardData)
├── MerchantService
│   ├── validateMerchant(merchantId, cardCode)
│   ├── recordRedemption(merchantId, cardCode, amount)
│   └── getMerchantCards(merchantId)
└── VoucherRepository
```

### 6. CommissionService

**Purpose**: Calculate, record, and manage commissions for the marketplace.

**Commission Model**:
| Category | Base Rate | Volume Tier 1 (>500k) | Volume Tier 2 (>2M) |
|---|---|---|---|
| Mobile Top-Up | 3% | 4% | 5% |
| Internet Packages | 5% | 6% | 7% |
| Gift Cards | 8% | 9% | 10% |
| Digital Goods | 12% | 13% | 15% |
| Bill Payment | 2% | 3% | 4% |

**Key Responsibilities**:
- Commission calculation on each completed transaction
- Tiered rates based on vendor monthly volume
- Automatic deduction at payment settlement
- Payout scheduling (instant for digital, 7-day for physical)
- Commission statements for vendors and Beza finance
- Tax withholding calculation where applicable

**Key Classes**:
```
CommissionService
├── CommissionController (admin + vendor)
├── CommissionCalculator
│   ├── calculate(orderItem)
│   ├── getEffectiveRate(vendorId, categoryId)
│   └── applyTier(amount, categoryId)
├── CommissionLedger
│   ├── record(transactionId, vendorId, amount, rate, commission)
│   ├── getVendorEarnings(vendorId, period)
│   ├── getPlatformRevenue(period)
│   └── getPendingPayouts(vendorId)
├── PayoutService
│   ├── requestPayout(vendorId, amount)
│   ├── processPayout(payoutId)
│   ├── getPayoutSchedule()
│   └── generatePayoutReport(period)
└── CommissionRepository
```

## Cross-Cutting Concerns

### Idempotency
- All payment-related operations use idempotency keys
- Top-up requests include unique request_id to prevent double-charging
- Order creation is idempotent using cart ID + user ID hash

### Error Handling
- Graceful degradation: catalog remains available if telecom API is down
- Circuit breaker on external API calls (failure threshold: 5 in 60s)
- Dead letter queue for failed fulfillment; manual retry via admin panel

### Caching Strategy
| Data | Cache | TTL | Invalidation |
|---|---|---|---|
| Product catalog | Redis | 5 min | On product update |
| Categories | Redis | 30 min | On category change |
| Cart | Redis | 24h | On cart modification |
| Telecom rates | Redis | 1h | On rate change |
| Vendor commissions | Redis | 1h | On commission update |

### Logging & Monitoring
- All service operations logged with correlation ID
- Telecom API calls logged with request/response payloads (excluding secrets)
- Metrics tracked: order volume, fulfillment latency, commission accrual
- Alerts on: fulfillment failure rate > 1%, telecom API timeout > 5%
