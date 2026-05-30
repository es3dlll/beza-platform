# Monetization Strategy — Fraud Management

## Executive Summary

Fraud prevention is primarily a cost-avoidance function, but it also serves as a **trust differentiator** that directly drives revenue growth. This document outlines how Beza's Fraud Management feature creates, protects, and captures value.

## Direct Value Protection

### 1. Fraud Loss Avoidance

The most direct financial impact is preventing fraud losses:

| Scenario | Monthly Volume | Fraud Rate | Monthly Loss Without Fraud Prev. | Monthly Loss With Fraud Prev. | Savings |
|----------|---------------|------------|----------------------------------|-------------------------------|---------|
| Y1 | 50M SYP | 0.8% | 400,000 SYP | 50,000 SYP | 350,000 SYP |
| Y2 | 500M SYP | 0.5% | 2.5M SYP | 250,000 SYP | 2.25M SYP |
| Y3 | 5B SYP | 0.3% | 15M SYP | 1.5M SYP | 13.5M SYP |

**Cumulative 3-year savings: ~16M SYP**

### 2. IFRS 9 Provision Reduction

Fraud losses are factored into Expected Credit Loss (ECL) provisions. Lower fraud losses = lower provisions = better P&L.

| Year | Provision Without Fraud Prev. | Provision With Fraud Prev. | Reduction |
|------|------------------------------|----------------------------|-----------|
| Y1 | 1.2M SYP | 0.3M SYP | 0.9M SYP |
| Y2 | 6M SYP | 1M SYP | 5M SYP |
| Y3 | 30M SYP | 5M SYP | 25M SYP |

## Indirect Revenue Drivers

### 3. Trust-Driven Transaction Volume

Fraud prevention builds user trust, which directly increases transaction volume:

| Trust Impact | Mechanism | Estimated Uplift |
|-------------|-----------|-----------------|
| New user acquisition | "Safest wallet in Syria" positioning | 15–25% higher conversion |
| Existing user frequency | Users transact more when they feel safe | 10–20% more transactions/user |
| Average transaction size | Users keep higher balances | 5–15% higher average txn |
| Agent network growth | Agents prefer low-dispute platform | 20–30% faster agent acquisition |

### 4. Reduced Churn

Fraud-related user churn is a significant cost:

| Churn Type | Annual Churn Rate | Cost Per Churned User | Annual Cost |
|------------|-------------------|----------------------|-------------|
| Fraud victim churn | 30% of victims leave within 30 days | 1,500 SYP (LTV) | 45M SYP per 100K victims |
| False positive churn | 5% of FP users leave within 7 days | 1,500 SYP (LTV) | 7.5M SYP per 100K false positives |

**With fraud prevention reducing both:**
- Fraud victims: 70% fewer → save 31.5M SYP per 100K potential victims
- False positives: < 3% → negligible churn

### 5. Fraud Insurance Product

A potentially significant revenue stream:

| Product | Description | Target Premium | Target Market |
|---------|-------------|---------------|---------------|
| Transaction Shield | User pays 0.5% per txn for fraud guarantee | 0.5% of txn value | High-value users |
| Agent Bond | Insurance against agent float theft | 1% of agent float | Agent network |
| Remittance Protection | Fee-based remittance fraud guarantee | 1% of remittance amount | Diaspora senders |

**Revenue Projection:**

| Product | Y1 | Y2 | Y3 |
|---------|-----|-----|-----|
| Transaction Shield | 25K SYP | 500K SYP | 5M SYP |
| Agent Bond | 50K SYP | 1M SYP | 10M SYP |
| Remittance Protection | 100K SYP | 2M SYP | 20M SYP |
| **Total** | **175K SYP** | **3.5M SYP** | **35M SYP** |

### 6. B2B Fraud API (Future)

Offer fraud screening as a service to other Syrian PSPs and banks:

| Service | Pricing | Target |
|---------|---------|--------|
| Transaction screening API | 5 SYP/screening | Other PSPs |
| Device fingerprinting | 2 SYP/fingerprint | Banks, PSPs |
| Agent fraud monitoring | Subscription (50K SYP/month) | Banks with agent networks |

**Revenue Projection:**

| Y1 | Y2 | Y3 |
|-----|-----|-----|
| Pilot (no revenue) | 500K SYP | 5M SYP |

## Total Monetization Impact

| Revenue Stream | Y1 | Y2 | Y3 |
|---------------|-----|-----|-----|
| Fraud loss saved | 350K SYP | 2.25M SYP | 13.5M SYP |
| Provision reduction | 900K SYP | 5M SYP | 25M SYP |
| Trust-driven volume uplift | 500K SYP | 5M SYP | 50M SYP |
| Churn reduction | 1M SYP | 10M SYP | 100M SYP |
| Insurance products | 175K SYP | 3.5M SYP | 35M SYP |
| B2B API | 0 | 500K SYP | 5M SYP |
| **Total** | **2.93M SYP** | **26.25M SYP** | **228.5M SYP** |

## Cost of Fraud Prevention

| Cost | Y1 | Y2 | Y3 |
|------|-----|-----|-----|
| Engineering (3 FTE) | 60M SYP | 60M SYP | 60M SYP |
| ML infrastructure | 15M SYP | 25M SYP | 35M SYP |
| Operations team (5 FTE) | 30M SYP | 40M SYP | 50M SYP |
| Telecom/Syriatel/MTN | 10M SYP | 15M SYP | 20M SYP |
| **Total** | **115M SYP** | **140M SYP** | **165M SYP** |

## Net Monetization

| Year | Total Benefit | Total Cost | Net Benefit | ROI |
|------|---------------|------------|-------------|-----|
| Y1 | 2.93M SYP | 115M SYP | (112M SYP) | 0.03x |
| Y2 | 26.25M SYP | 140M SYP | (113.75M SYP) | 0.19x |
| Y3 | 228.5M SYP | 165M SYP | 63.5M SYP | 1.38x |
| **3-year cumulative** | **257.68M SYP** | **420M SYP** | **(162.32M SYP)** | 0.61x |

**Note:** ROI becomes positive in Y3 as transaction volume scales. The fraud prevention investment is a growth enabler — without it, transaction volume growth would be limited by trust and fraud losses.

## Key Assumptions

| Assumption | Value | Sensitivity |
|------------|-------|-------------|
| Transaction volume growth | 10x YoY | High — if growth slower, ROI shifts right |
| Fraud rate (without prevention) | 0.8% | High — if Syrian market improves, fraud may be lower |
| User LTV | 1,500 SYP | Medium — depends on fee structure |
| Insurance adoption | 5% of users | Medium — depends on marketing |
| B2B API launch | Y3 | Low — optional revenue stream |

## Pricing Strategy for Insurance Products

| Tier | Coverage | Fee | Target Users |
|------|----------|-----|--------------|
| Basic | Up to 50K SYP fraud protection | 0.25% per txn | Mass market |
| Standard | Up to 250K SYP fraud protection | 0.5% per txn | Mid-market |
| Premium | Up to 1M SYP fraud protection | 1% per txn | High-value + diaspora |

## Go-to-Market for Monetization

1. **Phase 1 (Y1):** Focus on fraud loss prevention (cost savings). No monetization to end users.
2. **Phase 2 (Y2):** Launch Transaction Shield as opt-in insurance. Market as "Beza Security Guarantee."
3. **Phase 3 (Y3):** Expand insurance products. Explore B2B API. Fraud prevention is a profit center.
