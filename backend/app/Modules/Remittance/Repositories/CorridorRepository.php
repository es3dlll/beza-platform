<?php

declare(strict_types=1);

namespace Modules\Remittance\Repositories;

use Modules\Remittance\Models\Corridor;
use Modules\Remittance\DTOs\CreateCorridorDto;

final class CorridorRepository
{
    public function create(CreateCorridorDto $dto): Corridor
    {
        return Corridor::create([
            'name' => $dto->name,
            'source_country' => $dto->sourceCountry,
            'source_currency' => $dto->sourceCurrency,
            'target_currency' => $dto->targetCurrency,
            'fx_rate_source' => $dto->fxRateSource,
            'fixed_spread_pct' => $dto->fixedSpreadPct,
            'fee_type' => $dto->feeType,
            'fee_structure' => $dto->feeStructure,
            'min_amount' => $dto->minAmount,
            'max_amount' => $dto->maxAmount,
            'daily_limit_per_sender' => $dto->dailyLimitPerSender,
            'monthly_limit_per_sender' => $dto->monthlyLimitPerSender,
            'is_active' => $dto->isActive,
            'supported_payout_methods' => $dto->supportedPayoutMethods,
            'compliance_requirements' => $dto->complianceRequirements,
            'partner_name' => $dto->partnerName,
        ]);
    }

    public function findById(string $id): ?Corridor
    {
        return Corridor::find($id);
    }

    public function findActiveByCountry(string $sourceCountry): ?Corridor
    {
        return Corridor::where('source_country', $sourceCountry)
            ->where('is_active', true)
            ->first();
    }

    public function findAllActive(): iterable
    {
        return Corridor::where('is_active', true)->get();
    }

    public function update(string $id, array $data): ?Corridor
    {
        $corridor = $this->findById($id);
        if (!$corridor) {
            return null;
        }
        $corridor->update($data);
        return $corridor->fresh();
    }

    public function delete(string $id): bool
    {
        return Corridor::destroy($id) > 0;
    }
}
