# 14 — Backend Architecture

> **Key File** — Core domain model, service layer, and integration contracts.

---

## 1. Domain Model

### PayrollBatch

```python
class PayrollBatch:
    id: UUID              # PK
    company_id: UUID      # FK → payroll_companies
    batch_ref: str        # Human-readable: B-2026-05-001
    total_employees: int
    total_amount: Decimal # Sum of all employee salaries
    total_fee: Decimal    # 0.5 % × total_amount (capped)
    currency: str         # SYP | USD
    status: BatchStatus   # pending | processing | completed | partial_failure | failed
    schedule_date: date   # When to process (nullable = immediate)
    processed_at: datetime
    failed_count: int
    settled_at: datetime  # When settlement completed
    hold_ref: str         # CFE hold reference ID
    created_at: datetime
    updated_at: datetime
```

### BatchStatus (Enum)

```python
class BatchStatus(str, Enum):
    PENDING         = "pending"          # Created, not yet processed
    PROCESSING      = "processing"       # CFE hold placed, iterating employees
    COMPLETED       = "completed"        # All employees paid successfully
    PARTIAL_FAILURE  = "partial_failure" # Some employees failed (≤ 20 %)
    FAILED          = "failed"           # > 20 % failed, or hold failure
    SETTLED         = "settled"          # Settlement completed
```

### PayrollTransaction (per employee per batch)

```python
class PayrollTransaction:
    id: UUID
    batch_id: UUID
    employee_id: UUID
    amount: Decimal
    fee: Decimal
    status: TxStatus        # pending | success | failed | failed_permanent
    failure_reason: str | None  # "insufficient_balance" | "wallet_not_active" | "user_not_found"
    retry_count: int        # 0–3
    last_retry_at: datetime | None
    paid_at: datetime | None
    cfe_tx_ref: str | None  # CFE transaction reference
```

### TxStatus (Enum)

```python
class TxStatus(str, Enum):
    PENDING         = "pending"
    SUCCESS         = "success"
    FAILED          = "failed"
    FAILED_PERMANENT = "failed_permanent"
```

---

## 2. Core Services

### PayrollService

```python
class PayrollService:
    """
    Orchestrates the full payroll lifecycle.
    """

    async def process_batch(self, batch_id: UUID) -> BatchResult:
        """
        1. Load batch + company
        2. Verify company balance >= total_amount + fee
        3. Call CFE.hold(company_account_id, total_amount + fee)
        4. Set batch status = PROCESSING
        5. For each employee in batch:
            a. Call WalletService.credit(employee_wallet_id, amount)
            b. Record PayrollTransaction (success/fail)
            c. Increment failed_count if failed
        6. Call CFE.release_hold(hold_ref, remaining_amount)
        7. Call CFE.debit_fee(company_account_id, actual_fee)
        8. Set batch status = COMPLETED or PARTIAL_FAILURE
        9. Trigger PayslipGenerator for successful transactions
        10. Trigger NotificationService for all employees
        11. Return BatchResult summary
        """

    async def retry_failed(self, batch_id: UUID, employee_ids: List[UUID]) -> RetryResult:
        """
        1. For each employee_id:
            a. Verify transaction status = FAILED
            b. Verify company balance still sufficient
            c. Call WalletService.credit(employee_wallet_id, amount)
            d. Update transaction status + retry_count
        2. Recalculate batch failed_count
        3. If all resolved -> set batch status = COMPLETED
        4. Trigger notifications for newly successful payments
        """

    async def generate_payslips(self, batch_id: UUID) -> Dict[UUID, str]:
        """
        1. Query all successful transactions for batch
        2. For each: call PayslipGenerator.generate(transaction)
        3. Return map of employee_id -> s3_key
        4. Update batch with payslip_generated_at timestamp
        """

    async def reconcile(self, company_id: UUID, month: date) -> ReconciliationReport:
        """
        1. Query all batches for company in month
        2. Aggregate: total_debited, total_fees, total_credited, failed_amounts
        3. Verify against CFE settlement records
        4. Return ReconciliationReport
        """
```

### CompanyService

```python
class CompanyService:
    """
    Manages company lifecycle & payroll account.
    """

    async def onboard(
        self,
        name_ar: str,
        name_en: str,
        license_number: str,
        tax_id: str,
        authorized_signatory: SignatoryInfo,
        documents: List[UploadedFile],
    ) -> Company:
        """
        1. Validate documents (license, ID, tax cert)
        2. AML screening on beneficial owners
        3. Create company record (status = pending_review)
        4. Notify Beza admin for manual approval
        5. On approval:
            a. Create payroll ledger account (CFE)
            b. Generate API keys (if requested)
            c. Set settlement_period
            d. Send welcome notification to company
        """

    async def credit_check(self, company_id: UUID) -> CreditCheckResult:
        """
        1. Review company history:
            - Months on platform
            - Batch success rate
            - Previous settlement delays
        2. Determine T+1 eligibility
        3. Set settlement_limit (max unsettled amount)
        """

    async def settle(self, company_id: UUID, amount: Decimal) -> SettlementResult:
        """
        1. Verify company has outstanding settlement
        2. Call CFE.transfer(company_bank_account, beza_settlement_account, amount)
        3. Update company balance + last_settlement_at
        4. Mark relevant batches as SETTLED
        """
```

### PayslipGenerator

```python
class PayslipGenerator:
    """
    Generates PDF payslips with Arabic template.
    """

    async def generate(self, transaction: PayrollTransaction) -> str:
        """
        1. Load employee + company + batch data
        2. Render HTML template with:
            - Company logo + name (Arabic)
            - Employee name (Arabic)
            - Month / year
            - Breakdown: basic, allowances, deductions, net
            - Batch reference + transaction ID
            - Beza digital seal
            - QR code (link to verify on Beza)
        3. Convert HTML to PDF (Puppeteer)
        4. Encrypt PDF (AES-256) at rest
        5. Upload to S3: payslips/{company_id}/{batch_id}/{employee_id}.pdf
        6. Return S3 key
        """

    async def generate_batch_zip(self, batch_id: UUID) -> str:
        """
        1. Fetch all payslip S3 keys for batch
        2. Download, ZIP into single archive
        3. Upload ZIP to S3 with expiry URL (48 hours)
        4. Return download URL
        """
```

---

## 3. Integration: CFE (Core Financial Engine)

| Operation | CFE Method | Payload |
|-----------|-----------|---------|
| **Hold funds** | `POST /cfe/v1/holds` | `{ account_id, amount, reference: batch_id, expiry: 24h }` |
| **Release hold** | `DELETE /cfe/v1/holds/{hold_ref}` | — |
| **Credit wallet** | `POST /cfe/v1/wallets/{wallet_id}/credit` | `{ amount, currency, reference: tx_id }` |
| **Debit account** | `POST /cfe/v1/accounts/{account_id}/debit` | `{ amount, currency, reference: batch_id }` |
| **Check balance** | `GET /cfe/v1/accounts/{account_id}/balance` | Returns `available`, `held`, `currency` |

---

## 4. Module Layout

```
src/
  payroll/
    models/
      batch.py          # PayrollBatch ORM model
      transaction.py    # PayrollTransaction ORM model
      company.py        # PayrollCompany ORM model
      employee.py       # PayrollEmployee ORM model
      enums.py          # BatchStatus, TxStatus, SettlementPeriod
    services/
      payroll_service.py
      company_service.py
      payslip_generator.py
    api/
      routes.py         # FastAPI router
      schemas.py        # Pydantic request/response schemas
      validators.py     # CSV validation logic
    tasks/
      process_batch.py  # Celery task for async batch processing
      retry_failed.py   # Celery beat for scheduled retries
      cleanup.py        # Archive old payslips, cleanup expired holds
    notifications/
      templates.py      # Arabic notification templates
    utils/
      csv_parser.py
      pdf_renderer.py
      encryption.py
    integration/
      cfe_client.py     # HTTP client for CFE API
      sms_client.py     # SMS gateway client
```
