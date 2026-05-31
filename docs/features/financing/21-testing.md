# استراتيجية الاختبار — Testing Strategy

## Test Pyramid
- **Unit (60%)**: Jest/Vitest for services, models, utilities
- **Integration (15%)**: Supertest for API contracts, MSW for mocks
- **E2E (5%)**: Cypress for web, Detox for mobile
- **Manual QA (20%)**: Exploratory, UAT, Sharia compliance review

## Unit Testing

### Service Tests
| Module | Key Test Cases |
|--------|----------------|
| ApplicationService | Submit valid/invalid, duplicate prevention, state transitions, offer expiry |
| CreditScoringService | Score ranges, edge cases (no history), factor breakdown, model version fallback |
| DisbursementService | Wallet credit, merchant payment, split disbursement, failure recovery, idempotency |
| RepaymentService | Auto-deduct success/failure, retry logic (3 retries), partial payment allocation, early repayment |
| CollectionService | Escalation timing, restructure eligibility, default declaration, priority queue ordering |
| ShariaComplianceLayer | Contract generation correctness, profit disclosure validation, charity accounting accuracy |

### Scoring Model Validation
```typescript
describe('CreditScoringService', () => {
  it('calculates score within valid range (300-850)', async () => {
    const result = await scoringService.calculateScore(userId, 'qard_hasan', 300000);
    expect(result.score).toBeGreaterThanOrEqual(300);
    expect(result.score).toBeLessThanOrEqual(850);
  });

  it('rejects thin credit files (score < 550)', async () => {
    const result = await scoringService.calculateScore(userId, 'qard_hasan', 100000);
    expect(result.score).toBeLessThan(550);
  });

  it('generates profit rate between 5-12% for Murabaha', async () => {
    const result = await scoringService.generateOfferParams(userId, 680, 'murabaha', 2000000);
    expect(result.profitRate).toBeGreaterThanOrEqual(5);
    expect(result.profitRate).toBeLessThanOrEqual(12);
  });
});
```

## Integration Tests

### API Contract Tests
```typescript
describe('POST /financing/apply', () => {
  it('returns 201 for valid Qard Hasan application', async () => {
    const res = await request(app)
      .post('/v1/financing/apply')
      .set('Authorization', `Bearer ${validToken}`)
      .send({
        product_type: 'qard_hasan', amount: 300000,
        term_days: 90, purpose: 'medical',
        consent: { credit_check: true, auto_deduct: true, sharia_terms: true }
      });
    expect(res.status).toBe(201);
    expect(res.body.application_id).toBeDefined();
  });

  it('returns 403 when max active loans reached', async () => {
    const res = await request(app)
      .post('/v1/financing/apply')
      .set('Authorization', `Bearer ${validToken}`)
      .send({ product_type: 'qard_hasan', amount: 300000, term_days: 90, purpose: 'medical' });
    expect(res.status).toBe(403);
    expect(res.body.code).toBe('MAX_ACTIVE_LOANS');
  });
});
```

## E2E Tests
```typescript
describe('Financing Full Flow', () => {
  it('completes Qard Hasan lifecycle from application to repayment', async () => {
    // 1. Submit application
    // 2. Verify underwriting status
    // 3. Wait for approval notification
    // 4. Accept offer and e-sign
    // 5. Verify disbursement in wallet
    // 6. Verify repayment schedule generated
    // 7. Process auto-deduction
    // 8. Verify payment receipt
    // 9. Verify loan completion
  });
});
```

## Performance Tests
| Scenario | Target | Load |
|----------|--------|------|
| Concurrent applications | < 3s avg | 500 concurrent |
| Scoring engine | < 5s p95 | 100 req/s |
| Auto-deduction batch | < 60s for 10K users | 10K transactions |
| Portfolio dashboard | < 3s | 50 parallel queries |

## Sharia Compliance Testing
| Test | Method | Frequency |
|------|--------|-----------|
| Contract template review | Manual review by Sharia board | Quarterly |
| Profit disclosure accuracy | Automated cross-check cost vs sale price | Every Murabaha contract |
| Charity fee segregation | Audit of charity liability account vs general income | Monthly |
| Late fee calculation | Automated test: confirm fees go to charity account, not P&L | Every transaction |
| No compounding check | Automated: verify profit calculated on principal only | Every installment |
