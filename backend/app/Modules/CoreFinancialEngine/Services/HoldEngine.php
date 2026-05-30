<?php

declare(strict_types=1);

namespace Modules\CoreFinancialEngine\Services;

use Modules\CoreFinancialEngine\Contracts\HoldEngineInterface;
use Modules\CoreFinancialEngine\DTOs\HoldInstructionDto;
use Modules\CoreFinancialEngine\DTOs\HoldResultDto;
use Modules\Ledger\Services\HoldService;
use Modules\Ledger\DTOs\CreateHoldDto;

final class HoldEngine implements HoldEngineInterface
{
    public function __construct(
        private readonly HoldService $holds,
    ) {}

    public function place(HoldInstructionDto $dto): HoldResultDto
    {
        if (!$this->validateHold($dto->accountId, $dto->amount)) {
            return new HoldResultDto(
                success: false,
                holdId: '',
                amount: $dto->amount,
                status: 'rejected',
                error: 'insufficient_available_balance',
            );
        }

        $createDto = new CreateHoldDto(
            accountId: $dto->accountId,
            amount: $dto->amount,
            currency: $dto->currency,
            reason: $dto->reason,
            referenceType: $dto->referenceType,
            referenceId: $dto->referenceId,
            expiresAt: $dto->expiresAt,
        );

        try {
            $hold = $this->holds->place($createDto);
            return new HoldResultDto(
                success: true,
                holdId: $hold->id,
                amount: $hold->amount,
                status: $hold->status,
            );
        } catch (\Exception $e) {
            return new HoldResultDto(
                success: false,
                holdId: '',
                amount: $dto->amount,
                status: 'error',
                error: $e->getMessage(),
            );
        }
    }

    public function release(string $holdId, string $reason): HoldResultDto
    {
        try {
            $hold = $this->holds->release($holdId, $reason);
            return new HoldResultDto(
                success: true,
                holdId: $hold->id,
                amount: $hold->amount,
                status: $hold->status,
            );
        } catch (\Exception $e) {
            return new HoldResultDto(
                success: false,
                holdId: $holdId,
                amount: 0,
                status: 'error',
                error: $e->getMessage(),
            );
        }
    }

    public function validateHold(string $accountId, int $amount): bool
    {
        return $amount > 0;
    }
}
