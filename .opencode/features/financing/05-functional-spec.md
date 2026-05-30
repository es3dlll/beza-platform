# المواصفات الوظيفية التفصيلية — Detailed Functional Specification

## 1. Application Workflow

### States
```
DRAFT → SUBMITTED → UNDERWRITING → APPROVED → OFFER_ACCEPTED → DISBURSED → ACTIVE → COMPLETED
                        ↓                                             ↓
                    REJECTED                                      DEFAULTED
                                                                     ↓
                                                              RESTRUCTURED → ACTIVE
```

### State Transitions
| From | To | Trigger | Actor |
|------|----|---------|-------|
| DRAFT | SUBMITTED | User clicks "Submit" | User |
| SUBMITTED | UNDERWRITING | Auto-score calculated | System |
| UNDERWRITING | APPROVED | Score ≥ threshold OR manual approval | System/Admin |
| UNDERWRITING | REJECTED | Score < threshold AND manual reject | System/Admin |
| APPROVED | OFFER_ACCEPTED | User accepts offer + e-signs | User |
| APPROVED | REJECTED | Offer expires (7 days) | System |
| OFFER_ACCEPTED | DISBURSED | Funds transferred | System |
| DISBURSED | ACTIVE | Confirmation received | System |
| ACTIVE | COMPLETED | All payments made | System |
| ACTIVE | DEFAULTED | 90 days past due | System |
| DEFAULTED | RESTRUCTURED | New agreement signed | Admin + User |

## 2. Scoring Engine Rules

### Data Features (80+ total)
| Category | Features | Weight |
|----------|----------|--------|
| Transaction Behavior | Frequency, avg amount, merchant diversity, cash-in/cash-out ratio | 25% |
| Savings History | Avg daily balance, savings deposits, savings account linked | 20% |
| Bill Payment | Utility bill payment regularity, payment method | 15% |
| Agent Network | Number of agent interactions, agent ratings, agent complaint count | 10% |
| KYC & Identity | KYC level, account age, ID verification status | 10% |
| Existing Products | Wallet usage, Savings balance, Bill pay history, Card usage | 10% |
| External (future) | Syria Credit Bureau data, employer verification | 10% |

### Decision Matrix
| Score Range | Qard Hasan | Murabaha | Micro-Enterprise |
|-------------|------------|----------|------------------|
| 750–850 | Auto-approve up to SYP 500K | Auto-approve up to SYP 3M | Auto-approve up to SYP 5M |
| 650–749 | Auto-approve up to SYP 300K | Auto-approve up to SYP 1.5M | Manual review required |
| 550–649 | Guarantor required | Manual review, higher profit | Reject |
| 300–549 | Reject | Reject | Reject |
| < 300 | Reject | Reject | Reject |

## 3. Offer Generation Algorithm

### Inputs
- Credit score
- Requested amount
- Product type
- Term selected
- Existing relationship (wallet age, existing products)

### Output
- Approved amount (may differ from requested)
- Profit rate (Murabaha: 5–12%, Micro: 7–15%)
- Term adjustment (may be shorter/longer than requested)
- Down payment requirement (Murabaha only)

### Formula (Murabaha Profit Rate)
```
Base rate = 5%
Score adjustment = MAX(0, (750 - score) × 0.02)
Term adjustment = (term_days / 365) × 2%
Risk premium = wallet_age < 6 months ? 2% : 0%
Final rate = MIN(MAX(base + score_adj + term_adj + risk_premium, 5%), 12%)
```

## 4. Repayment Schedule Generation

### Equal Installment Method (Murabaha, Micro)
```
Total = Principal + Profit
Installment = Total / Number of installments
Each installment: principal_part + profit_part (proportional)
Profit allocation = profit_part declines over time
```

### Daily Deduction (Qard Hasan)
```
Daily amount = Principal / Term days
First deduction: Day 8 after disbursement (7-day grace)
Deduction time: 08:00 AM wallet time
```

## 5. Late Fee Calculation
```
Daily late fee = 
  Qard Hasan: SYP 5,000
  Murabaha: SYP 10,000  
  Micro: SYP 15,000
Grace period: 3 days (Qard), 7 days (Murabaha/Micro)
Fee starts: Grace_end_date + 1
Fee accrues daily until paid or default
Fee destination: Charity liability account
```

## 6. Restructuring Rules
- Available after 1 missed payment (before default)
- Max 2 restructurings per contract
- Restructuring options:
  a. Extend term (up to 50% additional)
  b. Reduce installment amount (principal only, profit unchanged)
  c. Payment holiday (1–3 months, term extended accordingly)
- Restructuring fee: SYP 25,000 (one-time, covers admin cost)
