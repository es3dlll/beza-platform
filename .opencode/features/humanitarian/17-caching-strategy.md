# Caching Strategy

## Cache Layers

| Layer | Technology | TTL | Usage |
|-------|-----------|-----|-------|
| Browser cache (NGO dashboard) | Next.js ISR + SWR | 5 min | Program lists, beneficiary tables |
| Agent app offline | IndexedDB + Zustand persist | Until sync | Beneficiary records, verification queue |
| Redis (shared) | Redis Cluster 6.x | Variable | API response cache, rate limiting, sessions |
| CDN (donor reports) | CloudFront / Cloudflare | 1 hour | Generated PDF/CSV reports |

## Cache Invalidation Events

| Event | Invalidated Cache |
|-------|-------------------|
| Program created/updated | Program list, program detail |
| Beneficiary uploaded | Beneficiary list, program detail |
| Distribution triggered | Program budget, distribution list |
| Distribution completed | Distribution list, spending dashboard |
| Verification completed | Beneficiary detail |
| Voucher redeemed | Voucher detail, spending dashboard |
| Report generated | Donor report list |

## Redis Cache Keys

```
humanitarian:program:{id}              → AidProgram (TTL: 5 min)
humanitarian:program:{id}:stats        → {beneficiary_count, budget_used, ...} (TTL: 2 min)
humanitarian:beneficiary:{id}          → AidBeneficiary (TTL: 10 min)
humanitarian:beneficiaries:program:{id}:page:{n} → Beneficiary[] (TTL: 2 min)
humanitarian:distributions:program:{id} → Distribution[] (TTL: 1 min)
humanitarian:spending:{program_id}:{from}:{to} → SpendingAggregate (TTL: 5 min)
humanitarian:report:{ngo_id}:{from}:{to} → ReportData (TTL: 30 min)
```

## Cache-Aside Pattern

```typescript
async function getProgramWithCache(id: string): Promise<AidProgram> {
  const cacheKey = `humanitarian:program:${id}`;
  
  // Try cache
  const cached = await redis.get(cacheKey);
  if (cached) return JSON.parse(cached);
  
  // Miss — load from DB
  const program = await db.programs.findByPk(id, {
    include: [/* ... */],
  });
  
  // Write cache
  await redis.setEx(cacheKey, 300, JSON.stringify(program));
  
  return program;
}
```

## Offline Cache Strategy (Agent App)

| Data | Storage | Size Limit | Sync Strategy |
|------|---------|------------|---------------|
| Beneficiary lookup (by phone hash) | IndexedDB | 1000 most recent | LRU eviction |
| Verification queue | IndexedDB | Unlimited (user prompt if >500) | Batch sync every 30s when online |
| Agent profile | IndexedDB | 1 record | On login |
| Program assignments | IndexedDB | 10 programs | On login |

## Rate Limiting (API)

| Endpoint | Rate Limit | Burst |
|----------|------------|-------|
| POST /distribute | 10 per minute per NGO | 20 |
| POST /beneficiaries (CSV upload) | 5 per hour per NGO | 10 |
| GET /reports/donor | 30 per hour per user | 50 |
| POST /vouchers/redeem | 10 per second per merchant | 20 |
| GET /spending | 60 per minute per user | 100 |
