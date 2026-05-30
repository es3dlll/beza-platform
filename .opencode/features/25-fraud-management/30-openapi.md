# OpenAPI Specification — FraudEngine Internal API

## Overview

This OpenAPI 3.0 specification defines the internal REST API for Beza's FraudEngine module. All endpoints are internal — consumed by other Beza modules and the fraud operations dashboard.

**Base URL:** `https://api.beza.sy/fraud` (internal)
**Version:** 1.0.0
**Contact:** fraud-team@beza.sy

---

```yaml
openapi: 3.0.0
info:
  title: Beza FraudEngine Internal API
  description: |
    Fraud detection and case management API for Beza's Fraud Prevention Platform.
    Used by all Beza modules (Wallet, Agent, Remittance, Merchant, Bills, Payroll)
    to screen transactions in real-time, and by the Fraud Operations team to
    manage investigations and cases.
  version: 1.0.0
  contact:
    name: Fraud Engineering Team
    email: fraud-team@beza.sy

servers:
  - url: https://api.beza.sy/fraud/v1
    description: Production internal API
  - url: https://staging-api.beza.sy/fraud/v1
    description: Staging environment

security:
  - ApiKeyAuth: []
  - BearerAuth: []

components:
  securitySchemes:
    ApiKeyAuth:
      type: apiKey
      in: header
      name: X-Fraud-Api-Key
      description: Internal API key for module-to-module authentication
    BearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT
      description: JWT token for fraud operations team authentication

  schemas:
    # ──────────────────────────────────────────
    # TRANSACTION SCREENING
    # ──────────────────────────────────────────

    ScreenTransactionRequest:
      type: object
      required:
        - feature_source
        - transaction_id
        - amount
        - currency
        - sender_id
        - recipient_id
        - context
      properties:
        feature_source:
          type: string
          enum: [wallet, agent, remittance, merchant, bills, payroll]
          description: Originating Beza module
          example: wallet
        transaction_id:
          type: string
          description: Unique transaction identifier from originating module
          example: txn_8hJ2kL4mN6pQ9rS1tU3v
        amount:
          type: number
          format: double
          description: Transaction amount
          example: 150000.00
        currency:
          type: string
          minLength: 3
          maxLength: 3
          description: ISO 4217 currency code
          example: SYP
        sender_id:
          type: string
          description: User ID of transaction sender
          example: usr_a1B2c3D4e5F6g7H8i9J0
        recipient_id:
          type: string
          description: User ID of transaction recipient
          example: usr_k1L2m3N4o5P6q7R8s9T0
        context:
          type: object
          description: Feature-specific context data
          properties:
            device_fingerprint:
              type: string
              example: fp_abc123def456
            device_name:
              type: string
              example: Samsung Galaxy S23
            device_os:
              type: string
              example: Android 14
            ip_address:
              type: string
              format: ipv4
              example: 10.0.0.1
            location:
              type: object
              properties:
                lat:
                  type: number
                  format: float
                  example: 33.5138
                lon:
                  type: number
                  format: float
                  example: 36.2765
                city:
                  type: string
                  example: Damascus
                region:
                  type: string
                  example: Damascus Governorate
            network_operator:
              type: string
              example: Syriatel
            is_new_device:
              type: boolean
              example: true
            user_agent:
              type: string
              example: BezaApp/2.4.1 (Android 14; Samsung S23)
            session_id:
              type: string
              example: sess_m9N0bV1cX2zL3k4J5h6G
        sender_profile:
          type: object
          properties:
            account_age_days:
              type: integer
              example: 180
            kyc_level:
              type: integer
              enum: [1, 2, 3]
              example: 2
            avg_transaction_amount:
              type: number
              format: double
              example: 45000.00
            transaction_count_30d:
              type: integer
              example: 24
            total_volume_30d:
              type: number
              format: double
              example: 1080000.00
            risk_tier:
              type: string
              enum: [low, standard, high, critical]
              example: standard
        recipient_profile:
          type: object
          properties:
            account_age_days:
              type: integer
              example: 90
            kyc_level:
              type: integer
              example: 1
            avg_transaction_amount:
              type: number
              example: 35000.00
            trust_score:
              type: integer
              minimum: 0
              maximum: 100
              example: 65

    FraudDecisionResult:
      type: object
      properties:
        risk_score:
          type: integer
          minimum: 0
          maximum: 100
          description: Overall fraud risk score (0=safe, 100=critical)
          example: 72
        risk_level:
          type: string
          enum: [safe, suspicious, highly_suspicious, critical]
          example: highly_suspicious
        decision:
          type: string
          enum: [approve, verify, review, block]
          description: Recommended action
          example: review
        action_taken:
          type: string
          description: Actual action executed
          example: flagged_for_review
        rules_triggered:
          type: array
          items:
            $ref: '#/components/schemas/TriggeredRule'
        ml_score:
          type: number
          format: float
          minimum: 0
          maximum: 1
          description: ML model fraud probability
          example: 0.72
        ml_model_version:
          type: string
          example: v1.2.3
        processing_time_ms:
          type: integer
          description: Total processing time in milliseconds
          example: 87
        alert_id:
          type: string
          nullable: true
          description: Alert ID if decision was review or block
          example: alt_4mN6pQ9rS1tU3vW5xY7

    TriggeredRule:
      type: object
      properties:
        rule_id:
          type: string
          example: DEV-001
        rule_name:
          type: string
          example: New Device Threshold
        score:
          type: integer
          minimum: 0
          maximum: 100
          example: 25
        action:
          type: string
          enum: [flag, slow, block, freeze]
          example: flag
        reason:
          type: string
          example: Device Samsung Galaxy S23 not seen in user 90-day history

    FraudError:
      type: object
      properties:
        error:
          type: string
          example: fraud_screening_failed
        message:
          type: string
          example: ML scoring service unavailable, fallback to rules-only mode
        decision:
          type: string
          example: verify

    # ──────────────────────────────────────────
    # FRAUD CASES
    # ──────────────────────────────────────────

    FraudCase:
      type: object
      properties:
        id:
          type: string
          format: uuid
          example: 550e8400-e29b-41d4-a716-446655440000
        case_number:
          type: string
          example: FR-2025-05678
        status:
          type: string
          enum:
            - alert
            - under_investigation
            - confirmed_fraud
            - false_positive
            - reported_cbs
            - escalated
            - recovered
            - closed_with_loss
            - closed
          example: under_investigation
        priority:
          type: string
          enum: [P0, P1, P2, P3]
          example: P0
        fraud_type:
          type: string
          enum:
            - account_takeover
            - sim_swap
            - agent_fraud
            - mule_account
            - social_engineering
            - phishing
            - synthetic_identity
            - merchant_collusion
            - insider_fraud
            - other
          example: account_takeover
        transaction_id:
          type: string
          example: txn_8hJ2kL4mN6pQ9rS1tU3v
        amount:
          type: number
          format: double
          example: 500000.00
        currency:
          type: string
          example: SYP
        victim_user_id:
          type: string
          example: usr_a1B2c3D4e5F6g7H8i9J0
        suspect_user_id:
          type: string
          nullable: true
          example: usr_k1L2m3N4o5P6q7R8s9T0
        risk_score:
          type: integer
          example: 78
        assigned_to:
          type: string
          nullable: true
          example: ops_sarah
        description:
          type: string
          example: Unauthorized transfer from new device. User confirmed via phone.
        sla_deadline:
          type: string
          format: date-time
          example: 2025-03-14T16:03:00+03:00
        created_at:
          type: string
          format: date-time
        updated_at:
          type: string
          format: date-time
        resolved_at:
          type: string
          format: date-time
          nullable: true

    FraudCaseListResponse:
      type: object
      properties:
        data:
          type: array
          items:
            $ref: '#/components/schemas/FraudCase'
        meta:
          type: object
          properties:
            current_page:
              type: integer
              example: 1
            per_page:
              type: integer
              example: 20
            total:
              type: integer
              example: 1842
            last_page:
              type: integer
              example: 93

    FraudCaseDecisionRequest:
      type: object
      required:
        - decision
      properties:
        decision:
          type: string
          enum:
            - confirm_fraud
            - false_positive
            - escalate_cbs
            - escalate_law_enforcement
            - recover_funds
            - close_with_loss
            - close
          description: Decision to apply to the case
          example: confirm_fraud
        notes:
          type: string
          description: Investigation notes to add to the case
          example: User confirmed credentials were compromised via phishing link. Fraudster device fingerprinted.
        evidence:
          type: array
          items:
            type: object
            properties:
              type:
                type: string
                enum: [device_log, transaction_graph, call_recording, screenshot, document]
                example: device_log
              description:
                type: string
                example: Phishing SMS screenshot from user
              file_url:
                type: string
                example: https://storage.beza.sy/evidence/FR-5678/phishing-sms.png

    # ──────────────────────────────────────────
    # DASHBOARD
    # ──────────────────────────────────────────

    DashboardKPIs:
      type: object
      properties:
        transactions_today:
          type: integer
          example: 284502
        fraud_rate:
          type: number
          format: float
          example: 0.08
        false_positive_rate:
          type: number
          format: float
          example: 2.7
        avg_decision_time_ms:
          type: integer
          example: 87
        blocked_amount_syp:
          type: number
          format: double
          example: 1250000.00
        open_cases:
          type: integer
          example: 18
        p0_alerts:
          type: integer
          example: 2
        p1_alerts:
          type: integer
          example: 5
        p2_alerts:
          type: integer
          example: 12

    DashboardAlert:
      type: object
      properties:
        id:
          type: string
          format: uuid
        priority:
          type: string
          enum: [P0, P1, P2]
          example: P0
        type:
          type: string
          example: account_takeover
        title:
          type: string
          example: ATO detected on user 8492
        message:
          type: string
          example: 500,000 SYP transfer attempt from new device. User not confirmed.
        transaction_id:
          type: string
          example: txn_8hJ2kL4mN6pQ9rS1tU3v
        risk_score:
          type: integer
          example: 92
        created_at:
          type: string
          format: date-time

    # ──────────────────────────────────────────
    # CBS REPORTS
    # ──────────────────────────────────────────

    CBSReport:
      type: object
      properties:
        report_period:
          type: object
          properties:
            start:
              type: string
              format: date
              example: 2025-01-01
            end:
              type: string
              format: date
              example: 2025-03-31
        summary:
          type: object
          properties:
            total_transactions:
              type: integer
              example: 25638402
            total_value_syp:
              type: number
              example: 1274583000.00
            fraud_cases:
              type: integer
              example: 1842
            fraud_value_syp:
              type: number
              example: 2183000.00
            fraud_rate:
              type: number
              format: float
              example: 0.17
            recovery_rate:
              type: number
              format: float
              example: 21.0
            false_positive_rate:
              type: number
              format: float
              example: 2.8
        by_fraud_type:
          type: array
          items:
            type: object
            properties:
              type:
                type: string
              cases:
                type: integer
              value_syp:
                type: number
        sars_filed:
          type: integer
          example: 38
        law_enforcement_referrals:
          type: integer
          example: 4

paths:
  # ──────────────────────────────────────────
  # SCREEN TRANSACTION
  # ──────────────────────────────────────────

  /screen:
    post:
      operationId: screenTransaction
      summary: Screen a transaction for fraud
      description: |
        Submit a transaction for real-time fraud screening. Returns a decision
        (approve, verify, review, or block) within 200ms. Called synchronously
        by all transaction-processing modules.
      tags:
        - Transaction Screening
      security:
        - ApiKeyAuth: []
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/ScreenTransactionRequest'
      responses:
        '200':
          description: Successful screening
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/FraudDecisionResult'
        '200':
          description: Degraded screening (fallback mode active)
          content:
            application/json:
              schema:
                allOf:
                  - $ref: '#/components/schemas/FraudDecisionResult'
                  - type: object
                    properties:
                      degraded:
                        type: boolean
                        example: true
                      fallback_reason:
                        type: string
                        example: ML service unavailable, rules-only scoring
        '503':
          description: Fraud engine unavailable (fail-open with amount cap)
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/FraudError'

  # ──────────────────────────────────────────
  # FRAUD CASES
  # ──────────────────────────────────────────

  /cases:
    get:
      operationId: listFraudCases
      summary: List fraud cases
      description: Retrieve fraud cases with filtering, sorting, and pagination
      tags:
        - Case Management
      security:
        - BearerAuth: []
      parameters:
        - name: status
          in: query
          schema:
            type: string
            enum:
              - alert
              - under_investigation
              - confirmed_fraud
              - false_positive
              - reported_cbs
              - escalated
              - recovered
              - closed_with_loss
              - closed
          description: Filter by case status
        - name: priority
          in: query
          schema:
            type: string
            enum: [P0, P1, P2, P3]
          description: Filter by priority
        - name: fraud_type
          in: query
          schema:
            type: string
          description: Filter by fraud type
        - name: assigned_to
          in: query
          schema:
            type: string
          description: Filter by assigned analyst (user ID or 'unassigned')
        - name: search
          in: query
          schema:
            type: string
          description: Search by case number, transaction ID, or user ID
        - name: date_from
          in: query
          schema:
            type: string
            format: date
          description: Filter cases created after this date
        - name: date_to
          in: query
          schema:
            type: string
            format: date
          description: Filter cases created before this date
        - name: sort_by
          in: query
          schema:
            type: string
            enum: [created_at, amount, risk_score, priority, updated_at]
            default: created_at
        - name: sort_order
          in: query
          schema:
            type: string
            enum: [asc, desc]
            default: desc
        - name: page
          in: query
          schema:
            type: integer
            default: 1
        - name: per_page
          in: query
          schema:
            type: integer
            default: 20
            maximum: 100
      responses:
        '200':
          description: List of fraud cases
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/FraudCaseListResponse'

  /cases/{id}:
    get:
      operationId: getFraudCase
      summary: Get fraud case details
      tags:
        - Case Management
      security:
        - BearerAuth: []
      parameters:
        - name: id
          in: path
          required: true
          schema:
            type: string
            format: uuid
          description: Fraud case UUID
      responses:
        '200':
          description: Fraud case details
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/FraudCase'
        '404':
          description: Case not found

  /cases/{id}/decision:
    post:
      operationId: makeCaseDecision
      summary: Make a decision on a fraud case
      description: |
        Confirm fraud, mark false positive, escalate, or close a case.
        Requires appropriate permissions based on decision type.
      tags:
        - Case Management
      security:
        - BearerAuth: []
      parameters:
        - name: id
          in: path
          required: true
          schema:
            type: string
            format: uuid
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/FraudCaseDecisionRequest'
      responses:
        '200':
          description: Decision applied successfully
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/FraudCase'
        '403':
          description: Insufficient permissions for this decision
        '422':
          description: Validation error (e.g., missing required evidence for confirm_fraud)

  # ──────────────────────────────────────────
  # DASHBOARD
  # ──────────────────────────────────────────

  /dashboard:
    get:
      operationId: getFraudDashboard
      summary: Get fraud dashboard data
      tags:
        - Dashboard
      security:
        - BearerAuth: []
      parameters:
        - name: period
          in: query
          schema:
            type: string
            enum: [24h, 7d, 30d]
            default: 24h
      responses:
        '200':
          description: Dashboard data
          content:
            application/json:
              schema:
                type: object
                properties:
                  kpis:
                    $ref: '#/components/schemas/DashboardKPIs'
                  alerts:
                    type: array
                    items:
                      $ref: '#/components/schemas/DashboardAlert'
                  fraud_rate_trend:
                    type: array
                    items:
                      type: object
                      properties:
                        date:
                          type: string
                          format: date
                        rate:
                          type: number

  # ──────────────────────────────────────────
  # CBS REPORTING
  # ──────────────────────────────────────────

  /reports/cbs:
    get:
      operationId: getCBSReport
      summary: Generate CBS quarterly fraud report
      tags:
        - Reporting
      security:
        - BearerAuth: []
      parameters:
        - name: year
          in: query
          required: true
          schema:
            type: integer
            example: 2025
        - name: quarter
          in: query
          required: true
          schema:
            type: integer
            enum: [1, 2, 3, 4]
      responses:
        '200':
          description: CBS quarterly fraud report
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/CBSReport'
        '404':
          description: Data not available for specified period

  /reports/cbs/download:
    get:
      operationId: downloadCBSReport
      summary: Download CBS quarterly report as PDF
      tags:
        - Reporting
      security:
        - BearerAuth: []
      parameters:
        - name: year
          in: query
          required: true
          schema:
            type: integer
        - name: quarter
          in: query
          required: true
          schema:
            type: integer
      responses:
        '200':
          description: PDF report file
          content:
            application/pdf:
              schema:
                type: string
                format: binary

  # ──────────────────────────────────────────
  # PROVISIONING DATA
  # ──────────────────────────────────────────

  /reports/provisioning:
    get:
      operationId: getProvisioningData
      summary: Get fraud loss data for IFRS 9 provisioning
      tags:
        - Reporting
      security:
        - BearerAuth: []
      parameters:
        - name: month
          in: query
          required: true
          schema:
            type: string
            format: month
            example: '2025-03'
      responses:
        '200':
          description: Provisioning data
          content:
            application/json:
              schema:
                type: object
                properties:
                  period:
                    type: string
                    example: '2025-03'
                  fraud_pd:
                    type: number
                    format: float
                    example: 0.0001842
                  fraud_lgd:
                    type: number
                    format: float
                    example: 0.79
                  fraud_ead:
                    type: number
                    format: double
                    example: 1185.00
                  expected_loss:
                    type: number
                    format: double
                    example: 172500.00
                  total_transactions:
                    type: integer
                    example: 1000000
                  total_value:
                    type: number
                    example: 50000000000.00
                  confirmed_fraud_cases:
                    type: integer
                    example: 184
                  fraud_value:
                    type: number
                    example: 218300.00
                  recovered_amount:
                    type: number
                    example: 45843.00
                  recovery_rate:
                    type: number
                    format: float
                    example: 21.0

  # ──────────────────────────────────────────
  # HEALTH CHECK
  # ──────────────────────────────────────────

  /health:
    get:
      operationId: fraudEngineHealth
      summary: Check fraud engine health
      tags:
        - System
      security: []
      responses:
        '200':
          description: All systems operational
          content:
            application/json:
              schema:
                type: object
                properties:
                  status:
                    type: string
                    enum: [healthy, degraded, down]
                    example: healthy
                  fraud_engine:
                    type: string
                    example: operational
                  ml_service:
                    type: string
                    example: operational
                  database:
                    type: string
                    example: operational
                  cache:
                    type: string
                    example: operational
                  avg_response_time_ms:
                    type: integer
                    example: 87
                  uptime_30d:
                    type: number
                    format: float
                    example: 99.97
                  last_model_trained:
                    type: string
                    format: date-time
        '503':
          description: Service degraded or unavailable
```

## API Usage Examples

### Screen a Transaction

```bash
curl -X POST https://api.beza.sy/fraud/v1/screen \
  -H "X-Fraud-Api-Key: ${FRAUD_API_KEY}" \
  -H "Content-Type: application/json" \
  -d '{
    "feature_source": "wallet",
    "transaction_id": "txn_8hJ2kL4mN6pQ9rS1tU3v",
    "amount": 150000.00,
    "currency": "SYP",
    "sender_id": "usr_a1B2c3D4e5F6g7H8i9J0",
    "recipient_id": "usr_k1L2m3N4o5P6q7R8s9T0",
    "context": {
      "device_fingerprint": "fp_abc123def456",
      "is_new_device": true,
      "location": {
        "city": "Aleppo"
      }
    },
    "sender_profile": {
      "account_age_days": 180,
      "avg_transaction_amount": 45000.00,
      "kyc_level": 2
    }
  }'

# Response:
{
  "risk_score": 72,
  "risk_level": "highly_suspicious",
  "decision": "review",
  "action_taken": "flagged_for_review",
  "rules_triggered": [
    {"rule_id": "DEV-001", "rule_name": "New Device Threshold", "score": 25, "action": "flag", "reason": "Device not seen in 90-day history"},
    {"rule_id": "TAMT-001", "rule_name": "Amount Spike", "score": 20, "action": "slow", "reason": "Amount 3.3x user average"}
  ],
  "ml_score": 0.72,
  "ml_model_version": "v1.2.3",
  "processing_time_ms": 87,
  "alert_id": "alt_4mN6pQ9rS1tU3vW5xY7"
}
```

### List Open Fraud Cases

```bash
curl -X GET "https://api.beza.sy/fraud/v1/cases?status=under_investigation&priority=P0&sort_by=risk_score&sort_order=desc" \
  -H "Authorization: Bearer ${JWT_TOKEN}"
```

### Make Case Decision

```bash
curl -X POST https://api.beza.sy/fraud/v1/cases/550e8400-e29b-41d4-a716-446655440000/decision \
  -H "Authorization: Bearer ${JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "decision": "confirm_fraud",
    "notes": "User confirmed credentials compromised. Fraudster device fingerprinted.",
    "evidence": [
      {
        "type": "call_recording",
        "description": "User phone confirmation call recording",
        "file_url": "https://storage.beza.sy/evidence/FR-5678/call-recording.mp3"
      }
    ]
  }'
```

## Rate Limits

| Endpoint Group | Limit | Window |
|---------------|-------|--------|
| /screen | 10,000 req/min | Per API key |
| /cases (GET) | 100 req/min | Per user |
| /cases/{id}/decision | 30 req/min | Per user |
| /dashboard | 10 req/min | Per user |
| /reports | 5 req/min | Per user |
