# المتطلبات الوظيفية — Functional Requirements

## FR1: Application Management
| ID | Requirement | Priority | Product |
|----|-------------|----------|---------|
| FR1.1 | User can submit a financing application specifying product, amount, purpose, term | P0 | All |
| FR1.2 | System validates eligibility (age, wallet age, KYC status) before submission | P0 | All |
| FR1.3 | User can upload supporting documents (ID, proof of income, invoices) | P0 | Murabaha, Micro |
| FR1.4 | User can invite guarantor(s) via Beza contact list or phone number | P0 | Qard Hasan |
| FR1.5 | Guarantor receives notification and can accept/reject with e-signature | P0 | Qard Hasan |
| FR1.6 | User can track application status in real-time | P0 | All |
| FR1.7 | User can cancel application while in draft/submitted status | P1 | All |
| FR1.8 | Admin can view all applications with filters and bulk actions | P0 | All |

## FR2: Credit Scoring
| ID | Requirement | Priority | Product |
|----|-------------|----------|---------|
| FR2.1 | System calculates credit score from wallet transaction history | P0 | All |
| FR2.2 | Score factors include: transaction frequency, average balance, savings patterns, agent interaction count, bill payment history | P0 | All |
| FR2.3 | Score ranges: 300–850 (VantageScore-inspired) | P0 | All |
| FR2.4 | ML model retrained monthly with new repayment data | P0 | All |
| FR2.5 | Score interpretation shown to user with improvement tips | P1 | All |
| FR2.6 | External bureau data (if available) integrated via Syria Credit Bureau | P2 | Micro |

## FR3: Approval & Underwriting
| ID | Requirement | Priority | Product |
|----|-------------|----------|---------|
| FR3.1 | Automated decision engine for Qard Hasan (score + guarantor = instant) | P0 | All |
| FR3.2 | Manual underwriter review for amounts > SYP 3,000,000 | P0 | Micro |
| FR3.3 | System generates optimal offer (amount, profit rate, term) based on risk profile | P0 | All |
| FR3.4 | User can accept or counter-offer (system re-evaluates) | P1 | Murabaha, Micro |
| FR3.5 | Approved offer expires after 7 days | P0 | All |

## FR4: Disbursement
| ID | Requirement | Priority | Product |
|----|-------------|----------|---------|
| FR4.1 | Qard Hasan: funds disbursed to user's Beza wallet | P0 | Qard Hasan |
| FR4.2 | Murabaha: funds disbursed to merchant/supplier (not user) | P0 | Murabaha |
| FR4.3 | Micro-Enterprise: funds to supplier or wallet (based on purpose) | P0 | Micro |
| FR4.4 | Disbursement via Beza CFE (Core Financial Engine) | P0 | All |
| FR4.5 | SMS + push notification on disbursement | P0 | All |
| FR4.6 | Disbursement can be split across multiple suppliers | P1 | Murabaha, Micro |

## FR5: Repayment
| ID | Requirement | Priority | Product |
|----|-------------|----------|---------|
| FR5.1 | Auto-deduction from wallet on scheduled due dates | P0 | All |
| FR5.2 | User can make manual early repayment anytime | P0 | All |
| FR5.3 | User can view full repayment schedule with breakdown (principal vs. profit) | P0 | All |
| FR5.4 | Partial payment allowed (applied to principal + profit proportionally) | P1 | All |
| FR5.5 | Auto-retry on insufficient balance (3 retries over 48 hours) | P0 | All |
| FR5.6 | SMS/WhatsApp reminder 3 days before due date | P0 | All |
| FR5.7 | Receipt generated for each payment | P0 | All |

## FR6: Late Payment & Collection
| ID | Requirement | Priority | Product |
|----|-------------|----------|---------|
| FR6.1 | Late fee calculated per day and moved to charity account | P0 | All |
| FR6.2 | Grace period: 3 days before late fee starts (Qard Hasan), 7 days (others) | P0 | All |
| FR6.3 | Automated collection sequence: SMS → call → email → field visit | P0 | All |
| FR6.4 | Restructuring option available before default | P0 | All |
| FR6.5 | Default declared after 90 days past due | P0 | All |
| FR6.6 | Provisioning rules: 25% at 30 days, 50% at 60 days, 100% at 90 days | P0 | All |

## FR7: Reporting
| ID | Requirement | Priority | Product |
|----|-------------|----------|---------|
| FR7.1 | Portfolio dashboard for management (disbursement, collections, NPL, PAR) | P0 | All |
| FR7.2 | Sharia compliance report (quarterly) | P0 | All |
| FR7.3 | Charity fee report (quarterly disbursement documentation) | P0 | All |
| FR7.4 | Cash flow forecasting report | P1 | All |
| FR7.5 | Credit score distribution report | P1 | All |
