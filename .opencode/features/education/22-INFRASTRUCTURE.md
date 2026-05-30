# 22 — Infrastructure

## 22.1 Hosting Architecture

```
┌─ User ─────────────────────┐
│  Parent App (Flutter + CDN) │
│  School Dashboard (Web)     │
└────────┬───────────────────┘
         │ Cloudflare (WAF · CDN · DNS)
         ▼
┌───────────────────────────────────────────────┐
│           Load Balancer (HAProxy)             │
│             Multi-AZ (Damascus DC1+DC2)        │
└────────┬──────────────┬──────────────┬────────┘
         ▼              ▼              ▼
┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│ API Servers  │ │ API Servers  │ │ API Servers  │
│ (Container)  │ │ (Container)  │ │ (Container)  │
│ Cluster 1    │ │ Cluster 2    │ │ Cluster 3    │
└──────┬───────┘ └──────┬───────┘ └──────┬───────┘
       │                │                │
       └────────────────┼────────────────┘
                        │
         ┌──────────────┼──────────────┐
         ▼              ▼              ▼
┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│ PostgreSQL   │ │ Redis        │ │ Kafka        │
│ Primary+Repl.│ │ Cluster      │ │ Cluster      │
│ (2 AZs)      │ │ (3 nodes)    │ │ (3 brokers)  │
└──────────────┘ └──────────────┘ └──────────────┘
         │                            │
         ▼                            ▼
┌──────────────────┐       ┌──────────────────┐
│ MinIO / S3       │       │ Elasticsearch    │
│ (Receipts, CSVs) │       │ (Search layer)   │
└──────────────────┘       └──────────────────┘
```

## 22.2 Compute Requirements

| Service | vCPU | RAM | Instances | Storage |
|---|---|---|---|---|
| Education API | 4 | 8 GB | 3-6 (auto-scale) | 50 GB SSD each |
| Payment Service | 4 | 8 GB | 2-4 | 50 GB SSD |
| Notification Service | 2 | 4 GB | 2-4 | 20 GB SSD |
| Kafka Broker | 8 | 16 GB | 3 | 200 GB SSD each |
| PostgreSQL Primary | 16 | 64 GB | 1 | 500 GB SSD |
| PostgreSQL Replica | 16 | 64 GB | 2 | 500 GB SSD |
| Redis | 4 | 16 GB | 3 | 100 GB SSD |

## 22.3 Scaling Strategy

| Trigger | Action |
|---|---|
| API CPU > 70% for 5 min | +2 API instances (max 12) |
| Payment queue > 1000 pending | +2 Payment instances |
| Kafka consumer lag > 10,000 | Scale consumer group |
| DB connections > 80% | Add read replica |
| Storage > 75% | Auto-increase volume size (via infra-as-code) |

## 22.4 Disaster Recovery

| Scenario | RTO | RPO | Strategy |
|---|---|---|---|
| Single AZ failure | 5 min | 0 (real-time) | Active-active multi-AZ |
| PostgreSQL primary failure | 2 min | < 5 sec | Auto-failover to replica |
| Full region failure | 4 hours | 15 min | Cross-region (Beirut DR) |
| Data corruption | 1 hour | 24 hours | Point-in-time recovery (WAL archives) |
| Kafka cluster failure | 10 min | 0 | Min replication factor 3 |

## 22.5 Network

- All inter-service communication over private VPC (10.x.x.x)
- Public endpoints via Cloudflare (DDoS protection + WAF)
- Syrian traffic routed through local ISP peering for low latency
- Database access restricted to service IPs only — no public DB endpoints
- Bastion host for SSH access (Cloudflare Tunnel — no open inbound ports)

## 22.6 CI/CD Pipeline

```
Developer → GitHub (push)
               │
               ▼
          GitHub Actions
               │
          ├── Lint + Type Check
          ├── Unit Tests
          ├── Integration Tests
          ├── Build container image
          ├── Push to registry
          ├── Deploy to staging
          ├── E2E tests on staging
          └── Deploy to production (manual approval gate)
```

## 22.7 Syrian Infrastructure Considerations

- Primary data centre in Damascus (Syriatel Telecom or similar)
- Secondary in Latakia (for geographic diversity)
- Internet connectivity: 4G backup for payment service if primary link fails
- Power: UPS + diesel generator at both data centres (rolling blackouts common)
- Bandwidth: minimum 1 Gbps symmetric at each DC
- Cooling: ambient 45°C summer peak must be within cooling capacity
