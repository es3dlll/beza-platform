# Merchant Backend Architecture

## Module Structure (Laravel)
```
app/Modules/Merchant/
├── Controllers/
│   ├── MerchantController.php        # Registration, profile, settings
│   ├── QrController.php              # QR code generation, serving
│   ├── PaymentLinkController.php     # Payment link CRUD
│   ├── PosController.php             # POS terminal pairing, sync
│   ├── TransactionController.php     # Merchant transaction history
│   ├── SettlementController.php      # Settlement queries, reports
│   └── WebhookController.php         # Webhook configuration, test
│
├── Actions/
│   ├── RegisterMerchantAction.php    # Full registration orchestration
│   ├── VerifyMerchantAction.php      # License + photo verification
│   ├── GenerateQrAction.php          # Static/dynamic QR generation
│   ├── CreatePaymentLinkAction.php   # Link creation with expiry
│   ├── PairPosTerminalAction.php     # Terminal pairing with cert
│   ├── ProcessQrPaymentAction.php    # QR payment orchestration
│   ├── ProcessLinkPaymentAction.php  # Payment link payment
│   ├── CalculateSettlementAction.php # Daily settlement batch
│   └── DeliverWebhookAction.php      # Webhook delivery with retry
│
├── Services/
│   ├── MerchantService.php           # CRUD, verification, tier mgmt
│   ├── QrService.php                 # QR generation (static/dynamic)
│   ├── PaymentLinkService.php        # Create, expire, track links
│   ├── PosService.php                # Terminal pairing, txn sync
│   ├── SettlementService.php         # Batch calc, net settlement, MDR
│   ├── MerchantWebhookService.php    # Webhook delivery, signature
│   ├── MdrService.php                # MDR rate calculation by tier
│   └── MerchantEventService.php      # Event emission
│
├── Repositories/
│   ├── MerchantRepository.php        # Merchant CRUD
│   ├── MerchantQrRepository.php      # QR code queries
│   ├── PaymentLinkRepository.php     # Link queries
│   ├── PosTerminalRepository.php     # Terminal queries
│   ├── MerchantTransactionRepository.php
│   └── SettlementRepository.php      # Settlement queries
│
├── Models/
│   ├── Merchant.php                  # Merchant model
│   ├── MerchantQrCode.php            # QR code record
│   ├── MerchantPaymentLink.php       # Payment link
│   ├── MerchantPosTerminal.php       # POS terminal
│   ├── MerchantTransaction.php       # Merchant-specific view
│   └── MerchantSettlement.php        # Settlement record
│
├── Policies/
│   ├── MerchantPolicy.php            # Merchant authz
│   └── TransactionPolicy.php         # Transaction authz
│
├── Events/
│   ├── MerchantRegistered.php
│   ├── MerchantVerified.php
│   ├── QrPaymentCompleted.php
│   ├── PaymentLinkPaid.php
│   ├── MerchantSettled.php
│   └── MerchantPayoutFailed.php
│
├── Jobs/
│   ├── ProcessSettlementJob.php      # Daily batch settlement
│   ├── DeliverWebhookJob.php         # Webhook delivery
│   ├── ExpirePaymentLinksJob.php     # Expire stale links
│   ├── NotifyMerchantPaymentJob.php  # Push/SMS notification
│   └── SyncPosTransactionsJob.php    # POS txn sync
│
├── Listeners/
│   ├── SendMerchantPaymentNotification.php
│   ├── LogMerchantActivity.php
│   ├── TriggerWebhookDelivery.php
│   └── UpdateMerchantAnalytics.php
│
├── Rules/
│   ├── ValidBusinessLicense.php
│   ├── ValidMerchantAmount.php
│   └── ValidPosSerialNumber.php
│
├── Enums/
│   ├── BusinessType.php              # grocery, restaurant, retail, etc.
│   ├── MerchantStatus.php            # pending, verified, suspended, closed
│   ├── MerchantTier.php              # micro, small, mid, enterprise
│   ├── QrType.php                    # static, dynamic
│   ├── QrStatus.php                  # active, inactive, expired
│   ├── PaymentLinkStatus.php         # pending, paid, expired
│   ├── PosTerminalStatus.php         # active, inactive, lost
│   └── SettlementStatus.php          # pending, processing, completed
│
├── Exceptions/
│   ├── MerchantNotFoundException.php
│   ├── QrNotFoundException.php
│   ├── PaymentLinkExpiredException.php
│   ├── PaymentLinkAlreadyPaidException.php
│   ├── PosPairingFailedException.php
│   ├── SettlementFailedException.php
│   └── WebhookDeliveryFailedException.php
│
├── Providers/
│   └── MerchantServiceProvider.php   # Module registration
│
└── routes/
    └── api.php                       # Route definitions
```

## Service Layer Detail

### MerchantService
```php
class MerchantService
{
    public function __construct(
        private MerchantRepository $merchantRepo,
        private MerchantQrRepository $qrRepo,
        private QrService $qrService,
        private CfeService $cfe,
        private MerchantEventService $eventService,
    ) {}

    public function register(RegisterMerchantRequest $request): Merchant
    {
        // 1. Create merchant record
        $merchant = $this->merchantRepo->create([
            'user_id' => $request->user->id,
            'tenant_id' => $request->tenant->id,
            'business_name' => $request->businessName,
            'business_type' => $request->businessType,
            'license_number' => $request->licenseNumber,
            'location' => $request->location,
            'status' => MerchantStatus::PENDING,
            'tier' => $this->calculateTier($request),
            'mdr_rate' => $this->getDefaultMdrRate($request->businessType),
            'settlement_period' => SettlementPeriod::DAILY,
        ]);

        // 2. Generate initial static QR code
        $qr = $this->qrService->generateStaticQr($merchant);

        // 3. Create CFE merchant account
        $this->cfe->createMerchantAccount(
            merchantId: $merchant->id,
            name: $merchant->business_name,
        );

        // 4. Emit event
        $this->eventService->emitMerchantRegistered($merchant);

        return $merchant;
    }

    public function verify(int $merchantId, bool $approved, ?string $rejectionReason): Merchant
    {
        $merchant = $this->merchantRepo->findOrFail($merchantId);
        $merchant->status = $approved ? MerchantStatus::VERIFIED : MerchantStatus::REJECTED;
        $merchant->verified_at = $approved ? now() : null;
        $merchant->rejection_reason = $rejectionReason;
        $merchant->save();

        $this->eventService->emitMerchantVerified($merchant, $approved);
        return $merchant;
    }

    public function getByUser(int $userId): ?Merchant
    {
        return $this->merchantRepo->findByUser($userId);
    }
}
```

### QrService
```php
class QrService
{
    public function __construct(
        private MerchantQrRepository $qrRepo,
        private QrCodeGenerator $qrGenerator,
        private CdnService $cdn,
    ) {}

    public function generateStaticQr(Merchant $merchant): MerchantQrCode
    {
        $payload = $this->buildQrPayload($merchant, QrType::STATIC);
        $qrImage = $this->qrGenerator->generate(
            data: $payload,
            size: 400,
            logo: $merchant->logo_url,
        );

        $cdnUrl = $this->cdn->upload($qrImage, "merchant/{$merchant->id}/qr_static.png");

        return $this->qrRepo->create([
            'merchant_id' => $merchant->id,
            'type' => QrType::STATIC,
            'qr_data' => $payload,
            'image_url' => $cdnUrl,
            'status' => QrStatus::ACTIVE,
        ]);
    }

    public function generateDynamicQr(Merchant $merchant, int $amount, ?Carbon $expiresAt): MerchantQrCode
    {
        $payload = $this->buildQrPayload($merchant, QrType::DYNAMIC, amount: $amount);
        $qrImage = $this->qrGenerator->generate(
            data: $payload,
            size: 400,
            logo: $merchant->logo_url,
        );

        $cdnUrl = $this->cdn->upload($qrImage, "merchant/{$merchant->id}/qr_dynamic_{$amount}.png");

        return $this->qrRepo->create([
            'merchant_id' => $merchant->id,
            'type' => QrType::DYNAMIC,
            'amount' => $amount,
            'qr_data' => $payload,
            'image_url' => $cdnUrl,
            'status' => QrStatus::ACTIVE,
            'expires_at' => $expiresAt,
        ]);
    }

    private function buildQrPayload(Merchant $merchant, QrType $type, ?int $amount = null): string
    {
        // Beza QR format: beza://pay/merchant/{merchant_id}?type={type}&amount={amount}
        $payload = "beza://pay/merchant/{$merchant->id}?type={$type->value}";
        if ($amount) {
            $payload .= "&amount={$amount}";
        }
        return $payload;
    }

    public function serveQrImage(int $qrId): \Illuminate\Http\Response
    {
        $qr = $this->qrRepo->findOrFail($qrId);
        // Increment scan count
        $qr->increment('scan_count');
        // Redirect to CDN URL (cached, fast)
        return redirect()->away($qr->image_url);
    }
}
```

### SettlementService
```php
class SettlementService
{
    public function __construct(
        private SettlementRepository $settlementRepo,
        private MerchantTransactionRepository $txnRepo,
        private MerchantRepository $merchantRepo,
        private MdrService $mdrService,
        private CfeService $cfe,
        private MerchantEventService $eventService,
    ) {}

    public function processDailySettlements(): void
    {
        $merchants = $this->merchantRepo->findActiveWithTransactions(now()->today());

        foreach ($merchants as $merchant) {
            $this->processMerchantSettlement($merchant);
        }
    }

    public function processMerchantSettlement(Merchant $merchant): MerchantSettlement
    {
        $transactions = $this->txnRepo->findCompletedByMerchantAndDate(
            $merchant->id, now()->startOfDay(), now()->endOfDay()
        );

        if ($transactions->isEmpty()) {
            // No transactions today — skip
            throw new \Exception("No transactions for merchant {$merchant->id}");
        }

        $grossAmount = $transactions->sum('amount');
        $mdrAmount = $this->mdrService->calculateMdr($merchant, $transactions);
        $netAmount = $grossAmount - $mdrAmount;

        // Create settlement record
        $settlement = $this->settlementRepo->create([
            'merchant_id' => $merchant->id,
            'period_start' => now()->startOfDay(),
            'period_end' => now()->endOfDay(),
            'gross_amount' => $grossAmount,
            'mdr_amount' => $mdrAmount,
            'net_amount' => $netAmount,
            'status' => SettlementStatus::PENDING,
        ]);

        // Post to CFE: debit settlement clearing, credit merchant wallet
        $merchantWallet = $this->cfe->getMerchantWallet($merchant->id);
        $this->cfe->executeTransfer(
            senderAccountId: $this->cfe->getSettlementClearingAccount(),
            recipientAccountId: $merchantWallet,
            amount: $netAmount,
            reference: "settlement_{$settlement->id}",
        );

        $settlement->status = SettlementStatus::COMPLETED;
        $settlement->paid_at = now();
        $settlement->save();

        $this->eventService->emitMerchantSettled($settlement);

        return $settlement;
    }
}
```

## API Endpoints

```php
// Merchant Module Routes (prefix: /api/v1/merchant)
// All routes require auth:sanctum unless noted

Route::post('/register', [MerchantController::class, 'register'])->withoutMiddleware(['auth:sanctum']);
Route::get('/status/{userId}', [MerchantController::class, 'status'])->withoutMiddleware(['auth:sanctum']);

Route::middleware(['auth:sanctum'])->group(function () {
    // Profile
    Route::get('/profile', [MerchantController::class, 'profile']);
    Route::put('/profile', [MerchantController::class, 'updateProfile']);

    // QR Codes
    Route::post('/qr/generate', [QrController::class, 'generate']);
    Route::get('/qr/{id}', [QrController::class, 'serve'])->withoutMiddleware(['auth:sanctum']);
    Route::get('/qr', [QrController::class, 'list']);

    // Payment Links
    Route::post('/payment-link', [PaymentLinkController::class, 'create']);
    Route::get('/payment-links', [PaymentLinkController::class, 'list']);
    Route::get('/payment-link/{id}', [PaymentLinkController::class, 'show']);
    Route::delete('/payment-link/{id}', [PaymentLinkController::class, 'cancel']);

    // POS Terminals
    Route::post('/pos/register', [PosController::class, 'register']);
    Route::post('/pos/pair', [PosController::class, 'pair']);
    Route::get('/pos/terminals', [PosController::class, 'list']);
    Route::post('/pos/unpair/{terminalId}', [PosController::class, 'unpair']);

    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/{id}', [TransactionController::class, 'show']);
    Route::get('/transactions/export', [TransactionController::class, 'export']);

    // Settlements
    Route::get('/settlements', [SettlementController::class, 'index']);
    Route::get('/settlements/{id}', [SettlementController::class, 'show']);
    Route::get('/settlements/{id}/download', [SettlementController::class, 'downloadPdf']);

    // Webhook
    Route::post('/webhook/configure', [WebhookController::class, 'configure']);
    Route::get('/webhook/config', [WebhookController::class, 'getConfig']);
    Route::post('/webhook/test', [WebhookController::class, 'sendTest']);
    Route::delete('/webhook/config', [WebhookController::class, 'remove']);

    // Refunds
    Route::post('/refund/{transactionId}', [TransactionController::class, 'refund']);
});
```
