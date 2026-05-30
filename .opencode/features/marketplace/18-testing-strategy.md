# Testing Strategy

## Test Levels

### Unit Tests
| Component | Coverage Target | Framework |
|---|---|---|
| ProductCatalogService | 95% | Jest (Node) |
| OrderService | 95% | Jest (Node) |
| CommissionService | 95% | Jest (Node) |
| VoucherService | 95% | Jest (Node) |
| Telecom providers | 90% | Jest (Node) + mocks |

### Integration Tests
| Scenario | Description |
|---|---|
| Top-up end-to-end with mock Syriatel API | Verify request format, response parsing, idempotency |
| Order creation → wallet hold → fulfillment → release | Full order lifecycle |
| Gift card purchase → generate → send → redeem | Full gift card flow |
| Commission calculation with tiered rates | Verify correct tier applied |
| Promo code validation and discount application | Edge cases: expired, max usage, category-specific |

### API Contract Tests
- Pact-based contract testing between Marketplace and Wallet service
- Consumer-driven contracts for telecom provider APIs
- Webhook signature verification tests

### Load Tests
| Scenario | Target | Tool |
|---|---|---|
| Product catalog browse (read-heavy) | 1,000 concurrent users, < 2s p95 | k6 |
| Top-up concurrent requests | 100 concurrent, < 10s p99 | k6 |
| Order creation + payment hold | 50 concurrent, < 5s p95 | k6 |
| Gift card generation | 200 cards/min | k6 |

### UAT Test Cases (Critical)
1. Syriatel top-up: valid number, 10,000 SYP → success within 10s
2. Syriatel top-up: invalid number → 400 error, no wallet deduction
3. MTN data bundle purchase → plan activated, confirmation received
4. Digital goods: purchase → code displayed in-app + sent via SMS
5. Gift card: purchase → send via WhatsApp → recipient opens → redeem at merchant
6. Order cancellation before fulfillment → wallet hold released
7. Insufficient wallet balance → clear error, no order created
8. Vendor commission: digital goods → 12% deducted, visible in vendor dashboard
9. Multiple items in cart → correct total with discounts
10. Promo code "WELCOME10" → 10% discount applied
