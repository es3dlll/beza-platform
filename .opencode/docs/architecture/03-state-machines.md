# Platform State Machines

Centralised state machines for all platform entities.

---

## WalletTransaction

```
States: pending → processing → completed
                  → failed
                  → disputed → resolved
                  → expired
```

### Transition Table
| From | To | Guard | Trigger | Allowed Roles |
|------|----|-------|---------|---------------|
| pending | processing | auth_check_passed AND limit_not_exceeded | User submits transfer | System |
| processing | completed | cfe_posting_success | CFE confirms posting | System |
| processing | failed | cfe_posting_failure OR fraud_block | CFE error / fraud | System |
| processing | expired | hold_timeout > 30min | Scheduler TTL | System |
| completed | disputed | within_24h AND user_initiated | User disputes | User |
| completed | reversed | within_24h AND admin_approved | Admin reversal | Admin |
| disputed | resolved | investigation_complete AND (refunded OR validated) | Compliance resolves | Compliance |
| pending | failed | validation_failed OR duplicate_detected | Pre-submit validation | System |
| completed | failed | reversal_failed | Reversal rejected by CFE | System |

### Timeout Rules
- **Hold expires:** 30 minutes after `pending → processing` if not completed
- **Dispute window:** 24 hours from completion
- **Reversal window:** 7 days from completion
- **Failed cleanup:** 90 days (archival)

### Roles
| State | Read | Write/Transition |
|-------|------|-----------------|
| pending | User, Admin | System |
| processing | User, Admin | System |
| completed | User, Admin | User (dispute), Admin (reversal) |
| failed | User, Admin | — |
| disputed | User, Admin | Compliance |
| resolved | User, Admin | — |
| expired | Admin | — |

---

## Remittance

```
States: draft → rate_locked → aml_screening → fx_conversion → disbursing → completed
         |  → expired          → aml_hold      → failed
         |                     → sanctions_block
         |                     → failed
         → cancelled
```

### Transition Table
| From | To | Guard | Trigger | Allowed |
|------|----|-------|---------|---------|
| draft | rate_locked | rate_fetched AND hold_placed | User accepts rate | User |
| draft | cancelled | user_confirms_cancel | User cancels | User |
| rate_locked | aml_screening | rate_not_expired | System auto-advances | System |
| rate_locked | expired | rate_ttl > 5min AND user_inactive | Scheduler | System |
| aml_screening | fx_conversion | aml_pass AND no_sanctions_hit | AML Engine | System |
| aml_screening | aml_hold | aml_flag AND pending_review | AML Engine | System |
| aml_screening | sanctions_block | sanctions_match | Screening Engine | System |
| aml_screening | failed | aml_reject | AML Engine | System |
| aml_hold | fx_conversion | compliance_officer_approves | Manual review | Compliance |
| aml_hold | failed | compliance_officer_rejects | Manual review | Compliance |
| fx_conversion | disbursing | conversion_success | FX Engine | System |
| fx_conversion | failed | conversion_failure OR provider_timeout | FX Engine | System |
| disbursing | completed | partner_confirms_disbursement | Partner callback | System |
| disbursing | failed | partner_rejects OR timeout_exceeded | Partner / Scheduler | System |
| expired | cancelled | refund_initiated | System auto-refund | System |
| sanctions_block | failed | confirmed_match | Compliance confirm | Compliance |
| sanctions_block | fx_conversion | false_positive_cleared | Compliance clears | Compliance |

### Timeout Rules
- **Rate lock TTL:** 5 minutes
- **AML screening timeout:** 2 minutes (fail-close to `aml_hold`)
- **Disbursement timeout:** 24 hours (retry 3×, then fail)
- **AML hold max:** 72 hours (auto-escalate)
- **Sanctions hold max:** 7 days (requires manual)
- **Draft expiry:** 24 hours

### Roles
| State | Read | Write |
|-------|------|-------|
| draft | User | User |
| rate_locked | User | System |
| aml_screening | User, Compliance | System, Compliance |
| aml_hold | Compliance | Compliance |
| sanctions_block | Compliance | Compliance |
| fx_conversion | Admin | System |
| disbursing | Admin | System |
| completed | User, Admin, Compliance | — |
| failed | User, Admin | — |
| expired | Admin | System |
| cancelled | User | — |

---

## Agent Application

```
States: submitted → document_review → background_check → approved
                  → rejected
                  → rejected
                  → rejected
```

### Transition Table
| From | To | Guard | Trigger | Allowed |
|------|----|-------|---------|---------|
| submitted | document_review | docs_complete | System auto | System |
| submitted | rejected | docs_incomplete OR invalid_id | Admin review | Admin |
| document_review | background_check | docs_verified AND no_forgery | Compliance check | Compliance |
| document_review | rejected | docs_fake OR mismatch | Compliance | Compliance |
| background_check | approved | criminal_clear AND financial_fit | Compliance approval | Compliance |
| background_check | rejected | criminal_record OR sanctions_match | Compliance | Compliance |
| approved | active | bond_paid AND training_done | Agent completes | Agent |
| active | suspended | compliance_flag OR kyc_expired | System / Compliance | System, Compliance |
| suspended | active | issue_resolved | Compliance | Compliance |
| active | terminated | voluntary OR fraud | Agent / Compliance | Agent / Compliance |

### Timeout Rules
- **Document review:** 48 hours auto-escalate to supervisor
- **Background check TAT:** 5 business days
- **Training completion:** 30 days after approval
- **KYC re-verification:** Every 12 months

---

## Loan Application (Financing)

```
States: draft → submitted → credit_check → risk_assessment → underwriting → approved → disbursing → active
         → cancelled      → declined      → declined       → declined                  → failed
                                                                                         → active
```

### Transition Table
| From | To | Guard | Trigger | Allowed |
|------|----|-------|---------|---------|
| draft | submitted | terms_accepted AND documents_uploaded | User submits | User |
| draft | cancelled | user_cancels | User cancels | User |
| submitted | credit_check | bureau_fetch_success | System | System |
| submitted | declined | bureau_fetch_failed OR poor_history | System | System |
| credit_check | risk_assessment | credit_score_received | Scoring engine | System |
| credit_check | declined | score_below_minimum | Scoring engine | System |
| risk_assessment | underwriting | risk_score_calculated | Scoring engine | System |
| risk_assessment | declined | risk_score_above_max | Scoring engine | System |
| underwriting | approved | underwriter_approves | Manual underwriting | Underwriter |
| underwriting | declined | underwriter_rejects | Manual underwriting | Underwriter |
| approved | disbursing | disbursement_triggered | System (user accepts) | User |
| disbursing | active | cfe_credit_success | CFE | System |
| disbursing | failed | cfe_credit_failure | CFE | System |
| active | completed | repayment_balance_zero | System | System |
| active | defaulted | 90_days_overdue | Scheduler | System |
| defaulted | collections | assigned_to_collections | Collections | System |

### Timeout Rules
- **Credit check:** 30 seconds (fail-fast)
- **Underwriting SLA:** 4 business hours
- **Disbursement:** 24 hours to accept, then auto-decline
- **Grace period:** 15 days post due-date
- **Default:** 90 days past due

---

## Settlement Batch

```
States: collecting → netting → reconciling → settling → completed
                                               → exception → manual_review → retry → reconciling
                                                                             → force_settle → settling
```

### Transition Table
| From | To | Guard | Trigger | Allowed |
|------|----|-------|---------|---------|
| collecting | netting | all_txns_collected | EOD scheduler | System |
| collecting | failed | collection_error | System | System |
| netting | reconciling | net_calculation_done | System | System |
| netting | failed | calculation_mismatch | System | System |
| reconciling | settling | internal_matches_external | Reconciliation engine | System |
| reconciling | exception | variance > threshold OR txn_orphaned | Reconciliation engine | System |
| exception | manual_review | ops_acknowledged | Ops picks up | Ops |
| manual_review | retry | fix_applied | Ops resolves | Ops |
| manual_review | force_settle | variance_accepted AND approved_by_finance | Finance approval | Finance |
| retry | reconciling | reconciliation_retry | System | System |
| settling | completed | all_disbursements_confirmed | Settlement engine | System |
| settling | failed | disbursement_failed_max_retries | Settlement engine | System |

### Timeout Rules
- **Collection timeout:** 30min per entity
- **Auto-retry:** 3 attempts on settle failure
- **Manual review SLA:** 4 hours (escalate to finance)
- **Batch cut-off:** 11:59 PM daily

---

## Card

```
States: issued → active → suspended → active
                        → frozen → active
                        → cancelled
                        → reported_stolen → cancelled
```

### Transition Table
| From | To | Guard | Trigger | Allowed |
|------|----|-------|---------|---------|
| issued | active | pin_set AND card_activated | User activates | User |
| active | suspended | fraud_alert_medium | Fraud engine | System |
| active | frozen | user_request OR fraud_alert_high | User / Fraud engine | User / System |
| active | cancelled | user_request | User / Admin | User / Admin |
| active | reported_stolen | user_reports_stolen | User report | User |
| suspended | active | review_cleared | Compliance | Compliance |
| frozen | active | user_unfreezes | User | User |
| reported_stolen | cancelled | confirmation_after_24h | System auto | System |
| issued | cancelled | not_activated_within_90d | Scheduler | System |

### Timeout Rules
- **Activation:** 90 days from issue
- **Auto-freeze lift:** 72 hours unless confirmed stolen
- **Stolen confirmation:** 24-hour cooldown before cancellation
- **Suspension review:** 48 hours max

---

## Savings Goal

```
States: active → paused → active
               → completed
               → cancelled
```

### Transition Table
| From | To | Guard | Trigger | Allowed |
|------|----|-------|---------|---------|
| active | paused | user_pauses | User action | User |
| paused | active | user_resumes | User action | User |
| active | completed | target_amount_reached OR target_date_passed | Scheduler | System |
| active | cancelled | user_cancels AND (balance_withdrawn OR forfeited) | User action | User |
| paused | cancelled | user_cancels | User action | User |
| completed | withdrawn | balance_transferred_to_wallet | User withdraws | User |

### Timeout Rules
- **Auto-save retry:** 3 attempts on insufficient balance, then pause
- **Goal expiry:** 6 months past target date (auto-cancel)

---

## Merchant

```
States: registered → verified → active → suspended → active
                 → rejected              → terminated
```

### Transition Table
| From | To | Guard | Trigger | Allowed |
|------|----|-------|---------|---------|
| registered | verified | business_docs_ok AND site_visit_done | Compliance | Compliance |
| registered | rejected | docs_invalid OR negative_list | Compliance | Compliance |
| verified | active | pos_commission_agreed AND integration_done | Onboarding | System |
| active | suspended | chargeback_ratio > threshold OR compliance_flag | System / Compliance | System, Compliance |
| active | terminated | merchant_request OR repeated_violation | Merchant / Compliance | Merchant, Compliance |
| suspended | active | issues_resolved AND fine_paid | Compliance | Compliance |

### Timeout Rules
- **Verification SLA:** 3 business days
- **Suspension auto-escalate:** 7 days → finance committee
- **Chargeback ratio threshold:** > 1.5% monthly

---

## Bill Payment

```
States: initiated → pending_confirmation → confirmed → completed
                 → failed                              → failed
                 → expired
```

### Transition Table
| From | To | Guard | Trigger | Allowed |
|------|----|-------|---------|---------|
| initiated | pending_confirmation | biller_validation_success | Biller API response | System |
| initiated | failed | biller_validation_failed | Biller API error | System |
| initiated | expired | user_inactive > 10min | Scheduler | System |
| pending_confirmation | confirmed | user_confirms | User confirms | User |
| pending_confirmation | failed | user_cancels | User cancels | User |
| pending_confirmation | expired | confirmation_timeout > 5min | Scheduler | System |
| confirmed | completed | biller_payment_success | Biller API | System |
| confirmed | failed | biller_payment_failed OR network_error | Biller / System | System |
| confirmed | expired | payment_processing_timeout | Scheduler | System |
| completed | partial_refund | merchant_initiated | Biller / Support | Admin |
| completed | full_refund | dispute_upheld | Compliance | Compliance |

### Timeout Rules
- **Initiation expiry:** 10 minutes
- **Confirmation window:** 5 minutes
- **Processing timeout:** 30 seconds per retry, 3 retries
- **Refund window:** 30 days from payment

---

## User KYC

```
States: not_started → documents_uploaded → verification_pending → approved
                                             → manual_review → approved
                                                              → rejected
```

### Transition Table
| From | To | Guard | Trigger | Allowed |
|------|----|-------|---------|---------|
| not_started | documents_uploaded | docs_submitted | User uploads | User |
| documents_uploaded | verification_pending | image_quality_ok AND auto_verify_eligible | System | System |
| documents_uploaded | manual_review | auto_verify_failed OR image_poor_quality | System | System |
| verification_pending | approved | auto_verify_pass | KYC engine | System |
| verification_pending | manual_review | auto_verify_inconclusive | KYC engine | System |
| manual_review | approved | officer_approves | Compliance | Compliance |
| manual_review | rejected | officer_rejects | Compliance | Compliance |
| approved | expired | tier_3_docs_expired | Scheduler | System |
| expired | documents_uploaded | new_docs_submitted | User | User |

### Timeout Rules
- **Auto-verify SLA:** 30 seconds
- **Manual review SLA:** 24 hours
- **KYC expiry:** Tier 1 (never), Tier 2 (5 years), Tier 3 (3 years)
- **Document re-upload limit:** 3 attempts, then 24-hour cooldown

---

## User Account

```
States: pending_phone → active → suspended → active
                                          → frozen → active
                                          → closed
                        → closed
```

### Transition Table
| From | To | Guard | Trigger | Allowed |
|------|----|-------|---------|---------|
| pending_phone | active | otp_verified AND pin_set | User completes onboarding | User |
| pending_phone | closed | duplicate_check_failed OR manual_block | System / Admin | System, Admin |
| active | suspended | kyc_expired OR aml_flag | System / Compliance | System, Compliance |
| active | frozen | user_request OR fraud_alert | User / System | User, System |
| active | closed | voluntary_closure | User request | User |
| active | closed | admin_closure_after_90d_inactive | Admin | Admin |
| suspended | active | issue_resolved | Compliance | Compliance |
| frozen | active | user_unfreezes OR alert_cleared | User / Compliance | User, Compliance |
| suspended | closed | 30_days_no_resolution | Scheduler | System |

### Timeout Rules
- **Phone verification timeout:** 10 minutes OTP
- **Suspension auto-escalate:** 30 days → account closure
- **Inactive account:** 90 days → dormant flag, 365 days → closure
- **Frozen duration:** indefinite until user or Compliance acts
