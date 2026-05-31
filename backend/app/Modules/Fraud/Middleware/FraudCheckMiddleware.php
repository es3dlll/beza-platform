<?php

declare(strict_types=1);

namespace App\Modules\Fraud\Middleware;

use App\Modules\Fraud\Services\FraudGuard;
use Closure;
use Illuminate\Http\Request;

final class FraudCheckMiddleware
{
    public function __construct(
        private readonly FraudGuard $fraudGuard,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if (!$request->isMethod('post')) {
            return $next($request);
        }

        $walletId = $request->input('wallet_id')
            ?? $request->input('from_wallet_id')
            ?? $request->input('agent_id');

        if ($walletId === null) {
            return $next($request);
        }

        $amount = (int) ($request->input('amount') ?? 0);

        if ($amount <= 0) {
            return $next($request);
        }

        try {
            $this->fraudGuard->preCheck(
                walletId: $walletId,
                amount: $amount,
                deviceData: [
                    'user_agent' => $request->userAgent(),
                    'ip_address' => $request->ip(),
                    'device_type' => $request->header('X-Device-Type', 'web'),
                    'app_version' => $request->header('X-App-Version'),
                    'os' => $request->header('X-OS'),
                ],
                kycTier: $request->header('X-KYC-Tier', 't0'),
                contextId: $request->input('idempotency_key'),
            );
        } catch (\App\Modules\Fraud\Exceptions\TransactionBlockedException $e) {
            return response()->json([
                'error' => 'Transaction blocked by fraud prevention',
                'code' => $e->getCode(),
                'score' => $e->score,
                'reason' => $e->reason,
            ], 403);
        }

        return $next($request);
    }
}
