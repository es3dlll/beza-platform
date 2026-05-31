# Remittance Backend Architecture

## Module Structure (Laravel)
```
app/Modules/Remittance/
├── Controllers/
│   ├── RemittanceController.php       # Send, receive, status
│   ├── BeneficiaryController.php      # CRUD beneficiaries
│   ├── RecurringController.php        # CRUD recurring transfers
│   ├── RequestMoneyController.php     # Request and respond
│   ├── CorridorController.php         # Admin: corridor management
│   └── FXRateController.php           # FX rate endpoints
│
├── Actions/
│   ├── SendRemittanceAction.php       # Full remittance orchestration
│   ├── ReceiveRemittanceAction.php    # Handle incoming (auto-create wallet)
│   ├── CancelTransferAction.php       # Cancel within 30-min window
│   ├── ExecuteRecurringAction.php     # Cron: execute due recurrings
│   ├── RequestMoneyAction.php         # Create payment request
│   ├── RespondToRequestAction.php     # Accept/decline request
│   ├── LockFXRateAction.php           # Lock rate for 60 seconds
│   └── ScreenBeneficiaryAction.php    # Sanctions screening on add
│
├── Services/
│   ├── RemittanceService.php          # Core remittance operations
│   ├── FXService.php                  # FX rate calculation + lock
│   ├── CorridorService.php            # Corridor limits, rules, config
│   ├── FeeService.php                 # Fee calculation engine
│   ├── BeneficiaryService.php         # Beneficiary CRUD + screening
│   ├── RecurringService.php           # Recurring execution engine
│   ├── ComplianceService.php          # AML screening per corridor
│   └── RemittanceEventService.php     # Event emission
│
├── Repositories/
│   ├── RemittanceRepository.php       # Transfer CRUD
│   ├── BeneficiaryRepository.php      # Beneficiary CRUD
│   ├── RecurringRepository.php        # Recurring CRUD + due query
│   ├── CorridorRepository.php         # Corridor config
│   ├── TransferRequestRepository.php  # Money requests
│   └── FXRateRepository.php           # FX rate logs
│
├── Models/
│   ├── Remittance.php                 # Transfer model
│   ├── Beneficiary.php                # Beneficiary model
│   ├── RecurringTransfer.php          # Recurring transfer model
│   ├── RemittanceCorridor.php         # Corridor model
│   ├── TransferRequest.php            # Money request model
│   └── FXRateLog.php                  # FX rate lock log
│
├── Policies/
│   ├── RemittancePolicy.php           # Authorization rules
│   ├── BeneficiaryPolicy.php          # Beneficiary access
│   └── RecurringPolicy.php            # Recurring transfer auth
│
├── Events/
│   ├── TransferSent.php               # Sent event
│   ├── TransferReceived.php           # Received event
│   ├── TransferFailed.php             # Failed event
│   ├── RecurringTransferExecuted.php  # Recurring executed
│   ├── RemittanceCompleted.php        # Full remittance complete
│   ├── FXLocked.php                   # Rate locked
│   └── MoneyRequested.php             # Money request created
│
├── Jobs/
│   ├── ExecuteRecurringTransfers.php  # Cron: process due recurrings
│   ├── ScreenTransferCompliance.php   # Async AML/sanctions check
│   ├── NotifyRemittanceRecipient.php  # Push/SMS to recipient
│   ├── NotifySenderDelivery.php       # Push to sender: delivered
│   ├── RetryFailedRecurring.php       # Retry failed recurrings
│   └── SyncFXRateToAnalytics.php      # Rate analytics
│
├── Rules/
│   ├── ValidRemittanceAmount.php
│   ├── CorridorActiveRule.php
│   ├── BeneficiaryOwnershipRule.php
│   └── SenderCountryRule.php
│
├── Enums/
│   ├── RemittanceStatus.php           # pending, fx_locked, completed, failed
│   ├── CorridorStatus.php             # active, maintenance, inactive
│   ├── RecurringFrequency.php         # weekly, biweekly, monthly, quarterly
│   ├── TransferType.php               # local_p2p, diaspora, recurring, request
│   └── DeliveryMethod.php             # wallet, agent_pickup, bank_deposit
│
├── Exceptions/
│   ├── InsufficientBalanceException.php
│   ├── DailyLimitExceededException.php
│   ├── CorridorInactiveException.php
│   ├── FXRateExpiredException.php
│   ├── InvalidBeneficiaryException.php
│   └── SanctionsBlockException.php
│
├── Providers/
│   └── RemittanceServiceProvider.php  # Module registration
│
└── routes/
    └── api.php                        # Route definitions
```

## Service Layer Detail

### RemittanceService
```php
class RemittanceService
{
    public function __construct(
        private RemittanceRepository $remittanceRepo,
        private FXService $fxService,
        private CorridorService $corridorService,
        private FeeService $feeService,
        private BeneficiaryService $beneficiaryService,
        private ComplianceService $complianceService,
        private WalletService $walletService,
        private EventService $eventService,
    ) {}

    public function send(SendRemittanceRequest $request): RemittanceResult
    {
        // 1. Validate corridor is active
        $corridor = $this->corridorService->getActiveCorridor(
            $request->sourceCurrency, $request->targetCurrency, $request->senderCountry
        );

        // 2. Validate sender limits (daily, monthly, per-corridor)
        $dailyUsage = $this->remittanceRepo->getDailyTotal($request->senderId, $corridor->id);
        $this->corridorService->validateLimit($dailyUsage, $request->amount, $request->sender);

        // 3. Get or lock FX rate
        $fxRate = $request->lockedRateId
            ? $this->fxService->getLockedRate($request->lockedRateId)
            : $this->fxService->getLiveRate($corridor->id);

        // 4. Calculate fee
        $fee = $this->feeService->calculateRemittanceFee(
            $request->amount, $corridor, $request->sender
        );

        // 5. Compliance screening
        $this->complianceService->screenTransfer(
            $request->sender, $request->beneficiary, $request->amount, $corridor
        );

        // 6. Debit sender (in source currency)
        $senderWallet = $this->walletService->getOrCreateWallet(
            $request->senderId, $request->sourceCurrency
        );
        $totalDebit = $request->amount + $fee;
        $debitResult = $this->walletService->debit($senderWallet->id, $totalDebit, $request->sourceCurrency);

        // 7. Convert via FX
        $targetAmount = $this->fxService->convert(
            $request->amount, $fxRate, $request->sourceCurrency, $request->targetCurrency
        );

        // 8. Credit recipient (in target currency)
        $recipientWallet = $this->walletService->getOrCreateWallet(
            $request->recipientId, $request->targetCurrency
        );
        $creditResult = $this->walletService->credit($recipientWallet->id, $targetAmount, $request->targetCurrency);

        // 9. Persist remittance record
        $remittance = $this->remittanceRepo->create([
            'sender_id' => $request->senderId,
            'beneficiary_id' => $request->beneficiaryId,
            'recipient_id' => $request->recipientId,
            'corridor_id' => $corridor->id,
            'source_amount' => $request->amount,
            'source_currency' => $request->sourceCurrency,
            'target_amount' => $targetAmount,
            'target_currency' => $request->targetCurrency,
            'fx_rate' => $fxRate->rate,
            'fee' => $fee,
            'total_debit' => $totalDebit,
            'status' => RemittanceStatus::COMPLETED,
        ]);

        // 10. Emit events
        $this->eventService->emitTransferSent($remittance);
        $this->eventService->emitRemittanceCompleted($remittance);

        // 11. Return result
        return new RemittanceResult(
            remittanceId: $remittance->id,
            status: RemittanceStatus::COMPLETED,
            targetAmount: $targetAmount,
            fxRate: $fxRate->rate,
            fee: $fee,
        );
    }
}
```

## API Endpoints
```php
// Remittance Module Routes (prefix: /api/v1/remittance)

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    // Send
    Route::post('/send', [RemittanceController::class, 'send']);
    Route::get('/send/{id}', [RemittanceController::class, 'detail']);
    Route::post('/send/{id}/cancel', [RemittanceController::class, 'cancel']);
    Route::get('/history', [RemittanceController::class, 'history']);
    Route::get('/history/export', [RemittanceController::class, 'export']);

    // FX
    Route::get('/fx/rate/{corridor}', [FXRateController::class, 'liveRate']);
    Route::post('/fx/lock', [FXRateController::class, 'lockRate']);

    // Beneficiaries
    Route::get('/beneficiaries', [BeneficiaryController::class, 'index']);
    Route::post('/beneficiaries', [BeneficiaryController::class, 'store']);
    Route::put('/beneficiaries/{id}', [BeneficiaryController::class, 'update']);
    Route::delete('/beneficiaries/{id}', [BeneficiaryController::class, 'destroy']);

    // Recurring
    Route::get('/recurring', [RecurringController::class, 'index']);
    Route::post('/recurring', [RecurringController::class, 'store']);
    Route::put('/recurring/{id}', [RecurringController::class, 'update']);
    Route::post('/recurring/{id}/pause', [RecurringController::class, 'pause']);
    Route::post('/recurring/{id}/resume', [RecurringController::class, 'resume']);
    Route::delete('/recurring/{id}', [RecurringController::class, 'destroy']);

    // Money Requests
    Route::post('/request', [RequestMoneyController::class, 'create']);
    Route::get('/request/pending', [RequestMoneyController::class, 'pending']);
    Route::post('/request/{id}/accept', [RequestMoneyController::class, 'accept']);
    Route::post('/request/{id}/decline', [RequestMoneyController::class, 'decline']);
    Route::delete('/request/{id}', [RequestMoneyController::class, 'cancel']);

    // Corridors
    Route::get('/corridors', [CorridorController::class, 'index']);
    Route::get('/corridors/{id}', [CorridorController::class, 'show']);
});

// Admin-only
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('/admin/remittance')->group(function () {
    Route::post('/corridors', [CorridorController::class, 'store']);
    Route::put('/corridors/{id}', [CorridorController::class, 'update']);
    Route::post('/corridors/{id}/toggle', [CorridorController::class, 'toggleStatus']);
    Route::get('/compliance/queue', [ComplianceController::class, 'queue']);
    Route::post('/compliance/queue/{id}/review', [ComplianceController::class, 'review']);
});
```
