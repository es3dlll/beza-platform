# Functional Requirements

## FR-01: Aid Program Management
| ID | Requirement | Priority |
|----|-------------|----------|
| FR-01.01 | System shall allow creation of aid programs with: name (Ar/En), description, program type (MPC, CCT, voucher), budget, currency (USD-pegged), start/end dates, distribution frequency | P0 |
| FR-01.02 | System shall support program templates for rapid deployment (predefined MPC, food voucher, winterization, education cash) | P1 |
| FR-01.03 | System shall allow pausing and resuming active programs | P0 |
| FR-01.04 | System shall show real-time budget utilisation per program | P0 |

## FR-02: Beneficiary Management
| ID | Requirement | Priority |
|----|-------------|----------|
| FR-02.01 | System shall accept beneficiary CSV upload with fields: full_name, unhcr_id, phone_number, governorate, district, family_size, head_of_household (Y/N), special_needs (Y/N) | P0 |
| FR-02.02 | System shall validate all uploaded records (phone format, duplicate UNHCR IDs, required fields) and return error report | P0 |
| FR-02.03 | System shall support individual beneficiary enrolment via API for real-time registration at agent point | P0 |
| FR-02.04 | System shall store sensitive beneficiary data encrypted at rest (AES-256-GCM) | P0 |
| FR-02.05 | System shall support beneficiary deactivation with reason (duplicate, fraudulent, deceased, withdrawn consent) | P0 |

## FR-03: Sanctions Screening
| ID | Requirement | Priority |
|----|-------------|----------|
| FR-03.01 | System shall screen all beneficiaries against UN sanctions lists, EU consolidated list, and OFAC SDN list before enrolment | P0 |
| FR-03.02 | System shall support fuzzy name matching (edit distance, phonetic variants, Arabic transliteration variations) | P0 |
| FR-03.03 | System shall require manual review for any potential match (score > 80%) | P0 |
| FR-03.04 | System shall maintain a screening audit trail: who screened, when, result, resolution | P0 |

## FR-04: Biometric Verification
| ID | Requirement | Priority |
|----|-------------|----------|
| FR-04.01 | System shall support fingerprint + facial biometric verification at agent point | P0 |
| FR-04.02 | System shall work offline — store verification queue locally, sync when online | P0 |
| FR-04.03 | System shall verify a beneficiary in < 30 seconds end-to-end | P0 |
| FR-04.04 | System shall support fallback to UNHCR ID + agent-verified photo when biometric unavailable | P1 |

## FR-05: Distribution Engine
| ID | Requirement | Priority |
|----|-------------|----------|
| FR-05.01 | System shall execute batch distributions to all enrolled program beneficiaries | P0 |
| FR-05.02 | System shall support individual ad-hoc distributions | P1 |
| FR-05.03 | System shall credit MPC directly to beneficiary Beza wallet in USD-pegged value | P0 |
| FR-05.04 | System shall issue e-vouchers as unique 12-digit codes with expiry date and restricted item list | P0 |
| FR-05.05 | System shall support conditional releases (e.g., release education cash only after school attendance verified) | P1 |
| FR-05.06 | System shall process 10,000-distribution batches in < 2 minutes | P0 |

## FR-06: Voucher Redemption
| ID | Requirement | Priority |
|----|-------------|----------|
| FR-06.01 | System shall allow merchant to enter voucher code (or scan QR) and see approved item list | P0 |
| FR-06.02 | System shall deduct items from voucher value as merchant scans | P0 |
| FR-06.03 | System shall settle merchant wallet within T+2 of voucher redemption | P0 |
| FR-06.04 | System shall allow partial voucher redemption (use part, keep remaining balance) | P0 |

## FR-07: Spending Monitoring
| ID | Requirement | Priority |
|----|-------------|----------|
| FR-07.01 | System shall categorise beneficiary spending based on merchant MCC codes and item categories | P0 |
| FR-07.02 | System shall provide aggregated spending dashboard: food, rent, health, education, transport, other | P0 |
| FR-07.03 | System shall track MPC burn rate — % of distributed funds spent within 7, 14, 30 days | P0 |
| FR-07.04 | System shall allow NGO to set spending thresholds and receive alerts if exceeded | P1 |

## FR-08: Donor Reporting
| ID | Requirement | Priority |
|----|-------------|----------|
| FR-08.01 | System shall generate donor-specific reports with: total disbursed, beneficiaries reached, average transfer value, spending breakdown, outcome indicators | P0 |
| FR-08.02 | System shall support report export as PDF, CSV, XLSX | P0 |
| FR-08.03 | System shall provide a reconciliation report matching NGO funds sent → Beza disbursed → beneficiary received → merchant settled | P0 |
