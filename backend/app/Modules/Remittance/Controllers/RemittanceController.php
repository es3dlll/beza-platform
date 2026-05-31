<?php

declare(strict_types=1);

namespace App\Modules\Remittance\Controllers;

use App\Modules\Remittance\Models\Remittance;
use App\Modules\Remittance\Services\RemittanceEngine;
use App\Modules\Remittance\Services\RemittanceQuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RemittanceController
{
    public function __construct(
        private readonly RemittanceQuoteService $quoteService,
        private readonly RemittanceEngine $engine,
    ) {}

    public function quote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_currency' => 'required|string|in:SYP,USD,EUR,SAR,AED',
            'to_currency' => 'required|string|in:SYP,USD,EUR,SAR,AED|different:from_currency',
            'amount' => 'required|integer|min:100000',
        ]);

        $quote = $this->quoteService->calculateQuote(
            fromCurrency: $validated['from_currency'],
            toCurrency: $validated['to_currency'],
            amount: $validated['amount'],
        );

        return response()->json(['data' => $quote]);
    }

    public function initiate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'idempotency_key' => 'required|string|max:64',
            'sender_id' => 'required|string',
            'recipient_name' => 'required|string|max:255',
            'recipient_phone' => 'required|string|max:20',
            'recipient_country' => 'required|string|size:2',
            'from_currency' => 'required|string|in:SYP,USD,EUR,SAR,AED',
            'to_currency' => 'required|string|in:SYP,USD,EUR,SAR,AED|different:from_currency',
            'source_amount' => 'required|integer|min:100000',
        ]);

        $remittance = $this->engine->initiate(
            idempotencyKey: $validated['idempotency_key'],
            senderId: $validated['sender_id'],
            recipientName: $validated['recipient_name'],
            recipientPhone: $validated['recipient_phone'],
            recipientCountry: $validated['recipient_country'],
            fromCurrency: $validated['from_currency'],
            toCurrency: $validated['to_currency'],
            sourceAmount: $validated['source_amount'],
        );

        return response()->json([
            'data' => [
                'remittance_id' => $remittance->remittance_id,
                'status' => $remittance->status,
                'destination_amount' => $remittance->destination_amount,
                'fee_amount' => $remittance->fee_amount,
                'total_charge' => $remittance->total_charge,
                'expires_at' => $remittance->expires_at,
            ],
        ], 201);
    }

    public function status(string $id): JsonResponse
    {
        $remittance = Remittance::where('remittance_id', $id)->firstOrFail();

        return response()->json([
            'data' => [
                'remittance_id' => $remittance->remittance_id,
                'status' => $remittance->status,
                'audit_trail' => $remittance->audit_trail,
                'cancellation_reason' => $remittance->cancellation_reason,
                'completed_at' => $remittance->completed_at,
            ],
        ]);
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $this->engine->cancelTransfer($id, $validated['reason']);

        return response()->json([
            'message' => 'Transfer cancelled successfully',
            'data' => ['remittance_id' => $id, 'status' => 'CANCELLED'],
        ]);
    }
}
