<?php

declare(strict_types=1);

namespace App\Modules\Core\Middleware;

use Closure;
use Illuminate\Http\Request;

final class ValidateFinancialRequest
{
    private const MIN_AMOUNT_FILS = 1;
    private const MAX_AMOUNT_FILS = 100_000_000_000;
    private const SUPPORTED_CURRENCIES = ['SYP', 'USD', 'EUR', 'TRY'];

    private const REFERENCE_PATTERN = '/^[A-Za-z0-9\-_]{1,64}$/';

    public function handle(Request $request, Closure $next): mixed
    {
        $amount = $request->input('amount_fils');
        $currency = $request->input('currency');
        $reference = $request->input('reference_id');

        if ($amount !== null) {
            if (!is_int($amount) || $amount < self::MIN_AMOUNT_FILS || $amount > self::MAX_AMOUNT_FILS) {
                return $this->error('المبلغ غير صالح. يجب أن يكون بين 1 فلس و100 مليار فلس', $request);
            }
        }

        if ($currency !== null && !in_array($currency, self::SUPPORTED_CURRENCIES, true)) {
            return $this->error('العملة غير مدعومة. العملات المدعومة: ' . implode('، ', self::SUPPORTED_CURRENCIES), $request);
        }

        if ($reference !== null && !preg_match(self::REFERENCE_PATTERN, $reference)) {
            return $this->error('صيغة الرقم التسلسلي غير صالحة', $request);
        }

        return $next($request);
    }

    private function error(string $message, Request $request): mixed
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => ['validation' => [$message]],
            'timestamp' => now()->toIso8601String(),
            'request_id' => $request->header('X-Request-Id'),
        ], 422);
    }
}
