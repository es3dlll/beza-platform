<?php

declare(strict_types=1);

namespace Modules\Wallet\Controllers;

use Modules\Wallet\DTOs\CreateWalletDto;
use Modules\Wallet\DTOs\DepositDto;
use Modules\Wallet\DTOs\WithdrawDto;
use Modules\Wallet\DTOs\TransferDto;
use Modules\Wallet\Http\Requests\CreateWalletRequest;
use Modules\Wallet\Http\Requests\DepositRequest;
use Modules\Wallet\Http\Requests\WithdrawRequest;
use Modules\Wallet\Http\Requests\TransferRequest;
use Modules\Wallet\Services\WalletService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WalletController
{
    use ApiResponse;
    public function __construct(
        private readonly WalletService $wallets,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $userWallets = \Modules\Wallet\Models\Wallet::where('user_id', $userId)->get();
        return $this->respond($userWallets);
    }

    public function create(CreateWalletRequest $request): JsonResponse
    {
        $dto = new CreateWalletDto(
            userId: $request->user()->id,
            currency: $request->input('currency', 'SYP'),
            kycTierRequired: (int) $request->input('kyc_tier_required', 1),
            dailyLimit: (int) $request->input('daily_limit', 5000000),
        );

        $wallet = $this->wallets->create($dto);
        return $this->respondCreated($wallet);
    }

    public function show(string $id): JsonResponse
    {
        try {
            $balance = $this->wallets->getBalance($id);
            return $this->respond($balance);
        } catch (\Modules\Wallet\Exceptions\WalletNotFoundException $e) {
            return $this->respondNotFound('Wallet');
        }
    }

    public function deposit(DepositRequest $request, string $id): JsonResponse
    {
        $dto = new DepositDto(
            walletId: $id,
            amount: (int) $request->input('amount'),
            currency: $request->input('currency', 'SYP'),
            referenceType: $request->input('reference_type', 'deposit'),
            referenceId: $request->input('reference_id', uniqid('dep_', true)),
            channel: $request->input('channel', 'api'),
            description: $request->input('description', ''),
        );

        try {
            $wallet = $this->wallets->deposit($dto);
            return $this->respond($wallet);
        } catch (\Exception $e) {
            return $this->respondError('WALLET_DEPOSIT_FAILED', $e->getMessage(), 'فشل الإيداع');
        }
    }

    public function withdraw(WithdrawRequest $request, string $id): JsonResponse
    {
        $dto = new WithdrawDto(
            walletId: $id,
            amount: (int) $request->input('amount'),
            currency: $request->input('currency', 'SYP'),
            referenceType: 'withdrawal',
            referenceId: $request->input('reference_id', uniqid('wth_', true)),
            channel: $request->input('channel', 'api'),
            description: $request->input('description', ''),
            applyFee: (bool) $request->input('apply_fee', true),
        );

        try {
            $wallet = $this->wallets->withdraw($dto);
            return $this->respond($wallet);
        } catch (\Modules\Wallet\Exceptions\InsufficientBalanceException $e) {
            return $this->respondError('WALLET_INSUFFICIENT_FUNDS', $e->getMessage(), 'الرصيد غير كافٍ', 422, ['required' => $e->required, 'available' => $e->available]);
        } catch (\Modules\Wallet\Exceptions\DailyLimitExceededException $e) {
            return $this->respondError('WALLET_LIMIT_EXCEEDED', $e->getMessage(), 'تم تجاوز الحد اليومي');
        } catch (\Exception $e) {
            return $this->respondError('WALLET_WITHDRAWAL_FAILED', $e->getMessage());
        }
    }

    public function transfer(TransferRequest $request, string $id): JsonResponse
    {
        $dto = new TransferDto(
            fromWalletId: $id,
            toWalletId: $request->input('to_wallet_id'),
            amount: (int) $request->input('amount'),
            currency: $request->input('currency', 'SYP'),
            referenceId: $request->input('reference_id', uniqid('trf_', true)),
            channel: $request->input('channel', 'api'),
            description: $request->input('description', ''),
            applyFee: (bool) $request->input('apply_fee', true),
        );

        try {
            $result = $this->wallets->transfer($dto);
            return $this->respond($result);
        } catch (\Modules\Wallet\Exceptions\InsufficientBalanceException $e) {
            return $this->respondError('WALLET_INSUFFICIENT_FUNDS', $e->getMessage(), 'الرصيد غير كافٍ للتحويل');
        } catch (\Exception $e) {
            return $this->respondError('WALLET_TRANSFER_FAILED', $e->getMessage());
        }
    }

    public function transactions(string $id): JsonResponse
    {
        try {
            $txns = $this->wallets->getTransactions($id);
            return $this->respond($txns);
        } catch (\Modules\Wallet\Exceptions\WalletNotFoundException $e) {
            return $this->respondNotFound('Wallet');
        }
    }
}
