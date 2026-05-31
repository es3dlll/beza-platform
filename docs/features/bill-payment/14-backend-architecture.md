# Bill Payment Backend Architecture

## Module Structure (Laravel)
```
app/Modules/BillPayment/
├── Controllers/
│   ├── BillController.php            # Fetch, history, scheduled
│   ├── PaymentController.php         # Execute payment
│   └── ReminderController.php        # Set/cancel reminders
│
├── Actions/
│   ├── FetchBillAction.php           # Fetch bill from biller
│   ├── PayBillAction.php             # Orchestrate full payment
│   ├── GetBillHistoryAction.php      # Paginated history query
│   ├── ScheduleBillAction.php        # Create reminder/schedule
│   ├── CancelScheduleAction.php      # Cancel schedule
│   └── ProcessCsvBatchAction.php     # Process incoming CSV batch files
│
├── Services/
│   ├── BillPaymentService.php        # Core orchestration service
│   ├── BillerProviderService.php     # Manages all biller integrations
│   ├── BillerFactory.php             # Factory to instantiate biller by type
│   ├── BillValidationService.php     # Customer ID format validation
│   ├── ReceiptService.php            # Receipt generation (PDF + reference)
│   ├── CsvBatchService.php           # CSV file parsing & reconciliation
│   └── BillingScheduler.php          # Due date reminders + auto-pay
│
├── Contracts/
│   ├── BillerInterface.php           # Contract for all biller integrations
│   └── BillerConfigInterface.php     # Biller configuration reader
│
├── Integrations/                     # One class per biller
│   ├── PeedElectricityBiller.php      # PEED (API)
│   ├── DamascusWaterBiller.php        # Damascus Water Authority (API)
│   ├── SyriatelBiller.php             # Syriatel (API)
│   ├── MtnBiller.php                  # MTN Syria (API)
│   ├── SyriaTelecomBiller.php         # Syria Telecom (API)
│   ├── AyaInternetBiller.php          # Aya Internet (API)
│   ├── SamanInternetBiller.php        # Saman Internet (API)
│   ├── GovernmentFeesBiller.php       # Civil Affairs/Passport (CSV)
│   └── UniversityFeesBiller.php       # University tuition (CSV)
│
├── Models/
│   ├── Biller.php                     # Biller registry model
│   ├── BillTransaction.php            # Bill payment transaction
│   ├── ScheduledBill.php              # Scheduled bill/reminder
│   ├── BillerConnectionLog.php        # Biller API interaction log
│   └── CsvBatchFile.php               # Uploaded CSV batch tracking
│
├── Jobs/
│   ├── ProcessBillPaymentJob.php     # Async payment processing
│   ├── NotifyBillDueJob.php          # Send due date reminder
│   ├── ProcessAutoPayJob.php         # Execute auto-pay
│   ├── RetryFailedBillPaymentJob.php  # Retry failed payments
│   └── ProcessCsvFileJob.php          # Parse and reconcile CSV
│
├── Listeners/
│   ├── SendBillReceiptNotification.php
│   ├── LogBillerConnection.php
│   └── ReconcileCsvPayment.php
│
├── Rules/
│   ├── ValidCustomerIdFormat.php      # Biller-specific ID format
│   ├── ValidBillAmount.php
│   └── BillerSupported.php
│
├── Enums/
│   ├── BillerType.php                 # peed, water, syriatel, mtn, etc.
│   ├── BillerCategory.php             # electricity, water, telecom, etc.
│   ├── BillerInterfaceType.php        # api, csv, manual
│   ├── BillTransactionStatus.php      # pending, paid, failed, refunded
│   ├── ScheduleType.php               # once, monthly, bi_monthly
│   └── ScheduleStatus.php             # active, paused, cancelled
│
├── Exceptions/
│   ├── BillerConnectionException.php
│   ├── BillNotFoundException.php
│   ├── BillAlreadyPaidException.php
│   ├── BillerApiDownException.php
│   └── InvalidCustomerIdException.php
│
├── Providers/
│   └── BillPaymentServiceProvider.php
│
└── routes/
    └── api.php
```

## Core Contracts

### BillerInterface
```php
<?php

namespace App\Modules\BillPayment\Contracts;

use App\Modules\BillPayment\DTOs\BillDTO;
use App\Modules\BillPayment\DTOs\PaymentResultDTO;
use App\Modules\BillPayment\DTOs\StatusCheckDTO;

interface BillerInterface
{
    /**
     * Fetch bill by customer ID from the biller system.
     *
     * @param string $customerId Biller-specific customer identifier
     * @return BillDTO Bill details including amount, due date, breakdown
     * @throws BillNotFoundException|BillerConnectionException
     */
    public function fetchBill(string $customerId): BillDTO;

    /**
     * Confirm payment with the biller system.
     *
     * @param string $customerId Customer identifier
     * @param string $amount Payment amount in SYP (smallest unit)
     * @param string $reference Beza internal reference
     * @param array $extra Biller-specific parameters
     * @return PaymentResultDTO Biller confirmation with reference
     * @throws BillerConnectionException
     */
    public function confirmPayment(
        string $customerId,
        string $amount,
        string $reference,
        array $extra = []
    ): PaymentResultDTO;

    /**
     * Check payment status with the biller.
     *
     * @param string $billerReference Biller-side transaction reference
     * @return StatusCheckDTO Current status from biller
     */
    public function checkStatus(string $billerReference): StatusCheckDTO;

    /**
     * Get the biller type identifier.
     *
     * @return string
     */
    public function getBillerType(): string;

    /**
     * Check if this biller supports a given feature.
     *
     * @param string $feature fetch, pay, status_check, auto_pay, partial_pay
     * @return bool
     */
    public function supportsFeature(string $feature): bool;
}
```

### BillDTO
```php
<?php

namespace App\Modules\BillPayment\DTOs;

use Carbon\Carbon;

class BillDTO
{
    public function __construct(
        public readonly string $customerId,
        public readonly string $customerName,
        public readonly string $customerAddress,
        public readonly string $billerType,
        public readonly string $billerName,
        public readonly string $invoiceNumber,
        public readonly string $billingPeriod,
        public readonly int $amount,              // In SYP (smallest unit)
        public readonly int $lateFee,             // Late payment fee
        public readonly int $totalDue,            // amount + lateFee
        public readonly ?int $vat,                // VAT if applicable
        public readonly Carbon $dueDate,
        public readonly array $breakdown,         // Itemized charges
        public readonly string $billerReference,
        public readonly ?Carbon $paymentDate,     // If already paid
        public readonly bool $isPaid,
    ) {}
}
```

### PaymentResultDTO
```php
<?php

namespace App\Modules\BillPayment\DTOs;

class PaymentResultDTO
{
    public function __construct(
        public readonly string $billerReference,  // Biller-side confirmation ref
        public readonly string $bezaReference,    // Beza-side reference
        public readonly string $status,           // completed, pending, failed
        public readonly ?string $failureReason,
        public readonly int $amount,
        public readonly array $rawResponse,       // Full biller response for audit
    ) {}
}
```

### StatusCheckDTO
```php
<?php

namespace App\Modules\BillPayment\DTOs;

class StatusCheckDTO
{
    public function __construct(
        public readonly string $billerReference,
        public readonly string $status,           // confirmed, pending, failed, refunded
        public readonly ?string $message,
        public readonly ?Carbon $confirmedAt,
    ) {}
}
```

## Service Layer Detail

### BillerProviderService
```php
class BillerProviderService
{
    public function __construct(
        private BillerFactory $factory,
        private BillValidationService $validator,
        private Repository $billerRepo,
    ) {}

    public function getBiller(string $billerType): BillerInterface
    {
        return $this->factory->make($billerType);
    }

    public function getAllActiveBillers(): Collection
    {
        return $this->billerRepo->findByStatus('active');
    }

    public function getBillersByCategory(string $category): Collection
    {
        return $this->billerRepo->findByCategory($category);
    }

    public function validateCustomerId(string $billerType, string $customerId): bool
    {
        return $this->validator->validate($billerType, $customerId);
    }

    public function getCustomerIdFormat(string $billerType): array
    {
        return $this->validator->getFormat($billerType);
    }
}
```

### BillerFactory
```php
class BillerFactory
{
    public function make(string $billerType): BillerInterface
    {
        return match ($billerType) {
            'peed' => app(PeedElectricityBiller::class),
            'damascus_water' => app(DamascusWaterBiller::class),
            'syriatel' => app(SyriatelBiller::class),
            'mtn' => app(MtnBiller::class),
            'syria_telecom' => app(SyriaTelecomBiller::class),
            'aya_internet' => app(AyaInternetBiller::class),
            'saman_internet' => app(SamanInternetBiller::class),
            'government_fees' => app(GovernmentFeesBiller::class),
            'university_fees' => app(UniversityFeesBiller::class),
            default => throw new InvalidBillerException("Unsupported biller: $billerType"),
        };
    }

    public function getAllTypes(): array
    {
        return [
            'peed', 'damascus_water', 'syriatel', 'mtn', 'syria_telecom',
            'aya_internet', 'saman_internet', 'government_fees', 'university_fees',
        ];
    }
}
```

### BillPaymentService (Orchestration)
```php
class BillPaymentService
{
    public function __construct(
        private BillerProviderService $billerProvider,
        private WalletService $walletService,
        private TransactionRepository $txnRepo,
        private ReceiptService $receiptService,
        private EventService $eventService,
        private CfeService $cfe,
        private FeeService $feeService,
    ) {}

    /**
     * Fetch a bill from the biller.
     */
    public function fetchBill(string $billerType, string $customerId): BillDTO
    {
        $biller = $this->billerProvider->getBiller($billerType);
        $bill = $biller->fetchBill($customerId);

        // Log the interaction
        BillerConnectionLog::log($billerType, 'fetch', $customerId, true);

        return $bill;
    }

    /**
     * Pay a bill - full orchestrated flow.
     */
    public function payBill(PayBillRequest $request): PaymentResult
    {
        DB::beginTransaction();
        try {
            // 1. Get biller integration
            $biller = $this->billerProvider->getBiller($request->billerType);

            // 2. Re-fetch bill to get current amount (prevent stale data)
            $bill = $biller->fetchBill($request->customerId);

            if ($bill->isPaid) {
                throw new BillAlreadyPaidException();
            }

            // 3. Calculate Beza fee
            $fee = $this->feeService->calculateBillPaymentFee(
                $bill->totalDue, $request->billerType
            );

            // 4. Verify wallet balance
            $wallet = $this->walletService->getOrCreateWallet(
                $request->user, Currency::SYP
            );
            $this->cfe->checkSufficientBalance(
                $wallet->cfe_account_id, $bill->totalDue + $fee
            );

            // 5. Execute CFE hold + post
            $bezaReference = 'BILL-' . strtoupper($request->billerType)
                . '-' . now()->format('Ymd') . '-' . Str::random(10);
            $cfeResult = $this->cfe->executeHoldAndPost(
                accountId: $wallet->cfe_account_id,
                amount: $bill->totalDue + $fee,
                reference: $bezaReference,
            );

            // 6. Confirm with biller
            $paymentResult = $biller->confirmPayment(
                customerId: $request->customerId,
                amount: (string) $bill->totalDue,
                reference: $bezaReference,
                extra: ['invoice_number' => $bill->invoiceNumber],
            );

            // 7. Save transaction
            $transaction = $this->txnRepo->create([
                'user_id' => $request->user->id,
                'biller_id' => $this->getBillerId($request->billerType),
                'biller_type' => $request->billerType,
                'customer_id' => $request->customerId,
                'customer_name' => $bill->customerName,
                'bill_amount' => $bill->totalDue,
                'fee' => $fee,
                'total' => $bill->totalDue + $fee,
                'reference' => $bezaReference,
                'biller_reference' => $paymentResult->billerReference,
                'invoice_number' => $bill->invoiceNumber,
                'status' => BillTransactionStatus::PAID,
                'paid_at' => now(),
                'cfe_reference' => $cfeResult->reference,
                'wallet_balance_after' => $cfeResult->balanceAfter,
            ]);

            // 8. Generate receipt
            $receipt = $this->receiptService->generate($transaction, $bill);

            // 9. Emit events
            $this->eventService->emitBillPaid($transaction, $receipt);
            $this->eventService->emitWalletDebited(
                $request->user, $bill->totalDue + $fee, 'bill_payment'
            );

            DB::commit();

            return new PaymentResult(
                transactionId: $transaction->id,
                bezaReference: $bezaReference,
                billerReference: $paymentResult->billerReference,
                status: BillTransactionStatus::PAID,
                receipt: $receipt,
            );
        } catch (BillerConnectionException $e) {
            DB::rollBack();
            BillerConnectionLog::log($request->billerType, 'pay', $request->customerId, false, $e->getMessage());
            // Reverse CFE hold if it was placed
            if (isset($cfeResult)) {
                $this->cfe->releaseHold($cfeResult->holdId);
            }
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function getBillerId(string $billerType): int
    {
        return Biller::where('type', $billerType)->value('id');
    }
}
```

### BillingScheduler
```php
class BillingScheduler
{
    public function __construct(
        private ScheduledBillRepository $scheduleRepo,
        private BillPaymentService $paymentService,
        private NotificationService $notifier,
    ) {}

    /**
     * Process due reminders — called by scheduler every hour.
     */
    public function processDueReminders(): void
    {
        $dueBills = $this->scheduleRepo->findDueForReminder(now());

        foreach ($dueBills as $scheduled) {
            $this->notifier->sendBillDueReminder(
                $scheduled->user,
                $scheduled->billerType,
                $scheduled->customerId,
                $scheduled->nextDue,
            );
            $scheduled->last_reminded_at = now();
            $scheduled->save();
        }
    }

    /**
     * Process auto-pay — called daily at 08:00.
     */
    public function processAutoPay(): void
    {
        $autoPayBills = $this->scheduleRepo->findDueForAutoPay(now());

        foreach ($autoPayBills as $scheduled) {
            try {
                $bill = $this->paymentService->fetchBill(
                    $scheduled->biller_type,
                    $scheduled->customer_id,
                );

                if ($bill->isPaid) {
                    $scheduled->autoPayStatus = 'already_paid';
                    $scheduled->save();
                    continue;
                }

                ProcessAutoPayJob::dispatch($scheduled);
            } catch (\Exception $e) {
                Log::error("Auto-pay failed for schedule {$scheduled->id}: {$e->getMessage()}");
                $scheduled->autoPayStatus = 'failed';
                $scheduled->last_error = $e->getMessage();
                $scheduled->save();
                $this->notifier->sendAutoPayFailed(
                    $scheduled->user, $scheduled->billerType, $scheduled->customerId
                );
            }
        }
    }
}
```

### ReceiptService
```php
class ReceiptService
{
    public function generate(BillTransaction $transaction, BillDTO $bill): Receipt
    {
        $receiptData = [
            'beza_reference' => $transaction->reference,
            'biller_reference' => $transaction->biller_reference,
            'invoice_number' => $bill->invoiceNumber,
            'biller_name_ar' => $bill->billerName,
            'customer_name' => $bill->customerName,
            'customer_id' => $bill->customerId,
            'customer_address' => $bill->customerAddress,
            'billing_period' => $bill->billingPeriod,
            'amount' => $bill->totalDue,
            'fee' => $transaction->fee,
            'total' => $transaction->total,
            'paid_at' => $transaction->paid_at->format('Y-m-d H:i:s'),
            'payment_method' => 'Beza Wallet',
        ];

        // Generate PDF receipt and store
        $pdfPath = $this->pdfGenerator->generate(
            'bill-payment::receipt',
            $receiptData,
            "receipt_{$transaction->reference}.pdf"
        );

        $url = Storage::disk('s3')->url($pdfPath);

        $transaction->update(['receipt_url' => $url]);

        return new Receipt(
            reference: $transaction->reference,
            url: $url,
            generatedAt: now(),
        );
    }
}
```

### CsvBatchService
```php
class CsvBatchService
{
    public function processUploadedFile(SplFileInfo $file, string $billerType): CsvBatchFile
    {
        $batch = CsvBatchFile::create([
            'biller_type' => $billerType,
            'filename' => $file->getFilename(),
            'status' => 'processing',
            'total_records' => 0,
        ]);

        ProcessCsvFileJob::dispatch($batch->id, Storage::path($file->getPathname()));

        return $batch;
    }

    public function parseAndStore(string $filePath, int $batchId): void
    {
        $rows = array_map('str_getcsv', file($filePath));
        $header = array_shift($rows);

        // Expected CSV columns vary by biller:
        // PEED CSV: customer_id, invoice_number, amount, due_date, billing_period, customer_name
        // Government CSV: national_id, fee_type, amount, reference_number, ministry
        // University CSV: student_id, university, semester, amount, due_date

        $billableItems = [];
        foreach ($rows as $row) {
            $billableItems[] = [
                'csv_batch_file_id' => $batchId,
                'customer_id' => $row[0],
                'reference' => $row[3] ?? null,
                'amount' => (int) $row[2],
                'due_date' => $row[4] ?? null,
                'metadata' => json_encode(array_combine($header, $row)),
                'status' => 'pending',
            ];
        }

        CsvBillableItem::insert($billableItems);

        CsvBatchFile::where('id', $batchId)->update([
            'status' => 'ready',
            'total_records' => count($billableItems),
        ]);

        // Notify users whose customer IDs match billable items
        $this->notifyMatchingUsers($batchId);
    }

    private function notifyMatchingUsers(int $batchId): void
    {
        $items = CsvBillableItem::where('csv_batch_file_id', $batchId)
            ->where('status', 'pending')
            ->get();

        foreach ($items as $item) {
            $users = User::whereHas('bills', function ($q) use ($item) {
                $q->where('customer_id', $item->customer_id);
            })->get();

            foreach ($users as $user) {
                $this->notifier->sendBillAvailableNotification(
                    $user, $item->customer_id, $item->amount, $item->due_date
                );
            }
        }
    }
}
```

## Currently Supported Billers
| Biller Type | Biller Name (Ar) | Interface | Customer ID Format | Features |
|------------|------------------|-----------|-------------------|----------|
| peed | الشركة العامة للكهرباء (PEED) | API | 24 digits (XXXX-XXXX-XXXX-XXXX-XXXX) | fetch, pay, status |
| damascus_water | مؤسسة مياه الشرب والصرف الصحي بدمشق | API | 10 digits | fetch, pay, status |
| syriatel | سيريتل | API | 10 digits (mobile) / 7 digits (fixed) | fetch, pay, status |
| mtn | إم تي إن | API | 10 digits (mobile) / 7 digits (fixed) | fetch, pay, status |
| syria_telecom | الاتصالات (الخط الأرضي + ADSL) | API | 7 digits (phone number) | fetch, pay, status |
| aya_internet | آية للإنترنت | API | 8 digits (subscription code) | fetch, pay, status |
| saman_internet | سامان للإنترنت | API | 8 digits (subscription code) | fetch, pay, status |
| government_fees | الرسوم الحكومية (الأحوال المدنية، الجوازات، العدل) | CSV | 16 digits (national ID) | fetch (from CSV), pay |
| university_fees | الرسوم الجامعية (جامعة دمشق، جامعة الشام) | CSV | 12 digits (student ID) | fetch (from CSV), pay |
