<?php

declare(strict_types=1);

namespace Modules\Remittance\Repositories;

use Modules\Remittance\Models\RemittanceOrder;
use Modules\Remittance\Enums\RemittanceStatus;
use Modules\Remittance\DTOs\CreateRemittanceDto;

final class RemittanceOrderRepository
{
    public function create(CreateRemittanceDto $dto, string $referenceNumber): RemittanceOrder
    {
        return RemittanceOrder::create([
            'corridor_id' => $dto->corridorId,
            'sender_user_id' => $dto->senderUserId,
            'sender_country' => $dto->senderCountry,
            'sender_full_name' => $dto->senderFullName,
            'sender_phone' => $dto->senderPhone,
            'sender_id_document' => $dto->senderIdDocument,
            'beneficiary_id' => $dto->beneficiaryId,
            'source_amount' => $dto->sourceAmount,
            'source_currency' => $dto->sourceCurrency,
            'target_amount' => 0,
            'target_currency' => 'SYP',
            'fx_rate_applied' => 0,
            'fee_amount_in_source' => 0,
            'fee_amount_in_target' => 0,
            'total_cost' => 0,
            'payout_method' => $dto->payoutMethod,
            'payout_wallet_id' => $dto->payoutWalletId,
            'payout_agent_id' => $dto->payoutAgentId,
            'payout_bank_account' => $dto->payoutBankAccount,
            'purpose_code' => $dto->purposeCode,
            'source_of_funds_declaration' => $dto->sourceOfFundsDeclaration,
            'status' => RemittanceStatus::PENDING->value,
            'reference_number' => $referenceNumber,
        ]);
    }

    public function findById(string $id): ?RemittanceOrder
    {
        return RemittanceOrder::with(['corridor', 'beneficiary'])->find($id);
    }

    public function findByReferenceNumber(string $referenceNumber): ?RemittanceOrder
    {
        return RemittanceOrder::where('reference_number', $referenceNumber)->first();
    }

    public function findBySender(string $userId, int $perPage = 15): iterable
    {
        return RemittanceOrder::where('sender_user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function updateQuoteDetails(
        string $id,
        int $targetAmount,
        float $fxRate,
        int $feeAmountInSource,
        int $feeAmountInTarget,
        int $totalCost,
        string $fxQuoteId,
    ): ?RemittanceOrder {
        $order = $this->findById($id);
        if (!$order) {
            return null;
        }
        $order->update([
            'target_amount' => $targetAmount,
            'fx_rate_applied' => $fxRate,
            'fee_amount_in_source' => $feeAmountInSource,
            'fee_amount_in_target' => $feeAmountInTarget,
            'total_cost' => $totalCost,
            'fx_quote_id' => $fxQuoteId,
            'status' => RemittanceStatus::QUOTED->value,
        ]);
        return $order->fresh();
    }

    public function updateStatus(string $id, RemittanceStatus $status, ?array $extra = null): ?RemittanceOrder
    {
        $order = $this->findById($id);
        if (!$order) {
            return null;
        }
        $data = ['status' => $status->value];
        if ($extra) {
            $data = array_merge($data, $extra);
        }
        $order->update($data);
        return $order->fresh();
    }

    public function getDailyTotalsForSender(string $userId): int
    {
        return (int) RemittanceOrder::where('sender_user_id', $userId)
            ->whereDate('created_at', today())
            ->whereNotIn('status', [
                RemittanceStatus::FAILED->value,
                RemittanceStatus::EXPIRED->value,
                RemittanceStatus::REFUNDED->value,
            ])
            ->sum('source_amount');
    }

    public function getMonthlyTotalsForSender(string $userId): int
    {
        return (int) RemittanceOrder::where('sender_user_id', $userId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->whereNotIn('status', [
                RemittanceStatus::FAILED->value,
                RemittanceStatus::EXPIRED->value,
                RemittanceStatus::REFUNDED->value,
            ])
            ->sum('source_amount');
    }
}
