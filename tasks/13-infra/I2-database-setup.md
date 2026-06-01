# I2: إعداد قاعدة البيانات الأولية

**المعرف:** `I2-database-setup`  
**الوحدة:** ⚙️ بنية تحتية  
**الأولوية:** 🔴 P0 — حرجة  

---

## الهدف

إنشاء قاعدة البيانات الأولية مع الجداول الأساسية للمنصة.

## الجداول الأساسية

### 1. users

```sql
CREATE TABLE users (
    id              BIGSERIAL PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    email           VARCHAR(255) UNIQUE NOT NULL,
    phone           VARCHAR(15) UNIQUE NOT NULL,
    password        VARCHAR(255) NOT NULL,
    kyc_level       SMALLINT DEFAULT 0,       -- 0=غير موثق, 1=بسيط, 2=كامل
    status          VARCHAR(20) DEFAULT 'active',
    locale          VARCHAR(5) DEFAULT 'ar',   -- ar / en
    email_verified_at TIMESTAMP,
    phone_verified_at  TIMESTAMP,
    created_at      TIMESTAMP DEFAULT NOW(),
    updated_at      TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_phone ON users(phone);
CREATE INDEX idx_users_status ON users(status);
```

### 2. wallets

```sql
CREATE TABLE wallets (
    id          BIGSERIAL PRIMARY KEY,
    user_id     BIGINT NOT NULL REFERENCES users(id),
    currency    CHAR(3) NOT NULL,              -- SYP, USD
    balance     BIGINT NOT NULL DEFAULT 0,     -- بالفلس (bigint)
    blocked     BIGINT NOT NULL DEFAULT 0,     -- رصيد مجمد
    status      VARCHAR(20) DEFAULT 'active',
    created_at  TIMESTAMP DEFAULT NOW(),
    updated_at  TIMESTAMP DEFAULT NOW(),
    UNIQUE(user_id, currency)
);

CREATE INDEX idx_wallets_user ON wallets(user_id);
CREATE INDEX idx_wallets_currency ON wallets(currency);
```

### 3. currencies

```sql
CREATE TABLE currencies (
    code        CHAR(3) PRIMARY KEY,           -- SYP, USD, EUR
    name_ar     VARCHAR(100) NOT NULL,
    name_en     VARCHAR(100) NOT NULL,
    symbol      VARCHAR(5) NOT NULL,
    decimals    SMALLINT DEFAULT 4,
    is_active   BOOLEAN DEFAULT true
);
```

### 4. transactions (دفتر الأستاذ — WORM)

```sql
CREATE TABLE transactions (
    id              BIGSERIAL PRIMARY KEY,
    uuid            UUID UNIQUE DEFAULT gen_random_uuid(),
    type            VARCHAR(30) NOT NULL,      -- transfer, deposit, withdrawal
    status          VARCHAR(20) NOT NULL,       -- pending, completed, failed, reversed
    from_wallet_id  BIGINT REFERENCES wallets(id),
    to_wallet_id    BIGINT REFERENCES wallets(id),
    amount          BIGINT NOT NULL,            -- بالفلس
    fee             BIGINT NOT NULL DEFAULT 0,
    currency        CHAR(3) NOT NULL,
    description     TEXT,
    reference_type  VARCHAR(50),               -- order_id, invoice_id, ...
    reference_id    BIGINT,
    created_at      TIMESTAMP DEFAULT NOW(),
    -- WORM: لا UPDATE, لا DELETE
);

CREATE INDEX idx_transactions_uuid ON transactions(uuid);
CREATE INDEX idx_transactions_from ON transactions(from_wallet_id);
CREATE INDEX idx_transactions_to ON transactions(to_wallet_id);
CREATE INDEX idx_transactions_created ON transactions(created_at);
```

## قاعدة بيانات التطوير

```sql
CREATE DATABASE beza_platform;
CREATE DATABASE beza_platform_testing;
```

## سياسة WORM

- جميع جداول المعاملات المالية **ممنوع منها UPDATE أو DELETE**
- أي خطأ يُعالج بـ **معاملة عكسية (Reversal)** وليس تعديل
- السجلات المالية تحتوي على `uuid` فريد للمرجعية

## معايير القبول

- [ ] `users` + `wallets` + `currencies` + `transactions` منشأة
- [ ] العلاقات والفهارس صحيحة
- [ ] `wallets.balance` و `transactions.amount` من نوع BIGINT (فلس)
- [ ] UNIQUE(user_id, currency) في wallets
- [ ] UUID تلقائي لكل معاملة
- [ ] WORM Policy موثقة (لا UPDATE/DELETE على المعاملات)
