# User Stories

## Epic 1: Program Setup
| ID | Story | Priority | Persona |
|----|-------|----------|---------|
| US-001 | As a Program Manager, I want to create a new aid program with name, budget, currency, and distribution rules so that I can begin a cash assistance cycle. | P0 | Layla |
| US-002 | As a Program Manager, I want to upload a CSV of beneficiaries with name, UNHCR ID, phone number, and family size so that I can register them in bulk. | P0 | Layla |
| US-003 | As a Program Manager, I want to set distribution rules (amount per person/household, frequency, start/end date) so that the system controls disbursement automatically. | P0 | Layla |
| US-004 | As a Compliance Officer, I want all beneficiaries to be screened against UN/EU/OFAC sanctions lists before enrolment so that we remain compliant. | P0 | Rania |

## Epic 2: Beneficiary Verification
| ID | Story | Priority | Persona |
|----|-------|----------|---------|
| US-005 | As a Field Agent, I want to verify a beneficiary's identity using fingerprint + face biometrics so that I can confirm they are who they claim. | P0 | Ahmed |
| US-006 | As a Field Agent, I want to look up a beneficiary by UNHCR registration number so that I can cross-check their data. | P0 | Ahmed |
| US-007 | As a Field Agent, I want to see a verification history so that I know if someone already collected their aid. | P1 | Ahmed |
| US-008 | As a Beneficiary, I want to be verified at the agent point without needing to show paper ID so that my privacy is respected. | P0 | Fatima |

## Epic 3: Distribution Execution
| ID | Story | Priority | Persona |
|----|-------|----------|---------|
| US-009 | As a Program Manager, I want to trigger a distribution batch so that all enrolled beneficiaries receive their cash simultaneously. | P0 | Layla |
| US-010 | As a Program Manager, I want to see distribution status (pending, completed, failed) in real time so that I can monitor progress. | P0 | Layla |
| US-011 | As a Program Manager, I want to retry failed distributions individually or in bulk so that no beneficiary misses their aid. | P1 | Layla |
| US-012 | As a Beneficiary, I want to receive a notification (SMS/USSD) when cash is credited so that I know to visit a merchant. | P0 | Fatima |

## Epic 4: Voucher Management
| ID | Story | Priority | Persona |
|----|-------|----------|---------|
| US-013 | As a Program Manager, I want to create an e-voucher program with a predefined item list so that beneficiaries can only purchase approved items. | P0 | Layla |
| US-014 | As a Beneficiary, I want to redeem my e-voucher at a partner merchant by entering a code so that I can buy food for my family. | P0 | Fatima |
| US-015 | As a Partner Merchant, I want to scan/enter voucher codes and confirm redemption so that I get paid for goods provided. | P0 | Abu Khaled |
| US-016 | As a Partner Merchant, I want to see my settlement history so that I can reconcile my accounts. | P1 | Abu Khaled |

## Epic 5: Spending Monitoring (MPC)
| ID | Story | Priority | Persona |
|----|-------|----------|---------|
| US-017 | As a Program Manager, I want to see aggregated spending data by category (food, rent, health, education) so that I can report outcomes to donors. | P0 | Layla |
| US-018 | As a Donor, I want to see a breakdown of how cash assistance is spent across sectors so that I can evaluate program effectiveness. | P0 | James |
| US-019 | As a Program Manager, I want to set spending alerts (e.g., if >50% spent on non-food) so that I can adjust program design. | P1 | Layla |

## Epic 6: Donor Reporting
| ID | Story | Priority | Persona |
|----|-------|----------|---------|
| US-020 | As a Donor, I want to download a donor report with funds disbursed, beneficiaries reached, and spending analysis so that I can meet audit requirements. | P0 | James |
| US-021 | As a Program Manager, I want to generate custom reports by date range, governorate, and program type. | P1 | Layla |
| US-022 | As an NGO, I want end-to-end fund traceability so that donor audits pass seamlessly. | P0 | Layla |

## Epic 7: Compliance & Sanctions
| ID | Story | Priority | Persona |
|----|-------|----------|---------|
| US-023 | As a Compliance Officer, I want to review sanctions screening results before final approval so that I can make risk-based decisions. | P0 | Rania |
| US-024 | As a Compliance Officer, I want a full audit log of all beneficiary verifications and distributions so that I can demonstrate due diligence. | P0 | Rania |
| US-025 | As a Compliance Officer, I want to flag beneficiaries sharing the same phone number or address (fraud detection) so that duplicate registrations are caught. | P1 | Rania |
