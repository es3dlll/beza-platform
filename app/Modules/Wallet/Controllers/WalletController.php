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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WalletController
{
    public function __construct(
        private readonly WalletService $wallets,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $userWallets = \Modules\Wallet\Models\Wallet::where('user_id', $userId)->get();
        return response()->json(['data' => $userWallets]);
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
        return response()->json(['data' => $wallet], 201);
    }

    public function show(string $id): JsonResponse
    {
        try {
            $balance = $this->wallets->getBalance($id);
            return response()->json(['data' => $balance]);
        } catch (\Modules\Wallet\Exceptions\WalletNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'WALLET_NOT_FOUND', 'message' => $e->getMessage()],
            ], 404);
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
            return response()->json(['data' => $wallet], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'WALLET_DEPOSIT_FAILED', 'message' => $e->getMessage()],
            ], 422);
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
            return response()->json(['data' => $wallet], 200);
        } catch (\Modules\Wallet\Exceptions\InsufficientBalanceException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'WALLET_INSUFFICIENT_FUNDS',
                    'message' => $e->getMessage(),
                    'message_ar' => 'الرصيد غير كافٍ',
                    'details' => ['required' => $e->required, 'available' => $e->available],
                ],
            ], 422);
        } catch (\Modules\Wallet\Exceptions\DailyLimitExceededException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'WALLET_LIMIT_EXCEEDED',
                    'message' => $e->getMessage(),
                    'message_ar' => 'تم تجاوز الحد اليومي',
                ],
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'WALLET_WITHDRAWAL_FAILED', 'message' => $e->getMessage()],
            ], 422);
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
            return response()->json(['data' => $result], 200);
        } catch (\Modules\Wallet\Exceptions\InsufficientBalanceException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'WALLET_INSUFFICIENT_FUNDS',
                    'message' => $e->getMessage(),
                    'message_ar' => 'الرصيد غير كافٍ للتحويل',
                ],
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'WALLET_TRANSFER_FAILED', 'message' => $e->getMessage()],
            ], 422);
        }
    }

    public function transactions(string $id): JsonResponse
    {
        try {
            $txns = $this->wallets->getTransactions($id);
            return response()->json(['data' => $txns]);
        } catch (\Modules\Wallet\Exceptions\WalletNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'WALLET_NOT_FOUND', 'message' => $e->getMessage()],
            ], 404);
        }
    }
}
