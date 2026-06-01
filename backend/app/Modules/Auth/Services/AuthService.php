<?php

namespace App\Modules\Auth\Services;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Modules\Wallet\Services\WalletService;

class AuthService
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    public function register(RegisterRequest $request): array
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => $request->password,
        ]);

        $wallets = $this->walletService->createDefaultWallets($user);

        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => $user->only(['id', 'name', 'email', 'phone']),
            'token' => $token,
            'wallets' => array_map(fn ($w) => [
                'id' => $w->id,
                'currency' => $w->currency,
                'balance' => number_format($w->balance, 4, '.', ''),
                'status' => $w->status,
            ], $wallets),
        ];
    }
}
