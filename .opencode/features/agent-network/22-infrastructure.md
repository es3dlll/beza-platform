# Agent Network Infrastructure

## Deployment Architecture

### Service Topology
```
┌─────────────────────────────────────────────────────────────────┐
│                          AWS / On-Prem                          │
│                                                                 │
│  ┌─────────────────────┐    ┌──────────────────────────────┐    │
│  │   Load Balancer     │    │   API Gateway (Kong)          │    │
│  │   (HAProxy/NLB)     │    │   Rate limiting, auth, SSL    │    │
│  └─────────┬───────────┘    └──────────────┬───────────────┘    │
│            │                               │                     │
│  ┌─────────▼───────────────────────────────▼───────────────┐    │
│  │              agent-pos-api Service                       │    │
│  │              (Laravel Octane, 5 replicas)                │    │
│  │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐  │    │
│  │  │ Pod 1    │ │ Pod 2    │ │ Pod 3    │ │ Pod 4+5  │  │    │
│  │  │ (AZ-a)   │ │ (AZ-a)   │ │ (AZ-b)   │ │ (AZ-b)   │  │    │
│  │  └──────────┘ └──────────┘ └──────────┘ └──────────┘  │    │
│  └────────────────────────┬───────────────────────────────┘    │
│                           │                                     │
│  ┌────────────────────────▼───────────────────────────────┐    │
│  │             Offline Sync Service                        │    │
│  │             (Node.js, 2 replicas)                       │    │
│  │             WebSocket + REST for sync                   │    │
│  └────────────────────────┬───────────────────────────────┘    │
│                           │                                     │
│  ┌────────────────────────▼───────────────────────────────┐    │
│  │  Database Layer                                        │    │
│  │  ┌────────────────┐  ┌────────────────────────────┐   │    │
│  │  │ MySQL Primary   │  │ MySQL Read Replica x2      │   │    │
│  │  │ (agents DB)     │  │ (queries, reports, export) │   │    │
│  │  └────────────────┘  └────────────────────────────┘   │    │
│  │  ┌────────────────┐  ┌────────────────────────────┐   │    │
│  │  │ Redis Cache     │  │ Redis Queue (horizon)      │   │    │
│  │  │ (float cache)   │  │ (commission settlement)   │   │    │
│  │  └────────────────┘  └────────────────────────────┘   │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  Monitoring & Observability                         │    │
│  │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────┐  │    │
│  │  │Prometheus│ │Grafana   │ │Loki      │ │Alert-│  │    │
│  │  │          │ │Dashboards│ │(logs)    │ │manager│  │    │
│  │  └──────────┘ └──────────┘ └──────────┘ └──────┘  │    │
│  └─────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
```

### Agent POS App (Client Side)
```
Component: Flutter Android POS App
Min SDK: Android 8.0 (API 26)
Target: Android 13+ (API 33)

Device Spec (Minimum):
  - CPU: Quad-core 1.8GHz
  - RAM: 3GB
  - Storage: 32GB
  - Screen: 8" tablet (1280x800) or 5.5" phone
  - Bluetooth: 4.0+ (for printer)
  - Connectivity: WiFi + 4G LTE
  - Biometric: Fingerprint sensor
  - Battery: 5000mAh (full day operation)

Recommended Device: Samsung Galaxy Tab A9 (SM-X110) or similar
Peripheral: Bluetooth thermal printer (58mm, e.g., Bixolon SRP-275III)
```

## Scaling for 10,000+ Agents

### Database Scaling
```
Current Load Estimate (10,000 agents):
  - Avg transactions per agent per day: 50
  - Total transactions per day: 500,000
  - Peak transactions per hour: 75,000 (1,250/min, 20/sec)
  - Float queries per second: 500
  - Commission calculations per day: 500,000

Scaling Strategy:
  1. MySQL Read Replicas: 2 replicas for query-heavy operations (history, reports)
  2. Redis Cache:
     - Agent float balance (TTL: 60s, invalidated on write)
     - Agent tier limits (TTL: 1h)
     - Customer verification codes (TTL: 5min)
  3. Partition agent_transactions by month (as in wallet schema)
  4. Shard agents table by city/region (optional, only if >50K agents)

Spatial Index Optimization:
  - Use MySQL SPATIAL index on agents.location
  - Agent locator queries: SELECT *, ST_Distance_Sphere(location, POINT(?, ?)) as dist
    FROM agents WHERE status='active' AND dist < 1000 ORDER BY dist LIMIT 20
  - Spatial index also on agent_transactions.location for regional analytics
```

### Sync Service Scaling
```
Offline Sync Service:
  - Technology: Node.js with Socket.io for real-time sync
  - Replicas: 2 minimum, scale to 5 at peak
  - Each replica handles 500 concurrent WebSocket connections
  - Total capacity: 2,500 concurrent sync sessions

Sync Protocol:
  - Batch sync every 5 minutes or on connectivity restore
  - Max batch size: 50 transactions
  - Idempotency key ensures no duplicates
  - Retry 3 times, then move to dead letter queue

WebSocket Events:
  - agent:connect — agent comes online
  - agent:sync — batch sync request
  - agent:float_alert — push low float alert
  - agent:commission_update — push commission earned
  - agent:announcement — push Beza announcement
```

### API Rate Limiting
```
Endpoint-Specific Limits (per agent):
  POST /cash-in:        10 requests/minute (safety margin)
  POST /cash-out:       10 requests/minute
  GET /float:           30 requests/minute
  GET /transactions:    20 requests/minute
  POST /sync:           2 requests/minute (batch sync)

Global Limits:
  Per IP:               1,000 requests/minute
  Burst:                2,000 requests/10 seconds
  Concurrent per agent: 5 connections
```

## POS Device Management

### Device Lifecycle
```
1. Procurement:
   - Bulk order from Samsung/lenovo (500+ units)
   - Pre-install Beza POS app
   - Configure MDM (Mobile Device Management) — use ManageEngine or Hexnode
   - Lock device to kiosk mode (single app mode)

2. Activation:
   - Device serial registered in agent_devices table
   - X.509 certificate generated and installed
   - Device bound to agent during activation

3. Monitoring:
   - Heartbeat every 5 minutes (last_seen_at updated)
   - Collect: battery level, storage, app version, OS version
   - Alert if device offline > 4 hours

4. Replacement:
   - Lost/stolen: remote wipe via MDM, deactivate certificate
   - Damaged: ship replacement within 24h, data restored from cloud
   - Upgrade: Gold+ agents get new device every 18 months

5. Decommission:
   - Wipe device remotely
   - Revoke certificate
   - Mark device_status = 'decommissioned'
   - Recycle or refurbish
```

### Infrastructure Cost Estimate (Monthly, 10K Agents)
| Item | Cost (USD) | Notes |
|------|-----------|-------|
| agent-pos-api (5 pods × 2GB RAM) | $2,000 | ECS/EKS |
| Sync service (2 pods × 1GB) | $500 | Node.js |
| MySQL Primary (db.r6g.large) | $300 | RDS |
| MySQL Read Replica ×2 | $400 | RDS |
| Redis (cache.r6g.large) | $250 | ElastiCache |
| Redis Queue | $150 | ElastiCache |
| Load Balancer | $200 | NLB |
| Object Storage (receipts) | $300 | S3-compatible |
| CDN (app updates, static) | $100 | CloudFront |
| SMS costs (5M/month) | $5,000 | Twilio-like |
| MDM licensing | $1,000 | Per-device fee |
| **Total** | **$10,200** | |
