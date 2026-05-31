<?php

declare(strict_types=1);

namespace App\Modules\Merchant\Controllers;

use App\Modules\Merchant\Models\Invoice;
use App\Modules\Merchant\Models\Merchant;
use App\Modules\Merchant\Services\MerchantEngine;
use App\Modules\Merchant\Services\QRService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MerchantController
{
    public function __construct(
        private readonly MerchantEngine $engine,
        private readonly QRService $qrService,
    ) {}

    public function onboard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'owner_id' => 'required|string',
            'phone' => 'required|string|max:20',
            'category' => 'nullable|string|in:goods_food,goods_general,goods_luxury,services_general,services_digital,services_financial',
            'settlement_cycle' => 'nullable|string|in:DAILY,WEEKLY,INSTANT',
        ]);

        $merchant = $this->engine->onboard($validated);

        return response()->json(['data' => $merchant->toArray()], 201);
    }

    public function createInvoice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'merchant_id' => 'required|string',
            'amount' => 'required|integer|min:1000',
            'description' => 'required|string|max:500',
            'category' => 'nullable|string|in:goods_food,goods_general,goods_luxury,services_general,services_digital,services_financial',
        ]);

        $result = $this->engine->createInvoice(
            merchantId: $validated['merchant_id'],
            amount: $validated['amount'],
            description: $validated['description'],
            category: $validated['category'] ?? 'goods_general',
        );

        return response()->json(['data' => $result], 201);
    }

    public function getQR(string $id): JsonResponse
    {
        $invoice = Invoice::where('invoice_id', $id)->firstOrFail();
        $merchant = Merchant::where('merchant_id', $invoice->merchant_id)->firstOrFail();

        $qrToken = $this->qrService->generate(
            invoiceId: $id,
            merchantId: $invoice->merchant_id,
            amount: $invoice->total_amount,
        );

        return response()->json([
            'data' => [
                'qr_token' => $qrToken->token(),
                'expires_in' => $qrToken->remainingSeconds(),
                'invoice_id' => $id,
                'merchant_name' => $merchant->business_name,
                'total_amount' => $invoice->total_amount,
            ],
        ]);
    }

    public function pay(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'qr_token' => 'required|string',
            'payee_id' => 'required|string',
        ]);

        $this->engine->initiatePayment(
            invoiceId: $id,
            qrToken: $validated['qr_token'],
            payeeId: $validated['payee_id'],
        );

        return response()->json([
            'message' => 'Payment initiated successfully',
            'data' => ['invoice_id' => $id, 'status' => 'PAID'],
        ]);
    }

    public function settlements(Request $request): JsonResponse
    {
        $merchantId = $request->input('merchant_id');

        $invoices = Invoice::where('merchant_id', $merchantId)
            ->where('status', 'PAID')
            ->orderByDesc('paid_at')
            ->get(['invoice_id', 'total_amount', 'tax_amount', 'paid_at', 'settlement_status']);

        return response()->json(['data' => $invoices]);
    }

    public function refund(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $this->engine->requestRefund($id, $validated['reason']);

        return response()->json([
            'message' => 'Refund requested',
            'data' => ['invoice_id' => $id, 'status' => 'REFUNDED'],
        ]);
    }
}
