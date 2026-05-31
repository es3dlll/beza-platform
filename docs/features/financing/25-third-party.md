# التكاملات الخارجية — Third-Party Integrations

## Current Integrations

| System | Integration Type | Purpose | Status |
|--------|------------------|---------|--------|
| Beza CFE (Core Financial Engine) | Internal API | All fund movements (disbursement, repayment) | ✅ Active |
| Beza Wallet Service | Internal API | Balance checks, wallet transactions | ✅ Active |
| Beza Savings Service | Internal API | Savings history for credit scoring | ✅ Active |
| Beza Agent Network | Internal API | Agent interactions for scoring, cash collection | ✅ Active |
| Beza Notification Service | Internal API | SMS, push, in-app notifications | ✅ Active |
| Beza KYC Service | Internal API | Identity verification, document validation | ✅ Active |

## Planned Integrations

| System | Timeline | Purpose |
|--------|----------|---------|
| Syria Credit Bureau | Q3 2026 | External credit history check, bureau reporting |
| Central Bank of Syria (CBS) Reporting | Q2 2026 | Automated regulatory reporting |
| E-Signature Provider (e.g., DocSign) | Q1 2026 | Legally binding digital signatures for contracts |
| SMS Gateway (direct operator) | Q1 2026 | Reliable SMS delivery for payment reminders |
| WhatsApp Business API | Q2 2026 | Rich notification channel for statements, reminders |
| Accounting System (e.g., Odoo) | Q2 2026 | Automated GL entries, financial reporting |
| Collection Agency API | Q3 2026 | External collection referral for defaulted accounts |
| Sharia Board Portal | Q2 2026 | Digital Sharia audit workflow |

## Integration Details

### 1. Beza CFE Integration
```yaml
endpoint: https://cfe.beza.internal/v1
auth: Service-to-service JWT (mTLS)
rate_limit: 1000 TPS

operations:
  transfer:
    method: POST /transfers
    payload:
      source_account: "beza_financing_pool"
      destination_account: "user_wallet_{userId}"
      amount: 300000
      currency: SYP
      reference: "FIN-DIS-{contractId}"
      description: "صرف تمويل قرض حسن"

  balance_check:
    method: GET /accounts/{accountId}/balance
    response:
      available_balance: 1250000
      currency: SYP
```

### 2. Beza KYC Service Integration
```yaml
endpoint: https://kyc.beza.internal/v1

verification_check:
  method: GET /users/{userId}/verification-status
  response:
    level: 2  # Must be ≥ 2 for financing
    status: verified
    documents:
      - national_id: verified
      - selfie: verified

  document_upload:
    method: POST /documents/upload
    payload:
      user_id: 67890
      document_type: invoice
      file: multipart
```

### 3. Syria Credit Bureau Integration (Planned)
```yaml
endpoint: https://api.cbs-syria.gov.sy/v1/bureau
auth: API Key + Mutual TLS

inquiry:
  method: POST /inquiry
  payload:
    national_id: "100-1234567"
    consent_ref: "CONS-2026-00001"
    purpose: "credit_evaluation"
  response:
    bureau_score: 650
    active_obligations: 2
    total_outstanding: SYP 750,000
    payment_history: "clean_12m"

reporting:
  method: POST /report
  payload:
    contract_ref: "BZ-QH-2026-00001"
    borrower_national_id: "100-1234567"
    principal: 300000
    status: active|completed|defaulted
    payment_history: [...]
```

### 4. CBS Regulatory Reporting (Planned)
```yaml
endpoint: https://api.cbs-syria.gov.sy/v1/reporting
frequency: monthly

report_types:
  portfolio_summary:
    format: XML (CBS schema)
    fields:
      - total_portfolio
      - avg_loan_size
      - active_borrowers
      - new_disbursements_month
      - collections_month
      - par_30_60_90
      - write_offs

  capital_adequacy:
    format: XML
    fields:
      - tier1_capital
      - tier2_capital
      - risk_weighted_assets
      - car_ratio
```

## Integration Contracts
```typescript
interface IntegrationContract {
  name: string;
  provider: string;
  protocol: 'REST' | 'gRPC' | 'SOAP' | 'GraphQL';
  authMethod: 'jwt' | 'api_key' | 'mTLS' | 'oauth2';
  sla: {
    uptime: number;        // e.g., 99.9
    maxLatency: number;    // ms
    throughput: number;    // TPS
  };
  circuitBreaker: {
    failureThreshold: number;
    recoveryTimeout: number;  // ms
  };
}

const cfeContract: IntegrationContract = {
  name: 'CFE',
  provider: 'internal',
  protocol: 'REST',
  authMethod: 'mTLS',
  sla: { uptime: 99.99, maxLatency: 200, throughput: 1000 },
  circuitBreaker: { failureThreshold: 5, recoveryTimeout: 30000 }
};
```
