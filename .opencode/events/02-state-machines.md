# Beza Platform — State Machines

> Version: 1.0 | Last Updated: 2026-05-29

## WalletTransaction

```
States: pending → processing → completed → reversed
              → failed
              → disputed → resolved
              → expired

Transitions:
  pending       → processing   authorization started
  processing    → completed    CFE posting success
  processing    → failed       CFE posting failure
  completed     → reversed     manual reversal within 24h
  completed     → disputed     customer disputes transaction
  disputed      → completed    dispute resolved in customer's favor
  disputed      → reversed     dispute resolved against customer
  processing    → expired      hold timeout reached

State Variable: holdId, amount, currency, expiresAt
Guards:
  completed → reversed    ONLY within 24h of completion
  processing → expired   ONLY if expiresAt < now
```

## Remittance

```
States: created → rate_locked → funded → processing → completed
             → expired
             → cancelled
             → failed → refunded

Transitions:
  created       → rate_locked    FX rate confirmed by user
  created       → expired        rate quote timeout (5 min)
  created       → cancelled      user cancels before funding
  rate_locked   → funded         sender pays full amount
  rate_locked   → expired        lock window elapsed
  funded        → processing     remittance submitted to corridor
  processing    → completed      recipient confirmed collection
  processing    → failed         corridor rejected or payout failed
  failed        → refunded       funds returned to sender
  expired       → cancelled      system auto-cancels
  funded        → cancelled      user/support cancels (pre-processing)
  processing    → cancelled      support intervention

State Variable: remittanceId, lockId, corridorRef, payoutRef
Guards:
  created → rate_locked    ONLY if quote is valid
  funded → processing      ONLY if full amount + fees received
  processing → completed   ONLY with recipient collection confirmation
```

## Agent Application

```
States: draft → submitted → under_review → approved
            → rejected
            → re_submitted

Transitions:
  draft           → submitted       agent completes & submits form
  submitted       → under_review    compliance team picks up
  under_review    → approved        all checks pass
  under_review    → rejected        verification failed
  rejected        → re_submitted    agent fixes & re-submits
  re_submitted    → under_review    review re-opened
  submitted       → approved        auto-approve (low-risk)
  under_review    → draft           returned for more info

State Variable: applicationId, reviewerId, checkResults
Guards:
  rejected → re_submitted   ONLY if resubmission allowed (< 3 attempts)
  submitted → approved      ONLY for low-risk, document-complete cases
```

## Loan Application

```
States: draft → submitted → underwriting → approved → disbursed → active → closed
                                             → rejected
                  → withdrawn
                  → expired

Transitions:
  draft         → submitted        user submits application
  submitted     → underwriting     risk engine evaluates
  submitted     → withdrawn        user withdraws before review
  submitted     → expired          30 days no review
  underwriting  → approved         risk score passes threshold
  underwriting  → rejected         risk score below threshold
  underwriting  → conditionally_approved → approved  conditions met
  approved      → disbursed        funds transferred to wallet
  approved      → expired          disbursement window (7d) passed
  disbursed     → active           first repayment received
  active        → closed           fully repaid
  active        → defaulted        >90 days overdue
  defaulted     → closed           charged off or recovered
  active        → restructured     repayment plan modified

State Variable: applicationId, riskScore, disbursedAmount, outstandingBalance
Guards:
  submitted → underwriting  ONLY if all docs uploaded
  approved → disbursed      ONLY if disbursedAmount <= approvedAmount
  active → defaulted        ONLY if overdue > 90 days
```

## Settlement Batch

```
States: open → closing → settled → reconciled
              → failed → retrying

Transitions:
  open         → closing        batch cutoff time reached
  closing      → settled        all transactions netted & transferred
  closing      → failed         transfer failure (insufficient funds)
  failed       → retrying       auto-retry after backoff
  retrying     → settled        retry succeeds
  retrying     → failed         max retries exhausted
  settled      → reconciled     GL matches batch totals
  closing      → open           reopened for late transactions

State Variable: batchId, cutoffTime, netAmount, retryCount
Guards:
  open → closing              ONLY at scheduled cutoff
  failed → retrying           ONLY if retryCount < 3
  settled → reconciled        ONLY if GL totals match batch totals
```

## Card

```
States: requested → issued → activated → suspended → re_activated
               → cancelled
               → lost_stolen → cancelled

Transitions:
  requested       → issued           card produced & dispatched
  issued          → activated        customer activates via PIN/OTP
  activated       → suspended        suspicious activity flagged
  suspended       → re_activated     fraud resolved, user confirmed
  suspended       → cancelled        customer requests cancellation
  activated       → cancelled        customer requests cancellation
  activated       → lost_stolen      customer reports lost/stolen
  lost_stolen     → cancelled        permanent block confirmed
  issued          → cancelled        never activated after 90 days
  requested       → cancelled        customer cancels before issuance

State Variable: cardId, cardPAN (masked), expiry, cvv, statusReason
Guards:
  issued → activated            ONLY within 90 days of issuance
  lost_stolen → cancelled       ONLY after customer confirms loss report
  suspended → re_activated      ONLY after fraud review clears
```

## Savings Goal

```
States: active → paused → completed
              → cancelled

Transitions:
  active        → paused          user pauses contributions
  active        → completed       target amount reached
  active        → cancelled       user cancels goal
  paused        → active          user resumes contributions
  paused        → cancelled       user cancels while paused
  paused        → completed       goal reached via auto-save resume

State Variable: goalId, targetAmount, currentAmount, targetDate
Guards:
  active → completed       ONLY if currentAmount >= targetAmount
  paused → completed       ONLY if currentAmount >= targetAmount (upon resume)
```

## Merchant

```
States: registered → verified → active → suspended
              → disabled
              → rejected

Transitions:
  registered      → verified        business docs approved
  registered      → rejected        docs failed verification
  verified        → active          settlement account linked
  active          → suspended       compliance flag or chargeback ratio > 1%
  suspended       → active          issues resolved
  active          → disabled        merchant requests closure
  suspended       → disabled        prolonged suspension (>90 days)
  verified        → active          auto-activate (low-risk, doc-complete)

State Variable: merchantId, settlementAccount, chargebackRatio
Guards:
  active → suspended           ONLY if chargebackRatio > 1% or compliance flag
  suspended → active           ONLY if all issues resolved and reviewed
  verified → active            ONLY if settlement account is valid
```

## Bill Payment

```
States: initiated → processing → paid
              → failed → refunded

Transitions:
  initiated       → processing       payment sent to biller gateway
  processing      → paid             biller confirms payment
  processing      → failed           biller rejects or timeout
  failed          → refunded         amount returned to wallet
  initiated       → failed           insufficient balance
  processing      → initiated        retry scheduled

State Variable: billerId, referenceNumber, amount, dueDate
Guards:
  initiated → processing       ONLY if wallet balance >= amount + fee
  failed → refunded            ONLY if amount was deducted before failure
  processing → initiated       ONLY if retryCount < 3 and biller allows retry
```

## User KYC

```
States: not_started → pending → verified
                     → rejected → re_submitted

Transitions:
  not_started     → pending         user submits KYC documents
  pending         → verified        documents approved
  pending         → rejected        documents invalid/suspicious
  rejected        → re_submitted    user uploads new documents
  re_submitted    → pending         re-verification queued
  re_submitted    → verified        auto-verify (NFC + biometric)
  pending         → not_started     user withdraws KYC process

State Variable: kycLevel (1-3), documentRefs, verificationMethod
Guards:
  pending → verified            ONLY if all required docs validated
  rejected → re_submitted       ONLY if resubmission < maxAttempts (3)
  re_submitted → verified       ONLY for NFC/biometric method
```
