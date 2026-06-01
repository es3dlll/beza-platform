# 04 - علاقات الجداول (Database Relationships / ER)

## رسم علاقات الكيانات (ER Diagram)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                            users                                        │
├─────────────────────────────────────────────────────────────────────────┤
│ id (PK)                                                                 │
│ name, phone, email, password, pin_code, status, ...                    │
└────────┬──────────────────────────────────────────┬──────────────────────┘
         │ 1                                     1  │
         │                                         │
         │                                         │
         ▼ 1:N                                    ▼ 1:N
┌────────────────────────────┐     ┌──────────────────────────────────────┐
│  commodity_holdings        │     │  commodity_transactions              │
├────────────────────────────┤     ├──────────────────────────────────────┤
│ id (PK)                    │     │ id (PK)                              │
│ user_id (FK → users.id)    │────▶│ user_id (FK → users.id)             │
│ commodity ENUM('gold','silver')│  │ commodity ENUM('gold','silver')      │
│ grams DECIMAL(15,4)        │     │ type ENUM('buy','sell')              │
│ avg_price_usd DECIMAL(15,2)│     │ grams DECIMAL(15,4)                  │
│ total_invested_usd DECIMAL │     │ price_usd DECIMAL(15,2)              │
│ created_at                 │     │ total_usd DECIMAL(15,2)              │
│ updated_at                 │     │ fee DECIMAL(15,2)                    │
├────────────────────────────┤     │ reference_number VARCHAR(50) UNIQUE  │
│ UNIQUE(user_id, commodity) │     │ status ENUM(...)                     │
└────────────────────────────┘     │ created_at TIMESTAMP                 │
                                   └──────────────────────────────────────┘
                                              │
                                              │ 1:N
                                              │
                                   ┌────────────────────────────┐
                                   │  commodity_orders          │
                                   ├────────────────────────────┤
                                   │ id (PK)                    │
                                   │ user_id (FK → users.id)    │
                                   │ type ENUM('buy','sell')    │
                                   │ commodity ENUM('gold','s') │
                                   │ grams DECIMAL(15,4)        │
                                   │ price_type ENUM('market','limit')
                                   │ limit_price DECIMAL(15,2)  │
                                   │ status ENUM(...)           │
                                   │ expires_at TIMESTAMP       │
                                   │ created_at TIMESTAMP       │
                                   └────────────────────────────┘

┌──────────────────────────────────────┐
│  commodity_prices                    │  ← مستقل (Standalone)
├──────────────────────────────────────┤
│ id (PK)                              │
│ commodity ENUM('gold','silver')      │
│ price_usd DECIMAL(15,2)              │
│ price_syp DECIMAL(15,2)              │
│ bid_usd DECIMAL(15,2)                │
│ ask_usd DECIMAL(15,2)                │
│ source VARCHAR(100)                  │
│ timestamp TIMESTAMP                  │
├──────────────────────────────────────┤
│ INDEX(commodity, timestamp)          │
└──────────────────────────────────────┘
```

## شرح العلاقات

| العلاقة | النوع | الشرح |
|---------|-------|-------|
| users → commodity_holdings | 1:N | المستخدم يمتلك عدة حيازات (لكن حيازة واحدة لكل سلعة) |
| users → commodity_transactions | 1:N | المستخدم لديه عدة معاملات شراء/بيع |
| users → commodity_orders | 1:N | المستخدم لديه عدة أوامر معلقة |
| commodity_prices | — | جدول مستقل، يُملأ من Price Feed |

## Constraints

| الجدول | الـ Constraint | الهدف |
|--------|---------------|-------|
| commodity_holdings | `UNIQUE(user_id, commodity)` | مستخدم واحد = صف واحد لكل سلعة (يتم تحديثه وليس إدراج جديد) |
| commodity_transactions | `reference_number UNIQUE` | منع ازدواجية المعاملات |
| commodity_prices | `INDEX(commodity, timestamp)` | تسريع استعلام آخر سعر لكل سلعة |

## Foreign Keys

| الجدول | FK | المرجع |
|--------|----|--------|
| commodity_holdings | user_id → users.id | CASCADE on DELETE |
| commodity_transactions | user_id → users.id | CASCADE on DELETE |
| commodity_orders | user_id → users.id | CASCADE on DELETE |

## ملاحظات تصميمية

1. **commodity_holdings** يستخدم `UNIQUE(user_id, commodity)` لأن كل مستخدم لديه حيازة واحدة لكل سلعة (يتم upsert عند كل شراء/بيع)
2. **commodity_prices** هو جدول سجلات (log) — كل دخول سعر يُضاف كسجل جديد مع timestamp للتدقيق والمراجعة
3. **commodity_orders** يدعم أوامر limit (أوامر معلقة تُنفذ عندما يصل السعر إلى حد معين)
