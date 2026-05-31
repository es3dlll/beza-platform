# Database Schema — Marketplace

## Entity Relationship Summary

```
marketplace_vendors
    │
    ├──< marketplace_products
    │       │
    │       ├──< marketplace_product_images
    │       ├──< marketplace_product_reviews
    │       │
    │       └──< marketplace_order_items
    │               │
    │               ├──< marketplace_topups
    │               └──< marketplace_gift_cards
    │
    ├──< marketplace_orders
    │       │
    │       └──< marketplace_order_items
    │
    └──< marketplace_commissions

marketplace_categories
    │
    └──< marketplace_products

marketplace_gift_cards
    │
    └──< marketplace_merchants
```

---

## Table: marketplace_categories

```sql
CREATE TABLE marketplace_categories (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    parent_id       UUID REFERENCES marketplace_categories(id),
    name_ar         VARCHAR(100) NOT NULL,
    name_en         VARCHAR(100) NOT NULL,
    slug            VARCHAR(120) NOT NULL UNIQUE,
    description_ar  TEXT,
    description_en  TEXT,
    icon_url        VARCHAR(500),
    sort_order      INTEGER NOT NULL DEFAULT 0,
    commission_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    is_active       BOOLEAN NOT NULL DEFAULT true,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_categories_parent ON marketplace_categories(parent_id);
CREATE INDEX idx_categories_slug ON marketplace_categories(slug);
CREATE INDEX idx_categories_active ON marketplace_categories(is_active) WHERE is_active = true;
```

---

## Table: marketplace_vendors

```sql
CREATE TABLE marketplace_vendors (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id             UUID NOT NULL REFERENCES users(id) UNIQUE,
    business_name       VARCHAR(200) NOT NULL,
    business_name_ar    VARCHAR(200),
    slug                VARCHAR(120) NOT NULL UNIQUE,
    description         TEXT,
    description_ar      TEXT,
    phone               VARCHAR(20) NOT NULL,
    email               VARCHAR(255) NOT NULL,
    website             VARCHAR(500),
    logo_url            VARCHAR(500),
    cover_url           VARCHAR(500),
    category_id         UUID REFERENCES marketplace_categories(id),
    commission_tier     VARCHAR(20) NOT NULL DEFAULT 'standard'
                        CHECK (commission_tier IN ('standard', 'premium', 'enterprise')),
    status              VARCHAR(20) NOT NULL DEFAULT 'pending'
                        CHECK (status IN ('pending', 'active', 'suspended', 'rejected')),
    verification_status VARCHAR(20) NOT NULL DEFAULT 'unverified'
                        CHECK (verification_status IN ('unverified', 'verified', 'rejected')),
    business_license_url VARCHAR(500),
    tax_id              VARCHAR(50),
    payout_method       VARCHAR(20) NOT NULL DEFAULT 'wallet'
                        CHECK (payout_method IN ('wallet', 'bank', 'both')),
    bank_account_name   VARCHAR(200),
    bank_name           VARCHAR(100),
    bank_iban           VARCHAR(50),
    monthly_volume      DECIMAL(15,2) NOT NULL DEFAULT 0,
    total_sales         INTEGER NOT NULL DEFAULT 0,
    avg_rating          DECIMAL(2,1) NOT NULL DEFAULT 0.0,
    response_time       VARCHAR(50),
    is_featured         BOOLEAN NOT NULL DEFAULT false,
    rejected_reason     TEXT,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_vendors_status ON marketplace_vendors(status);
CREATE INDEX idx_vendors_category ON marketplace_vendors(category_id);
CREATE INDEX idx_vendors_featured ON marketplace_vendors(is_featured) WHERE is_featured = true;
CREATE INDEX idx_vendors_slug ON marketplace_vendors(slug);
```

---

## Table: marketplace_products

```sql
CREATE TABLE marketplace_products (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    vendor_id           UUID NOT NULL REFERENCES marketplace_vendors(id),
    category_id         UUID NOT NULL REFERENCES marketplace_categories(id),
    title_ar            VARCHAR(200) NOT NULL,
    title_en            VARCHAR(200),
    slug                VARCHAR(200) NOT NULL UNIQUE,
    description_ar      TEXT,
    description_en      TEXT,
    terms_ar            TEXT,
    terms_en            TEXT,
    price               DECIMAL(15,2) NOT NULL CHECK (price > 0),
    compare_at_price    DECIMAL(15,2),
    currency            VARCHAR(3) NOT NULL DEFAULT 'SYP',
    stock               INTEGER NOT NULL DEFAULT 0 CHECK (stock >= 0),
    low_stock_threshold INTEGER NOT NULL DEFAULT 10,
    delivery_type       VARCHAR(20) NOT NULL
                        CHECK (delivery_type IN (
                            'INSTANT_CODE', 'VENDOR_API', 'MANUAL', 'PHYSICAL'
                        )),
    fulfillment_config  JSONB,
    attributes          JSONB,
    is_active           BOOLEAN NOT NULL DEFAULT false,
    moderation_status   VARCHAR(20) NOT NULL DEFAULT 'pending'
                        CHECK (moderation_status IN ('pending', 'approved', 'rejected')),
    moderated_by        UUID REFERENCES users(id),
    moderated_at        TIMESTAMPTZ,
    rejection_reason    TEXT,
    total_sales         INTEGER NOT NULL DEFAULT 0,
    avg_rating          DECIMAL(2,1) NOT NULL DEFAULT 0.0,
    review_count        INTEGER NOT NULL DEFAULT 0,
    is_featured         BOOLEAN NOT NULL DEFAULT false,
    tags_ar             TEXT[],
    tags_en             TEXT[],
    search_vector       TSVECTOR,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_products_vendor ON marketplace_products(vendor_id);
CREATE INDEX idx_products_category ON marketplace_products(category_id);
CREATE INDEX idx_products_active ON marketplace_products(is_active, moderation_status)
    WHERE is_active = true AND moderation_status = 'approved';
CREATE INDEX idx_products_price ON marketplace_products(price);
CREATE INDEX idx_products_rating ON marketplace_products(avg_rating DESC);
CREATE INDEX idx_products_sales ON marketplace_products(total_sales DESC);
CREATE INDEX idx_products_slug ON marketplace_products(slug);
CREATE INDEX idx_products_search ON marketplace_products USING GIN(search_vector);
CREATE INDEX idx_products_tags_ar ON marketplace_products USING GIN(tags_ar);
CREATE INDEX idx_products_tags_en ON marketplace_products USING GIN(tags_en);
```

---

## Table: marketplace_product_images

```sql
CREATE TABLE marketplace_product_images (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    product_id  UUID NOT NULL REFERENCES marketplace_products(id) ON DELETE CASCADE,
    url         VARCHAR(500) NOT NULL,
    sort_order  INTEGER NOT NULL DEFAULT 0,
    is_primary  BOOLEAN NOT NULL DEFAULT false,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_product_images_product ON marketplace_product_images(product_id);
```

---

## Table: marketplace_orders

```sql
CREATE TABLE marketplace_orders (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_number    VARCHAR(20) NOT NULL UNIQUE,
    user_id         UUID NOT NULL REFERENCES users(id),
    vendor_id       UUID NOT NULL REFERENCES marketplace_vendors(id),
    status          VARCHAR(20) NOT NULL DEFAULT 'pending'
                    CHECK (status IN (
                        'pending', 'hold_placed', 'processing',
                        'fulfilled', 'completed', 'cancelled',
                        'refund_pending', 'refunded'
                    )),
    subtotal        DECIMAL(15,2) NOT NULL CHECK (subtotal >= 0),
    discount_amount DECIMAL(15,2) NOT NULL DEFAULT 0 CHECK (discount_amount >= 0),
    promo_code      VARCHAR(50),
    commission_total DECIMAL(15,2) NOT NULL DEFAULT 0,
    total           DECIMAL(15,2) NOT NULL CHECK (total >= 0),
    currency        VARCHAR(3) NOT NULL DEFAULT 'SYP',
    wallet_hold_id  VARCHAR(100),
    notes           TEXT,
    cancel_reason   TEXT,
    refund_amount   DECIMAL(15,2),
    cancelled_at    TIMESTAMPTZ,
    completed_at    TIMESTAMPTZ,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_orders_user ON marketplace_orders(user_id);
CREATE INDEX idx_orders_vendor ON marketplace_orders(vendor_id);
CREATE INDEX idx_orders_status ON marketplace_orders(status);
CREATE INDEX idx_orders_number ON marketplace_orders(order_number);
CREATE INDEX idx_orders_created ON marketplace_orders(created_at DESC);
CREATE INDEX idx_orders_user_status ON marketplace_orders(user_id, status);
```

---

## Table: marketplace_order_items

```sql
CREATE TABLE marketplace_order_items (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id            UUID NOT NULL REFERENCES marketplace_orders(id) ON DELETE CASCADE,
    product_id          UUID NOT NULL REFERENCES marketplace_products(id),
    vendor_id           UUID NOT NULL REFERENCES marketplace_vendors(id),
    title_ar            VARCHAR(200) NOT NULL,
    title_en            VARCHAR(200),
    quantity            INTEGER NOT NULL CHECK (quantity > 0),
    unit_price          DECIMAL(15,2) NOT NULL,
    total_price         DECIMAL(15,2) NOT NULL,
    commission_amount   DECIMAL(15,2) NOT NULL DEFAULT 0,
    commission_rate     DECIMAL(5,2) NOT NULL DEFAULT 0,
    delivery_type       VARCHAR(20) NOT NULL,
    fulfillment_status  VARCHAR(20) NOT NULL DEFAULT 'pending'
                        CHECK (fulfillment_status IN (
                            'pending', 'processing', 'delivered',
                            'failed', 'refunded'
                        )),
    fulfillment_data    JSONB,
    fulfilled_at        TIMESTAMPTZ,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_order_items_order ON marketplace_order_items(order_id);
CREATE INDEX idx_order_items_product ON marketplace_order_items(product_id);
CREATE INDEX idx_order_items_vendor ON marketplace_order_items(vendor_id);
CREATE INDEX idx_order_items_fulfillment ON marketplace_order_items(fulfillment_status);
```

---

## Table: marketplace_topups

```sql
CREATE TABLE marketplace_topups (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_item_id       UUID NOT NULL REFERENCES marketplace_order_items(id),
    user_id             UUID NOT NULL REFERENCES users(id),
    phone_number        VARCHAR(15) NOT NULL,
    network             VARCHAR(10) NOT NULL CHECK (network IN ('syriatel', 'mtn')),
    amount              DECIMAL(15,2) NOT NULL CHECK (amount > 0),
    fee                 DECIMAL(15,2) NOT NULL DEFAULT 0,
    total_charged       DECIMAL(15,2) NOT NULL,
    idempotency_key     VARCHAR(100) NOT NULL UNIQUE,
    provider_request_id VARCHAR(100),
    provider_response   JSONB,
    status              VARCHAR(20) NOT NULL DEFAULT 'pending'
                        CHECK (status IN (
                            'pending', 'processing', 'completed',
                            'failed', 'refunded'
                        )),
    failure_reason      TEXT,
    retry_count         INTEGER NOT NULL DEFAULT 0,
    completed_at        TIMESTAMPTZ,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_topups_user ON marketplace_topups(user_id);
CREATE INDEX idx_topups_phone ON marketplace_topups(phone_number);
CREATE INDEX idx_topups_network ON marketplace_topups(network);
CREATE INDEX idx_topups_status ON marketplace_topups(status);
CREATE INDEX idx_topups_idempotency ON marketplace_topups(idempotency_key);
CREATE INDEX idx_topups_created ON marketplace_topups(created_at DESC);
```

---

## Table: marketplace_gift_cards

```sql
CREATE TABLE marketplace_gift_cards (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_item_id       UUID REFERENCES marketplace_order_items(id),
    merchant_id         UUID NOT NULL REFERENCES marketplace_merchants(id),
    code                VARCHAR(20) NOT NULL UNIQUE,
    qr_code_url         VARCHAR(500),
    initial_balance     DECIMAL(15,2) NOT NULL CHECK (initial_balance > 0),
    remaining_balance   DECIMAL(15,2) NOT NULL CHECK (remaining_balance >= 0),
    currency            VARCHAR(3) NOT NULL DEFAULT 'SYP',
    purchaser_id        UUID NOT NULL REFERENCES users(id),
    recipient_name      VARCHAR(100),
    recipient_phone     VARCHAR(20),
    recipient_email     VARCHAR(255),
    personal_message    TEXT,
    status              VARCHAR(20) NOT NULL DEFAULT 'active'
                        CHECK (status IN (
                            'active', 'partially_redeemed', 'redeemed',
                            'expired', 'cancelled', 'refunded'
                        )),
    delivery_method     VARCHAR(20) NOT NULL
                        CHECK (delivery_method IN (
                            'whatsapp', 'sms', 'email', 'in_app', 'pdf'
                        )),
    delivery_status     VARCHAR(20) NOT NULL DEFAULT 'pending'
                        CHECK (delivery_status IN (
                            'pending', 'sent', 'delivered', 'failed'
                        )),
    expires_at          TIMESTAMPTZ NOT NULL,
    redeemed_at         TIMESTAMPTZ,
    cancelled_at        TIMESTAMPTZ,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_gift_cards_code ON marketplace_gift_cards(code);
CREATE INDEX idx_gift_cards_purchaser ON marketplace_gift_cards(purchaser_id);
CREATE INDEX idx_gift_cards_recipient ON marketplace_gift_cards(recipient_phone);
CREATE INDEX idx_gift_cards_merchant ON marketplace_gift_cards(merchant_id);
CREATE INDEX idx_gift_cards_status ON marketplace_gift_cards(status);
CREATE INDEX idx_gift_cards_expiry ON marketplace_gift_cards(expires_at)
    WHERE status IN ('active', 'partially_redeemed');
```

---

## Table: marketplace_merchants

```sql
CREATE TABLE marketplace_merchants (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name_ar         VARCHAR(200) NOT NULL,
    name_en         VARCHAR(200),
    slug            VARCHAR(120) NOT NULL UNIQUE,
    description_ar  TEXT,
    description_en  TEXT,
    logo_url        VARCHAR(500),
    cover_url       VARCHAR(500),
    website         VARCHAR(500),
    category        VARCHAR(50) NOT NULL
                    CHECK (category IN ('retail', 'food', 'electronics', 'fashion', 'entertainment', 'services', 'other')),
    denominations   JSONB NOT NULL DEFAULT '[]',
    allows_custom_denomination BOOLEAN NOT NULL DEFAULT false,
    min_denomination DECIMAL(15,2),
    max_denomination DECIMAL(15,2),
    commission_rate DECIMAL(5,2) NOT NULL DEFAULT 8.00,
    redemption_type VARCHAR(20) NOT NULL DEFAULT 'online'
                    CHECK (redemption_type IN ('online', 'in_store', 'both')),
    is_active       BOOLEAN NOT NULL DEFAULT true,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_merchants_active ON marketplace_merchants(is_active) WHERE is_active = true;
CREATE INDEX idx_merchants_slug ON marketplace_merchants(slug);
CREATE INDEX idx_merchants_category ON marketplace_merchants(category);
```

---

## Table: marketplace_commissions

```sql
CREATE TABLE marketplace_commissions (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_item_id   UUID NOT NULL REFERENCES marketplace_order_items(id),
    vendor_id       UUID NOT NULL REFERENCES marketplace_vendors(id),
    product_id      UUID NOT NULL REFERENCES marketplace_products(id),
    order_id        UUID NOT NULL REFERENCES marketplace_orders(id),
    category_id     UUID NOT NULL REFERENCES marketplace_categories(id),
    amount          DECIMAL(15,2) NOT NULL CHECK (amount >= 0),
    rate            DECIMAL(5,2) NOT NULL,
    tier            VARCHAR(20) NOT NULL
                    CHECK (tier IN ('standard', 'premium', 'enterprise')),
    status          VARCHAR(20) NOT NULL DEFAULT 'accrued'
                    CHECK (status IN ('accrued', 'settled', 'paid', 'reversed')),
    settled_at      TIMESTAMPTZ,
    paid_at         TIMESTAMPTZ,
    payout_id       UUID,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_commissions_vendor ON marketplace_commissions(vendor_id);
CREATE INDEX idx_commissions_order ON marketplace_commissions(order_id);
CREATE INDEX idx_commissions_status ON marketplace_commissions(status);
CREATE INDEX idx_commissions_payout ON marketplace_commissions(payout_id)
    WHERE payout_id IS NOT NULL;
CREATE INDEX idx_commissions_created ON marketplace_commissions(created_at DESC);
```

---

## Table: marketplace_vendor_payouts

```sql
CREATE TABLE marketplace_vendor_payouts (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    vendor_id       UUID NOT NULL REFERENCES marketplace_vendors(id),
    amount          DECIMAL(15,2) NOT NULL CHECK (amount > 0),
    fee             DECIMAL(15,2) NOT NULL DEFAULT 0,
    net_amount      DECIMAL(15,2) NOT NULL CHECK (net_amount > 0),
    payout_method   VARCHAR(20) NOT NULL,
                    CHECK (payout_method IN ('wallet', 'bank')),
    status          VARCHAR(20) NOT NULL DEFAULT 'pending'
                    CHECK (status IN ('pending', 'processing', 'completed', 'failed')),
    bank_reference  VARCHAR(100),
    failure_reason  TEXT,
    requested_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    processed_at    TIMESTAMPTZ,
    completed_at    TIMESTAMPTZ
);

CREATE INDEX idx_payouts_vendor ON marketplace_vendor_payouts(vendor_id);
CREATE INDEX idx_payouts_status ON marketplace_vendor_payouts(status);
```

---

## Table: marketplace_promo_codes

```sql
CREATE TABLE marketplace_promo_codes (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    code            VARCHAR(50) NOT NULL UNIQUE,
    description_ar  TEXT,
    description_en  TEXT,
    discount_type   VARCHAR(10) NOT NULL CHECK (discount_type IN ('percentage', 'fixed')),
    discount_value  DECIMAL(15,2) NOT NULL CHECK (discount_value > 0),
    min_order_amount DECIMAL(15,2),
    max_discount    DECIMAL(15,2),
    usage_limit     INTEGER,
    usage_count     INTEGER NOT NULL DEFAULT 0,
    per_user_limit  INTEGER NOT NULL DEFAULT 1,
    applies_to      VARCHAR(20) NOT NULL DEFAULT 'all'
                    CHECK (applies_to IN ('all', 'category', 'vendor', 'product')),
    applies_to_id   UUID,
    starts_at       TIMESTAMPTZ NOT NULL,
    expires_at      TIMESTAMPTZ NOT NULL,
    is_active       BOOLEAN NOT NULL DEFAULT true,
    created_by      UUID NOT NULL REFERENCES users(id),
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_promo_code ON marketplace_promo_codes(code);
CREATE INDEX idx_promo_active ON marketplace_promo_codes(is_active, starts_at, expires_at)
    WHERE is_active = true;
```

---

## Table: marketplace_product_reviews

```sql
CREATE TABLE marketplace_product_reviews (
    id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    product_id  UUID NOT NULL REFERENCES marketplace_products(id),
    order_id    UUID NOT NULL REFERENCES marketplace_orders(id),
    user_id     UUID NOT NULL REFERENCES users(id),
    rating      INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment     TEXT,
    is_verified BOOLEAN NOT NULL DEFAULT true,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_reviews_product ON marketplace_product_reviews(product_id);
CREATE INDEX idx_reviews_user ON marketplace_product_reviews(user_id);
CREATE UNIQUE INDEX idx_reviews_unique ON marketplace_product_reviews(product_id, order_id, user_id);
```

---

## Table: marketplace_saved_recipients

```sql
CREATE TABLE marketplace_saved_recipients (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id         UUID NOT NULL REFERENCES users(id),
    phone_number    VARCHAR(15) NOT NULL,
    network         VARCHAR(10) NOT NULL CHECK (network IN ('syriatel', 'mtn')),
    name            VARCHAR(100) NOT NULL,
    is_favorite     BOOLEAN NOT NULL DEFAULT false,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_saved_recipients_user ON marketplace_saved_recipients(user_id);
CREATE UNIQUE INDEX idx_saved_recipients_unique ON marketplace_saved_recipients(user_id, phone_number);
```

---

## Full-Text Search Index (PostgreSQL)

```sql
-- Trigger to update search_vector on product insert/update
CREATE OR REPLACE FUNCTION marketplace_products_search_update()
RETURNS TRIGGER AS $$
BEGIN
    NEW.search_vector :=
        setweight(to_tsvector('arabic', COALESCE(NEW.title_ar, '')), 'A') ||
        setweight(to_tsvector('english', COALESCE(NEW.title_en, '')), 'A') ||
        setweight(to_tsvector('arabic', COALESCE(NEW.description_ar, '')), 'B') ||
        setweight(to_tsvector('english', COALESCE(NEW.description_en, '')), 'B') ||
        setweight(to_tsvector('simple', COALESCE(array_to_string(NEW.tags_ar, ' '), '')), 'C') ||
        setweight(to_tsvector('simple', COALESCE(array_to_string(NEW.tags_en, ' '), '')), 'C');
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_products_search
    BEFORE INSERT OR UPDATE ON marketplace_products
    FOR EACH ROW
    EXECUTE FUNCTION marketplace_products_search_update();
```
