<?php

declare(strict_types=1);

namespace App\Modules\Remittance\Controllers;

use App\Modules\Core\Enums\Currency;
use App\Modules\Core\Services\ApiResponse;
use App\Modules\Core\ValueObjects\Money;
use App\Modules\FX\Services\FXRateProvider;
use App\Modules\Remittance\Events\RemittanceInitiated;
use App\Modules\Remittance\Models\Remittance;
use App\Modules\Remittance\Services\RemittanceFeeCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class RemittanceController extends Controller
{
    public function __construct(
        private readonly FXRateProvider $rateProvider,
        private readonly RemittanceFeeCalculator $feeCalculator,
    ) {}

    public function initiate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receiver_name' => 'required|string|max:200',
            'receiver_phone' => 'nullable|string|max:20',
            'from_currency' => 'required|string|in:SYP,USD,EUR,TRY',
            'to_currency' => 'required|string|in:SYP,USD,EUR,TRY',
            'from_amount_fils' => 'required|integer|min:1000|max:100_000_000_000',
        ]);

        if ($validated['from_currency'] === $validated['to_currency']) {
            return ApiResponse::error('عملة المصدر والهدف يجب أن تكون مختلفة', null, 400);
        }

        $fromCurrency = Currency::from($validated['from_currency']);
        $toCurrency = Currency::from($validated['to_currency']);
        $amount = Money::fromFils($validated['from_amount_fils'], $fromCurrency);

        $conversion = $this->rateProvider->convert($amount->fils(), $fromCurrency, $toCurrency);
        if (!$conversion) {
            return ApiResponse::error('سعر الصرف غير متاح حالياً', null, 503);
        }

        $fee = $this->feeCalculator->calculate($amount, $validated['from_currency'], $validated['to_currency']);

        if ($fee['fee_fils'] >= $amount->fils()) {
            return ApiResponse::error('الرسوم تتجاوز المبلغ', null, 400);
        }

        $userId = $request->user()?->id ?? 'anonymous';
        $referenceNumber = 'REM-' . strtoupper(bin2hex(random_bytes(6)));

        $remittance = Remittance::create([
            'sender_user_id' => $userId,
            'receiver_name' => $validated['receiver_name'],
            'receiver_phone' => $validated['receiver_phone'] ?? null,
            'from_currency' => $validated['from_currency'],
            'to_currency' => $validated['to_currency'],
            'from_amount_fils' => $amount->fils(),
            'to_amount_fils' => $conversion['to_amount_fils'],
            'exchange_rate_id' => $conversion['rate']->id,
            'rate_used_fils_per_unit' => $conversion['rate_fils_per_unit'],
            'fee_fils' => $fee['fee_fils'],
            'total_charged_fils' => $amount->fils() + $fee['fee_fils'],
            'status' => 'pending',
            'reference_number' => $referenceNumber,
            'metadata' => [
                'fee_breakdown' => $fee['breakdown'],
                'rate_provider' => $conversion['rate']->provider,
                'rate_valid_until' => $conversion['rate']->valid_until->toIso8601String(),
            ],
        ]);

        event(new RemittanceInitiated(
            remittance: $remittance,
            amount: $amount,
            fromCurrency: $validated['from_currency'],
            toCurrency: $validated['to_currency'],
            userId: $userId,
        ));

        return ApiResponse::success([
            'remittance' => $remittance->fresh(),
            'fee' => $fee,
            'conversion' => [
                'from_amount_fils' => $conversion['from_amount_fils'],
                'to_amount_fils' => $conversion['to_amount_fils'],
                'rate_fils_per_unit' => $conversion['rate_fils_per_unit'],
                'rate_provider' => $conversion['rate']->provider,
                'rate_valid_until' => $conversion['rate']->valid_until->toIso8601String(),
            ],
        ], 'تم بدء التحويل الدولي بنجاح', 201);
    }

    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_amount_fils' => 'required|integer|min:1000',
            'from_currency' => 'required|string|in:SYP,USD,EUR,TRY',
            'to_currency' => 'required|string|in:SYP,USD,EUR,TRY',
        ]);

        $fromCurrency = Currency::from($validated['from_currency']);
        $toCurrency = Currency::from($validated['to_currency']);

        $conversion = $this->rateProvider->convert($validated['from_amount_fils'], $fromCurrency, $toCurrency);
        if (!$conversion) {
            return ApiResponse::error('سعر الصرف غير متاح', null, 503);
        }

        $fee = $this->feeCalculator->calculate(
            Money::fromFils($validated['from_amount_fils'], $fromCurrency),
            $validated['from_currency'],
            $validated['to_currency'],
        );

        return ApiResponse::success([
            'from_amount_fils' => $validated['from_amount_fils'],
            'to_amount_fils' => $conversion['to_amount_fils'],
            'rate_fils_per_unit' => $conversion['rate_fils_per_unit'],
            'fee_fils' => $fee['fee_fils'],
            'net_amount_fils' => $fee['net_amount_fils'],
            'fee_breakdown' => $fee['breakdown'],
            'rate_provider' => $conversion['rate']->provider,
            'rate_valid_until' => $conversion['rate']->valid_until->toIso8601String(),
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $query = Remittance::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('from_currency')) {
            $query->where('from_currency', $request->from_currency);
        }
        if ($request->filled('to_currency')) {
            $query->where('to_currency', $request->to_currency);
        }
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        return ApiResponse::success($query->orderBy('created_at', 'desc')->paginate(20));
    }

    public function show(string $id): JsonResponse
    {
        $remittance = Remittance::findOrFail($id);
        return ApiResponse::success($remittance);
    }
}
