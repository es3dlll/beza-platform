# 04 - علاقات قاعدة البيانات (Database Relationships)

## مخطط ER (ER Diagram)
```
┌──────────────────┐        ┌─────────────────────────────────────┐
│    merchants      │------->│        merchant_products            │
│──────────────────│ 1     M│─────────────────────────────────────│
│ id               │        │ PK id                               │
└──────────────────┘        │ FK merchant_id                      │
                            │ name                                │
                            │ price_syp / price_usd               │
                            │ category                            │
                            │ stock (nullable)                    │
                            │ is_active                           │
                            └────────────────┬────────────────────┘
                                             │ 1
                                             │ hasMany
                                             ▼
                                   ┌────────────────────┐
                                   │   product_images    │
                                   │────────────────────│
                                   │ PK id              │
                                   │ FK product_id      │
                                   │ image_path         │
                                   │ is_primary         │
                                   └────────────────────┘
```

## العلاقات (Relationships)
- **merchants → merchant_products**: واحد لمتعدد. التاجر يملك عدة منتجات.
- **merchant_products → product_images**: واحد لمتعدد. كل منتج له صور متعددة، مع صورة أساسية واحدة.

## المخطط التفصيلي (Detailed Schema)
```sql
-- merchant_products table
merchant_id BIGINT UNSIGNED NOT NULL,
name VARCHAR(255) NOT NULL,
description TEXT NULL,
price_syp DECIMAL(15,2) NOT NULL,
price_usd DECIMAL(15,2) NOT NULL,
category VARCHAR(100) NULL,
stock INT UNSIGNED NULL,
is_active BOOLEAN DEFAULT TRUE,
FOREIGN KEY (merchant_id) REFERENCES merchants(id) ON DELETE CASCADE,
INDEX idx_merchant_active (merchant_id, is_active)
```

ملاحظة: unique constraint على (merchant_id + name) يمنع إضافة منتج مكرر لنفس التاجر.
