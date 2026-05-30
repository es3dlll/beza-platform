# Analytics & Reporting

## Reports

### 1. Marketplace Overview Dashboard
| Metric | Description | Update Frequency |
|---|---|---|
| GMV (Gross Merchandise Volume) | Total value of all orders | Real-time |
| Total orders | Count of all orders placed | Real-time |
| Active buyers | Unique users who placed >= 1 order | Daily |
| Average order value (AOV) | GMV / number of orders | Real-time |
| Commission earned | Total platform commission | Real-time |
| Vendor count | Active vendors | Real-time |
| Top-up volume | Total top-up amount | Real-time |
| Gift cards sold | Count and value | Real-time |

### 2. Product Performance Reports
- Top 100 products by revenue (daily/weekly/monthly)
- Products by category performance
- Low-stock alerts for top-selling products
- Price elasticity analysis
- Conversion rate by product

### 3. Vendor Analytics
- Per-vendor: sales, commissions, payouts, ratings
- Vendor rank by category
- Vendor acquisition funnel (applied -> approved -> active -> first sale)
- Vendor churn rate (no sales in 60 days)
- Average vendor response time

### 4. Financial Reports
| Report | Frequency | Audience |
|---|---|---|
| Commission statement | Weekly | Vendors (self-serve) |
| Payout summary | Per payout | Vendors |
| Platform revenue report | Monthly | Finance |
| Settlement report | Weekly | Telecom partners |
| Tax withholding report | Monthly | Finance |
| Breakage revenue report | Monthly | Finance |

### 5. Operational Reports
- Fulfillment SLA compliance (by vendor)
- Top-up success rate (by network, by hour)
- Refund rate and reasons
- Dispute resolution time
- Support ticket volume by category
- Fraud detection alerts

## Data Pipeline

```
Marketplace Services
    │
    ├── Event Stream (Kafka)
    │   ├── order.created
    │   ├── order.fulfilled
    │   ├── topup.completed
    │   ├── giftcard.purchased
    │   └── commission.recorded
    │
    ├── Real-time (Redis + WebSocket)
    │   └── Vendor dashboard live counters
    │
    └── Batch (Airflow daily)
        ├── ETL -> Data Warehouse (ClickHouse)
        ├── Rollup tables (hourly/daily/monthly)
        └── Report generation
```

## Event Schema (Kafka)

```json
{
  "eventType": "order.created",
  "timestamp": "2026-05-29T10:30:00Z",
  "data": {
    "orderId": "MKT-2026-05190",
    "userId": "usr_123",
    "vendorId": "ven_789",
    "totalAmount": 47700,
    "itemCount": 3,
    "currency": "SYP",
    "categoryId": "cat_digital_games"
  }
}
```

## Self-Serve Analytics (Vendors)

Vendors can access via dashboard:
- Sales graph (daily/weekly/monthly)
- Top 10 products
- Conversion rate (views -> orders)
- Customer geography (city-level)
- Hourly order pattern
- Device breakdown (iOS/Android)
