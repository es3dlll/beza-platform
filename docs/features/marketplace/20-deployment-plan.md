# Deployment Plan

## Release Phases

### Phase 1 — MVP (v1.0, Week 12)
**Scope**:
- Mobile top-up (Syriatel + MTN)
- Digital goods (game credits, streaming codes)
- Wallet payment with hold/release
- Basic vendor onboarding (invite-only, 10 vendors)

**Target Users**: 500 internal beta testers + 1,000 early adopters

**Deployment**:
- Feature flag: `marketplace.enabled` (default: false for all users)
- Graduated rollout: 1% → 5% → 25% → 50% → 100% over 2 weeks
- Telecom integration in sandbox mode first → production after 1 week

### Phase 2 — Gift Cards (v1.1, Week 16)
**Scope**:
- Gift card purchase and send (5 merchants)
- WhatsApp/SMS delivery
- QR code generation
- Merchant redemption portal

### Phase 3 — Bill Payment (v1.2, Week 20)
**Scope**:
- Utility bill payment
- Rewards integration
- Auto top-up feature
- Saved recipients

### Phase 4 — Marketplace Maturity (v1.3, Week 24)
**Scope**:
- Open vendor registration
- Physical goods support
- Bulk gift card (enterprise)
- Advanced analytics
- Promo engine v2

## Rollout Strategy

### Canary Deployment (Kubernetes)
```yaml
# Initial canary: 5% traffic
apiVersion: flagger.app/v1beta1
kind: Canary
metadata:
  name: marketplace-service
spec:
  targetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: marketplace-service
  service:
    port: 3000
  analysis:
    interval: 5m
    threshold: 10
    maxWeight: 50
    stepWeight: 5
    metrics:
      - name: request-success-rate
        threshold: 99
        interval: 1m
      - name: request-duration
        threshold: 500
        interval: 1m
```

### Rollback Criteria
| Metric | Threshold | Action |
|---|---|---|
| API error rate | > 1% (p99) | Auto-rollback |
| Top-up success rate | < 99% | Auto-rollback |
| P95 response time | > 2s | Auto-rollback |
| Order creation failure | > 5% | Manual rollback |
| Telecom integration errors | > 3% | Manual rollback |

## Infrastructure Requirements

| Component | Spec | Quantity |
|---|---|---|
| API server (Node.js) | 2 vCPU, 4GB RAM | 3 pods (min) / 10 (max) |
| PostgreSQL | 4 vCPU, 16GB RAM, 200GB SSD | 1 primary + 1 replica |
| Redis | 2 vCPU, 4GB RAM | 1 cluster (3 nodes) |
| Object storage | - | CDN-enabled bucket |
| Telecom API proxy | 1 vCPU, 2GB RAM | 2 pods (HA) |

## Database Migrations

- Zero-downtime migrations using Sqitch
- Backward-compatible schema changes only
- Rollback scripts required for every migration
- Migrations run as pre-deployment job in CI/CD pipeline
