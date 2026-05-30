<?php

declare(strict_types=1);

namespace Modules\Bills\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Bills\DTOs\CreateBillProviderDto;
use Modules\Bills\DTOs\BillInquiryDto;
use Modules\Bills\DTOs\PayBillDto;
use Modules\Bills\Http\Requests\CreateBillProviderRequest;
use Modules\Bills\Http\Requests\BillInquiryRequest;
use Modules\Bills\Http\Requests\PayBillRequest;
use Modules\Bills\Services\BillProviderService;
use Modules\Bills\Services\BillPaymentService;

class BillController extends Controller
{
    public function __construct(
        private readonly BillProviderService $providerService,
        private readonly BillPaymentService $paymentService,
    ) {}

    public function listProviders(): JsonResponse
    {
        $providers = $this->providerService->allActive();
        return response()->json(['data' => $providers]);
    }

    public function createProvider(CreateBillProviderRequest $request): JsonResponse
    {
        $dto = new CreateBillProviderDto(
            code: $request->input('code'),
            name: $request->input('name'),
            nameAr: $request->input('name_ar'),
            category: $request->input('category'),
            accountLabel: $request->input('account_label'),
            accountFormatRegex: $request->input('account_format_regex'),
            supportedAccountTypes: $request->input('supported_account_types'),
            feePercentage: (float) $request->input('fee_percentage', 0.5),
            feeMinSyp: (int) $request->input('fee_min_syp', 100),
            feeMaxSyp: (int) $request->input('fee_max_syp', 2000),
            isActive: $request->boolean('is_active', true),
            integrationConfig: $request->input('integration_config'),
        );

        $provider = $this->providerService->create($dto);
        return response()->json(['data' => $provider], 201);
    }

    public function inquire(BillInquiryRequest $request): JsonResponse
    {
        $dto = new BillInquiryDto(
            userId: $request->user()->id,
            billProviderId: $request->input('bill_provider_id'),
            accountNumber: $request->input('account_number'),
        );

        $payment = $this->paymentService->inquire($dto);
        return response()->json(['data' => $payment], 201);
    }

    public function pay(PayBillRequest $request): JsonResponse
    {
        $dto = new PayBillDto(
            billPaymentId: $request->input('bill_payment_id'),
            amount: (int) $request->input('amount', 0),
        );

        $payment = $this->paymentService->pay($dto);
        return response()->json(['data' => $payment]);
    }

    public function refund(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);
        $payment = $this->paymentService->refund($id, $request->input('reason'));
        return response()->json(['data' => $payment]);
    }

    public function history(Request $request): JsonResponse
    {
        $payments = $this->paymentService->findByUser(
            $request->user()->id,
            (int) $request->input('per_page', 15),
        );
        return response()->json(['data' => $payments]);
    }

    public function showPayment(string $id): JsonResponse
    {
        $payment = $this->paymentService->findById($id);
        if (!$payment) {
            return response()->json(['error' => 'BILL_NOT_FOUND'], 404);
        }
        return response()->json(['data' => $payment]);
    }
}
