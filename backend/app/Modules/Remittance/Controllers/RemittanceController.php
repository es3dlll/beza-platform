<?php

declare(strict_types=1);

namespace Modules\Remittance\Controllers;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Remittance\DTOs\CreateCorridorDto;
use Modules\Remittance\DTOs\RegisterBeneficiaryDto;
use Modules\Remittance\DTOs\CreateRemittanceDto;
use Modules\Remittance\Http\Requests\CreateCorridorRequest;
use Modules\Remittance\Http\Requests\RegisterBeneficiaryRequest;
use Modules\Remittance\Http\Requests\CreateRemittanceRequest;
use Modules\Remittance\Services\CorridorService;
use Modules\Remittance\Services\BeneficiaryService;
use Modules\Remittance\Services\RemittanceService;

final class RemittanceController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly CorridorService $corridorService,
        private readonly BeneficiaryService $beneficiaryService,
        private readonly RemittanceService $remittanceService,
    ) {}

    public function createCorridor(CreateCorridorRequest $request): JsonResponse
    {
        $dto = new CreateCorridorDto(
            name: $request->input('name'),
            sourceCountry: $request->input('source_country'),
            sourceCurrency: $request->input('source_currency'),
            targetCurrency: $request->input('target_currency', 'SYP'),
            fxRateSource: $request->input('fx_rate_source', 'cbs_official'),
            fixedSpreadPct: (float) $request->input('fixed_spread_pct', 2.0),
            feeType: $request->input('fee_type', 'percentage'),
            feeStructure: $request->input('fee_structure'),
            minAmount: (int) $request->input('min_amount', 25000),
            maxAmount: (int) $request->input('max_amount', 10000000),
            dailyLimitPerSender: (int) $request->input('daily_limit_per_sender', 50000000),
            monthlyLimitPerSender: (int) $request->input('monthly_limit_per_sender', 250000000),
            isActive: $request->boolean('is_active', true),
            supportedPayoutMethods: $request->input('supported_payout_methods', ['wallet']),
            complianceRequirements: $request->input('compliance_requirements'),
            partnerName: $request->input('partner_name'),
        );

        $corridor = $this->corridorService->create($dto);

        return $this->respondCreated($corridor);
    }

    public function listCorridors(): JsonResponse
    {
        $corridors = $this->corridorService->allActive();
        return $this->respond($corridors);
    }

    public function registerBeneficiary(RegisterBeneficiaryRequest $request): JsonResponse
    {
        $dto = new RegisterBeneficiaryDto(
            userId: $request->user()->id,
            fullNameAr: $request->input('full_name_ar'),
            fullNameEn: $request->input('full_name_en'),
            phone: $request->input('phone'),
            nationalId: $request->input('national_id'),
            relationship: $request->input('relationship'),
            governorate: $request->input('governorate'),
            city: $request->input('city'),
            address: $request->input('address'),
            metadata: $request->input('metadata'),
        );

        $beneficiary = $this->beneficiaryService->register($dto);

        return $this->respondCreated($beneficiary);
    }

    public function listBeneficiaries(Request $request): JsonResponse
    {
        $beneficiaries = $this->beneficiaryService->findByUser($request->user()->id);
        return $this->respond($beneficiaries);
    }

    public function createRemittance(CreateRemittanceRequest $request): JsonResponse
    {
        $dto = new CreateRemittanceDto(
            corridorId: $request->input('corridor_id'),
            senderUserId: $request->user()->id,
            senderCountry: $request->user()->country_code ?? 'UNKNOWN',
            senderFullName: $request->input('sender_full_name'),
            senderPhone: $request->input('sender_phone'),
            senderIdDocument: $request->input('sender_id_document'),
            beneficiaryId: $request->input('beneficiary_id'),
            sourceAmount: (int) $request->input('source_amount'),
            sourceCurrency: $request->input('source_currency'),
            payoutMethod: $request->input('payout_method'),
            purposeCode: $request->input('purpose_code'),
            sourceOfFundsDeclaration: $request->input('source_of_funds_declaration'),
            payoutWalletId: $request->input('payout_wallet_id'),
            payoutAgentId: $request->input('payout_agent_id'),
            payoutBankAccount: $request->input('payout_bank_account'),
        );

        $order = $this->remittanceService->create($dto);

        return $this->respondCreated($order);
    }

    public function screenRemittance(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'passed' => 'required|boolean',
            'case_id' => 'sometimes|nullable|string',
        ]);

        $order = $this->remittanceService->screen($id, $request->boolean('passed'), $request->input('case_id'));

        return $this->respond($order);
    }

    public function quoteRemittance(string $id): JsonResponse
    {
        $order = $this->remittanceService->quote($id);
        return $this->respond($order);
    }

    public function confirmPaidIn(Request $request, string $id): JsonResponse
    {
        $request->validate(['amount_paid' => 'required|integer|min:1']);
        $order = $this->remittanceService->confirmPaidIn($id, (int) $request->input('amount_paid'));
        return $this->respond($order);
    }

    public function completeRemittance(string $id): JsonResponse
    {
        $order = $this->remittanceService->complete($id);
        return $this->respond($order);
    }

    public function failRemittance(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);
        $order = $this->remittanceService->fail($id, $request->input('reason'));
        return $this->respond($order);
    }

    public function refundRemittance(Request $request, string $id): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);
        $order = $this->remittanceService->refund($id, $request->input('reason'));
        return $this->respond($order);
    }

    public function showRemittance(string $id): JsonResponse
    {
        $order = $this->remittanceService->findById($id);
        if (!$order) {
            return $this->respondError('REMITTANCE_NOT_FOUND', null, null, 404);
        }
        return $this->respond($order);
    }

    public function listRemittances(Request $request): JsonResponse
    {
        $orders = $this->remittanceService->findBySender(
            $request->user()->id,
            (int) $request->input('per_page', 15),
        );
        return $this->respond($orders);
    }
}
