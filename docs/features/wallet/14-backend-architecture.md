# Wallet Backend Architecture

## Module Structure (Laravel)
```
app/Modules/Wallet/
├── Controllers/
│   ├── WalletController.php         # Balance, history, settings
│   ├── TransferController.php       # Send money, request money
│   ├── FundingController.php        # Cash-in, bank top-up
│   └── WithdrawalController.php     # Cash-out, bank withdrawal
│
├── Actions/
│   ├── CreateWalletAction.php       # Create wallet account(s)
│   ├── GetBalanceAction.php         # Get balance (with caching)
│   ├── SendMoneyAction.php          # Full P2P transfer orchestration
│   ├── CashInAction.php             # Agent/wallet funding
│   ├── CashOutAction.php            # Wallet/agent withdrawal
│   └── GetTransactionHistoryAction.php
│
├── Services/
│   ├── WalletService.php            # Core wallet operations
│   ├── TransferService.php          # P2P transfer business logic
│   ├── LimitService.php             # KYC-based limits
│   ├── FeeService.php               # Fee calculation engine
│   └── WalletEventService.php       # Event emission
│
├── Repositories/
│   ├── WalletRepository.php         # Wallet CRUD
│   ├── TransactionRepository.php    # Transaction query + pagination
│   └── WalletBalanceRepository.php  # Balance queries
│
├── Models/
│   ├── Wallet.php                   # Wallet model
│   ├── WalletTransaction.php        # Transaction model
│   └── WalletBalance.php            # Balance view/model
│
├── Policies/
│   ├── WalletPolicy.php             # Authorization rules
│   └── TransferPolicy.php           # Transfer authorization
│
├── Events/
│   ├── WalletCredited.php           # Deposit event
│   ├── WalletDebited.php            # Withdrawal event
│   ├── TransferSent.php             # P2P sent
│   └── TransferReceived.php         # P2P received
│
├── Jobs/
│   ├── ProcessTransferJob.php       # Async transfer processing
│   ├── NotifyRecipientJob.php       # Push/SMS notification
│   └── SyncWalletToAnalyticsJob.php
│
├── Listeners/
│   ├── SendTransferNotification.php
│   ├── LogWalletActivity.php
│   └── UpdateSavingsRoundup.php
│
├── Rules/
│   ├── SufficientBalance.php
│   ├── ValidTransferAmount.php
│   └── WithinDailyLimit.php
│
├── Enums/
│   ├── TransactionType.php          # send, receive, cash_in, cash_out, bill
│   ├── TransactionStatus.php        # pending, completed, failed, reversed
│   ├── WalletCurrency.php           # SYP, USD
│   ├── WalletType.php               # main, savings, card
│   └── FundingSource.php            # agent, bank, card, remittance
│
├── Exceptions/
│   ├── InsufficientBalanceException.php
│   ├── DailyLimitExceededException.php
│   ├── InvalidRecipientException.php
│   └── WalletNotFoundException.php
│
├── Providers/
│   └── WalletServiceProvider.php    # Module registration
│
└── routes/
    └── api.php                      # Route definitions
```

## Service Layer Detail

### WalletService
```php
class WalletService
{
    public function __construct(
        private WalletRepository $walletRepo,
        private WalletBalanceRepository $balanceRepo,
        private CfeService $cfe,
        private FeeService $feeService,
        private LimitService $limitService,
        private EventService $eventService,
    ) {}

    public function getOrCreateWallet(User $user, Currency $currency): Wallet
    {
        return $this->walletRepo->firstOrCreate(
            ['user_id' => $user->id, 'currency' => $currency],
            ['type' => WalletType::MAIN, 'status' => WalletStatus::ACTIVE]
        );
    }

    public function getBalance(int $userId, Currency $currency): BalanceDTO
    {
        $wallet = $this->walletRepo->findByUserAndCurrency($userId, $currency);
        return $this->cfe->getBalance($wallet->cfe_account_id);
    }

    public function getBalances(int $userId): array
    {
        $wallets = $this->walletRepo->findAllByUser($userId);
        $balances = [];
        foreach ($wallets as $wallet) {
            $balances[$wallet->currency->value] = $this->getBalance($userId, $wallet->currency);
        }
        return $balances;
    }
}
```

### TransferService
```php
class TransferService
{
    public function __construct(
        private WalletService $walletService,
        private FeeService $feeService,
        private LimitService $limitService,
        private CfeService $cfe,
        private TransactionRepository $txnRepo,
        private UserRepository $userRepo,
        private EventService $eventService,
    ) {}

    public function send(SendMoneyRequest $request): TransactionResult
    {
        // 1. Validate sender wallet exists and has sufficient balance
        $senderWallet = $this->walletService->getOrCreateWallet(
            $request->sender, $request->currency
        );
        $this->cfe->checkSufficientBalance(
            $senderWallet->cfe_account_id, $request->amount + $request->fee
        );

        // 2. Validate daily limit
        $dailyTotal = $this->txnRepo->getDailyTotal($request->sender, $request->currency);
        $this->limitService->validateTransferLimit($dailyTotal, $request->amount, $request->sender);

        // 3. Find or create recipient wallet
        $recipient = $this->userRepo->findByPhone($request->recipientPhone);
        $recipientWallet = $this->walletService->getOrCreateWallet($recipient, $request->currency);

        // 4. Calculate fee
        $fee = $this->feeService->calculateTransferFee($request->amount, $request->sender);

        // 5. Execute CFE transaction (hold → post → settle)
        $cfeResult = $this->cfe->executeTransfer(
            senderAccountId: $senderWallet->cfe_account_id,
            recipientAccountId: $recipientWallet->cfe_account_id,
            amount: $request->amount,
            fee: $fee,
            reference: $request->idempotencyKey,
        );

        // 6. Persist transaction record
        $transaction = $this->txnRepo->create([
            'sender_id' => $request->sender->id,
            'recipient_id' => $recipient->id,
            'amount' => $request->amount,
            'fee' => $fee,
            'currency' => $request->currency,
            'status' => TransactionStatus::COMPLETED,
            'cfe_reference' => $cfeResult->reference,
            'sender_balance_after' => $cfeResult->senderBalance,
            'recipient_balance_after' => $cfeResult->recipientBalance,
            'note' => $request->note,
        ]);

        // 7. Emit events
        $this->eventService->emitTransferSent($transaction);
        $this->eventService->emitTransferReceived($transaction);

        // 8. Return result
        return new TransactionResult(
            transactionId: $transaction->id,
            status: TransactionStatus::COMPLETED,
            receipt: ReceiptDTO::fromTransaction($transaction),
        );
    }
}
```

## API Endpoints

```php
// Wallet Module Routes (prefix: /api/v1/wallet)

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    // Balance
    Route::get('/balance', [WalletController::class, 'balance']);
    Route::get('/balance/{currency}', [WalletController::class, 'balanceByCurrency']);

    // Transactions
    Route::get('/transactions', [WalletController::class, 'transactions']);
    Route::get('/transactions/{id}', [WalletController::class, 'transactionDetail']);
    Route::get('/transactions/export', [WalletController::class, 'exportTransactions']);

    // Transfer
    Route::post('/transfer/send', [TransferController::class, 'send']);
    Route::post('/transfer/request', [TransferController::class, 'requestMoney']);
    Route::post('/transfer/respond', [TransferController::class, 'respondToRequest']);
    Route::get('/transfer/requests', [TransferController::class, 'pendingRequests']);

    // Funding
    Route::post('/funding/cash-in', [FundingController::class, 'cashIn']);
    Route::post('/funding/bank-topup', [FundingController::class, 'bankTopUp']);

    // Withdrawal
    Route::post('/withdrawal/cash-out', [WithdrawalController::class, 'cashOut']);
    Route::post('/withdrawal/bank', [WithdrawalController::class, 'bankWithdrawal']);
});
```
