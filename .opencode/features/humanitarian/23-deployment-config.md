# Deployment Configuration

## Infrastructure (AWS — Syria Humanitarian Region)

| Component | Service | Configuration |
|-----------|---------|---------------|
| Compute | ECS Fargate (serverless) | 2 vCPU, 4GB RAM per task, auto-scaled |
| Database | RDS PostgreSQL 15 | db.r6g.large (multi-AZ), 500GB storage, auto-scaled |
| Cache | ElastiCache Redis 7 | cache.r6g.large cluster mode, 3 shards |
| Queue | Elasticache Redis (same cluster, dedicated DB) | DB 1 for queues, DB 2 for cache |
| File storage | S3 (Jordan region) | Beneficiary CSV uploads, donor reports |
| CDN | CloudFront | Static assets, generated reports |
| DNS | Route 53 | `humanitarian.beza.iq` |
| WAF | AWS WAF + Shield | Rate limiting, SQL injection, XSS |
| Monitoring | CloudWatch + Grafana | Custom dashboards, alerts |
| Tracing | AWS X-Ray | Distributed tracing |
| Secrets | AWS Secrets Manager | DB creds, API keys, encryption keys |

## Environment Matrix

| Environment | Region | Use | Scaling |
|-------------|--------|-----|---------|
| `dev` | eu-west-1 (Ireland) | Development, unit tests | 1 task, 1 reader DB |
| `staging` | eu-west-1 (Ireland) | Integration tests, UAT | 2 tasks, 2 reader DB |
| `production` | me-south-1 (Bahrain) | Live — Syria operations | Auto-scale (2-20 tasks) |
| `dr` | eu-central-1 (Frankfurt) | Disaster recovery | 0 tasks (warm standby) |

## Container Configuration

### Backend Service
```yaml
# docker-compose.humanitarian.yml (simplified)
version: '3.8'
services:
  humanitarian-api:
    image: beza/humanitarian-api:${VERSION}
    environment:
      - DB_HOST=${DB_HOST}
      - DB_NAME=beza_humanitarian
      - REDIS_URL=${REDIS_URL}
      - SANCTIONS_API_URL=${SANCTIONS_API_URL}
      - SANCTIONS_API_KEY=${SANCTIONS_API_KEY}
      - WALLET_SERVICE_URL=${WALLET_SERVICE_URL}
      - ENCRYPTION_KEY=${ENCRYPTION_KEY}
      - NODE_ENV=production
    secrets:
      - encryption_key
      - sanctions_api_key
    deploy:
      replicas: 4
      resources:
        limits:
          cpus: '2'
          memory: 4G

  humanitarian-worker:
    image: beza/humanitarian-worker:${VERSION}
    environment:
      - DB_HOST=${DB_HOST}
      - REDIS_URL=${REDIS_URL}
    # Same pattern, 2 replicas for queue processing
```

## Database Configuration

| Parameter | Value | Rationale |
|-----------|-------|-----------|
| Instance class | db.r6g.large | Memory-optimised for encrypted data |
| Storage | 500GB gp3 (auto-scaling enabled) | Initial scale — 500k beneficiaries |
| Read replicas | 2 (production) | Dashboard queries offloaded |
| Backup retention | 35 days | Donor compliance |
| Point-in-time recovery | Enabled | Funds tracking requires rollback capability |
| Encryption | AWS KMS (AES-256) | At-rest encryption |
| Parameter group | `humanitarian-params` | Customised: statement_timeout=30s, idle_in_transaction_session_timeout=60s |

## CI/CD Pipeline

```yaml
# .github/workflows/humanitarian.yml
name: Humanitarian CI/CD
on:
  push:
    paths: ['services/humanitarian/**']
  pull_request:
    paths: ['services/humanitarian/**']

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - run: npm ci
      - run: npm test -- --coverage
      - run: npm run lint
      - run: npm run typecheck
      - run: npm run test:integration

  build-and-deploy:
    needs: test
    if: github.ref == 'refs/heads/main'
    steps:
      - run: docker build -t beza/humanitarian-api:${{ github.sha }}
      - run: aws ecs update-service --cluster humanitarian --service api --force-new-deployment
```

## Feature-Specific Config (Environment Variables)

```bash
# .env.production
HUMANITARIAN_ENABLED=true
HUMANITARIAN_MPC_ENABLED=true
HUMANITARIAN_VOUCHER_ENABLED=true
HUMANITARIAN_BIOMETRIC_ENABLED=true
HUMANITARIAN_SPENDING_MONITORING_ENABLED=true

# Sanctions
SANCTIONS_UN_LIST_URL=https://scsanctions.un.org/resources/xml/en/consolidated.xml
SANCTIONS_EU_LIST_URL=https://webgate.ec.europa.eu/fsd/fsf/public/files/xmlFullSanctionsList_1_1/content
SANCTIONS_OFAC_SDN_URL=https://www.treasury.gov/ofac/downloads/sdn.xml
SANCTIONS_SCORE_THRESHOLD=80

# Wallet
WALLET_SERVICE_URL=https://api.beza.iq/v1/wallet
WALLET_SERVICE_API_KEY=${WALLET_SERVICE_API_KEY}

# SMS
SMS_PROVIDER=twilio
SMS_FROM_NUMBER=+963xxxxxxx  # Syrian local number
SMS_TEMPLATES_PATH=./templates/sms/ar/

# Encryption
ENCRYPTION_KEY_VERSION=1
ENCRYPTION_KEY_ROTATION_DAYS=90
