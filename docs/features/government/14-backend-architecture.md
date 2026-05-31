# Government Collections Backend Architecture

## Module Structure (Laravel)
```
app/Modules/GovernmentCollect/
├── Controllers/
│   ├── TaxController.php              # Tax query + pay
│   ├── FineController.php             # Traffic fine + court fee query + pay
│   ├── PassportController.php         # Passport fee payment
│   ├── TuitionController.php          # University tuition payment
│   ├── VehicleController.php          # Vehicle registration payment
│   ├── MunicipalityController.php     # Municipality fee payment
│   ├── CivilRegistryController.php    # Civil registry fee payment
│   ├── ReceiptController.php          # Receipt generation + verification
│   ├── PaymentHistoryController.php   # Payment history
│   ├── BillerController.php           # Biller management
│   └── ReconciliationController.php   # Reconciliation operations
│
├── Actions/
│   ├── QueryTaxAction.php             # Query ministry for tax obligations
│   ├── PayTaxAction.php               # Execute tax payment
│   ├── QueryFineAction.php            # Query traffic/court fines
│   ├── PayFineAction.php              # Execute fine payment
│   ├── QueryTuitionAction.php         # Query university tuition
│   ├── PayTuitionAction.php           # Execute tuition payment
│   ├── QueryPassportAction.php        # Query passport application fee
│   ├── PayPassportAction.php          # Execute passport payment
│   ├── PayVehicleAction.php           # Execute vehicle registration payment
│   ├── PayMunicipalityAction.php      # Execute municipality fee payment
│   ├── PayCivilRegistryAction.php     # Execute civil registry payment
│   ├── GenerateReceiptAction.php      # Generate official receipt
│   ├── VerifyReceiptAction.php        # Verify receipt QR/hash
│   └── RunReconciliationAction.php    # Daily reconciliation
│
├── Services/
│   ├── GovPaymentGatewayService.php   # Central e-payment gateway integration
│   ├── MinistryIntegrator.php         # Abstract ministry connection manager
│   ├── TaxPaymentService.php          # Tax bill query + payment orchestration
│   ├── FinePaymentService.php         # Traffic fine + court fee orchestration
│   ├── TuitionPaymentService.php      # University fee orchestration
│   ├── PassportPaymentService.php     # Passport fee orchestration
│   ├── VehiclePaymentService.php      # Vehicle fee orchestration
│   ├── MunicipalityPaymentService.php # Municipality fee orchestration
│   ├── CivilRegistryService.php       # Civil registry fee orchestration
│   ├── InvoiceService.php             # Official government receipt generator
│   ├── ReconciliationService.php      # Match Beza payments to government records
│   ├── ReceiptVerificationService.php # QR + hash receipt validation
│   └── BillerRegistryService.php      # Ministry/department registry
│
├── Integrations/
│   ├── MinistryOfFinanceAdapter.php   # Tax ministry API adapter
│   ├── MinistryOfInteriorAdapter.php  # Passport/civil registry adapter
│   ├── TrafficAuthorityAdapter.php    # Traffic fines adapter
│   ├── CourtSystemAdapter.php         # Court fees adapter
│   ├── UniversityPortalAdapter.php    # University tuition adapter
│   ├── MunicipalityPortalAdapter.php  # Municipality fees adapter
│   ├── CentralGatewayAdapter.php      # Syria central e-payment gateway
│   ├── SoapClient.php                 # SOAP client for legacy gov systems
│   └── FileBasedSyncAdapter.php       # File-based sync for offline ministries
│
├── Repositories/
│   ├── GovernmentTransactionRepository.php
│   ├── GovernmentReceiptRepository.php
│   ├── GovernmentBillerRepository.php
│   ├── GovernmentReconciliationRepository.php
│   └── SavedPayerRepository.php
│
├── Models/
│   ├── GovernmentBiller.php           # Ministry/department
│   ├── GovernmentTransaction.php      # Payment transaction
│   ├── GovernmentReceipt.php          # Official receipt
│   └── GovernmentReconciliation.php   # Reconciliation record
│
├── Policies/
│   ├── GovernmentTransactionPolicy.php
│   └── GovernmentReceiptPolicy.php
│
├── Events/
│   ├── GovernmentPaymentInitiated.php
│   ├── GovernmentPaymentCompleted.php
│   ├── GovernmentPaymentFailed.php
│   ├── GovernmentReceiptGenerated.php
│   ├── GovernmentReconciliationCompleted.php
│   └── SettlementToMinistryCompleted.php
│
├── Jobs/
│   ├── ProcessMinistrySettlement.php     # Batch settlement to ministries
│   ├── RunDailyReconciliation.php        # Daily reconciliation job
│   ├── NotifyUpcomingDeadline.php        # Deadline reminder notifications
│   ├── SyncMinistryStatuses.php          # Sync payment statuses from ministries
│   └── RetryFailedPayment.php            # Automatic retry for failed payments
│
├── Listeners/
│   ├── SendPaymentConfirmationNotification.php
│   ├── UpdateReconciliationStatus.php
│   ├── LogPaymentForAnalytics.php
│   └── NotifyMinistryOfPayment.php
│
├── Rules/
│   ├── ValidTaxId.php
│   ├── ValidPassportNumber.php
│   ├── ValidStudentId.php
│   ├── ValidLicensePlate.php
│   └── ValidMinistryCode.php
│
├── Enums/
│   ├── GovernmentServiceType.php        # tax, fine, passport, tuition, vehicle, etc.
│   ├── PaymentStatus.php                # initiated, pending, completed, failed, settled
│   ├── ReconciliationStatus.php         # matched, mismatched, pending, resolved
│   └── SettlementMethod.php             # wire, batch_api, file_based, manual
│
├── Exceptions/
│   ├── MinistryConnectionException.php
│   ├── TaxIdNotFoundException.php
│   ├── PaymentTimeoutException.php
│   ├── ReconciliationMismatchException.php
│   ├── DuplicatePaymentException.php
│   └── InsufficientBalanceException.php
│
├── Providers/
│   └── GovernmentCollectServiceProvider.php
│
├── Console/
│   └── Commands/
│       ├── RunDailyReconciliation.php       # Cron: daily at 02:00
│       ├── SyncPaymentStatuses.php          # Cron: every 30 min
│       ├── RetryFailedPayments.php          # Cron: every hour
│       └── SendDeadlineReminders.php        # Cron: daily at 09:00
│
└── routes/
    └── api.php
```

## Key Service Implementations

### GovPaymentGatewayService
```php
class GovPaymentGatewayService
{
    // Integration with Syria's central e-payment system (if exists)
    // or ministry-specific direct integrations

    public function __construct(
        private MinistryIntegrator $integrator,
        private GovernmentTransactionRepository $txnRepo,
    ) {}

    public function query(string $serviceType, string $referenceId): QueryResult
    {
        $adapter = $this->integrator->getAdapter($serviceType);
        return $adapter->queryObligations($referenceId);
    }

    public function pay(
        string $serviceType,
        string $referenceId,
        int $amount,
        string $idempotencyKey
    ): PaymentResult {
        // 1. Reserve amount in Beza wallet
        // 2. Call ministry adapter to confirm
        // 3. If confirmed, settle
        // 4. Generate receipt
        // 5. Return result
    }
}
```

### TaxPaymentService
```php
class TaxPaymentService
{
    public function queryTaxBill(string $taxId): TaxBillResult
    {
        // 1. Validate tax ID format (10-digit Syrian tax ID)
        // 2. Call Ministry of Finance adapter
        // 3. Return outstanding obligations with penalties
    }

    public function payTax(PayTaxRequest $request): PaymentResult
    {
        // 1. Verify amount matches ministry quote
        // 2. Debit user wallet
        // 3. Settle to Ministry of Finance
        // 4. Generate receipt
        // 5. Emit event
    }
}
```

### ReconciliationService
```php
class ReconciliationService
{
    public function runDaily(): ReconciliationSummary
    {
        // 1. Fetch Beza payments since last reconciliation
        // 2. Fetch ministry records (via API or file)
        // 3. Match by reference number, amount, date
        // 4. Flag mismatches
        // 5. Generate reconciliation report
    }

    public function matchPayment(string $bezaRef, string $ministryRef): MatchResult
    {
        // Compare amounts, dates, reference numbers
        // Return match confidence score
    }
}
```

### InvoiceService
```php
class InvoiceService
{
    public function generate(GovernmentTransaction $txn): GovernmentReceipt
    {
        // 1. Generate unique receipt reference (GOV-YYYY-MMDD-XXXX)
        // 2. Compute SHA-256 hash of receipt data
        // 3. Generate QR code content (URL to verify receipt)
        // 4. Create PDF receipt
        // 5. Store receipt record
        // 6. Return receipt
    }

    public function verify(string $receiptRef, string $qrData): bool
    {
        // 1. Look up receipt by reference
        // 2. Recompute hash and compare
        // 3. Check receipt not revoked
        // 4. Return verification result
    }
}
```
