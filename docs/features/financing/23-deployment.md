# النشر والعمليات — Deployment & DevOps

## Architecture Overview
```
┌─────────────────────────────────────────────────────┐
│                   Kubernetes Cluster                │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌────────┐ │
│  │ Financing │ │ Scoring  │ │Repayment │ │Collection│ │
│  │ API      │ │ Service  │ │ Service  │ │ Service  │ │
│  │ (3 pods) │ │ (5 pods) │ │ (3 pods) │ │ (2 pods) │ │
│  └──────────┘ └──────────┘ └──────────┘ └────────┘ │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐            │
│  │ Redis    │ │ Kafka    │ │PostgreSQL│            │
│  │ (3 pods) │ │ (3 pods) │ │ (Primary │            │
│  │          │ │          │ │ + 2 RO) │            │
│  └──────────┘ └──────────┘ └──────────┘            │
└─────────────────────────────────────────────────────┘
```

## CI/CD Pipeline (GitHub Actions)

### Pipeline Stages
```yaml
name: Financing Service CI/CD

on:
  push:
    paths:
      - 'services/financing/**'
      - 'libs/financing-shared/**'

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - run: npm ci
      - run: npm run test:unit
      - run: npm run test:integration
      - run: npm run lint

  build:
    needs: test
    steps:
      - run: docker build -t beza/financing-service:${{ github.sha }}
      - run: docker push beza/financing-service:${{ github.sha }}
      - run: npm run build:contract-templates

  deploy-staging:
    needs: build
    environment: staging
    steps:
      - run: kubectl set image deployment/financing-api financing-api=beza/financing-service:${{ github.sha }}

  deploy-production:
    needs: deploy-staging
    environment: production
    steps:
      - run: kubectl set image deployment/financing-api financing-api=beza/financing-service:${{ github.sha }}
```

## Docker Images
```dockerfile
FROM node:20-alpine AS base

FROM base AS deps
WORKDIR /app
COPY package*.json ./
RUN npm ci --only=production

FROM base AS builder
WORKDIR /app
COPY . .
RUN npm ci && npm run build

FROM base AS runner
WORKDIR /app
ENV NODE_ENV=production
COPY --from=deps /app/node_modules ./node_modules
COPY --from=builder /app/dist ./dist
COPY --from=builder /app/templates ./templates

EXPOSE 3000
CMD ["node", "dist/main.js"]
```

## Environment Configuration
```yaml
environments:
  development:
    db_pool_size: 5
    scoring_concurrency: 2
    queue_concurrency: 2
    log_level: debug

  staging:
    db_pool_size: 20
    scoring_concurrency: 5
    queue_concurrency: 5
    log_level: info

  production:
    db_pool_size: 50
    scoring_concurrency: 10
    queue_concurrency: 15
    log_level: warn
    auto_scaling:
      financing_api:
        min_replicas: 3
        max_replicas: 20
        target_cpu: 70
      scoring_service:
        min_replicas: 5
        max_replicas: 30
        target_cpu: 60
```

## Database Migrations
```bash
# Migration naming convention:
# YYYYMMDD_HHMM_description.sql

# Run migrations
npm run migrate:up

# Rollback
npm run migrate:down

# Seed reference data (products, etc.)
npm run seed:financing-products
```

## Health Checks
| Endpoint | Purpose | Expected |
|----------|---------|----------|
| /health | Basic liveness | 200 OK |
| /health/ready | Readiness (DB, Redis, Kafka) | 200 with dependency status |
| /metrics | Prometheus metrics | 200 with metrics |
| /health/cache | Cache connectivity | 200 or 503 |
| /health/queue | Queue connectivity | 200 or 503 |

## Backup & Recovery
```yaml
backup:
  database:
    schedule: "0 2 * * *"  # Daily 02:00
    retention: 30 days
    type: pg_dump custom format
    storage: S3 (encrypted)
  
  contract_pdfs:
    schedule: "0 3 * * *"
    retention: 7 years (regulatory)
    storage: S3 (Glacier after 1 year)

recovery:
  rpo: 5 minutes (streaming replication)
  rto: 30 minutes (full recovery)
  test_schedule: quarterly
```

## Feature Flags (LaunchDarkly)
```yaml
flag_key: financing-enabled
  environments:
    development: true
    staging: true
    production: true

flag_key: financing-murabaha-enabled
  environments:
    development: true
    staging: true
    production: phased_rollout (50%)

flag_key: financing-micro-enabled
  environments:
    development: true
    staging: false
    production: false

flag_key: financing-ml-model-v2
  environments:
    development: true
    staging: true
    production: canary (10%)
```
