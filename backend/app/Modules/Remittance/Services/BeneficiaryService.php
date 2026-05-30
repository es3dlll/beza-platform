<?php

declare(strict_types=1);

namespace Modules\Remittance\Services;

use Modules\Remittance\Models\Beneficiary;
use Modules\Remittance\DTOs\RegisterBeneficiaryDto;
use Modules\Remittance\Repositories\BeneficiaryRepository;
use Modules\Remittance\Exceptions\RemittanceBeneficiaryNotFoundException;
use Modules\Remittance\Exceptions\RemittanceBeneficiaryKycIncompleteException;

final class BeneficiaryService
{
    public function __construct(
        private readonly BeneficiaryRepository $beneficiaryRepository,
    ) {}

    public function register(RegisterBeneficiaryDto $dto): Beneficiary
    {
        return $this->beneficiaryRepository->create($dto);
    }

    public function findById(string $id): Beneficiary
    {
        $beneficiary = $this->beneficiaryRepository->findById($id);
        if (!$beneficiary) {
            throw new RemittanceBeneficiaryNotFoundException($id);
        }
        return $beneficiary;
    }

    public function findByUser(string $userId): iterable
    {
        return $this->beneficiaryRepository->findByUser($userId);
    }

    public function ensureKycCompleted(string $id): void
    {
        $beneficiary = $this->findById($id);
        if (!$beneficiary->kyc_completed) {
            throw new RemittanceBeneficiaryKycIncompleteException($id);
        }
    }

    public function completeKyc(string $id): ?Beneficiary
    {
        return $this->beneficiaryRepository->completeKyc($id);
    }
}
