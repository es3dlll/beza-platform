# Government Collections Domain Models

## Entity Definitions

### GovernmentBiller
```php
class GovernmentBiller
{
    public function __construct(
        public readonly string $id,
        public readonly string $code,            // MOF, MOI, TRAF, COURT
        public string $nameAr,                    // وزارة المالية
        public ?string $nameEn,
        public string $type,                      // ministry, department, municipality, university
        public array $serviceTypes,               // ['tax_income', 'tax_property', ...]
        public string $integrationType,           // api, file_batch, portal, manual
        public string $adapterClass,
        public float $feePercentage,              // 0.50 = 0.5%
        public string $settlementMethod,          // wire, batch_api, file_based, manual
        public bool $isActive,
        public ?array $metadata,
    ) {}

    public function supportsService(string $serviceType): bool
    {
        return in_array($serviceType, $this->serviceTypes);
    }
}
```

### GovernmentTransaction
```php
class GovernmentTransaction
{
    public function __construct(
        public readonly string $id,
        public readonly string $transactionId,     // gov_txn_{ulid}
        public readonly string $billerId,
        public string $serviceType,                 // tax_income, passport, tuition, etc.
        public string $billerReference,             // Tax ID, passport app#, student ID
        public ?string $billerObligationId,         // Ministry's obligation reference

        public int $amount,                         // Amount due to ministry
        public int $bezaFee,
        public int $penalty,
        public int $discount,
        public int $totalCharged,                   // amount + fee + penalty - discount

        public string $currency,                    // SYP
        public string $status,                      // initiated, pending, completed, failed, settled
        public ?string $failureReason,
        public ?string $failureCode,

        public bool $ministryConfirmed,
        public ?string $ministryReference,
        public ?string $ministryConfirmedAt,

        public ?string $walletTransactionId,
        public string $settlementStatus,            // pending, settled, failed
        public ?string $settledAt,

        public ?string $receiptRef,
        public ?string $receiptHash,

        public string $createdAt,
        public string $updatedAt,
    ) {}

    public function isSuccessful(): bool
    {
        return $this->status === 'completed' && $this->ministryConfirmed;
    }

    public function isSettled(): bool
    {
        return $this->settlementStatus === 'settled';
    }

    public function canRetry(): bool
    {
        return in_array($this->status, ['failed', 'pending_minitry'])
            && !$this->ministryConfirmed;
    }
}
```

### GovernmentReceipt
```php
class GovernmentReceipt
{
    public function __construct(
        public readonly string $id,
        public readonly string $transactionId,
        public readonly string $receiptRef,          // GOV-YYYY-MMDD-XXXX

        public string $receiptType,                  // payment, refund, void
        public string $billerNameAr,
        public string $serviceNameAr,
        public ?string $payerName,
        public ?string $payerIdNumber,
        public int $amountPaid,
        public int $feePaid,
        public int $totalPaid,
        public string $currency,

        public string $ministryNameAr,
        public ?string $ministryReference,

        public string $receiptHash,                  // SHA-256
        public string $qrData,
        public string $qrGeneratedAt,

        public ?string $pdfPath,
        public ?string $pdfGeneratedAt,

        public string $verificationUrl,
        public bool $isRevoked,
        public ?string $revokedAt,
        public ?string $revokedReason,

        public string $generatedAt,
    ) {}

    public function verifyIntegrity(): bool
    {
        $expectedHash = hash('sha256', $this->getVerificationData());
        return hash_equals($expectedHash, $this->receiptHash);
    }

    private function getVerificationData(): string
    {
        return implode('|', [
            $this->receiptRef,
            $this->totalPaid,
            $this->currency,
            $this->ministryReference ?? '',
            $this->generatedAt,
        ]);
    }
}
```

### GovernmentReconciliation
```php
class GovernmentReconciliation
{
    public function __construct(
        public readonly string $id,
        public readonly string $billerId,
        public string $periodStart,
        public string $periodEnd,
        public string $reconciliationDate,

        public int $bezaTotal,
        public int $ministryTotal,
        public int $variance,
        public float $variancePct,

        public int $bezaCount,
        public int $ministryCount,
        public int $matchedCount,
        public int $mismatchedCount,
        public int $missingFromBeza,
        public int $missingFromMinistry,

        public string $status,                       // pending, matched, has_mismatches, resolved
        public ?string $resolutionNotes,
        public bool $autoResolved,
    ) {}

    public function isPerfectMatch(): bool
    {
        return $this->variance === 0
            && $this->mismatchedCount === 0
            && $this->missingFromBeza === 0
            && $this->missingFromMinistry === 0;
    }
}
```
