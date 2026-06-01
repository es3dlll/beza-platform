# 02 - مكان العملية في النظام (Architecture)

## رسم معماري (Architecture Diagram)

```
┌─────────────────────────────────────────────────────────────────────┐
│                        Flutter / React                              │
│                   GoldScreen / GoldPage                              │
└───────────────────────────┬─────────────────────────────────────────┘
                            │ HTTPS / JSON
                            ▼
┌─────────────────────────────────────────────────────────────────────┐
│                   Laravel API (api/v1/commodity/)                    │
│                                                                     │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │              Middleware Stack                                │   │
│  │  ┌──────────┐ ┌──────────┐ ┌─────────────┐ ┌────────────┐  │   │
│  │  │ auth:    │ │ throttle │ │ verifyPin   │ │ validate:  │  │   │
│  │  │ api      │ │ :10,1    │ │ (sell only) │ │ FormRequest│  │   │
│  │  └──────────┘ └──────────┘ └─────────────┘ └────────────┘  │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                     │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │              CommodityController                              │   │
│  │  GET  /prices     → getPrices()                              │   │
│  │  POST /buy        → buy()                                    │   │
│  │  POST /sell       → sell()                                   │   │
│  │  GET  /holdings   → getHoldings()                            │   │
│  │  GET  /history    → getHistory()                              │   │
│  └──────────────────────┬──────────────────────────────────────┘   │
│                          │                                          │
│  ┌──────────────────────▼──────────────────────────────────────┐   │
│  │              CommodityService                                │   │
│  │  ┌──────────────────┐ ┌─────────────────┐                   │   │
│  │  │ executeBuy()     │ │ executeSell()   │                   │   │
│  │  │ - Price check    │ │ - Holding check │                   │   │
│  │  │ - Wallet deduct  │ │ - 24h min check │                   │   │
│  │  │ - Holding upsert │ │ - Wallet credit │                   │   │
│  │  │ - Tx create      │ │ - Holding decr  │                   │   │
│  │  └──────────────────┘ └─────────────────┘                   │   │
│  └──────────────────────┬──────────────────────────────────────┘   │
│                          │                                          │
│  ┌──────────────────────▼──────────────────────────────────────┐   │
│  │  ┌──────────────────────┐  ┌──────────────────────┐        │   │
│  │  │   WalletService      │  │  PriceFeedProvider   │        │   │
│  │  │  - decrement()       │  │  - getPrice(commodity)│        │   │
│  │  │  - increment()       │  │  - isMarketOpen()    │        │   │
│  │  │  - freeze()          │  │  - getSpread()       │        │   │
│  │  │  - lockForUpdate()   │  │  │                   │        │   │
│  │  └──────────────────────┘  │  ┌──────────────────┐│        │   │
│  │                            │  │ XAU/USD API      ││        │   │
│  │                            │  │ XAG/USD API      ││        │   │
│  │                            │  └──────────────────┘│        │   │
│  │                            └──────────────────────┘        │   │
│  └──────────────────────┬──────────────────────────────────────┘   │
│                          │                                          │
│  ┌──────────────────────▼──────────────────────────────────────┐   │
│  │              قاعدة البيانات (MySQL/PostgreSQL)               │   │
│  │  ┌────────────────┐ ┌────────────────┐ ┌────────────────┐   │   │
│  │  │commodity_prices│ │commodity_      │ │commodity_      │   │   │
│  │  │                │ │holdings        │ │transactions    │   │   │
│  │  └────────────────┘ └────────────────┘ └────────────────┘   │   │
│  │  ┌────────────────┐ ┌────────────────┐                      │   │
│  │  │commodity_orders│ │ wallets        │                      │   │
│  │  └────────────────┘ └────────────────┘                      │   │
│  └─────────────────────────────────────────────────────────────┘   │
│                                                                     │
│  ┌─────────────────────────────────────────────────────────────┐   │
│  │              Events / Listeners (Async)                      │   │
│  │  GoldPurchased → SendPurchaseReceipt + UpdateHoldingValuation│   │
│  │  GoldSold      → SendSaleReceipt                             │   │
│  │  PriceAlert    → SendPriceAlertNotification                  │   │
│  └─────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────┘
```

## مكونات النظام

| الطبقة | المكون | المسؤولية |
|--------|--------|-----------|
| Presentation | Flutter `GoldScreen` / React `GoldPage` | عرض الأسعار، نموذج شراء/بيع، مخطط المحفظة |
| Application | `CommodityController` | استقبال الطلبات، إرجاع الاستجابات |
| Domain | `CommodityService` | منطق الأعمال الأساسي |
| Integration | `PriceFeedProvider` | جلب الأسعار من API خارجي، التحقق من فتح السوق |
| Integration | `WalletService` | إدارة رصيد المحافظ |
| Persistence | 4 جداول + wallets | تخزين البيانات |
| Async | Events → Listeners | إشعارات وتحديثات |

## مسار الطلب (Request Path)

```
Flutter App
    ↓ POST /api/v1/commodity/buy  (JSON: {commodity: "gold", amount_spent: 500, currency: "USD"})
    ↓
auth:api → throttle:10,1 → CommodityBuyRequest (validation)
    ↓
CommodityController@buy
    ↓
CommodityService::executeBuy()
    ├── PriceFeedProvider::getPrice("gold")     ← XAU/USD API
    ├── PriceFeedProvider::isMarketOpen()       ← Check weekend
    ├── WalletService::getWallet(userId, "USD") ← User's USD wallet
    ├── WalletService::decrement(wallet, 500)   ← Deduct
    ├── CommodityHolding::updateOrCreate()      ← Add grams
    └── CommodityTransaction::create()          ← Log
    ↓
Event: GoldPurchased::dispatch()
    ↓
Response: {success: true, grams: 4.85, price_per_gram: 103.00, holding: {...}}
```

## تقنيات مستخدمة

- **Laravel 11** — PHP Framework
- **MySQL 8.0** — InnoDB (ACID, Row-Level Locking)
- **JWT** — API authentication (token-based)
- **PriceFeedProvider** — خدمة مخصصة لجلب الأسعار (XAU/USD via GoldAPI.io أو equivalent)
- **Redis** — تخزين الأسعار مؤقتاً (TTL: 30s)
- **Queue (Horizon)** — معالجة الإشعارات بشكل غير متزامن
- **Flutter BLoC** — إدارة الحالة في التطبيق
- **React Hooks** — إدارة الحالة في الويب
