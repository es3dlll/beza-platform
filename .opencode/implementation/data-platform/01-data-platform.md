# Data Platform Engineering Spec

## Architecture
```
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│ Application  │───>│  PostgreSQL  │───>│  Debezium    │
│ (Laravel)    │    │  (10+ ms)    │    │  (CDC)       │
└──────────────┘    └──────────────┘    └──────┬───────┘
                                              │
                    ┌─────────────────────────┐
                    │                         │
            ┌───────▼───────┐         ┌───────▼───────┐
            │   RabbitMQ    │         │   Kafka       │
            │  (Events)     │         │  (CDC Streams)│
            └───────┬───────┘         └───────┬───────┘
                    │                         │
            ┌───────▼───────┐         ┌───────▼───────┐
            │  ClickHouse   │         │  Elasticsearch│
            │  (Analytics)  │         │  (Search)     │
            └───────┬───────┘         └───────────────┘
                    │
            ┌───────▼───────┐
            │    S3 / GCS   │
            │  (Data Lake)  │
            └───────────────┘
```

## ClickHouse Schema

### Transaction Analytics
```sql
CREATE TABLE analytics.wallet_transactions (
    event_date Date,
    event_time DateTime,
    tenant_id UInt32,
    transaction_id String,
    user_id UInt32,
    type LowCardinality(String),
    currency LowCardinality(String),
    amount Int64,
    fee Int64,
    status LowCardinality(String),
    device_id String,
    agent_id Nullable(UInt32),
    merchant_id Nullable(UInt32),
    country FixedString(2),
    city String,
    sign Int8  -- 1 for debit, -1 for credit
) ENGINE = ReplacingMergeTree
PARTITION BY toYYYYMM(event_date)
ORDER BY (event_date, tenant_id, type, user_id);
```

### User Behavior
```sql
CREATE TABLE analytics.user_sessions (
    event_date Date,
    session_id String,
    user_id UInt32,
    device_id String,
    app_version String,
    os String,
    screen_views Nested(
        screen String,
        duration_sec UInt32,
        timestamp DateTime
    ),
    started_at DateTime,
    ended_at DateTime,
    duration_sec UInt32,
    crash Bool
) ENGINE = MergeTree
PARTITION BY toYYYYMM(event_date)
ORDER BY (event_date, user_id);
```

## ETL Pipelines

### Real-time (Streaming)
```
Source: RabbitMQ (events) → ClickHouse (Materialized View)
Consumer: Golang or Laravel worker
Frequency: Continuous (< 100ms latency)
Transformation: JSON payload → ClickHouse columns
```

### Batch (Daily)
```
Source: PostgreSQL → S3 (Parquet) → ClickHouse
Tool: Spark or dbt
Frequency: Daily at 02:00
Tables: Wallet balances (snapshot), User profiles, Agent performance
```

### Search Indexing
```
Source: PostgreSQL CDC (Debezium) → Kafka → Elasticsearch
Sync: Near real-time (< 5s delay)
Indexes:
  - users: name, phone, email, national_id (fuzzy search)
  - transactions: reference, user_id, amount, date, status
  - agents: name, shop_name, location (geo search)
```

## Reports (dbt)
```yaml
# dbt_project.yml
models:
  analytics:
    daily_summary:
      +materialized: table
      +partition_by: event_date
      sql: |
        SELECT
            event_date,
            tenant_id,
            type,
            currency,
            COUNT(*) as transaction_count,
            SUM(amount) as total_amount,
            SUM(fee) as total_fees,
            COUNTIf(status = 'failed') as failed_count,
            AVG(amount) as avg_amount
        FROM analytics.wallet_transactions
        GROUP BY event_date, tenant_id, type, currency

    user_retention:
      +materialized: table
      sql: |
        SELECT
            cohort_date,
            day_1_retention,
            day_7_retention,
            day_30_retention,
            day_90_retention
        FROM analytics.retention_calculated
```
